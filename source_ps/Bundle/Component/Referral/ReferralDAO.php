<?php
/**
 * 설성목장 추천 리워드 시스템
 * 추천 관계 및 리워드 데이터 접근 클래스
 */

namespace Component\Referral;

use Framework\Object\SingletonTrait;

class ReferralDAO
{
    use SingletonTrait;

    /** @var \DB */
    private $db;

    public function __construct()
    {
        $this->db = \App::load('DB');
    }

    // ─────────────────────────────────────────
    // 회원 추천 코드
    // ─────────────────────────────────────────

    /**
     * 회원의 추천 코드 정보 조회
     */
    public function getMemberByMemNo($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT * FROM referral_members WHERE mem_no = {$memNo}";
        return $this->db->query_fetch($query, [], false);
    }

    /**
     * 추천 코드로 회원 조회
     */
    public function getMemberByCode($referralCode)
    {
        $referralCode = addslashes($referralCode);
        $query = "SELECT * FROM referral_members WHERE referral_code = '{$referralCode}'";
        return $this->db->query_fetch($query, [], false);
    }

    /**
     * 추천 코드 중복 확인
     */
    public function isCodeExists($referralCode)
    {
        $referralCode = addslashes($referralCode);
        $query = "SELECT COUNT(*) AS cnt FROM referral_members WHERE referral_code = '{$referralCode}'";
        $result = $this->db->query_fetch($query, [], false);
        return $result['cnt'] > 0;
    }

    /**
     * 추천 회원 등록
     */
    public function insertMember($memNo, $referralCode, $referredByMemNo = null)
    {
        $memNo = intval($memNo);
        $referralCode = addslashes($referralCode);
        $refBy = $referredByMemNo ? intval($referredByMemNo) : 'NULL';
        $query = "INSERT INTO referral_members (mem_no, referral_code, referred_by_mem_no, grade, grade_multiplier, created_dt)
                  VALUES ({$memNo}, '{$referralCode}', {$refBy}, '씨앗', 1.0, NOW())";
        return $this->db->query_fetch($query);
    }

    /**
     * 등급 업데이트
     */
    public function updateGrade($memNo, $grade, $multiplier)
    {
        $memNo = intval($memNo);
        $grade = addslashes($grade);
        $multiplier = floatval($multiplier);
        $query = "UPDATE referral_members SET grade = '{$grade}', grade_multiplier = {$multiplier} WHERE mem_no = {$memNo}";
        return $this->db->query_fetch($query);
    }

    // ─────────────────────────────────────────
    // 추천 트리
    // ─────────────────────────────────────────

    /**
     * 추천 트리 등록
     */
    public function insertReferralTree($ancestorMemNo, $descendantMemNo, $depth)
    {
        $ancestorMemNo = intval($ancestorMemNo);
        $descendantMemNo = intval($descendantMemNo);
        $depth = intval($depth);
        $query = "INSERT INTO referral_tree (ancestor_mem_no, descendant_mem_no, depth, created_dt)
                  VALUES ({$ancestorMemNo}, {$descendantMemNo}, {$depth}, NOW())";
        return $this->db->query_fetch($query);
    }

    /**
     * 특정 회원의 상위 추천인 조회 (최대 5단계)
     */
    public function getAncestors($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT rt.ancestor_mem_no, rt.depth, rt.created_dt, rm.grade_multiplier
                  FROM referral_tree rt
                  LEFT JOIN referral_members rm ON rt.ancestor_mem_no = rm.mem_no
                  WHERE rt.descendant_mem_no = {$memNo}
                    AND rt.depth <= 5
                  ORDER BY rt.depth ASC";
        return $this->db->query_fetch($query, []);
    }

    /**
     * 특정 회원의 직접 추천 수 조회 (depth 1)
     */
    public function getDirectReferralCount($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT COUNT(*) AS cnt FROM referral_tree WHERE ancestor_mem_no = {$memNo} AND depth = 1";
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['cnt'];
    }

    /**
     * 특정 회원의 전체 네트워크 수 조회
     */
    public function getTotalNetworkCount($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT COUNT(*) AS cnt FROM referral_tree WHERE ancestor_mem_no = {$memNo}";
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['cnt'];
    }

    /**
     * 단계별 네트워크 수 조회
     */
    public function getNetworkCountByDepth($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT depth, COUNT(*) AS cnt FROM referral_tree
                  WHERE ancestor_mem_no = {$memNo} GROUP BY depth ORDER BY depth ASC";
        return $this->db->query_fetch($query, []);
    }

    // ─────────────────────────────────────────
    // 리워드 내역
    // ─────────────────────────────────────────

    /**
     * 리워드 내역 저장
     */
    public function insertReward($receiverMemNo, $orderNo, $buyerMemNo, $depth, $baseRate, $multiplier, $finalPoints)
    {
        $receiverMemNo = intval($receiverMemNo);
        $orderNo = addslashes($orderNo);
        $buyerMemNo = intval($buyerMemNo);
        $depth = intval($depth);
        $baseRate = floatval($baseRate);
        $multiplier = floatval($multiplier);
        $finalPoints = intval($finalPoints);
        $query = "INSERT INTO referral_rewards
                    (receiver_mem_no, order_no, buyer_mem_no, depth, base_rate, multiplier, final_points, status, created_dt)
                  VALUES ({$receiverMemNo}, '{$orderNo}', {$buyerMemNo}, {$depth}, {$baseRate}, {$multiplier}, {$finalPoints}, '지급완료', NOW())";
        return $this->db->query_fetch($query);
    }

