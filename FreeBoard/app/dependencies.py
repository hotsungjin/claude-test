from typing import Optional
from fastapi import Cookie, HTTPException, status
from app.supabase_client import supabase


async def get_current_user(access_token: Optional[str] = Cookie(default=None)) -> Optional[dict]:
    """현재 로그인 사용자 반환. 미로그인 시 None."""
    if not access_token:
        return None
    try:
        user_resp = supabase.auth.get_user(access_token)
        if not user_resp.user:
            return None
        user_id = str(user_resp.user.id)
        result = supabase.table("users").select("*").eq("id", user_id).single().execute()
        user = result.data
        if not user or not user.get("is_active"):
            return None
        return user
    except Exception:
        return None


async def require_login(access_token: Optional[str] = Cookie(default=None)) -> dict:
    """로그인 필수. 미로그인 시 401."""
    user = await get_current_user(access_token)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="로그인이 필요합니다.",
        )
    return user


async def require_admin(access_token: Optional[str] = Cookie(default=None)) -> dict:
    """관리자 필수. 미로그인/비관리자 시 403."""
    user = await require_login(access_token)
    if not user.get("is_admin"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="관리자 권한이 필요합니다.",
        )
    return user
