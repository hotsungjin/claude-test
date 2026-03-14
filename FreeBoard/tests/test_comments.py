"""
Phase 4 댓글 시스템 통합 테스트
"""
import uuid
import pytest
from httpx import AsyncClient, ASGITransport

from app.main import app
from app.supabase_client import supabase
from app.services.auth_service import delete_user_by_id

_uid = uuid.uuid4().hex[:8]
MEMBER_EMAIL = f"cmt_member_{_uid}@mailtest.com"
MEMBER_USERNAME = f"cmt_member_{_uid}"
OTHER_EMAIL = f"cmt_other_{_uid}@mailtest.com"
OTHER_USERNAME = f"cmt_other_{_uid}"
ADMIN_EMAIL = f"cmt_admin_{_uid}@mailtest.com"
ADMIN_USERNAME = f"cmt_admin_{_uid}"
TEST_PASSWORD = "TestPass1234!"

_created_comment_ids: list[int] = []
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
    for cid in _created_comment_ids:
        try:
            supabase.table("comments").delete().eq("id", cid).execute()
        except Exception:
            pass
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
def test_post_id(member_token):
    """댓글 테스트용 게시글 (자유게시판)"""
    board = supabase.table("boards").select("id").eq("name", "자유게시판").single().execute()
    board_id = board.data["id"]
    member = supabase.table("users").select("id").eq("email", MEMBER_EMAIL).single().execute()
    result = supabase.table("posts").insert({
        "board_id": board_id,
        "author_id": member.data["id"],
        "title": f"댓글테스트_{_uid}",
        "content": "<p>댓글 테스트용</p>",
    }).execute()
    pid = result.data[0]["id"]
    _created_post_ids.append(pid)
    return pid


@pytest.fixture(scope="module")
def admin_comment_board_id():
    """comment_perm='admin' 게시판"""
    r = supabase.table("boards").insert({
        "name": f"admin_cmt_{_uid}",
        "description": "admin 댓글 테스트",
        "read_perm": "all",
        "write_perm": "admin",
        "comment_perm": "admin",
        "display_order": 97,
    }).execute()
    bid = r.data[0]["id"]
    _created_board_ids.append(bid)
    return bid


@pytest.fixture(scope="module")
def admin_comment_post_id(admin_comment_board_id, admin_token):
    """admin 댓글 게시판의 게시글"""
    admin = supabase.table("users").select("id").eq("email", ADMIN_EMAIL).single().execute()
    result = supabase.table("posts").insert({
        "board_id": admin_comment_board_id,
        "author_id": admin.data["id"],
        "title": f"admin_cmt_post_{_uid}",
        "content": "<p>관리자 댓글 테스트용</p>",
    }).execute()
    pid = result.data[0]["id"]
    _created_post_ids.append(pid)
    return pid


# ── 댓글 작성 ─────────────────────────────────────────────────

async def test_comment_guest_blocked(http, test_post_id):
    """비로그인 → 댓글 작성 302"""
    tok = await _csrf(http)
    r = await http.post(
        f"/api/posts/{test_post_id}/comments",
        data={"content": "무단 댓글"},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 302
    assert "/login" in r.headers.get("location", "")


async def test_comment_member_success(http, member_token, test_post_id):
    """회원 → 댓글 작성 성공 (HTMX partial 반환)"""
    tok = await _csrf(http)
    r = await http.post(
        f"/api/posts/{test_post_id}/comments",
        data={"content": f"첫 번째 댓글_{_uid}"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
            "HX-Request": "true",
        },
    )
    assert r.status_code == 200
    assert "<html" not in r.text  # partial 응답
    assert f"첫 번째 댓글_{_uid}" in r.text

    # comment_count 반영 확인
    post = supabase.table("posts").select("comment_count").eq("id", test_post_id).single().execute()
    assert post.data["comment_count"] >= 1

    # 생성된 댓글 ID 추출 (cleanup용)
    result = supabase.table("comments").select("id").eq("post_id", test_post_id).execute()
    for c in result.data:
        if c["id"] not in _created_comment_ids:
            _created_comment_ids.append(c["id"])


async def test_comment_admin_perm_blocks_member(http, member_token, admin_comment_post_id):
    """comment_perm='admin' → 일반 회원 댓글 403"""
    tok = await _csrf(http)
    r = await http.post(
        f"/api/posts/{admin_comment_post_id}/comments",
        data={"content": "불법 댓글"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 403


async def test_comment_admin_can_post_anywhere(http, admin_token, test_post_id):
    """관리자 → 어디서나 댓글 가능"""
    tok = await _csrf(http)
    r = await http.post(
        f"/api/posts/{test_post_id}/comments",
        data={"content": f"관리자 댓글_{_uid}"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
            "HX-Request": "true",
        },
    )
    assert r.status_code == 200
    result = supabase.table("comments").select("id").eq("post_id", test_post_id).execute()
    for c in result.data:
        if c["id"] not in _created_comment_ids:
            _created_comment_ids.append(c["id"])


# ── 대댓글 ───────────────────────────────────────────────────

async def test_reply_comment(http, member_token, test_post_id):
    """대댓글 작성"""
    if not _created_comment_ids:
        pytest.skip("부모 댓글 없음")
    parent_id = _created_comment_ids[0]
    tok = await _csrf(http)
    r = await http.post(
        f"/api/posts/{test_post_id}/comments",
        data={"content": f"대댓글_{_uid}", "parent_id": str(parent_id)},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
            "HX-Request": "true",
        },
    )
    assert r.status_code == 200
    # DB에서 parent_id 확인
    result = supabase.table("comments").select("id, parent_id").eq("post_id", test_post_id).execute()
    replies = [c for c in result.data if c.get("parent_id") == parent_id]
    assert replies, "대댓글이 DB에 없음"
    for c in result.data:
        if c["id"] not in _created_comment_ids:
            _created_comment_ids.append(c["id"])


# ── 댓글 삭제 ─────────────────────────────────────────────────

async def test_delete_comment_other_blocked(http, other_token, test_post_id):
    """다른 회원 → 타인 댓글 삭제 403"""
    if not _created_comment_ids:
        pytest.skip("댓글 없음")
    cid = _created_comment_ids[0]
    tok = await _csrf(http)
    r = await http.delete(
        f"/api/comments/{cid}",
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={other_token}",
        },
    )
    assert r.status_code == 403


async def test_delete_comment_by_author(http, member_token, test_post_id):
    """작성자 → 본인 댓글 삭제 성공"""
    # 삭제용 댓글 직접 생성
    member = supabase.table("users").select("id").eq("email", MEMBER_EMAIL).single().execute()
    result = supabase.table("comments").insert({
        "post_id": test_post_id,
        "author_id": member.data["id"],
        "content": f"삭제용 댓글_{_uid}",
    }).execute()
    cid = result.data[0]["id"]

    tok = await _csrf(http)
    r = await http.delete(
        f"/api/comments/{cid}",
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
            "HX-Request": "true",
        },
    )
    assert r.status_code == 200
    deleted = supabase.table("comments").select("is_deleted").eq("id", cid).single().execute()
    assert deleted.data["is_deleted"] is True


async def test_admin_delete_any_comment(http, admin_token, test_post_id):
    """관리자 → 타인 댓글 삭제 가능"""
    if not _created_comment_ids:
        pytest.skip("댓글 없음")
    cid = _created_comment_ids[0]
    tok = await _csrf(http)
    r = await http.delete(
        f"/api/comments/{cid}",
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
            "HX-Request": "true",
        },
    )
    assert r.status_code == 200
