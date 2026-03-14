"""
Phase 2 게시판 통합 테스트
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
MEMBER_EMAIL = f"brd_member_{_uid}@mailtest.com"
MEMBER_USERNAME = f"brd_member_{_uid}"
ADMIN_EMAIL = f"brd_admin_{_uid}@mailtest.com"
ADMIN_USERNAME = f"brd_admin_{_uid}"
TEST_PASSWORD = "TestPass1234!"

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
    """모듈 전체 테스트 후 정리"""
    yield
    for bid in _created_board_ids:
        try:
            supabase.table("boards").delete().eq("id", bid).execute()
        except Exception:
            pass
    for email in [MEMBER_EMAIL, ADMIN_EMAIL]:
        try:
            result = supabase.table("users").select("id").eq("email", email).execute()
            if result.data:
                delete_user_by_id(result.data[0]["id"])
        except Exception:
            pass


async def _csrf(client: AsyncClient) -> str:
    r = await client.get("/")
    return r.cookies.get("csrf_token", "")


async def _signup(client: AsyncClient, email: str, username: str) -> str:
    """회원가입 후 access_token 반환"""
    tok = await _csrf(client)
    r = await client.post(
        "/api/auth/signup",
        data={
            "email": email,
            "username": username,
            "password": TEST_PASSWORD,
            "password_confirm": TEST_PASSWORD,
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 200, f"signup failed: {r.text}"
    return r.cookies["access_token"]


async def _login(client: AsyncClient, email: str) -> str:
    """로그인 후 access_token 반환"""
    tok = await _csrf(client)
    r = await client.post(
        "/api/auth/login",
        data={"email": email, "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 200, f"login failed: {r.text}"
    return r.cookies["access_token"]


def _ensure_user(email: str, username: str) -> None:
    """사용자 생성. 기존 계정(테이블 or Auth 고아)은 모두 삭제 후 재생성."""
    from app.services.auth_service import create_user

    # 1. users 테이블에 있으면 삭제 (Auth도 같이)
    existing = supabase.table("users").select("id").eq("email", email).execute()
    if existing.data:
        delete_user_by_id(existing.data[0]["id"])

    # 2. Auth에 고아 계정이 있으면 삭제 (per_page=1000으로 전체 조회)
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

    # 3. 새로 생성
    create_user(email, username, TEST_PASSWORD)


@pytest.fixture(scope="module")
async def member_token(http):
    _ensure_user(MEMBER_EMAIL, MEMBER_USERNAME)
    return await _login(http, MEMBER_EMAIL)


@pytest.fixture(scope="module")
async def admin_token(http, member_token):
    """관리자 계정 생성: Supabase admin API로 직접 생성 후 is_admin=True 설정"""
    _ensure_user(ADMIN_EMAIL, ADMIN_USERNAME)
    supabase.table("users").update({"is_admin": True}).eq("email", ADMIN_EMAIL).execute()
    return await _login(http, ADMIN_EMAIL)


@pytest.fixture(scope="module")
def public_board_id():
    """read_perm='all'인 게시판 ID (자유게시판)"""
    r = supabase.table("boards").select("id").eq("name", "자유게시판").single().execute()
    return r.data["id"]


@pytest.fixture(scope="module")
def member_board_id():
    """read_perm='member'인 게시판 생성"""
    r = supabase.table("boards").insert({
        "name": f"member_only_{_uid}",
        "description": "멤버 전용 테스트 게시판",
        "read_perm": "member",
        "write_perm": "member",
        "comment_perm": "member",
        "display_order": 99,
    }).execute()
    bid = r.data[0]["id"]
    _created_board_ids.append(bid)
    return bid


# ── 메인 페이지 ─────────────────────────────────────────────────

async def test_main_page_renders(http):
    r = await http.get("/")
    assert r.status_code == 200
    # 초기 게시판 이름 확인
    assert "자유게시판" in r.text or "공지사항" in r.text


# ── 게시판 접근 권한 ────────────────────────────────────────────

async def test_public_board_accessible_to_guest(http, public_board_id):
    """read_perm='all' → 비로그인도 접근 가능"""
    r = await http.get(f"/boards/{public_board_id}")
    assert r.status_code == 200


async def test_member_board_blocks_guest(http, member_board_id):
    """read_perm='member' → 비로그인은 /login으로 302"""
    r = await http.get(f"/boards/{member_board_id}")
    assert r.status_code == 302
    assert "/login" in r.headers.get("location", "")


async def test_member_board_accessible_to_member(http, member_token, member_board_id):
    """read_perm='member' → 로그인 회원은 접근 가능"""
    r = await http.get(
        f"/boards/{member_board_id}",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 200


async def test_board_htmx_returns_partial(http, public_board_id):
    """HX-Request 헤더 → post_list partial 반환 (전체 페이지 아님)"""
    r = await http.get(
        f"/boards/{public_board_id}",
        headers={"HX-Request": "true"},
    )
    assert r.status_code == 200
    # partial은 <html> 태그 없음
    assert "<html" not in r.text


# ── 관리자 페이지 ───────────────────────────────────────────────

async def test_admin_boards_page_blocks_guest():
    """비로그인 → /admin/boards 302 (쿠키 없는 fresh client 사용)"""
    async with AsyncClient(
        transport=ASGITransport(app=app),
        base_url="http://test",
        follow_redirects=False,
    ) as fresh:
        r = await fresh.get("/admin/boards")
    assert r.status_code == 302


async def test_admin_boards_page_blocks_member(http, member_token):
    """일반 회원 → /admin/boards 403"""
    r = await http.get(
        "/admin/boards",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 403


async def test_admin_boards_page_accessible_to_admin(http, admin_token):
    """관리자 → /admin/boards 200"""
    r = await http.get(
        "/admin/boards",
        headers={"Cookie": f"access_token={admin_token}"},
    )
    assert r.status_code == 200
    assert "게시판" in r.text


# ── 관리자 게시판 CRUD ──────────────────────────────────────────

async def test_admin_creates_board(http, admin_token):
    """관리자 → 게시판 생성"""
    tok = await _csrf(http)
    board_name = f"테스트게시판_{_uid}"
    r = await http.post(
        "/admin/boards",
        data={
            "name": board_name,
            "description": "테스트용",
            "read_perm": "all",
            "write_perm": "member",
            "comment_perm": "member",
            "display_order": "10",
        },
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code in (200, 201), r.text
    # 생성된 게시판이 DB에 있는지 확인 후 cleanup 목록에 추가
    result = supabase.table("boards").select("id").eq("name", board_name).execute()
    assert result.data, "게시판이 DB에 없음"
    _created_board_ids.append(result.data[0]["id"])


async def test_admin_updates_board(http, admin_token):
    """관리자 → 게시판 수정"""
    # 수정할 게시판 생성
    created = supabase.table("boards").insert({
        "name": f"수정전_{_uid}",
        "description": "before",
        "read_perm": "all",
        "write_perm": "member",
        "comment_perm": "member",
        "display_order": 20,
    }).execute()
    bid = created.data[0]["id"]
    _created_board_ids.append(bid)

    tok = await _csrf(http)
    r = await http.put(
        f"/admin/boards/{bid}",
        data={
            "name": f"수정후_{_uid}",
            "description": "after",
            "read_perm": "member",
            "write_perm": "admin",
            "comment_perm": "all",
            "display_order": "20",
        },
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 200, r.text
    updated = supabase.table("boards").select("*").eq("id", bid).single().execute()
    assert updated.data["read_perm"] == "member"
    assert updated.data["write_perm"] == "admin"


async def test_admin_deletes_board(http, admin_token):
    """관리자 → 게시판 삭제"""
    created = supabase.table("boards").insert({
        "name": f"삭제용_{_uid}",
        "description": "will be deleted",
        "read_perm": "all",
        "write_perm": "member",
        "comment_perm": "member",
        "display_order": 30,
    }).execute()
    bid = created.data[0]["id"]

    tok = await _csrf(http)
    r = await http.delete(
        f"/admin/boards/{bid}",
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 200, r.text
    result = supabase.table("boards").select("id").eq("id", bid).execute()
    assert not result.data, "삭제된 게시판이 아직 DB에 존재"


async def test_non_admin_cannot_create_board(http, member_token):
    """일반 회원 → 게시판 생성 403"""
    tok = await _csrf(http)
    r = await http.post(
        "/admin/boards",
        data={
            "name": f"불법게시판_{_uid}",
            "description": "hack",
            "read_perm": "all",
            "write_perm": "member",
            "comment_perm": "member",
            "display_order": "0",
        },
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 403
