from fastapi import APIRouter, Form, Request, Depends
from fastapi.responses import Response, HTMLResponse

from app.config import settings
from app.dependencies import get_current_user
from app.schemas.auth import SignupSchema, LoginSchema
from app.services import auth_service
from app.services.auth_service import AuthError
from app.templates_env import templates

router = APIRouter()

ACCESS_TOKEN_COOKIE = "access_token"
COOKIE_MAX_AGE = 60 * 60 * 24 * 7  # 7일


def _error_response(request: Request, error: str):
    return templates.TemplateResponse(
        request, "partials/auth_error.html", {"error": error}, status_code=422
    )


# ── 로그인 페이지 ──────────────────────────────────────────────
@router.get("/login", response_class=HTMLResponse)
async def login_page(
    request: Request,
    error: str = "",
    current_user: dict | None = Depends(get_current_user),
):
    if current_user:
        return Response(status_code=302, headers={"Location": "/"})
    return templates.TemplateResponse(
        request, "pages/login.html", {"current_user": None, "error": error}
    )


# ── 회원가입 페이지 ────────────────────────────────────────────
@router.get("/signup", response_class=HTMLResponse)
async def signup_page(
    request: Request,
    error: str = "",
    current_user: dict | None = Depends(get_current_user),
):
    if current_user:
        return Response(status_code=302, headers={"Location": "/"})
    return templates.TemplateResponse(
        request, "pages/signup.html", {"current_user": None, "error": error}
    )


# ── 로그인 처리 (HTMX POST) ────────────────────────────────────
@router.post("/api/auth/login")
async def login_post(
    request: Request,
    email: str = Form(...),
    password: str = Form(...),
):
    try:
        schema = LoginSchema(email=email, password=password)
        token = auth_service.authenticate_user(str(schema.email), schema.password)
    except AuthError as e:
        return _error_response(request, str(e))
    except ValueError as e:
        return _error_response(request, str(e))
    except Exception:
        return _error_response(request, "로그인 중 오류가 발생했습니다.")

    response = Response(status_code=200)
    response.set_cookie(
        ACCESS_TOKEN_COOKIE,
        token,
        httponly=True,
        secure=settings.cookie_secure,
        samesite=settings.cookie_samesite,
        max_age=COOKIE_MAX_AGE,
    )
    response.headers["HX-Redirect"] = "/"
    return response


# ── 회원가입 처리 (HTMX POST) ──────────────────────────────────
@router.post("/api/auth/signup")
async def signup_post(
    request: Request,
    email: str = Form(...),
    username: str = Form(...),
    password: str = Form(...),
    password_confirm: str = Form(...),
):
    try:
        schema = SignupSchema(
            email=email,
            username=username,
            password=password,
            password_confirm=password_confirm,
        )
        schema.validate_passwords_match()
        auth_service.create_user(str(schema.email), schema.username, schema.password)
        token = auth_service.authenticate_user(str(schema.email), schema.password)
    except AuthError as e:
        return _error_response(request, str(e))
    except ValueError as e:
        return _error_response(request, str(e))
    except Exception:
        return _error_response(request, "회원가입 중 오류가 발생했습니다.")

    response = Response(status_code=200)
    response.set_cookie(
        ACCESS_TOKEN_COOKIE,
        token,
        httponly=True,
        secure=settings.cookie_secure,
        samesite=settings.cookie_samesite,
        max_age=COOKIE_MAX_AGE,
    )
    response.headers["HX-Redirect"] = "/"
    return response


# ── 로그아웃 (HTMX POST) ───────────────────────────────────────
@router.post("/api/auth/logout")
async def logout_post():
    response = Response(status_code=200)
    response.delete_cookie(ACCESS_TOKEN_COOKIE)
    response.headers["HX-Redirect"] = "/login"
    return response
