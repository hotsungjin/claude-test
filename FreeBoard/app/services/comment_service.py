from typing import Optional

from app.supabase_client import supabase


class CommentError(Exception):
    pass


def get_comments(post_id: int) -> list[dict]:
    """게시글 댓글 목록 (is_deleted=False, 생성순, author join)"""
    result = (
        supabase.table("comments")
        .select("id, post_id, parent_id, content, is_deleted, created_at, author_id, users(id, username)")
        .eq("post_id", post_id)
        .order("created_at")
        .execute()
    )
    comments = []
    for row in (result.data or []):
        row["author"] = row.pop("users", None)
        comments.append(row)
    return _build_tree(comments)


def _build_tree(comments: list[dict]) -> list[dict]:
    """댓글을 2단계 트리로 구성 (parent → replies)"""
    by_id = {c["id"]: c for c in comments}
    roots = []
    for c in comments:
        c.setdefault("replies", [])
        pid = c.get("parent_id")
        if pid and pid in by_id:
            by_id[pid].setdefault("replies", []).append(c)
        else:
            roots.append(c)
    return roots


def get_comment(comment_id: int) -> dict:
    try:
        result = (
            supabase.table("comments")
            .select("*, users(id, username)")
            .eq("id", comment_id)
            .single()
            .execute()
        )
        row = result.data
        row["author"] = row.pop("users", None)
        return row
    except Exception:
        raise CommentError("존재하지 않는 댓글입니다.")


def create_comment(
    post_id: int,
    author_id: str,
    content: str,
    parent_id: Optional[int] = None,
) -> dict:
    data: dict = {
        "post_id": post_id,
        "author_id": author_id,
        "content": content,
    }
    if parent_id is not None:
        data["parent_id"] = parent_id
    try:
        result = supabase.table("comments").insert(data).execute()
        comment = result.data[0]
        # posts.comment_count 증가
        _update_comment_count(post_id, +1)
        return comment
    except Exception as e:
        raise CommentError(f"댓글 작성 실패: {e}")


def delete_comment(comment_id: int) -> None:
    """소프트 삭제"""
    try:
        row = supabase.table("comments").select("post_id").eq("id", comment_id).single().execute()
        post_id = row.data["post_id"]
        supabase.table("comments").update({"is_deleted": True}).eq("id", comment_id).execute()
        _update_comment_count(post_id, -1)
    except CommentError:
        raise
    except Exception as e:
        raise CommentError(f"댓글 삭제 실패: {e}")


def _update_comment_count(post_id: int, delta: int) -> None:
    try:
        post = supabase.table("posts").select("comment_count").eq("id", post_id).single().execute()
        new_count = max(0, (post.data.get("comment_count") or 0) + delta)
        supabase.table("posts").update({"comment_count": new_count}).eq("id", post_id).execute()
    except Exception:
        pass


def check_comment_permission(board: dict, user: Optional[dict]) -> bool:
    perm = board.get("comment_perm", "member")
    if perm == "all":
        return True
    if perm == "member":
        return user is not None
    if perm == "admin":
        return user is not None and user.get("is_admin", False)
    return False


def check_comment_owner(comment: dict, user: Optional[dict]) -> bool:
    if not user:
        return False
    if user.get("is_admin"):
        return True
    return str(comment.get("author_id")) == str(user.get("id"))
