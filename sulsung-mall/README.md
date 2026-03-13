# 설성목장몰 (Sulsung Farm Mall)

설성목장의 신선한 제품을 판매하는 자체 개발 쇼핑몰입니다.
고도몰 의존성을 완전히 제거하고 최신 기술 스택으로 자체 구축하였습니다.

## 기술 스택

| 항목 | 기술 |
|------|------|
| 프레임워크 | Next.js 15 (App Router) |
| 언어 | TypeScript |
| 스타일링 | Tailwind CSS |
| 데이터베이스 | Supabase (PostgreSQL + RLS) |
| 인증 | Supabase Auth (이메일 / 카카오 / 구글) |
| 결제 | 토스페이먼츠 v2 (Direct) |
| 알림 | 솔라피 (카카오 알림톡 / SMS) |
| 호스팅 | AWS (ECS Fargate) 또는 Vercel |

## 주요 기능

### 쇼핑몰 (Store)
- 상품 목록 / 검색 / 카테고리 필터
- 상품 상세 (옵션 선택, 수량 조절)
- 장바구니
- 주문 / 결제 (토스페이먼츠 v2)
- 마이페이지 (주문내역, 마일리지, 쿠폰, 리뷰, 배송지, 추천인)
- 공지사항 / FAQ / 1:1 문의
- 회원가입 / 로그인 (이메일 + 소셜)

### 관리자 (Admin `/admin`)
- 상품 관리 (등록/수정/삭제)
- 주문 관리 (상태 변경 + 이력 관리)
- 회원 관리 (등급/마일리지 조정)
- 공지사항 / 1:1 문의 답변
- 네이버 쇼핑 XML 피드 (`/api/v1/feed/naver`)

## 디렉토리 구조

```
src/
├── app/
│   ├── (store)/          # 쇼핑몰 프론트엔드
│   │   ├── page.tsx      # 홈
│   │   ├── goods/        # 상품
│   │   ├── cart/         # 장바구니
│   │   ├── order/        # 주문/결제
│   │   ├── auth/         # 인증
│   │   ├── mypage/       # 마이페이지
│   │   ├── notice/       # 공지사항
│   │   └── faq/          # FAQ
│   ├── (admin)/          # 관리자
│   │   └── admin/
│   │       ├── dashboard/
│   │       ├── goods/
│   │       ├── orders/
│   │       ├── members/
│   │       ├── notices/
│   │       └── inquiries/
│   └── api/v1/           # REST API
├── components/
│   ├── store/            # 쇼핑몰 컴포넌트
│   └── admin/            # 관리자 컴포넌트
├── lib/
│   ├── supabase/         # Supabase 클라이언트
│   ├── payment/          # 토스페이먼츠
│   └── notification/     # 솔라피
├── types/                # TypeScript 타입
├── utils/                # 유틸리티 함수
└── constants/            # 상수
```

## 환경 변수 설정

`.env.local` 파일을 실제 값으로 업데이트하세요:

```env
# Supabase (필수)
NEXT_PUBLIC_SUPABASE_URL=https://xxxx.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJhbGci...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGci...

# 토스페이먼츠 (결제)
NEXT_PUBLIC_TOSS_CLIENT_KEY=test_ck_...
TOSS_SECRET_KEY=test_sk_...

# 솔라피 (알림톡/SMS)
SOLAPI_API_KEY=...
SOLAPI_API_SECRET=...
SOLAPI_SENDER_PHONE=01012345678
SOLAPI_PFID=...

# 앱 설정
NEXT_PUBLIC_SITE_URL=https://sulsung-mall.com
```

## 개발 시작

```bash
# 의존성 설치
npm install

# 개발 서버 실행
npm run dev

# 빌드
npm run build
```

## 데이터베이스 마이그레이션

```bash
# Supabase CLI 설치
npm install -g supabase

# 마이그레이션 적용 (Supabase 프로젝트 연결 후)
supabase db push

# TypeScript 타입 생성
supabase gen types typescript --project-id YOUR_PROJECT_ID > src/types/database.ts
```

## 보안

- **RLS**: 모든 테이블에 Row Level Security 정책 적용 — DB 레벨 데이터 격리
- **서버사이드 가격 검증**: 주문 확정 시 서버에서 금액 재계산 (위변조 방지)
- **Zod**: 모든 API 입력값 서버사이드 검증
- **인증 미들웨어**: `/admin`, `/mypage` 경로 자동 보호

## 주요 API

| 경로 | 설명 |
|------|------|
| `GET /api/v1/feed/naver` | 네이버 쇼핑 XML 피드 |
| `POST /api/v1/orders` | 주문 생성 |
| `POST /api/v1/payment/confirm` | 결제 확정 (토스) |
| `PATCH /api/v1/admin/orders/[id]/status` | 주문 상태 변경 |
| `POST /api/v1/admin/members/[id]/mileage` | 마일리지 지급/차감 |
