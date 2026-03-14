"""
Phase 5 사용자 관리 통합 테스트
"""
import uuid
import pytest
from httpx import AsyncClient, ASGITransport

from app.main import app
from app.supabase_client import supabase
from app.services.auth_service import delete_user_by_id

_uid = uuid.uuid4().hex[:8]
MEMBER_EMAIL = f"au_member_{_uid}@mailtest.com"
MEMBER_USERNAME = f"au_member_{_uid}"
TARGET_EMAIL = f"au_target_{_uid}@mailtest.com"
TARGET_USERNAME = f"au_target_{_uid}"
ADMIN_EMAIL = f"au_admin_{_uid}@mailtest.com"
ADMIN_USERNAME = f"au_admin_{_uid}"
TEST_PASSWORD = "TestPass1234!"


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
    for email in [MEMBER_EMAIL, TARGET_EMAIL, ADMIN_EMAIL]:
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
async def admin_token(http, member_token):
    _ensure_user(ADMIN_EMAIL, ADMIN_USERNAME)
    supabase.table("users").update({"is_admin": True}).eq("email", ADMIN_EMAIL).execute()
    return await _login(http, ADMIN_EMAIL)


@pytest.fixture(scope="module")
def target_user_id(admin_token):
    """수정 대상 사용자 생성"""
    _ensure_user(TARGET_EMAIL, TARGET_USERNAME)
    result = supabase.table("users").select("id").eq("email", TARGET_EMAIL).single().execute()
    return result.data["id"]


# ── 관리자 사용자 목록 ─────────────────────────────────────────

async def test_admin_users_page_blocks_guest():
    """비로그인 → /admin/users 302"""
    async with AsyncClient(
        transport=ASGITransport(app=app),
        base_url="http://test",
        follow_redirects=False,
    ) as fresh:
        r = await fresh.get("/admin/users")
    assert r.status_code == 302


async def test_admin_users_page_blocks_member(http, member_token):
    """일반 회원 → /admin/users 403"""
    r = await http.get(
        "/admin/users",
        headers={"Cookie": f"access_token={member_token}"},
    )
    assert r.status_code == 403


async def test_admin_users_page_accessible_to_admin(http, admin_token):
    """관리자 → /admin/users 200, 사용자 목록 포함"""
    r = await http.get(
        "/admin/users",
        headers={"Cookie": f"access_token={admin_token}"},
    )
    assert r.status_code == 200
    assert "사용자" in r.text


# ── 사용자 권한/활성화 변경 ────────────────────────────────────

async def test_admin_grant_admin(http, admin_token, target_user_id):
    """관리자 → 대상 사용자에게 관리자 권한 부여"""
    tok = await _csrf(http)
    r = await http.put(
        f"/admin/users/{target_user_id}",
        data={"is_admin": "true", "is_active": "true"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 200
    updated = supabase.table("users").select("is_admin").eq("id", target_user_id).single().execute()
    assert updated.data["is_admin"] is True


async def test_admin_revoke_admin(http, admin_token, target_user_id):
    """관리자 → 관리자 권한 해제"""
    tok = await _csrf(http)
    r = await http.put(
        f"/admin/users/{target_user_id}",
        data={"is_admin": "false", "is_active": "true"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 200
    updated = supabase.table("users").select("is_admin").eq("id", target_user_id).single().execute()
    assert updated.data["is_admin"] is False


async def test_admin_deactivate_user(http, admin_token, target_user_id):
    """관리자 → 사용자 비활성화"""
    tok = await _csrf(http)
    r = await http.put(
        f"/admin/users/{target_user_id}",
        data={"is_admin": "false", "is_active": "false"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={admin_token}",
        },
    )
    assert r.status_code == 200
    updated = supabase.table("users").select("is_active").eq("id", target_user_id).single().execute()
    assert updated.data["is_active"] is False


async def test_member_cannot_update_user(http, member_token, target_user_id):
    """일반 회원 → 사용자 수정 403"""
    tok = await _csrf(http)
    r = await http.put(
        f"/admin/users/{target_user_id}",
        data={"is_admin": "true", "is_active": "true"},
        headers={
            "X-CSRF-Token": tok,
            "Cookie": f"csrf_token={tok}; access_token={member_token}",
        },
    )
    assert r.status_code == 403