    /**
     * 주문번호로 이미 처리된 리워드인지 확인 (중복 방지)
     */
    public function isOrderRewarded($orderNo)
    {
        $orderNo = addslashes($orderNo);
        $query = "SELECT COUNT(*) AS cnt FROM referral_rewards WHERE order_no = '{$orderNo}'";
        $result = $this->db->query_fetch($query, [], false);
        return $result['cnt'] > 0;
    }

    /**
     * 회원의 이번 달 리워드 합계
     */
    public function getThisMonthRewardTotal($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT COALESCE(SUM(final_points), 0) AS total
                  FROM referral_rewards
                  WHERE receiver_mem_no = {$memNo}
                    AND DATE_FORMAT(created_dt, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['total'];
    }

    /**
     * 회원의 누적 리워드 합계
     */
    public function getTotalRewardEarned($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT COALESCE(SUM(final_points), 0) AS total FROM referral_rewards WHERE receiver_mem_no = {$memNo}";
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['total'];
    }

    // ─────────────────────────────────────────
    // 마일스톤
    // ─────────────────────────────────────────

    /**
     * 달성한 마일스톤 목록 조회
     */
    public function getAchievedMilestones($memNo)
    {
        $memNo = intval($memNo);
        $query = "SELECT milestone_type FROM referral_milestones WHERE mem_no = {$memNo}";
        $rows = $this->db->query_fetch($query, []);
        return array_column($rows, 'milestone_type');
    }

    /**
     * 마일스톤 달성 저장
     */
    public function insertMilestone($memNo, $milestoneType, $pointsGiven)
    {
        $memNo = intval($memNo);
        $milestoneType = addslashes($milestoneType);
        $pointsGiven = intval($pointsGiven);
        $query = "INSERT INTO referral_milestones (mem_no, milestone_type, points_given, achieved_dt)
                  VALUES ({$memNo}, '{$milestoneType}', {$pointsGiven}, NOW())";
        return $this->db->query_fetch($query);
    }

    // ─────────────────────────────────────────
    // 관리자 통계
    // ─────────────────────────────────────────

    /**
     * 전체 추천 회원 수
     */
    public function getTotalMemberCount()
    {
        $query = 'SELECT COUNT(*) AS cnt FROM referral_members';
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['cnt'];
    }

    /**
     * 이번 달 신규 추천 가입자 수
     */
    public function getThisMonthNewMemberCount()
    {
        $query = 'SELECT COUNT(*) AS cnt FROM referral_members
                  WHERE DATE_FORMAT(created_dt, \'%Y-%m\') = DATE_FORMAT(NOW(), \'%Y-%m\')';
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['cnt'];
    }

    /**
     * 이번 달 리워드 지급 총액
     */
    public function getThisMonthTotalReward()
    {
        $query = 'SELECT COALESCE(SUM(final_points), 0) AS total FROM referral_rewards
                  WHERE DATE_FORMAT(created_dt, \'%Y-%m\') = DATE_FORMAT(NOW(), \'%Y-%m\')';
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['total'];
    }

    /**
     * 지난 달 리워드 지급 총액
     */
    public function getLastMonthTotalReward()
    {
        $query = 'SELECT COALESCE(SUM(final_points), 0) AS total FROM referral_rewards
                  WHERE DATE_FORMAT(created_dt, \'%Y-%m\') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), \'%Y-%m\')';
        $result = $this->db->query_fetch($query, [], false);
        return (int)$result['total'];
    }

    /**
     * 등급 분포 조회
     */
    public function getGradeDistribution()
    {
        $query = 'SELECT grade, COUNT(*) AS cnt FROM referral_members GROUP BY grade';
        return $this->db->query_fetch($query, []);
    }

    /**
     * 상위 추천인 목록
     */
    public function getTopReferrers($limit = 10)
    {
        $query = 'SELECT rm.mem_no, rm.referral_code, rm.grade, rm.grade_multiplier,
                         COUNT(rt.descendant_mem_no) AS network_count,
                         COALESCE(SUM(rw.final_points), 0) AS total_reward
                  FROM referral_members rm
                  LEFT JOIN referral_tree rt ON rm.mem_no = rt.ancestor_mem_no AND rt.depth = 1
                  LEFT JOIN referral_rewards rw ON rm.mem_no = rw.receiver_mem_no
                  GROUP BY rm.mem_no
                  ORDER BY total_reward DESC
                  LIMIT ?';
        return $this->db->query_fetch($query, [$limit]);
    }

    /**
     * 최근 리워드 내역
     */
    public function getRecentRewards($limit = 20)
    {
        $query = 'SELECT * FROM referral_rewards ORDER BY created_dt DESC LIMIT ?';
        return $this->db->query_fetch($query, [$limit]);
    }

    /**
     * 어뷰징 감지: 24시간 내 5건 이상 추천한 회원
     */
    public function getSuspiciousMembers()
    {
        $query = 'SELECT ancestor_mem_no, COUNT(*) AS cnt FROM referral_tree
                  WHERE created_dt >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND depth = 1
                  GROUP BY ancestor_mem_no
                  HAVING cnt >= 5';
        return $this->db->query_fetch($query, []);
    }
}
