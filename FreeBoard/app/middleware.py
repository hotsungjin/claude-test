import secrets
from fastapi import Request
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware

CSRF_COOKIE_NAME = "csrf_token"
CSRF_HEADER_NAME = "X-CSRF-Token"
SAFE_METHODS = {"GET", "HEAD", "OPTIONS"}


class CSRFMiddleware(BaseHTTPMiddleware):
    """
    Double Submit Cookie 방식 CSRF 보호.
    - GET 등 안전한 메서드: csrf_token 쿠키 자동 발급
    - POST/PUT/DELETE: X-CSRF-Token 헤더와 쿠키 값 비교
    모든 mutating 요청은 HTMX를 사용하므로 HTMX가 자동으로 헤더를 추가함.
    """

    async def dispatch(self, request: Request, call_next):
        csrf_token = request.cookies.get(CSRF_COOKIE_NAME)
        if not csrf_token:
            csrf_token = secrets.token_hex(32)

        if request.method not in SAFE_METHODS:
            header_token = request.headers.get(CSRF_HEADER_NAME)
            cookie_token = request.cookies.get(CSRF_COOKIE_NAME)

            if not cookie_token or not header_token:
                return JSONResponse(
                    {"detail": "CSRF 토큰이 필요합니다."},
                    status_code=403,
                )
            if not secrets.compare_digest(cookie_token, header_token):
                return JSONResponse(
                    {"detail": "CSRF 토큰이 유효하지 않습니다."},
                    status_code=403,
                )

        response = await call_next(request)

        # CSRF 쿠키 발급 (HttpOnly 아님: JS에서 읽어야 함)
        response.set_cookie(
            CSRF_COOKIE_NAME,
            csrf_token,
            httponly=False,
            samesite="lax",
            secure=False,
            max_age=3600 * 24,
        )
        return response
