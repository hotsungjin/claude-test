<?php
/**
 * 설성목장 추천 리워드 시스템
 * 추천 코드 발급 및 추천 트리 관리 서비스
 */

namespace Component\Referral;

use Framework\Object\SingletonTrait;

class ReferralService
{
    use SingletonTrait;

    /** 단계별 기본 리워드율 */
    const REWARD_RATES = [
        1 => 0.05,   // 5%
        2 => 0.02,   // 2%
        3 => 0.01,   // 1%
        4 => 0.005,  // 0.5%
        5 => 0.002,  // 0.2%
    ];

    /** 등급 기준 (직접 추천 수 기준) */
    const GRADE_CONFIG = [
        ['grade' => '목장', 'multiplier' => 2.0,  'required' => 20],
        ['grade' => '숲',   'multiplier' => 1.75, 'required' => 10],
        ['grade' => '나무', 'multiplier' => 1.5,  'required' => 5],
        ['grade' => '새싹', 'multiplier' => 1.25, 'required' => 2],
        ['grade' => '씨앗', 'multiplier' => 1.0,  'required' => 0],
    ];

    /** 마일스톤 설정 */
    const MILESTONE_CONFIG = [
        'first_referral' => ['label' => '첫 추천 성공',   'points' => 5000,  'required' => 1,  'type' => 'direct'],
        'network_5'      => ['label' => '직접 추천 5명',  'points' => 20000, 'required' => 5,  'type' => 'direct'],
        'network_10'     => ['label' => '네트워크 10명',  'points' => 30000, 'required' => 10, 'type' => 'total'],
    ];

    /** @var ReferralDAO */
    private $dao;

    public function __construct()
    {
        $this->dao = ReferralDAO::getInstance();
    }

    // ─────────────────────────────────────────
    // 회원 가입 시 호출
    // ─────────────────────────────────────────

    /**
     * 신규 회원 추천 등록
     * JoinOkController에서 가입 완료 후 호출
     *
     * @param int    $memNo        신규 회원 번호
     * @param string $referralCode 입력한 추천 코드 (없으면 null)
     */
    public function registerNewMember($memNo, $referralCode = null)
    {
        // 이미 등록된 회원이면 중복 방지
        if ($this->dao->getMemberByMemNo($memNo)) {
            return false;
        }

        // 추천인 확인
        $referrerMemNo = null;
        if ($referralCode) {
            $referrer = $this->dao->getMemberByCode(trim($referralCode));
            if ($referrer) {
                $referrerMemNo = $referrer['mem_no'];
            }
        }

        // 고유 추천 코드 발급
        $newCode = $this->generateUniqueCode();

        // 회원 등록
        $this->dao->insertMember($memNo, $newCode, $referrerMemNo);

        // 추천 트리 구성
        if ($referrerMemNo) {
            $this->buildReferralTree($memNo, $referrerMemNo);
            // 추천인 등급 업데이트
            $this->updateGrade($referrerMemNo);
        }

        return $newCode;
    }

    // ─────────────────────────────────────────
    // 추천 코드 발급
    // ─────────────────────────────────────────

