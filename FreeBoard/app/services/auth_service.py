from app.supabase_client import supabase


class AuthError(Exception):
    """인증 관련 에러"""


def create_user(email: str, username: str, password: str) -> dict:
    """
    Supabase Auth에 사용자 생성 후 users 테이블에 메타데이터 삽입.
    Email confirmation은 OFF 상태이므로 admin.create_user 사용.
    """
    # 중복 username 확인
    existing = (
        supabase.table("users")
        .select("id")
        .eq("username", username)
        .execute()
    )
    if existing.data:
        raise AuthError("이미 사용 중인 닉네임입니다.")

    # Supabase Auth에 사용자 생성 (이메일 확인 자동 통과)
    try:
        auth_resp = supabase.auth.admin.create_user(
            {
                "email": email,
                "password": password,
                "email_confirm": True,
            }
        )
    except Exception as e:
        msg = str(e).lower()
        if "already registered" in msg or "already been registered" in msg or "already exists" in msg or "unique" in msg or "user not allowed" in msg:
            raise AuthError("이미 사용 중인 이메일입니다.")
        raise AuthError(f"회원가입 실패: {e}")

    if not auth_resp.user:
        raise AuthError("회원가입 중 오류가 발생했습니다.")

    user_id = str(auth_resp.user.id)

    # users 테이블에 메타데이터 삽입
    try:
        result = (
            supabase.table("users")
            .insert({"id": user_id, "email": email, "username": username})
            .execute()
        )
        return result.data[0]
    except Exception as e:
        # DB 삽입 실패 시 Auth에서도 롤백
        try:
            supabase.auth.admin.delete_user(user_id)
        except Exception:
            pass
        raise AuthError(f"사용자 정보 저장 실패: {e}")


def authenticate_user(email: str, password: str) -> str:
    """이메일/비밀번호로 로그인. 성공 시 Supabase access_token 반환."""
    from supabase import create_client
    from app.config import settings

    # 글로벌 서비스롤 클라이언트에 세션이 오염되지 않도록 임시 anon 클라이언트 사용
    anon_client = create_client(settings.supabase_url, settings.supabase_anon_key)
    try:
        resp = anon_client.auth.sign_in_with_password(
            {"email": email, "password": password}
        )
    except Exception as e:
        msg = str(e).lower()
        if any(k in msg for k in ("invalid", "credentials", "wrong", "not found", "email", "password")):
            raise AuthError("이메일 또는 비밀번호가 올바르지 않습니다.")
        raise AuthError(f"로그인 실패: {e}")

    if not resp.session:
        raise AuthError("이메일 또는 비밀번호가 올바르지 않습니다.")

    access_token = resp.session.access_token
    user_id = str(resp.user.id)

    # users 테이블에 사용자가 있는지 확인 (is_active 포함)
    try:
        user = (
            supabase.table("users")
            .select("*")
            .eq("id", user_id)
            .single()
            .execute()
        ).data
    except Exception:
        raise AuthError("등록된 사용자 정보를 찾을 수 없습니다.")

    if not user:
        raise AuthError("등록된 사용자 정보를 찾을 수 없습니다.")
    if not user.get("is_active"):
        raise AuthError("비활성화된 계정입니다. 관리자에게 문의하세요.")

    return access_token


def delete_user_by_id(user_id: str) -> None:
    """테스트 정리용: Auth + users 테이블에서 삭제"""
    try:
        supabase.auth.admin.delete_user(user_id)
    except Exception:
        pass
    try:
        supabase.table("users").delete().eq("id", user_id).execute()
    except Exception:
        pass
