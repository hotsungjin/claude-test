"""
Phase 1 인증 통합 테스트
실제 Supabase에 연결하여 테스트 (RLS OFF, Email confirmation OFF)
"""
import uuid
import pytest
from httpx import AsyncClient, ASGITransport, Cookies

from app.main import app
from app.supabase_client import supabase
from app.services.auth_service import delete_user_by_id

# ── 테스트용 계정 정보 ──────────────────────────────────────────
_uid = uuid.uuid4().hex[:8]
TEST_EMAIL = f"tester_{_uid}@mailtest.com"
TEST_USERNAME = f"tester_{_uid}"
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
    """모듈 전체 테스트 후 테스트 사용자 삭제"""
    yield
    try:
        result = (
            supabase.table("users").select("id").eq("email", TEST_EMAIL).execute()
        )
        if result.data:
            delete_user_by_id(result.data[0]["id"])
    except Exception:
        pass


async def csrf(client: AsyncClient) -> str:
    """GET / 로 CSRF 쿠키 획득"""
    r = await client.get("/")
    token = r.cookies.get("csrf_token", "")
    return token


# ── 페이지 렌더링 ───────────────────────────────────────────────

async def test_login_page_renders(http):
    r = await http.get("/login")
    assert r.status_code == 200
    assert "로그인" in r.text


async def test_signup_page_renders(http):
    r = await http.get("/signup")
    assert r.status_code == 200
    assert "회원가입" in r.text


# ── CSRF 보호 ───────────────────────────────────────────────────

async def test_post_without_csrf_is_blocked(http):
    """X-CSRF-Token 헤더 없는 POST → 403"""
    r = await http.post(
        "/api/auth/login",
        data={"email": "any@test.com", "password": "any"},
    )
    assert r.status_code == 403


# ── 회원가입 ────────────────────────────────────────────────────

async def test_signup_success(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/signup",
        data={
            "email": TEST_EMAIL,
            "username": TEST_USERNAME,
            "password": TEST_PASSWORD,
            "password_confirm": TEST_PASSWORD,
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 200, r.text
    assert r.headers.get("HX-Redirect") == "/"
    assert "access_token" in r.cookies


async def test_signup_duplicate_email(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/signup",
        data={
            "email": TEST_EMAIL,
            "username": f"other_{uuid.uuid4().hex[:6]}",
            "password": TEST_PASSWORD,
            "password_confirm": TEST_PASSWORD,
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422
    assert "이메일" in r.text


async def test_signup_duplicate_username(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/signup",
        data={
            "email": f"other_{uuid.uuid4().hex[:8]}@mailtest.com",
            "username": TEST_USERNAME,
            "password": TEST_PASSWORD,
            "password_confirm": TEST_PASSWORD,
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422
    assert "닉네임" in r.text


async def test_signup_password_mismatch(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/signup",
        data={
            "email": f"mm_{uuid.uuid4().hex[:8]}@mailtest.com",
            "username": f"mm_{uuid.uuid4().hex[:6]}",
            "password": TEST_PASSWORD,
            "password_confirm": "DifferentPass!",
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422
    assert "비밀번호" in r.text


async def test_signup_short_password(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/signup",
        data={
            "email": f"sh_{uuid.uuid4().hex[:8]}@mailtest.com",
            "username": f"sh_{uuid.uuid4().hex[:6]}",
            "password": "short",
            "password_confirm": "short",
        },
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422


# ── 로그인 ──────────────────────────────────────────────────────

async def test_login_success(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/login",
        data={"email": TEST_EMAIL, "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 200, r.text
    assert r.headers.get("HX-Redirect") == "/"
    assert "access_token" in r.cookies


async def test_login_wrong_password(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/login",
        data={"email": TEST_EMAIL, "password": "WrongPass999!"},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422
    assert "이메일" in r.text or "비밀번호" in r.text


async def test_login_unknown_email(http):
    tok = await csrf(http)
    r = await http.post(
        "/api/auth/login",
        data={"email": "nobody_xx@mailtest.com", "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert r.status_code == 422


# ── 로그아웃 ────────────────────────────────────────────────────

async def test_logout(http):
    tok = await csrf(http)
    login_r = await http.post(
        "/api/auth/login",
        data={"email": TEST_EMAIL, "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert login_r.status_code == 200
    access = login_r.cookies["access_token"]

    tok2 = await csrf(http)
    logout_r = await http.post(
        "/api/auth/logout",
        headers={"X-CSRF-Token": tok2, "Cookie": f"csrf_token={tok2}; access_token={access}"},
    )
    assert logout_r.status_code == 200
    assert logout_r.headers.get("HX-Redirect") == "/login"


# ── 인증 상태 보호 ──────────────────────────────────────────────

async def test_logged_in_redirected_from_login(http):
    """유효한 access_token으로 /login 접근 → 302"""
    tok = await csrf(http)
    login_r = await http.post(
        "/api/auth/login",
        data={"email": TEST_EMAIL, "password": TEST_PASSWORD},
        headers={"X-CSRF-Token": tok, "Cookie": f"csrf_token={tok}"},
    )
    assert login_r.status_code == 200
    access = login_r.cookies["access_token"]

    r = await http.get("/login", headers={"Cookie": f"access_token={access}"})
    assert r.status_code == 302