    /**
     * 고유 추천 코드 생성 (6자리 영문+숫자)
     */
    private function generateUniqueCode()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if (!$this->dao->isCodeExists($code)) {
                return $code;
            }
        }
        // 충돌 극히 드물지만 타임스탬프로 보완
        return strtoupper(substr(base_convert(time(), 10, 36), -6));
    }

    // ─────────────────────────────────────────
    // 추천 트리 구성
    // ─────────────────────────────────────────

    /**
     * 신규 회원 기준으로 5단계까지 추천 트리 등록
     */
    private function buildReferralTree($newMemNo, $referrerMemNo)
    {
        // depth 1: 직접 추천인
        $this->dao->insertReferralTree($referrerMemNo, $newMemNo, 1);

        // depth 2~5: 추천인의 상위 조상들
        $ancestors = $this->dao->getAncestors($referrerMemNo);
        foreach ($ancestors as $ancestor) {
            $newDepth = $ancestor['depth'] + 1;
            if ($newDepth <= 5) {
                $this->dao->insertReferralTree($ancestor['ancestor_mem_no'], $newMemNo, $newDepth);
            }
        }
    }

    // ─────────────────────────────────────────
    // 등급 관리
    // ─────────────────────────────────────────

    /**
     * 직접 추천 수 기반 등급 자동 업데이트
     */
    public function updateGrade($memNo)
    {
        $directCount = $this->dao->getDirectReferralCount($memNo);

        $grade = '씨앗';
        $multiplier = 1.0;

        foreach (self::GRADE_CONFIG as $config) {
            if ($directCount >= $config['required']) {
                $grade = $config['grade'];
                $multiplier = $config['multiplier'];
                break;
            }
        }

        $this->dao->updateGrade($memNo, $grade, $multiplier);

        return ['grade' => $grade, 'multiplier' => $multiplier];
    }

    // ─────────────────────────────────────────
    // 마이페이지 데이터
    // ─────────────────────────────────────────

    /**
     * 회원 추천 현황 전체 조회 (마이페이지용)
     */
    public function getMemberStats($memNo)
    {
        $member = $this->dao->getMemberByMemNo($memNo);
        if (!$member) {
            return null;
        }

        $directCount  = $this->dao->getDirectReferralCount($memNo);
        $totalNetwork = $this->dao->getTotalNetworkCount($memNo);
        $byDepth      = $this->dao->getNetworkCountByDepth($memNo);
        $thisMonth    = $this->dao->getThisMonthRewardTotal($memNo);
        $totalEarned  = $this->dao->getTotalRewardEarned($memNo);
        $milestones   = $this->getMilestoneStatus($memNo, $directCount, $totalNetwork);

        // 다음 등급 계산
        $nextGrade = $this->getNextGrade($member['grade'], $directCount);

        // depth별 배열 정리
        $depthMap = [];
        foreach ($byDepth as $row) {
            $depthMap[$row['depth']] = (int)$row['cnt'];
        }

        return [
            'referral_code'   => $member['referral_code'],
            'grade'           => $member['grade'],
            'multiplier'      => $member['grade_multiplier'],
            'next_grade'      => $nextGrade['name'],
            'grade_progress'  => $nextGrade['progress'],
            'this_month'      => $thisMonth,
            'total_earned'    => $totalEarned,
            'total_network'   => $totalNetwork,
            'network_by_depth'=> $depthMap,
            'milestones'      => $milestones,
        ];
    }

    /**
     * 다음 등급 정보 계산
     */
    private function getNextGrade($currentGrade, $directCount)
    {
        $config = array_reverse(self::GRADE_CONFIG);
        $nextConfig = null;
        foreach ($config as $c) {
            if ($directCount < $c['required']) {
                $nextConfig = $c;
            }
        }

        if (!$nextConfig || $currentGrade === '목장') {
            return ['name' => '목장', 'progress' => 100];
        }

        $progress = $nextConfig['required'] > 0
            ? min(100, round(($directCount / $nextConfig['required']) * 100))
            : 100;

        return ['name' => $nextConfig['grade'], 'progress' => $progress];
    }

    /**
     * 마일스톤 달성 현황
     */
    private function getMilestoneStatus($memNo, $directCount, $totalNetwork)
    {
        $achieved = $this->dao->getAchievedMilestones($memNo);
        $result = [];

        foreach (self::MILESTONE_CONFIG as $type => $config) {
            $current = $config['type'] === 'direct' ? $directCount : $totalNetwork;
            $result[] = [
                'type'     => $type,
                'label'    => $config['label'],
                'points'   => $config['points'],
                'achieved' => in_array($type, $achieved),
                'progress' => min($current, $config['required']),
                'required' => $config['required'],
            ];
        }

        return $result;
    }
}
