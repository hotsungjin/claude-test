from typing import Optional

from fastapi import APIRouter, Depends, Form, Request
from fastapi.responses import HTMLResponse, RedirectResponse

from app.dependencies import get_current_user
from app.schemas.comment import CommentCreateSchema
from app.services import board_service, post_service, comment_service
from app.templates_env import templates

router = APIRouter(prefix="/api")


# ── 댓글 작성 ────────────────────────────────────────────────
@router.post("/posts/{post_id}/comments")
async def create_comment(
    post_id: int,
    request: Request,
    content: str = Form(...),
    parent_id: Optional[int] = Form(default=None),
    current_user: dict | None = Depends(get_current_user),
):
    if not current_user:
        return RedirectResponse("/login", status_code=302)

    # 게시글과 게시판 조회
    try:
        post = post_service.get_post(post_id)
    except post_service.PostError:
        from fastapi import HTTPException
        raise HTTPException(status_code=404, detail="게시글을 찾을 수 없습니다.")

    try:
        board = board_service.get_board(post["board_id"])
    except board_service.BoardError:
        from fastapi import HTTPException
        raise HTTPException(status_code=404, detail="게시판을 찾을 수 없습니다.")

    if not comment_service.check_comment_permission(board, current_user):
        from fastapi import HTTPException
        raise HTTPException(status_code=403, detail="댓글 작성 권한이 없습니다.")

    try:
        schema = CommentCreateSchema(content=content, parent_id=parent_id)
        comment_service.create_comment(
            post_id, current_user["id"], schema.content, schema.parent_id
        )
    except (comment_service.CommentError, ValueError) as e:
        from fastapi import HTTPException
        raise HTTPException(status_code=422, detail=str(e))

    # HTMX 요청이면 댓글 목록 partial 반환
    comments = comment_service.get_comments(post_id)
    post_refreshed = post_service.get_post(post_id)
    return templates.TemplateResponse(
        request,
        "partials/comments.html",
        {
            "comments": comments,
            "post": post_refreshed,
            "board": board,
            "current_user": current_user,
        },
    )


# ── 댓글 삭제 ────────────────────────────────────────────────
@router.delete("/comments/{comment_id}")
async def delete_comment(
    comment_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    if not current_user:
        return RedirectResponse("/login", status_code=302)

    try:
        comment = comment_service.get_comment(comment_id)
    except comment_service.CommentError:
        from fastapi import HTTPException
        raise HTTPException(status_code=404, detail="댓글을 찾을 수 없습니다.")

    if not comment_service.check_comment_owner(comment, current_user):
        from fastapi import HTTPException
        raise HTTPException(status_code=403, detail="삭제 권한이 없습니다.")

    comment_service.delete_comment(comment_id)

    # HTMX 요청이면 댓글 목록 partial 반환
    post_id = comment["post_id"]
    try:
        post = post_service.get_post(post_id)
        board = board_service.get_board(post["board_id"])
    except Exception:
        from fastapi.responses import Response
        return Response(status_code=200)

    comments = comment_service.get_comments(post_id)
    return templates.TemplateResponse(
        request,
        "partials/comments.html",
        {
            "comments": comments,
            "post": post,
            "board": board,
            "current_user": current_user,
        },
    )
