# 빈번한 실수 & 해결 방법

## 1. Supabase JWT 알고리즘
- **문제**: Supabase access token은 ES256(비대칭) 알고리즘 사용
- **증상**: `JWSError('The specified alg value is not allowed')` — python-jose HS256으로 검증 시도
- **해결**: `python-jose.jwt.decode()` 대신 `supabase.auth.get_user(access_token)` 사용

## 2. TemplateResponse 파라미터 순서
- **문제**: 구형 방식 `TemplateResponse(name, {"request": request})` → Starlette deprecation warning
- **해결**: `TemplateResponse(request, name, context)` 사용

## 3. Jinja2 tojson 필터 없음
- **문제**: FastAPI Jinja2Templates 기본 환경에 `tojson` 필터 없음
- **해결**: `app/templates_env.py`에 공유 인스턴스 생성 후 `tojson` 필터 추가
  ```python
  templates.env.filters["tojson"] = lambda obj: json.dumps(obj, ensure_ascii=False)
  ```

## 4. 이메일 도메인 검증
- **문제**: `@aizeva.test` 등 `.test` TLD는 email-validator가 RFC 2606 위반으로 거부
- **해결**: 테스트 이메일은 `@mailtest.com` 사용

## 5. Supabase 중복 이메일 에러 메시지
- **문제**: 중복 이메일로 가입 시 "User not allowed" 반환 (직관적이지 않음)
- **해결**: `auth_service.py`에서 `"user not allowed"` 문자열 체크하여 한국어 메시지로 변환

## 6. HTMX 컨텍스트 키 불일치
- **문제**: boards.py HTMX 응답에서 `board` 키로 넘겼지만 템플릿은 `active_board` 기대
- **해결**: HTMX 응답에서도 `active_board` 키 사용

## 7. Docker 내 DNS 간헐적 실패
- **문제**: `httpx.ConnectError: [Errno -2] Name or service not known` — Supabase REST API 연결 실패
- **패턴**: `supabase.table()` REST 호출 시 간헐적 DNS 실패 (auth 호출은 정상)
- **해결**: 테스트 재실행 시 정상화됨 (transient Docker DNS 이슈)

## 8. pytest.ini asyncio_mode = auto + 모듈 스코프 async fixture
- **문제**: pytest-asyncio 0.24+ 에서 module-scoped async fixture에 asyncio_default_fixture_loop_scope 경고
- **상태**: 기능상 문제 없음, 경고만 발생

## 9. supabase.auth.sign_in_with_password 전역 클라이언트 세션 오염
- **문제**: `sign_in_with_password`가 전역 `supabase` 싱글톤에 세션을 저장 → 이후 `admin.create_user` 등 admin API 호출이 user token으로 실행되어 실패 (`A user with this email address has already been registered`)
- **증상**: 테스트에서 member 로그인 후 admin 계정 생성 시 "이미 사용 중인 이메일" 에러
- **해결**: `authenticate_user`에서 anon key로 임시 클라이언트 생성 후 로그인 (`create_client(url, anon_key)`) — 전역 서비스롤 클라이언트를 오염시키지 않음

## 10. list_users() 기본 페이지 크기 10
- **문제**: `supabase.auth.admin.list_users()` 기본값이 10개만 반환 → 고아 계정 탐색 시 놓칠 수 있음
- **해결**: `list_users(per_page=1000)` 사용

## 11. Supabase 중복 이메일 에러 메시지 (admin.create_user)
- **문제**: `admin.create_user`로 중복 이메일 생성 시 "A user with this email address has **already been** registered" 반환
- **기존 체크**: `"already registered" in msg` → "already **been** registered"에서 불일치
- **해결**: `"already been registered" in msg` 조건 추가
