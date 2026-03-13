-- ============================================================
--  브랜드 (Brands) 테이블
-- ============================================================

CREATE TABLE brands (
    id          SERIAL PRIMARY KEY,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    logo_url    TEXT,
    description TEXT,
    is_active   BOOLEAN NOT NULL DEFAULT true,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- goods.brand 텍스트 → brands 테이블 FK 전환
ALTER TABLE goods ADD COLUMN brand_id INTEGER REFERENCES brands(id) ON DELETE SET NULL;
