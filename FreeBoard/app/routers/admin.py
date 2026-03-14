from fastapi import APIRouter, Depends, Form, Request
from fastapi.responses import HTMLResponse, Response

from app.dependencies import get_current_user
from app.schemas.board import BoardCreateSchema, BoardUpdateSchema
from app.services import board_service
from app.supabase_client import supabase
from app.templates_env import templates

router = APIRouter(prefix="/admin")


def _require_admin(current_user: dict | None) -> dict:
    from fastapi import HTTPException
    if not current_user:
        raise HTTPException(status_code=302, headers={"Location": "/login"})
    if not current_user.get("is_admin"):
        raise HTTPException(status_code=403, detail="관리자 권한이 필요합니다.")
    return current_user


# ── 게시판 관리 페이지 ──────────────────────────────────────────
@router.get("/boards", response_class=HTMLResponse)
async def admin_boards_page(
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    boards = board_service.get_boards()
    return templates.TemplateResponse(
        request,
        "pages/admin/boards.html",
        {"boards": boards, "current_user": current_user},
    )


# ── 게시판 생성 ─────────────────────────────────────────────────
@router.post("/boards")
async def admin_create_board(
    request: Request,
    name: str = Form(...),
    description: str = Form(""),
    read_perm: str = Form("all"),
    write_perm: str = Form("member"),
    comment_perm: str = Form("member"),
    display_order: int = Form(0),
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    try:
        schema = BoardCreateSchema(
            name=name,
            description=description,
            read_perm=read_perm,
            write_perm=write_perm,
            comment_perm=comment_perm,
            display_order=display_order,
        )
        board_service.create_board(
            schema.name, schema.description,
            schema.read_perm, schema.write_perm,
            schema.comment_perm, schema.display_order,
        )
    except (board_service.BoardError, ValueError) as e:
        return templates.TemplateResponse(
            request,
            "partials/admin_error.html",
            {"error": str(e)},
            status_code=422,
        )

    boards = board_service.get_boards()
    response = templates.TemplateResponse(
        request,
        "partials/board_list_admin.html",
        {"boards": boards, "current_user": current_user},
    )
    response.headers["HX-Trigger"] = "boardCreated"
    return response


# ── 게시판 수정 ─────────────────────────────────────────────────
@router.put("/boards/{board_id}")
async def admin_update_board(
    board_id: int,
    request: Request,
    name: str = Form(...),
    description: str = Form(""),
    read_perm: str = Form("all"),
    write_perm: str = Form("member"),
    comment_perm: str = Form("member"),
    display_order: int = Form(0),
    is_active: bool = Form(True),
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    try:
        schema = BoardUpdateSchema(
            name=name,
            description=description,
            read_perm=read_perm,
            write_perm=write_perm,
            comment_perm=comment_perm,
            display_order=display_order,
            is_active=is_active,
        )
        board_service.update_board(
            board_id,
            name=schema.name,
            description=schema.description,
            read_perm=schema.read_perm,
            write_perm=schema.write_perm,
            comment_perm=schema.comment_perm,
            display_order=schema.display_order,
            is_active=schema.is_active,
        )
    except (board_service.BoardError, ValueError) as e:
        return templates.TemplateResponse(
            request,
            "partials/admin_error.html",
            {"error": str(e)},
            status_code=422,
        )

    boards = board_service.get_boards()
    return templates.TemplateResponse(
        request,
        "partials/board_list_admin.html",
        {"boards": boards, "current_user": current_user},
    )


# ── 게시판 삭제 ─────────────────────────────────────────────────
@router.delete("/boards/{board_id}")
async def admin_delete_board(
    board_id: int,
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    try:
        board_service.delete_board(board_id)
    except board_service.BoardError as e:
        return Response(content=str(e), status_code=422)

    return Response(status_code=200)


# ── 사용자 관리 페이지 ──────────────────────────────────────────
@router.get("/users", response_class=HTMLResponse)
async def admin_users_page(
    request: Request,
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    result = supabase.table("users").select("*").order("created_at").execute()
    users = result.data or []
    return templates.TemplateResponse(
        request,
        "pages/admin/users.html",
        {"users": users, "current_user": current_user},
    )


# ── 사용자 권한/활성화 변경 ─────────────────────────────────────
@router.put("/users/{user_id}")
async def admin_update_user(
    user_id: str,
    request: Request,
    is_admin: str = Form("false"),
    is_active: str = Form("true"),
    current_user: dict | None = Depends(get_current_user),
):
    _require_admin(current_user)
    try:
        supabase.table("users").update({
            "is_admin": is_admin.lower() == "true",
            "is_active": is_active.lower() == "true",
        }).eq("id", user_id).execute()
    except Exception as e:
        return Response(content=str(e), status_code=422)

    result = supabase.table("users").select("*").order("created_at").execute()
    users = result.data or []
    return templates.TemplateResponse(
        request,
        "partials/user_list_admin.html",
        {"users": users, "current_user": current_user},
    )
