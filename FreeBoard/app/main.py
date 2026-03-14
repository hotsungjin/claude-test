from fastapi import FastAPI, Request
from fastapi.staticfiles import StaticFiles
from fastapi.responses import HTMLResponse

from app.middleware import CSRFMiddleware
from app.routers import auth, boards, admin, posts, comments
from app.dependencies import get_current_user
from app.services import board_service, post_service
from app.templates_env import templates

app = FastAPI(title="무료 게시판", version="0.1.0")

# CSRF 미들웨어
app.add_middleware(CSRFMiddleware)

# Static files
app.mount("/static", StaticFiles(directory="static"), name="static")

# ── Routers ────────────────────────────────────────────────────
app.include_router(auth.router)
app.include_router(boards.router)
app.include_router(posts.router)
app.include_router(comments.router)
app.include_router(admin.router)


# ── Health check ───────────────────────────────────────────────
@app.get("/health")
async def health():
    return {"status": "ok"}


# ── 메인 페이지 ─────────────────────────────────────────────────
@app.get("/", response_class=HTMLResponse)
async def root(request: Request):
    current_user = await get_current_user(request.cookies.get("access_token"))
    all_boards = board_service.get_boards()
    active_board = all_boards[0] if all_boards else None
    posts = post_service.get_posts(active_board["id"]) if active_board else []
    can_write = board_service.check_write_permission(active_board, current_user) if active_board else False
    return templates.TemplateResponse(
        request,
        "pages/main.html",
        {
            "boards": all_boards,
            "active_board": active_board,
            "posts": posts,
            "current_user": current_user,
            "can_write": can_write,
        },
    )


# ── 이용약관 / 개인정보 (플레이스홀더) ──────────────────────────
@app.get("/terms", response_class=HTMLResponse)
async def terms(request: Request):
    current_user = await get_current_user(request.cookies.get("access_token"))
    return templates.TemplateResponse(
        request,
        "pages/terms.html",
        {"current_user": current_user},
    )


@app.get("/privacy", response_class=HTMLResponse)
async def privacy(request: Request):
    current_user = await get_current_user(request.cookies.get("access_token"))
    return templates.TemplateResponse(
        request,
        "pages/privacy.html",
        {"current_user": current_user},
    )
