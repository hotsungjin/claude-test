-- ============================================================
-- AIZEVA DB 스키마
-- Supabase SQL Editor 또는 MCP로 실행
-- ============================================================

-- 1. 사용자 메타데이터 (Supabase Auth uid와 1:1 매핑)
CREATE TABLE IF NOT EXISTS users (
  id          UUID PRIMARY KEY,              -- Supabase auth.users.id와 동일
  email       TEXT UNIQUE NOT NULL,
  username    TEXT UNIQUE NOT NULL,
  is_admin    BOOLEAN NOT NULL DEFAULT FALSE,
  is_active   BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 2. 게시판
CREATE TABLE IF NOT EXISTS boards (
  id           SERIAL PRIMARY KEY,
  name         TEXT UNIQUE NOT NULL,
  description  TEXT,
  read_perm    TEXT NOT NULL DEFAULT 'all'
               CHECK (read_perm IN ('all', 'member', 'admin')),
  write_perm   TEXT NOT NULL DEFAULT 'member'
               CHECK (write_perm IN ('member', 'admin')),
  comment_perm TEXT NOT NULL DEFAULT 'member'
               CHECK (comment_perm IN ('all', 'member', 'admin')),
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3. 게시글
CREATE TABLE IF NOT EXISTS posts (
  id            SERIAL PRIMARY KEY,
  board_id      INTEGER NOT NULL REFERENCES boards(id) ON DELETE CASCADE,
  author_id     UUID REFERENCES users(id) ON DELETE SET NULL,
  title         TEXT NOT NULL,
  content       TEXT NOT NULL,        -- Quill HTML (bleach 처리됨)
  view_count    INTEGER NOT NULL DEFAULT 0,
  comment_count INTEGER NOT NULL DEFAULT 0,
  is_deleted    BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_posts_board_id     ON posts(board_id);
CREATE INDEX IF NOT EXISTS idx_posts_author_id    ON posts(author_id);
CREATE INDEX IF NOT EXISTS idx_posts_created_at   ON posts(created_at DESC);

-- 4. 댓글 (2단계: parent_id NULL = 최상위 댓글, NOT NULL = 대댓글)
CREATE TABLE IF NOT EXISTS comments (
  id         SERIAL PRIMARY KEY,
  post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
  author_id  UUID REFERENCES users(id) ON DELETE SET NULL,
  parent_id  INTEGER REFERENCES comments(id) ON DELETE CASCADE,
  content    TEXT NOT NULL,
  is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_comments_post_id   ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_comments_parent_id ON comments(parent_id);

-- 5. 초기 게시판 샘플 데이터
INSERT INTO boards (name, description, read_perm, write_perm, comment_perm, display_order)
VALUES
  ('공지사항', '관리자 공지사항', 'all', 'admin', 'all', 0),
  ('자유게시판', '자유롭게 이야기하세요', 'all', 'member', 'member', 1),
  ('질문게시판', '궁금한 점을 물어보세요', 'all', 'member', 'member', 2)
ON CONFLICT (name) DO NOTHING;
