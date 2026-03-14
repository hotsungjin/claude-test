from fastapi import APIRouter, Depends, Form, Request
from fastapi.responses import HTMLResponse, RedirectResponse

from app.dependencies import get_current_user
from app.schemas.post import PostCreateSchema, PostUpdateSchema
from app.services import board_service, post_service, comment_service
from app.templates_env import templates

router = APIRouter()


def _get_board_or_redirect(board_id: int):
    try:
        return board_service.get_board(board_id)
    except board_service.BoardError:
        return None


# ── 글쓰기 폼 ────────────────────────────────────────────────
@router.get("/boards/{board_id}/posts/new", response_class=HTMLResponse)
async def new_post_form(
    board_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    board = _get_board_or_redirect(board_id)
    if not board:
        return RedirectResponse("/", status_code=302)

    if not current_user:
        return RedirectResponse("/login", status_code=302)

    if not post_service.check_write_permission(board, current_user):
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    return templates.TemplateResponse(
        request,
        "pages/post_form.html",
        {"board": board, "post": None, "current_user": current_user},
    )


# ── 글 작성 ──────────────────────────────────────────────────
@router.post("/boards/{board_id}/posts")
async def create_post(
    board_id: int,
    request: Request,
    title: str = Form(...),
    content: str = Form(...),
    current_user: dict | None = Depends(get_current_user),
):
    board = _get_board_or_redirect(board_id)
    if not board:
        return RedirectResponse("/", status_code=302)

    if not current_user:
        return RedirectResponse("/login", status_code=302)

    if not post_service.check_write_permission(board, current_user):
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    try:
        schema = PostCreateSchema(title=title, content=content)
        post = post_service.create_post(
            board_id, current_user["id"], schema.title, schema.content
        )
    except (post_service.PostError, ValueError) as e:
        return templates.TemplateResponse(
            request,
            "pages/post_form.html",
            {"board": board, "post": None, "error": str(e), "current_user": current_user},
            status_code=422,
        )

    return RedirectResponse(
        f"/boards/{board_id}/posts/{post['id']}", status_code=302
    )


# ── 글 상세 ──────────────────────────────────────────────────
@router.get("/boards/{board_id}/posts/{post_id}", response_class=HTMLResponse)
async def post_detail(
    board_id: int,
    post_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    board = _get_board_or_redirect(board_id)
    if not board:
        return RedirectResponse("/", status_code=302)

    if not board_service.check_read_permission(board, current_user):
        return RedirectResponse("/login", status_code=302)

    try:
        post = post_service.get_post(post_id)
    except post_service.PostError:
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    post_service.increment_view_count(post_id)

    boards = board_service.get_boards()
    post_comments = comment_service.get_comments(post_id)
    can_write = post_service.check_write_permission(board, current_user)
    return templates.TemplateResponse(
        request,
        "pages/post_detail.html",
        {
            "board": board,
            "boards": boards,
            "post": post,
            "current_user": current_user,
            "comments": post_comments,
            "can_write": can_write,
        },
    )


# ── 글 수정 폼 ────────────────────────────────────────────────
@router.get("/boards/{board_id}/posts/{post_id}/edit", response_class=HTMLResponse)
async def edit_post_form(
    board_id: int,
    post_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    board = _get_board_or_redirect(board_id)
    if not board:
        return RedirectResponse("/", status_code=302)

    if not current_user:
        return RedirectResponse("/login", status_code=302)

    try:
        post = post_service.get_post(post_id)
    except post_service.PostError:
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    if not post_service.check_post_owner(post, current_user):
        return RedirectResponse(f"/boards/{board_id}/posts/{post_id}", status_code=302)

    return templates.TemplateResponse(
        request,
        "pages/post_form.html",
        {"board": board, "post": post, "current_user": current_user},
    )


# ── 글 수정 ──────────────────────────────────────────────────
@router.put("/boards/{board_id}/posts/{post_id}")
async def update_post(
    board_id: int,
    post_id: int,
    request: Request,
    title: str = Form(...),
    content: str = Form(...),
    current_user: dict | None = Depends(get_current_user),
):
    board = _get_board_or_redirect(board_id)
    if not board:
        return RedirectResponse("/", status_code=302)

    if not current_user:
        return RedirectResponse("/login", status_code=302)

    try:
        post = post_service.get_post(post_id)
    except post_service.PostError:
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    if not post_service.check_post_owner(post, current_user):
        return RedirectResponse(f"/boards/{board_id}/posts/{post_id}", status_code=302)

    try:
        schema = PostUpdateSchema(title=title, content=content)
        post_service.update_post(post_id, schema.title, schema.content)
    except (post_service.PostError, ValueError) as e:
        return templates.TemplateResponse(
            request,
            "pages/post_form.html",
            {"board": board, "post": post, "error": str(e), "current_user": current_user},
            status_code=422,
        )

    return RedirectResponse(
        f"/boards/{board_id}/posts/{post_id}", status_code=302
    )


# ── 글 삭제 ──────────────────────────────────────────────────
@router.delete("/boards/{board_id}/posts/{post_id}")
async def delete_post(
    board_id: int,
    post_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    if not current_user:
        return RedirectResponse("/login", status_code=302)

    try:
        post = post_service.get_post(post_id)
    except post_service.PostError:
        return RedirectResponse(f"/boards/{board_id}", status_code=302)

    if not post_service.check_post_owner(post, current_user):
        return RedirectResponse(f"/boards/{board_id}/posts/{post_id}", status_code=302)

    post_service.delete_post(post_id)
    return RedirectResponse(f"/boards/{board_id}", status_code=302)
