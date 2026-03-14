-- ============================================================
--  설성목장 추천 리워드 시스템 — DB 테이블 설치 SQL
--  고도몰 DB에 접속한 뒤 순서대로 실행하세요.
--  실행 도구: phpMyAdmin / MySQL Workbench / 고도몰 DB 콘솔
-- ============================================================

-- 1. 추천 회원 테이블
--    모든 회원의 추천 코드, 등급, 배율을 저장합니다.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_members` (
    `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `mem_no`              INT UNSIGNED     NOT NULL COMMENT '고도몰 회원번호',
    `referral_code`       VARCHAR(10)      NOT NULL COMMENT '내 추천 코드 (6자리)',
    `referred_by_mem_no`  INT UNSIGNED     DEFAULT NULL COMMENT '나를 추천한 회원번호',
    `grade`               ENUM('씨앗','새싹','나무','숲','목장') NOT NULL DEFAULT '씨앗',
    `grade_multiplier`    DECIMAL(4,2)     NOT NULL DEFAULT 1.00 COMMENT '리워드 배율',
    `created_dt`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mem_no`       (`mem_no`),
    UNIQUE KEY `uq_referral_code`(`referral_code`),
    KEY `idx_referred_by`        (`referred_by_mem_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='추천 회원 코드 및 등급';


-- 2. 추천 트리 테이블
--    ancestor(상위)가 descendant(하위)를 depth 단계로 연결합니다.
--    회원 가입 시 최대 5단계까지 삽입됩니다.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_tree` (
    `id`                  BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `ancestor_mem_no`     INT UNSIGNED     NOT NULL COMMENT '상위 추천인 회원번호',
    `descendant_mem_no`   INT UNSIGNED     NOT NULL COMMENT '하위 피추천인 회원번호',
    `depth`               TINYINT UNSIGNED NOT NULL COMMENT '추천 단계 (1~5)',
    `created_dt`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tree_pair` (`ancestor_mem_no`, `descendant_mem_no`),
    KEY `idx_ancestor`    (`ancestor_mem_no`),
    KEY `idx_descendant`  (`descendant_mem_no`),
    KEY `idx_created_dt`  (`created_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='5단계 추천 관계 트리';


-- 3. 리워드 내역 테이블
--    주문 완료 시 단계별로 적립된 마일리지 내역을 저장합니다.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_rewards` (
    `id`               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `receiver_mem_no`  INT UNSIGNED     NOT NULL COMMENT '리워드 수령 회원번호',
    `order_no`         VARCHAR(50)      NOT NULL COMMENT '고도몰 주문번호',
    `buyer_mem_no`     INT UNSIGNED     NOT NULL COMMENT '구매자 회원번호',
    `depth`            TINYINT UNSIGNED NOT NULL COMMENT '추천 단계 (1~5)',
    `base_rate`        DECIMAL(5,3)     NOT NULL COMMENT '기본 요율 (0.050 = 5%)',
    `multiplier`       DECIMAL(4,2)     NOT NULL COMMENT '등급 배율 적용값',
    `final_points`     INT UNSIGNED     NOT NULL COMMENT '최종 지급 포인트',
    `status`           ENUM('지급완료','취소') NOT NULL DEFAULT '지급완료',
    `created_dt`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_receiver`    (`receiver_mem_no`),
    KEY `idx_order_no`    (`order_no`),
    KEY `idx_buyer`       (`buyer_mem_no`),
    KEY `idx_created_dt`  (`created_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='추천 리워드 마일리지 지급 내역';


-- 4. 마일스톤 달성 테이블
--    회원이 마일스톤(보너스 목표)을 달성하면 1회만 기록됩니다.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_milestones` (
    `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `mem_no`           INT UNSIGNED     NOT NULL COMMENT '회원번호',
    `milestone_type`   VARCHAR(30)      NOT NULL COMMENT 'first_referral | network_5 | network_10',
    `points_given`     INT UNSIGNED     NOT NULL COMMENT '지급된 포인트',
    `achieved_dt`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_member_milestone` (`mem_no`, `milestone_type`),
    KEY `idx_mem_no` (`mem_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='마일스톤 보너스 달성 내역';


-- ============================================================
--  설치 확인 쿼리 (실행 후 4개 테이블이 보이면 정상)
-- ============================================================
SHOW TABLES LIKE 'referral_%';
