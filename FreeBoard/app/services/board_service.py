from app.supabase_client import supabase


class BoardError(Exception):
    """게시판 관련 에러"""


def get_boards() -> list[dict]:
    """활성 게시판 목록 (display_order 오름차순)"""
    result = (
        supabase.table("boards")
        .select("*")
        .eq("is_active", True)
        .order("display_order")
        .execute()
    )
    return result.data or []


def get_board(board_id: int) -> dict:
    """특정 게시판 조회. 없으면 BoardError."""
    try:
        result = (
            supabase.table("boards")
            .select("*")
            .eq("id", board_id)
            .single()
            .execute()
        )
        return result.data
    except Exception:
        raise BoardError("존재하지 않는 게시판입니다.")


def check_read_permission(board: dict, user: dict | None) -> bool:
    """read_perm 충족 여부 확인"""
    perm = board.get("read_perm", "all")
    if perm == "all":
        return True
    if perm == "member":
        return user is not None
    if perm == "admin":
        return user is not None and user.get("is_admin", False)
    return False


def check_write_permission(board: dict, user: dict | None) -> bool:
    """write_perm 충족 여부 확인"""
    perm = board.get("write_perm", "member")
    if not user:
        return False
    if perm == "member":
        return True
    if perm == "admin":
        return user.get("is_admin", False)
    return False


def create_board(
    name: str,
    description: str,
    read_perm: str,
    write_perm: str,
    comment_perm: str,
    display_order: int,
) -> dict:
    """게시판 생성"""
    try:
        result = (
            supabase.table("boards")
            .insert({
                "name": name,
                "description": description,
                "read_perm": read_perm,
                "write_perm": write_perm,
                "comment_perm": comment_perm,
                "display_order": display_order,
            })
            .execute()
        )
        return result.data[0]
    except Exception as e:
        msg = str(e).lower()
        if "unique" in msg or "duplicate" in msg:
            raise BoardError("이미 사용 중인 게시판 이름입니다.")
        raise BoardError(f"게시판 생성 실패: {e}")


def update_board(board_id: int, **kwargs) -> dict:
    """게시판 수정"""
    try:
        result = (
            supabase.table("boards")
            .update(kwargs)
            .eq("id", board_id)
            .execute()
        )
        if not result.data:
            raise BoardError("게시판을 찾을 수 없습니다.")
        return result.data[0]
    except BoardError:
        raise
    except Exception as e:
        raise BoardError(f"게시판 수정 실패: {e}")


def delete_board(board_id: int) -> None:
    """게시판 삭제"""
    try:
        supabase.table("boards").delete().eq("id", board_id).execute()
    except Exception as e:
        raise BoardError(f"게시판 삭제 실패: {e}")
