import bleach

from app.supabase_client import supabase

# bleach 허용 태그/속성 (Quill.js 출력 기준)
ALLOWED_TAGS = [
    "p", "br", "strong", "em", "u", "s", "blockquote", "pre", "code",
    "h1", "h2", "h3", "ol", "ul", "li", "a", "img", "span", "div",
]
ALLOWED_ATTRIBUTES = {
    "a": ["href", "title", "target", "rel"],
    "img": ["src", "alt", "width", "height"],
    "span": ["class", "style"],
    "div": ["class"],
    "p": ["class"],
    "pre": ["class"],
    "code": ["class"],
}


class PostError(Exception):
    pass


def _sanitize(content: str) -> str:
    return bleach.clean(
        content,
        tags=ALLOWED_TAGS,
        attributes=ALLOWED_ATTRIBUTES,
        strip=True,
    )


def get_posts(board_id: int) -> list[dict]:
    """게시판 글 목록 (is_deleted=False, 최신순)"""
    result = (
        supabase.table("posts")
        .select("id, title, view_count, comment_count, created_at, author_id, users(username)")
        .eq("board_id", board_id)
        .eq("is_deleted", False)
        .order("created_at", desc=True)
        .execute()
    )
    posts = []
    for row in (result.data or []):
        row["author"] = row.pop("users", None)
        posts.append(row)
    return posts


def get_post(post_id: int) -> dict:
    try:
        result = (
            supabase.table("posts")
            .select("*, users(id, username)")
            .eq("id", post_id)
            .eq("is_deleted", False)
            .single()
            .execute()
        )
        row = result.data
        row["author"] = row.pop("users", None)
        return row
    except Exception:
        raise PostError("존재하지 않는 게시글입니다.")


def create_post(board_id: int, author_id: str, title: str, content: str) -> dict:
    clean = _sanitize(content)
    try:
        result = (
            supabase.table("posts")
            .insert({
                "board_id": board_id,
                "author_id": author_id,
                "title": title,
                "content": clean,
            })
            .execute()
        )
        return result.data[0]
    except Exception as e:
        raise PostError(f"게시글 작성 실패: {e}")


def update_post(post_id: int, title: str, content: str) -> dict:
    clean = _sanitize(content)
    try:
        result = (
            supabase.table("posts")
            .update({"title": title, "content": clean, "updated_at": "now()"})
            .eq("id", post_id)
            .execute()
        )
        return result.data[0]
    except Exception as e:
        raise PostError(f"게시글 수정 실패: {e}")


def delete_post(post_id: int) -> None:
    """소프트 삭제"""
    try:
        supabase.table("posts").update({"is_deleted": True}).eq("id", post_id).execute()
    except Exception as e:
        raise PostError(f"게시글 삭제 실패: {e}")


def increment_view_count(post_id: int) -> None:
    try:
        supabase.rpc("increment_post_view", {"post_id": post_id}).execute()
    except Exception:
        # RPC 없으면 직접 업데이트
        try:
            post = supabase.table("posts").select("view_count").eq("id", post_id).single().execute()
            new_count = (post.data.get("view_count") or 0) + 1
            supabase.table("posts").update({"view_count": new_count}).eq("id", post_id).execute()
        except Exception:
            pass


def check_write_permission(board: dict, user: dict | None) -> bool:
    perm = board.get("write_perm", "member")
    if perm == "member":
        return user is not None
    if perm == "admin":
        return user is not None and user.get("is_admin", False)
    return False


def check_post_owner(post: dict, user: dict | None) -> bool:
    """작성자 또는 관리자인지 확인"""
    if not user:
        return False
    if user.get("is_admin"):
        return True
    return str(post.get("author_id")) == str(user.get("id"))
