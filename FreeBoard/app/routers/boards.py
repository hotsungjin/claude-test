from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, RedirectResponse

from app.dependencies import get_current_user
from app.services import board_service, post_service
from app.templates_env import templates

router = APIRouter()


@router.get("/boards/{board_id}", response_class=HTMLResponse)
async def board_page(
    board_id: int,
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    try:
        board = board_service.get_board(board_id)
    except board_service.BoardError:
        return RedirectResponse("/", status_code=302)

    if not board_service.check_read_permission(board, current_user):
        return RedirectResponse("/login", status_code=302)

    boards = board_service.get_boards()
    post_list = post_service.get_posts(board_id)
    can_write = board_service.check_write_permission(board, current_user)

    # HTMX 요청이면 post_list partial만 반환
    if request.headers.get("HX-Request"):
        return templates.TemplateResponse(
            request,
            "partials/post_list.html",
            {"active_board": board, "posts": post_list, "current_user": current_user, "can_write": can_write},
        )

    return templates.TemplateResponse(
        request,
        "pages/main.html",
        {
            "boards": boards,
            "active_board": board,
            "posts": post_list,
            "current_user": current_user,
            "can_write": can_write,
        },
    )
