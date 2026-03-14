"""
Phase 3 게시글 CRUD 통합 테스트
실제 Supabase에 연결하여 테스트
"""
import uuid
import pytest
from httpx import AsyncClient, ASGITransport

from app.main import app
from app.supabase_client import supabase
from app.services.auth_service import delete_user_by_id

# ── 테스트용 계정 정보 ──────────────────────────────────────────
_uid = uuid.uuid4().hex[:8]
MEMBER_EMAIL = f"post_member_{_uid}@mailtest.com"
MEMBER_USERNAME = f"post_member_{_uid}"
OTHER_EMAIL = f"post_other_{_uid}@mailtest.com"
OTHER_USERNAME = f"post_other_{_uid}"
ADMIN_EMAIL = f"post_admin_{_uid}@mailtest.com"
ADMIN_USERNAME = f"post_admin_{_uid}"
TEST_PASSWORD = "TestPass1234!"

_created_post_ids: list[int] = []
_created_board_ids: list[int] = []


@pytest.fixture(scope="module")
async def http():
    async with AsyncClient(
        transport=ASGITransport(app=app),
        base_url="http://test",
        follow_redirects=False,
    ) as ac:
        yield ac


@pytest.fixture(scope="module", autouse=True)
def cleanup():
    yield
    for pid in _created_post_ids:
        try:
            supabase.table("posts").delete().eq("id", pid).execute()
        except Exception:
            pass
    for bid in _created_board_ids:
        try:
            supabase.table("boards").delete().eq("id", bid).execute()
        except Exception:
            pass
    for email in [MEMBER_EMAIL, OTHER_EMAIL, ADMIN_EMAIL]:
        try:
            result = supabase.table("users").select("id").eq("email", email).execute()
            if result.data:
                delete_user_by_id(result.data[0]["id"])
        except Exception:
            pass


async def _csrf(client: AsyncClient) -> str:
    r = await client.get("/")
    return r.cookies.get("csrf_token", "")


def _ensure_user(email: str, username: str) -> None:
    from app.services.auth_service import create_user
    existing = supabase.table("users").select("id").eq("email", email).execute()
    if existing.data:
        delete_user_by_id(existing.data[0]["id"])
    try:
        user_list = supabase.auth.admin.list_users(per_page=1000)
        if not isinstance(user_list, list):
            user_list = getattr(user_list, "users", []) or []
        for au in user_list:
            if getattr(au, "email", None) == email:
                supabase.auth.admin.delete_user(str(au.id))
                break
    except Exception:
        pass
    create_user(email, username, TEST_PASSWORD)


