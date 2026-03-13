-- ============================================================
--  설성목장몰 — 전체 DB 스키마
--  Supabase PostgreSQL
-- ============================================================

-- ── Extensions ────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- 한국어 포함 유사 검색

-- ============================================================
--  1. 회원 (Members)
-- ============================================================
CREATE TABLE members (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    auth_id         UUID UNIQUE REFERENCES auth.users(id) ON DELETE CASCADE,
    email           TEXT UNIQUE NOT NULL,
    name            TEXT NOT NULL,
    phone           TEXT,
    birth_date      DATE,
    gender          TEXT CHECK (gender IN ('M','F','N')),
    grade           TEXT NOT NULL DEFAULT '일반' CHECK (grade IN ('일반','우수','VIP','VVIP')),
    mileage         INTEGER NOT NULL DEFAULT 0,
    mileage_expires_at TIMESTAMPTZ,
    deposit         INTEGER NOT NULL DEFAULT 0,        -- 예치금(캐시)
    social_provider TEXT CHECK (social_provider IN ('kakao','naver','google','apple')),
    social_id       TEXT,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    is_dormant      BOOLEAN NOT NULL DEFAULT false,    -- 휴면 계정
    dormant_at      TIMESTAMPTZ,
    marketing_sms   BOOLEAN NOT NULL DEFAULT false,
    marketing_email BOOLEAN NOT NULL DEFAULT false,
    marketing_kakao BOOLEAN NOT NULL DEFAULT false,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 마일리지 로그
CREATE TABLE mileage_logs (
    id          BIGSERIAL PRIMARY KEY,
    member_id   UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    delta       INTEGER NOT NULL,                      -- 양수:적립, 음수:사용
    balance     INTEGER NOT NULL,                      -- 변경 후 잔액
    reason      TEXT NOT NULL,                         -- '주문적립','리뷰적립','관리자지급' 등
    order_id    UUID,
    expires_at  TIMESTAMPTZ,                           -- 마일리지 만료일
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 예치금 로그
CREATE TABLE deposit_logs (
    id          BIGSERIAL PRIMARY KEY,
    member_id   UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    delta       INTEGER NOT NULL,
    balance     INTEGER NOT NULL,
    reason      TEXT NOT NULL,
    order_id    UUID,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 회원 배송지
CREATE TABLE member_addresses (
    id            UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    member_id     UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    label         TEXT NOT NULL DEFAULT '집',          -- '집','회사','기타'
    recipient     TEXT NOT NULL,
    phone         TEXT NOT NULL,
    zipcode       TEXT NOT NULL,
    address1      TEXT NOT NULL,
    address2      TEXT,
    is_default    BOOLEAN NOT NULL DEFAULT false,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  2. 상품 카테고리 (Categories)
-- ============================================================
CREATE TABLE categories (
    id          SERIAL PRIMARY KEY,
    parent_id   INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT true,
    image_url   TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  3. 상품 (Goods)
-- ============================================================
CREATE TABLE goods (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    category_id     INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name            TEXT NOT NULL,
    slug            TEXT NOT NULL UNIQUE,
    summary         TEXT,                              -- 상품 요약
    description     TEXT,                              -- 상세설명 (HTML)
    origin          TEXT,                              -- 원산지
    manufacturer    TEXT,                              -- 제조사
    brand           TEXT,
    weight          INTEGER,                           -- 무게(g) - 배송비 계산용
    price           INTEGER NOT NULL,                  -- 정가
    sale_price      INTEGER,                           -- 할인가 (NULL이면 정가)
    cost_price      INTEGER,                           -- 원가 (비공개)
    tax_type        TEXT NOT NULL DEFAULT 'taxable' CHECK (tax_type IN ('taxable','free','zero')),
    status          TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','soldout','deleted')),
    stock           INTEGER NOT NULL DEFAULT 0,
    stock_alert_qty INTEGER NOT NULL DEFAULT 5,        -- 재고 부족 알림 기준
    is_option       BOOLEAN NOT NULL DEFAULT false,    -- 옵션상품 여부
    is_bundle       BOOLEAN NOT NULL DEFAULT false,    -- 묶음상품 여부
    is_gift         BOOLEAN NOT NULL DEFAULT false,    -- 사은품 여부
    mileage_rate    DECIMAL(5,2) NOT NULL DEFAULT 1.0, -- 마일리지 적립률(%)
    thumbnail_url   TEXT,
    images          JSONB NOT NULL DEFAULT '[]',       -- [{url, sort_order, alt}]
    required_info   JSONB NOT NULL DEFAULT '{}',       -- 법정 필수정보
    tags            TEXT[] NOT NULL DEFAULT '{}',
    naver_category  TEXT,                              -- 네이버 쇼핑 카테고리 코드
    google_category TEXT,
    view_count      INTEGER NOT NULL DEFAULT 0,
    sale_count      INTEGER NOT NULL DEFAULT 0,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 상품 옵션 그룹 (색상, 사이즈 등)
CREATE TABLE goods_option_groups (
    id          SERIAL PRIMARY KEY,
    goods_id    UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    name        TEXT NOT NULL,                         -- '색상', '용량' 등
    sort_order  INTEGER NOT NULL DEFAULT 0
);

-- 상품 옵션 값
CREATE TABLE goods_options (
    id              SERIAL PRIMARY KEY,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    group_id        INTEGER REFERENCES goods_option_groups(id) ON DELETE CASCADE,
    name            TEXT NOT NULL,                     -- '빨강', '500ml' 등
    price_delta     INTEGER NOT NULL DEFAULT 0,        -- 추가금액
    stock           INTEGER NOT NULL DEFAULT 0,
    sku             TEXT,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    is_active       BOOLEAN NOT NULL DEFAULT true
);

-- 묶음 상품 구성
CREATE TABLE bundle_items (
    id              SERIAL PRIMARY KEY,
    bundle_goods_id UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    qty             INTEGER NOT NULL DEFAULT 1,
    sort_order      INTEGER NOT NULL DEFAULT 0
);

-- 연관 상품
CREATE TABLE goods_relations (
    id              SERIAL PRIMARY KEY,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    related_goods_id UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    type            TEXT NOT NULL DEFAULT 'related' CHECK (type IN ('related','upsell','gift'))
);

-- ============================================================
--  4. 주문 (Orders)
-- ============================================================
CREATE TABLE orders (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_no        TEXT NOT NULL UNIQUE,              -- 주문번호 (예: ORD20240101-0001)
    member_id       UUID REFERENCES members(id) ON DELETE SET NULL,
    -- 비회원 정보
    guest_name      TEXT,
    guest_phone     TEXT,
    guest_email     TEXT,
    -- 금액
    goods_amount    INTEGER NOT NULL,                  -- 상품 합계
    discount_amount INTEGER NOT NULL DEFAULT 0,        -- 할인 합계
    coupon_amount   INTEGER NOT NULL DEFAULT 0,        -- 쿠폰 할인
    mileage_used    INTEGER NOT NULL DEFAULT 0,        -- 마일리지 사용
    deposit_used    INTEGER NOT NULL DEFAULT 0,        -- 예치금 사용
    shipping_amount INTEGER NOT NULL DEFAULT 0,        -- 배송비
    total_amount    INTEGER NOT NULL,                  -- 최종 결제금액
    -- 배송지
    recipient       TEXT NOT NULL,
    phone           TEXT NOT NULL,
    zipcode         TEXT NOT NULL,
    address1        TEXT NOT NULL,
    address2        TEXT,
    delivery_memo   TEXT,
    -- 상태
    status          TEXT NOT NULL DEFAULT 'pending_payment'
                    CHECK (status IN (
                        'pending_payment',   -- 입금대기
                        'paid',              -- 결제완료
                        'preparing',         -- 상품준비중
                        'shipped',           -- 배송중
                        'delivered',         -- 배송완료
                        'confirmed',         -- 구매확정
                        'cancel_requested',  -- 취소요청
                        'cancelled',         -- 취소완료
                        'return_requested',  -- 반품요청
                        'returning',         -- 반품중
                        'returned',          -- 반품완료
                        'exchange_requested',-- 교환요청
                        'exchanged'          -- 교환완료
                    )),
    admin_memo      TEXT,                              -- 관리자 메모
    user_memo       TEXT,                              -- 구매자 요청사항
    paid_at         TIMESTAMPTZ,
    confirmed_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 주문 상태 이력
CREATE TABLE order_status_logs (
    id          BIGSERIAL PRIMARY KEY,
    order_id    UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    from_status TEXT,
    to_status   TEXT NOT NULL,
    reason      TEXT,
    created_by  TEXT NOT NULL DEFAULT 'system',        -- 'system','admin','member'
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 주문 상품 항목
CREATE TABLE order_items (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_id        UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE RESTRICT,
    option_id       INTEGER REFERENCES goods_options(id) ON DELETE SET NULL,
    goods_name      TEXT NOT NULL,                     -- 주문 시점 상품명 스냅샷
    option_name     TEXT,                              -- 주문 시점 옵션명 스냅샷
    qty             INTEGER NOT NULL,
    unit_price      INTEGER NOT NULL,                  -- 주문 시점 단가
    total_price     INTEGER NOT NULL,                  -- qty * unit_price
    mileage_earned  INTEGER NOT NULL DEFAULT 0,        -- 적립 예정 마일리지
    status          TEXT NOT NULL DEFAULT 'normal'
                    CHECK (status IN ('normal','cancel_requested','cancelled','return_requested','returned')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  5. 결제 (Payments)
-- ============================================================
CREATE TABLE payments (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_id        UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    pg_provider     TEXT NOT NULL DEFAULT 'toss'
                    CHECK (pg_provider IN ('toss','naverpay','kakaopay','bank')),
    payment_key     TEXT UNIQUE,                       -- 토스 paymentKey
    payment_method  TEXT,                              -- 'card','bank','vbank','phone'
    amount          INTEGER NOT NULL,
    status          TEXT NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending','done','cancelled','partial_cancelled','failed')),
    approved_at     TIMESTAMPTZ,
    raw_response    JSONB,                             -- PG 원본 응답 저장
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 현금영수증
CREATE TABLE cash_receipts (
    id              SERIAL PRIMARY KEY,
    payment_id      UUID NOT NULL REFERENCES payments(id),
    order_id        UUID NOT NULL REFERENCES orders(id),
    receipt_type    TEXT NOT NULL CHECK (receipt_type IN ('personal','business')),
    id_number       TEXT NOT NULL,                     -- 주민번호/사업자번호
    amount          INTEGER NOT NULL,
    receipt_key     TEXT,                              -- 국세청 승인번호
    issued_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 세금계산서
CREATE TABLE tax_invoices (
    id              SERIAL PRIMARY KEY,
    order_id        UUID NOT NULL REFERENCES orders(id),
    business_no     TEXT NOT NULL,                     -- 사업자번호
    company_name    TEXT NOT NULL,
    ceo_name        TEXT NOT NULL,
    business_type   TEXT,
    business_item   TEXT,
    email           TEXT NOT NULL,
    amount          INTEGER NOT NULL,
    tax_amount      INTEGER NOT NULL,
    status          TEXT NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending','issued','cancelled')),
    issued_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  6. 배송 (Deliveries)
-- ============================================================
CREATE TABLE delivery_companies (
    id          SERIAL PRIMARY KEY,
    code        TEXT NOT NULL UNIQUE,                  -- 'CJ','LOTTE','POST' 등
    name        TEXT NOT NULL,
    tracking_url TEXT,                                 -- 배송 조회 URL 템플릿
    is_active   BOOLEAN NOT NULL DEFAULT true
);

CREATE TABLE deliveries (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_id        UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    courier_id      INTEGER REFERENCES delivery_companies(id),
    courier_name    TEXT,
    tracking_no     TEXT,
    status          TEXT NOT NULL DEFAULT 'ready'
                    CHECK (status IN ('ready','shipped','in_transit','delivered','failed')),
    shipped_at      TIMESTAMPTZ,
    delivered_at    TIMESTAMPTZ,
    raw_tracking    JSONB,                             -- 배송 추적 원본 데이터
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 배송비 정책
CREATE TABLE shipping_policies (
    id              SERIAL PRIMARY KEY,
    name            TEXT NOT NULL,
    type            TEXT NOT NULL CHECK (type IN ('free','fixed','conditional')),
    base_fee        INTEGER NOT NULL DEFAULT 0,
    free_threshold  INTEGER,                           -- 무료배송 기준금액
    jeju_fee        INTEGER NOT NULL DEFAULT 0,        -- 제주 추가배송비
    island_fee      INTEGER NOT NULL DEFAULT 0,        -- 도서산간 추가배송비
    is_default      BOOLEAN NOT NULL DEFAULT false,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  7. 쿠폰 (Coupons)
-- ============================================================
CREATE TABLE coupons (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name            TEXT NOT NULL,
    code            TEXT UNIQUE,                       -- NULL이면 자동발급 쿠폰
    type            TEXT NOT NULL CHECK (type IN ('amount','rate','shipping')),
    discount_amount INTEGER,                           -- 정액 할인
    discount_rate   DECIMAL(5,2),                      -- 정률 할인(%)
    max_discount    INTEGER,                           -- 정률 최대 할인금액
    min_order_amount INTEGER NOT NULL DEFAULT 0,       -- 최소 주문금액
    target          TEXT NOT NULL DEFAULT 'all' CHECK (target IN ('all','specific_goods','specific_category')),
    target_ids      UUID[],                            -- 적용 상품/카테고리 ID
    is_duplicate    BOOLEAN NOT NULL DEFAULT false,    -- 중복 사용 가능 여부
    max_uses        INTEGER,                           -- 최대 발급수 (NULL=무제한)
    use_count       INTEGER NOT NULL DEFAULT 0,
    starts_at       TIMESTAMPTZ,
    expires_at      TIMESTAMPTZ,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 쿠폰 발급 내역
CREATE TABLE coupon_issues (
    id              BIGSERIAL PRIMARY KEY,
    coupon_id       UUID NOT NULL REFERENCES coupons(id) ON DELETE CASCADE,
    member_id       UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    order_id        UUID REFERENCES orders(id),
    status          TEXT NOT NULL DEFAULT 'unused' CHECK (status IN ('unused','used','expired')),
    used_at         TIMESTAMPTZ,
    issued_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(coupon_id, member_id)
);

-- ============================================================
--  8. 리뷰 (Reviews)
-- ============================================================
CREATE TABLE reviews (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    member_id       UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    order_item_id   UUID REFERENCES order_items(id) ON DELETE SET NULL,
    rating          SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title           TEXT,
    content         TEXT NOT NULL,
    images          JSONB NOT NULL DEFAULT '[]',
    is_best         BOOLEAN NOT NULL DEFAULT false,
    is_visible      BOOLEAN NOT NULL DEFAULT true,
    mileage_given   INTEGER NOT NULL DEFAULT 0,
    admin_reply     TEXT,
    replied_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(member_id, order_item_id)
);

-- ============================================================
--  9. 게시판 (Boards)
-- ============================================================
CREATE TABLE notices (
    id          SERIAL PRIMARY KEY,
    title       TEXT NOT NULL,
    content     TEXT NOT NULL,
    is_pinned   BOOLEAN NOT NULL DEFAULT false,
    is_active   BOOLEAN NOT NULL DEFAULT true,
    view_count  INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE faqs (
    id          SERIAL PRIMARY KEY,
    category    TEXT NOT NULL DEFAULT '일반',
    question    TEXT NOT NULL,
    answer      TEXT NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT true,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE inquiries (                               -- 1:1 문의
    id          UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    member_id   UUID REFERENCES members(id) ON DELETE SET NULL,
    goods_id    UUID REFERENCES goods(id) ON DELETE SET NULL,
    order_id    UUID REFERENCES orders(id) ON DELETE SET NULL,
    category    TEXT NOT NULL DEFAULT '일반' CHECK (category IN ('일반','주문','배송','취소/반품','상품','기타')),
    title       TEXT NOT NULL,
    content     TEXT NOT NULL,
    is_secret   BOOLEAN NOT NULL DEFAULT false,
    status      TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','answered','closed')),
    reply       TEXT,
    replied_at  TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  10. 마케팅 (Marketing)
-- ============================================================
CREATE TABLE banners (
    id              SERIAL PRIMARY KEY,
    name            TEXT NOT NULL,
    position        TEXT NOT NULL DEFAULT 'main_top'
                    CHECK (position IN ('main_top','main_middle','main_bottom','popup','aside')),
    image_url       TEXT NOT NULL,
    mobile_image_url TEXT,
    link_url        TEXT,
    alt             TEXT,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    starts_at       TIMESTAMPTZ,
    ends_at         TIMESTAMPTZ,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE popups (
    id              SERIAL PRIMARY KEY,
    name            TEXT NOT NULL,
    content         TEXT,                              -- HTML 내용
    image_url       TEXT,
    link_url        TEXT,
    position_x      INTEGER NOT NULL DEFAULT 0,
    position_y      INTEGER NOT NULL DEFAULT 0,
    width           INTEGER NOT NULL DEFAULT 400,
    starts_at       TIMESTAMPTZ,
    ends_at         TIMESTAMPTZ,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 타임세일
CREATE TABLE time_sales (
    id              SERIAL PRIMARY KEY,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    sale_price      INTEGER NOT NULL,
    stock_limit     INTEGER,                           -- 한정 수량 (NULL=무제한)
    sold_count      INTEGER NOT NULL DEFAULT 0,
    starts_at       TIMESTAMPTZ NOT NULL,
    ends_at         TIMESTAMPTZ NOT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 장바구니 리마인더 설정
CREATE TABLE cart_reminders (
    id              SERIAL PRIMARY KEY,
    member_id       UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    goods_id        UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    option_id       INTEGER REFERENCES goods_options(id),
    qty             INTEGER NOT NULL DEFAULT 1,
    reminded_at     TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  11. 알림 로그 (Notifications)
-- ============================================================
CREATE TABLE notification_logs (
    id          BIGSERIAL PRIMARY KEY,
    member_id   UUID REFERENCES members(id) ON DELETE SET NULL,
    type        TEXT NOT NULL,                         -- 'order_confirm','shipping' 등
    channel     TEXT NOT NULL CHECK (channel IN ('kakao','sms','email')),
    receiver    TEXT NOT NULL,                         -- 수신번호/이메일
    title       TEXT,
    message     TEXT NOT NULL,
    status      TEXT NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending','sent','failed')),
    error_msg   TEXT,
    sent_at     TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 알림 템플릿
CREATE TABLE notification_templates (
    id          SERIAL PRIMARY KEY,
    type        TEXT NOT NULL UNIQUE,
    name        TEXT NOT NULL,
    channel     TEXT NOT NULL,
    template_id TEXT,                                  -- 카카오 템플릿 코드
    content     TEXT NOT NULL,
    variables   TEXT[] NOT NULL DEFAULT '{}',
    is_active   BOOLEAN NOT NULL DEFAULT true,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  12. 관리자 (Admin)
-- ============================================================
CREATE TABLE admin_users (
    id          UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    auth_id     UUID UNIQUE REFERENCES auth.users(id) ON DELETE CASCADE,
    email       TEXT UNIQUE NOT NULL,
    name        TEXT NOT NULL,
    role        TEXT NOT NULL DEFAULT 'staff'
                CHECK (role IN ('superadmin','admin','staff')),
    permissions JSONB NOT NULL DEFAULT '{}',           -- 세부 권한
    is_active   BOOLEAN NOT NULL DEFAULT true,
    last_login_at TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE admin_logs (
    id          BIGSERIAL PRIMARY KEY,
    admin_id    UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    action      TEXT NOT NULL,
    target_type TEXT,                                  -- 'order','goods','member' 등
    target_id   TEXT,
    detail      JSONB,
    ip          TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  13. 장바구니 (Cart) — 세션 기반 + 회원 병합
-- ============================================================
CREATE TABLE carts (
    id          UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    member_id   UUID REFERENCES members(id) ON DELETE CASCADE,
    session_id  TEXT,                                  -- 비회원 세션
    goods_id    UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    option_id   INTEGER REFERENCES goods_options(id) ON DELETE SET NULL,
    qty         INTEGER NOT NULL DEFAULT 1,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 위시리스트
CREATE TABLE wishlists (
    id          BIGSERIAL PRIMARY KEY,
    member_id   UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    goods_id    UUID NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(member_id, goods_id)
);

-- ============================================================
--  14. 사이트 설정 (Site Config)
-- ============================================================
CREATE TABLE site_configs (
    key         TEXT PRIMARY KEY,
    value       JSONB NOT NULL,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 기본 설정값 삽입
INSERT INTO site_configs (key, value) VALUES
    ('site_name',       '"설성목장몰"'),
    ('site_description','"신선한 목장 직배송 쇼핑몰"'),
    ('contact_email',   '"cs@sulsung.co.kr"'),
    ('contact_phone',   '"1588-0000"'),
    ('business_no',     '"000-00-00000"'),
    ('ceo_name',        '"대표자명"'),
    ('address',         '"경기도 이천시 설성면"'),
    ('shipping_policy_id', '1'),
    ('mileage_rate',    '1.0'),
    ('review_mileage',  '500');

-- ============================================================
--  15. 외부채널 피드 (Channel Feeds)
-- ============================================================
CREATE TABLE channel_feeds (
    id          SERIAL PRIMARY KEY,
    channel     TEXT NOT NULL CHECK (channel IN ('naver','google','kakao')),
    status      TEXT NOT NULL DEFAULT 'active',
    last_generated_at TIMESTAMPTZ,
    item_count  INTEGER,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
--  16. 추천 리워드 (기존 sulsung-reward 통합)
-- ============================================================
CREATE TABLE referral_members (
    id                  SERIAL PRIMARY KEY,
    member_id           UUID NOT NULL UNIQUE REFERENCES members(id) ON DELETE CASCADE,
    referral_code       TEXT NOT NULL UNIQUE,
    referred_by_member_id UUID REFERENCES members(id),
    grade               TEXT NOT NULL DEFAULT '씨앗'
                        CHECK (grade IN ('씨앗','새싹','나무','숲','목장')),
    grade_multiplier    DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE referral_tree (
    id                  BIGSERIAL PRIMARY KEY,
    ancestor_member_id  UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    descendant_member_id UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    depth               SMALLINT NOT NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(ancestor_member_id, descendant_member_id)
);

CREATE TABLE referral_rewards (
    id              BIGSERIAL PRIMARY KEY,
    receiver_id     UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    order_id        UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    buyer_id        UUID NOT NULL REFERENCES members(id),
    depth           SMALLINT NOT NULL,
    base_rate       DECIMAL(5,3) NOT NULL,
    multiplier      DECIMAL(4,2) NOT NULL,
    final_points    INTEGER NOT NULL,
    status          TEXT NOT NULL DEFAULT '지급완료' CHECK (status IN ('지급완료','취소')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE referral_milestones (
    id              SERIAL PRIMARY KEY,
    member_id       UUID NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    milestone_type  TEXT NOT NULL,
    points_given    INTEGER NOT NULL,
    achieved_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(member_id, milestone_type)
);

-- ============================================================
--  인덱스
-- ============================================================
CREATE INDEX idx_members_email ON members(email);
CREATE INDEX idx_members_phone ON members(phone);
CREATE INDEX idx_members_grade ON members(grade);
CREATE INDEX idx_mileage_logs_member ON mileage_logs(member_id);
CREATE INDEX idx_goods_category ON goods(category_id);
CREATE INDEX idx_goods_status ON goods(status);
CREATE INDEX idx_goods_name_trgm ON goods USING gin(name gin_trgm_ops);
CREATE INDEX idx_orders_member ON orders(member_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at DESC);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_goods ON order_items(goods_id);
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_reviews_goods ON reviews(goods_id);
CREATE INDEX idx_reviews_member ON reviews(member_id);
CREATE INDEX idx_carts_member ON carts(member_id);
CREATE INDEX idx_carts_session ON carts(session_id);
CREATE INDEX idx_notification_logs_member ON notification_logs(member_id);
CREATE INDEX idx_admin_logs_created ON admin_logs(created_at DESC);

-- ============================================================
--  RLS (Row Level Security) 정책
-- ============================================================
ALTER TABLE members ENABLE ROW LEVEL SECURITY;
ALTER TABLE mileage_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE deposit_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE member_addresses ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE carts ENABLE ROW LEVEL SECURITY;
ALTER TABLE wishlists ENABLE ROW LEVEL SECURITY;
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE coupon_issues ENABLE ROW LEVEL SECURITY;
ALTER TABLE inquiries ENABLE ROW LEVEL SECURITY;

-- 회원: 자신의 데이터만 조회/수정
CREATE POLICY "members_own" ON members
    FOR ALL USING (auth.uid() = auth_id);

CREATE POLICY "addresses_own" ON member_addresses
    FOR ALL USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "mileage_own" ON mileage_logs
    FOR SELECT USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "deposit_own" ON deposit_logs
    FOR SELECT USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "orders_own" ON orders
    FOR ALL USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "order_items_own" ON order_items
    FOR SELECT USING (order_id IN (SELECT id FROM orders WHERE member_id = (SELECT id FROM members WHERE auth_id = auth.uid())));

CREATE POLICY "payments_own" ON payments
    FOR SELECT USING (order_id IN (SELECT id FROM orders WHERE member_id = (SELECT id FROM members WHERE auth_id = auth.uid())));

CREATE POLICY "carts_own" ON carts
    FOR ALL USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "wishlists_own" ON wishlists
    FOR ALL USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "reviews_own_write" ON reviews
    FOR INSERT WITH CHECK (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "reviews_public_read" ON reviews
    FOR SELECT USING (is_visible = true);

CREATE POLICY "coupon_issues_own" ON coupon_issues
    FOR SELECT USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

CREATE POLICY "inquiries_own" ON inquiries
    FOR ALL USING (member_id = (SELECT id FROM members WHERE auth_id = auth.uid()));

-- 공개 읽기 허용 테이블
ALTER TABLE goods ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE notices ENABLE ROW LEVEL SECURITY;
ALTER TABLE faqs ENABLE ROW LEVEL SECURITY;
ALTER TABLE banners ENABLE ROW LEVEL SECURITY;

CREATE POLICY "goods_public" ON goods FOR SELECT USING (status = 'active');
CREATE POLICY "categories_public" ON categories FOR SELECT USING (is_active = true);
CREATE POLICY "notices_public" ON notices FOR SELECT USING (is_active = true);
CREATE POLICY "faqs_public" ON faqs FOR SELECT USING (is_active = true);
CREATE POLICY "banners_public" ON banners FOR SELECT USING (is_active = true);

-- ============================================================
--  updated_at 자동 업데이트 트리거
-- ============================================================
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_members_updated_at BEFORE UPDATE ON members FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_goods_updated_at BEFORE UPDATE ON goods FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_payments_updated_at BEFORE UPDATE ON payments FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_deliveries_updated_at BEFORE UPDATE ON deliveries FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_carts_updated_at BEFORE UPDATE ON carts FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_reviews_updated_at BEFORE UPDATE ON reviews FOR EACH ROW EXECUTE FUNCTION update_updated_at();
