# AIZEVA 개발 진행 상황

## Phase 0: 인프라 셋업 ✅
- requirements.txt, Dockerfile, docker-compose.yml, nginx.conf
- app/config.py, app/supabase_client.py, app/middleware.py, app/dependencies.py
- app/main.py 기본 구조
- Supabase DB 스키마 적용 (users, boards, posts, comments)
- 초기 게시판 샘플 데이터 (공지사항, 자유게시판, 질문게시판)
- templates/base.html (shadcn 스타일, Tailwind CDN, HTMX CDN)

## Phase 1: 인증 시스템 ✅ (13/13 테스트 통과)
- app/schemas/auth.py — SignupSchema, LoginSchema (pydantic v2)
- app/services/auth_service.py — create_user, authenticate_user, delete_user_by_id
- app/routers/auth.py — GET /login, GET /signup, POST /api/auth/login, POST /api/auth/signup, POST /api/auth/logout
- templates/pages/login.html, signup.html
- templates/partials/auth_error.html
- CSRF Double Submit Cookie (헤더 전용 검증)
- Supabase ES256 JWT 검증: supabase.auth.get_user() 사용 (python-jose HS256이 아닌)

### Phase 1 주요 발견사항
- Supabase 토큰이 ES256 알고리즘 사용 → python-jose로 HS256 검증 불가
  → `supabase.auth.get_user(token)` 사용으로 해결
- 이메일 도메인: `.test` TLD는 email-validator가 거부 → `@mailtest.com` 사용
- 중복 이메일 에러: Supabase가 "User not allowed" 반환 → str 매핑 필요

## Phase 2: 게시판 관리 ✅ (12/12 테스트 통과)
- app/schemas/board.py — BoardCreateSchema, BoardUpdateSchema
- app/services/board_service.py — get_boards, get_board, check_read/write_permission, CRUD
- app/routers/boards.py — GET /boards/{id} (HTMX partial 지원)
- app/routers/admin.py — GET/POST/PUT/DELETE /admin/boards
- app/templates_env.py — 공유 Jinja2Templates (tojson 필터 포함)
- templates/pages/main.html — 2컬럼 (게시판 목록 | 게시글 목록)
- templates/partials/post_list.html — 게시글 목록 (HTMX partial)
- templates/partials/board_list_admin.html — 관리자 게시판 테이블
- templates/partials/board_form_fields.html — 게시판 생성/수정 폼 필드
- templates/partials/admin_error.html — 관리자 에러 응답
- templates/pages/admin/boards.html — 관리자 게시판 관리 페이지
- tests/test_boards.py — 13개 테스트 (TDD 작성 완료)

## Phase 3: 게시글 CRUD ✅ (16/16 테스트 통과)
- app/schemas/post.py — PostCreateSchema, PostUpdateSchema
- app/services/post_service.py — get_posts, get_post, create/update/delete, XSS(bleach), 조회수
- app/routers/posts.py — GET/POST /boards/{id}/posts, GET/PUT/DELETE /boards/{id}/posts/{id}
- templates/pages/post_form.html — Quill.js 에디터
- templates/pages/post_detail.html — 글 상세 + 수정/삭제 버튼

## Phase 4: 댓글 시스템 ✅ (8/8 테스트 통과)
- app/schemas/comment.py — CommentCreateSchema
- app/services/comment_service.py — get_comments, create/delete, 2단계 트리, comment_count 동기화
- app/routers/comments.py — POST /api/posts/{id}/comments, DELETE /api/comments/{id}
- templates/partials/comments.html — HTMX partial (대댓글 포함)

## Phase 5: 사용자 관리 ✅ (7/7 테스트 통과)
- app/routers/admin.py — GET /admin/users, PUT /admin/users/{id}
- templates/pages/admin/users.html — 사용자 목록 + 권한/활성화 모달
- templates/partials/user_list_admin.html — HTMX partial

## Phase 6: E2E 테스트 ✅

### 검증 완료 시나리오 (Playwright MCP)
- 회원가입 → 로그인 → 로그인 상태 확인 (헤더 사용자명)
- 게시글 작성 (Quill 에디터) → 상세 페이지 확인
- 게시글 수정 → 제목/내용 변경 확인
- 게시글 삭제 → 목록으로 리다이렉트 확인
- 댓글 작성 → HTMX 댓글 카운트 업데이트 확인
- 대댓글 (답글) 작성 → 들여쓰기 트리 렌더링 확인
- 비로그인 상태: /admin/boards, /admin/users, /boards/{id}/posts/new → /login 리다이렉트
- 일반 회원: /admin/* → 403 차단 확인
- 관리자: 게시판 생성/삭제 (HTMX 테이블 업데이트)
- 관리자: 사용자 목록 조회, 권한 수정 모달

### Phase 6 발견 및 수정 사항
- **post_form.html PUT redirect loop**: `redirect: 'follow'`로 PUT→동일URL 리다이렉트 시 Chrome ERR_TOO_MANY_REDIRECTS 발생
  → `redirect: 'manual'` + `credentials: 'include'`로 수정, 클라이언트가 직접 action URL로 이동