async def _login(client: AsyncClient, email: str) -> str:
    tok = await _csrf(client)
    r = await client.post(
        "/api/auth/login",
        data={"email": email, "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 200, f"login failed: {r.text}"
    return r.cookies["access_token"]


@pytest.fixture(scope="module")
async def member_token(http):
    _ensure_user(MEMBER_EMAIL, MEMBER_USERNAME)
    return await _login(http, MEMBER_EMAIL)


@pytest.fixture(scope="module")
async def other_token(http, member_token):
    _ensure_user(OTHER_EMAIL, OTHER_USERNAME)
    return await _login(http, OTHER_EMAIL)


@pytest.fixture(scope="module")
async def admin_token(http, other_token):
    _ensure_user(ADMIN_EMAIL, ADMIN_USERNAME)
    supabase.table("users").update({"is_admin": True}).eq("email", ADMIN_EMAIL).execute()
    return await _login(http, ADMIN_EMAIL)


@pytest.fixture(scope="module")
def public_board_id():
    """write_perm='member' read_perm='all' 공개 게시판"""
    r = supabase.table("boards").select("id").eq("name", "자유게시판").single().execute()
    return r.data["id"]


@pytest.fixture(scope="module")
def admin_write_board_id():
    """write_perm='admin' 게시판 생성"""
    r = supabase.table("boards").insert({
        "name": f"admin_write_{_uid}",
        "description": "admin 쓰기 테스트 게시판",
        "read_perm": "all",
        "write_perm": "admin",
        "comment_perm": "member",
        "display_order": 98,
    }).execute()
    bid = r.data[0]["id"]
    _created_board_ids.append(bid)
    return bid


# ── 글쓰기 폼 접근 권한 ────────────────────────────────────────

async def test_write_form_blocks_guest(http, public_board_id):
    """비로그인 → /boards/{id}/posts/new 302 /login"""
    r = await http.get(f"/boards/{public_board_id}/posts/new")
    assert r.status_code == 302
    assert "/login" in r.headers.get("location", "")


async def test_write_form_accessible_to_member(http, member_token, public_board_id):
    """로그인 회원 → 글쓰기 폼 200"""
    r = await http.get(
        f"/boards/{public_board_id}/posts/new",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 200


async def test_write_form_blocks_member_on_admin_board(http, member_token, admin_write_board_id):
    """일반 회원 → admin 쓰기 게시판 글쓰기 폼 403"""
    r = await http.get(
        f"/boards/{admin_write_board_id}/posts/new",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 403


# ── 글 작성 ────────────────────────────────────────────────────

async def test_create_post_success(http, member_token, public_board_id):
    """회원 → 글 작성 성공 → 상세 페이지 302"""
    tok = await _csrf(http)
    r = await http.post(
        f"/boards/{public_board_id}/posts",
        data={"title": f"테스트 제목_{_uid}", "content": "<p>테스트 내용입니다.</p>"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 302
    location = r.headers.get("location", "")
    assert f"/boards/{public_board_id}/posts/" in location
    post_id = int(location.split("/")[-1])
    _created_post_ids.append(post_id)


async def test_create_post_guest_blocked(http, public_board_id):
    """비로그인 → 글 작성 302 /login"""
    tok = await _csrf(http)
    r = await http.post(
        f"/boards/{public_board_id}/posts",
        data={"title": "무단 작성", "content": "내용"},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 302
    assert "/login" in r.headers.get("location", "")


async def test_create_post_admin_board_member_blocked(http, member_token, admin_write_board_id):
    """일반 회원 → admin 쓰기 게시판 글 작성 403"""
    tok = await _csrf(http)
    r = await http.post(
        f"/boards/{admin_write_board_id}/posts",
        data={"title": "불법 작성", "content": "내용"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 403


# ── 글 상세 조회 & 조회수 ──────────────────────────────────────

async def test_post_detail_renders(http, public_board_id):
    """공개 게시판 글 → 비로그인도 조회 가능"""
    # _created_post_ids에 test_create_post_success가 추가했을 것
    if not _created_post_ids:
        pytest.skip("이전 테스트에서 게시글이 생성되지 않음")
    post_id = _created_post_ids[0]
    r = await http.get(f"/boards/{public_board_id}/posts/{post_id}")
    assert r.status_code == 200
    assert f"테스트 제목_{_uid}" in r.text


async def test_post_view_count_increments(http, public_board_id):
    """게시글 조회 시 조회수 증가"""
    if not _created_post_ids:
        pytest.skip("이전 테스트에서 게시글이 생성되지 않음")
    post_id = _created_post_ids[0]
    before = supabase.table("posts").select("view_count").eq("id", post_id).single().execute()
    await http.get(f"/boards/{public_board_id}/posts/{post_id}")
    after = supabase.table("posts").select("view_count").eq("id", post_id).single().execute()
    assert after.data["view_count"] > before.data["view_count"]


async def test_xss_content_is_sanitized(http, member_token, public_board_id):
    """XSS 스크립트 태그 → bleach로 제거"""
    tok = await _csrf(http)
    xss_content = '<p>안전한 내용</p><script>alert("xss")</script>'
    r = await http.post(
        f"/boards/{public_board_id}/posts",
        data={"title": f"XSS테스트_{_uid}", "content": xss_content},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 302
    post_id = int(r.headers["location"].split("/")[-1])
    _created_post_ids.append(post_id)

    # DB에서 직접 확인
    post = supabase.table("posts").select("content").eq("id", post_id).single().execute()
    assert "<script>" not in post.data["content"]


# ── 글 수정 ────────────────────────────────────────────────────

async def test_edit_form_accessible_to_author(http, member_token, public_board_id):
    """작성자 → 수정 폼 200"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    r = await http.get(
        f"/boards/{public_board_id}/posts/{post_id}/edit",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 200


async def test_edit_form_blocked_to_other_member(http, other_token, public_board_id):
    """다른 회원 → 수정 폼 403"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    r = await http.get(
        f"/boards/{public_board_id}/posts/{post_id}/edit",
        headers={"Cookie": f"access_token={other_token}"},
    )
    assert r.status_code == 403


async def test_update_post_by_author(http, member_token, public_board_id):
    """작성자 → 글 수정 성공"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    tok = await _csrf(http)
    r = await http.put(
        f"/boards/{public_board_id}/posts/{post_id}",
        data={"title": f"수정된 제목_{_uid}", "content": "<p>수정된 내용</p>"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 302
    updated = supabase.table("posts").select("title").eq("id", post_id).single().execute()
    assert f"수정된 제목_{_uid}" in updated.data["title"]


async def test_update_post_blocked_to_other(http, other_token, public_board_id):
    """다른 회원 → 글 수정 403"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    tok = await _csrf(http)
    r = await http.put(
        f"/boards/{public_board_id}/posts/{post_id}",
        data={"title": "해킹", "content": "내용"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={other_token}",
        },
    )
    assert r.status_code == 403


async def test_admin_can_edit_any_post(http, admin_token, public_board_id):
    """관리자 → 타인 글 수정 가능"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    tok = await _csrf(http)
    r = await http.put(
        f"/boards/{public_board_id}/posts/{post_id}",
        data={"title": f"관리자수정_{_uid}", "content": "<p>관리자 수정</p>"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 302


# ── 글 삭제 ────────────────────────────────────────────────────

async def test_delete_post_blocked_to_other(http, other_token, public_board_id):
    """다른 회원 → 글 삭제 403"""
    if not _created_post_ids:
        pytest.skip("게시글 없음")
    post_id = _created_post_ids[0]
    tok = await _csrf(http)
    r = await http.delete(
        f"/boards/{public_board_id}/posts/{post_id}",
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={other_token}",
        },
    )
    assert r.status_code == 403


async def test_delete_post_by_author(http, member_token, public_board_id):
    """작성자 → 글 삭제 성공"""
    # 삭제 전용 게시글 생성
    tok = await _csrf(http)
    r = await http.post(
        f"/boards/{public_board_id}/posts",
        data={"title": f"삭제용_{_uid}", "content": "<p>삭제될 내용</p>"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 302
    post_id = int(r.headers["location"].split("/")[-1])

    tok2 = await _csrf(http)
    r2 = await http.delete(
        f"/boards/{public_board_id}/posts/{post_id}",
        headers={
            "X-CSRF-Token": tok2,
            "Cookie": f"csrf_token={tok2}; access_token={member_token}",
        },
    )
    assert r2.status_code == 302
    # soft delete: is_deleted=True
    result = supabase.table("posts").select("is_deleted").eq("id", post_id).single().execute()
    assert result.data["is_deleted"] is True
