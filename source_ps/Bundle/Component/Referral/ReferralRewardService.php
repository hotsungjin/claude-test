<?php
/**
 * 설성목장 추천 리워드 시스템
 * 리워드 계산 및 마일리지 지급 서비스
 */

namespace Bundle\Component\Referral;

use Bundle\Component\Mileage\Mileage;
use Framework\Object\SingletonTrait;

class ReferralRewardService extends \Component\AbstractComponent
{
    use SingletonTrait;

    /** 1년 유효기간 (초) */
    const REWARD_VALID_SECONDS = 365 * 24 * 60 * 60;

    /** @var ReferralDAO */
    private $dao;

    /** @var ReferralService */
    private $referralService;

    /** @var Mileage */
    private $mileage;

    public function __construct()
    {
        parent::__construct();
        $this->dao             = ReferralDAO::getInstance();
        $this->referralService = ReferralService::getInstance();
        $this->mileage         = \App::load(Mileage::class);
    }

    // ─────────────────────────────────────────
    // 주문 완료 시 호출 (OrderEndService 훅)
    // ─────────────────────────────────────────

    /**
     * 주문 완료 시 리워드 처리
     *
     * @param int    $buyerMemNo 구매자 회원번호
     * @param string $orderNo    주문번호
     * @param int    $amount     결제금액
     */
    public function processOrderReward($buyerMemNo, $orderNo, $amount)
    {
        // 중복 처리 방지
        if ($this->dao->isOrderRewarded($orderNo)) {
            return false;
        }

        // 구매자의 상위 추천인 조회 (1~5단계)
        $ancestors = $this->dao->getAncestors($buyerMemNo);
        if (empty($ancestors)) {
            return false;
        }

        foreach ($ancestors as $ancestor) {
            // 1년 유효기간 체크
            if (!$this->isWithinValidPeriod($ancestor['created_dt'])) {
                continue;
            }

            $depth      = (int)$ancestor['depth'];
            $baseRate   = ReferralService::REWARD_RATES[$depth] ?? 0;
            $multiplier = (float)$ancestor['grade_multiplier'];
            $points     = (int)floor($amount * $baseRate * $multiplier);

            if ($points <= 0) {
                continue;
            }

            // 리워드 내역 저장
            $this->dao->insertReward(
                $ancestor['ancestor_mem_no'],
                $orderNo,
                $buyerMemNo,
                $depth,
                $baseRate,
                $multiplier,
                $points
            );

            // 고도몰 마일리지 지급
            $this->addMileage(
                $ancestor['ancestor_mem_no'],
                $points,
                $orderNo,
                $depth
            );
        }

        // 마일스톤 체크 (구매자 기준 추천인에게)
        $directReferrer = $this->getDirectReferrer($ancestors);
        if ($directReferrer) {
            $this->checkAndGrantMilestones($directReferrer);
        }

        return true;
    }

    // ─────────────────────────────────────────
    // 마일리지 지급
    // ─────────────────────────────────────────

    /**
     * 고도몰 마일리지 시스템으로 포인트 지급
     */
    private function addMileage($memNo, $points, $orderNo, $depth)
    {
        $reasonCd = Mileage::REASON_CODE_GROUP . Mileage::REASON_CODE_ETC;
        $contents = sprintf('추천 리워드 지급 (L%d, 주문번호: %s)', $depth, $orderNo);

        $this->mileage->setMemberMileage(
            $memNo,
            $points,
            $reasonCd,
            Mileage::MODE_RECOMMEND,
            'referral',
            $orderNo,
            $contents,
            false
        );
    }

    // ─────────────────────────────────────────
    // 마일스톤
    // ─────────────────────────────────────────

    /**
     * 마일스톤 달성 여부 확인 및 지급
     */
    public function checkAndGrantMilestones($memNo)
    {
        $directCount  = $this->dao->getDirectReferralCount($memNo);
        $totalNetwork = $this->dao->getTotalNetworkCount($memNo);
        $achieved     = $this->dao->getAchievedMilestones($memNo);

        foreach (ReferralService::MILESTONE_CONFIG as $type => $config) {
            if (in_array($type, $achieved)) {
                continue;
            }

            $current = $config['type'] === 'direct' ? $directCount : $totalNetwork;

            if ($current >= $config['required']) {
                // 마일스톤 달성 저장
                $this->dao->insertMilestone($memNo, $type, $config['points']);

                // 마일리지 지급
                $reasonCd = Mileage::REASON_CODE_GROUP . Mileage::REASON_CODE_ETC;
                $this->mileage->setMemberMileage(
                    $memNo,
                    $config['points'],
                    $reasonCd,
                    Mileage::MODE_RECOMMEND,
                    'milestone',
                    null,
                    '추천 마일스톤 달성: ' . $config['label'],
                    false
                );
            }
        }
    }

    // ─────────────────────────────────────────
    // 주문 취소 시 리워드 회수
    // ─────────────────────────────────────────

    /**
     * 주문 취소 시 지급된 리워드 회수
     */
    public function cancelOrderReward($orderNo)
    {
        $query = 'SELECT * FROM referral_rewards WHERE order_no = ? AND status = \'지급완료\'';
        $rewards = $this->db->query_fetch($query, [$orderNo]);

        if (empty($rewards)) {
            return false;
        }

        foreach ($rewards as $reward) {
            // 마일리지 차감 (음수로 지급)
            $reasonCd = Mileage::REASON_CODE_GROUP . Mileage::REASON_CODE_ETC;
            $this->mileage->setMemberMileage(
                $reward['receiver_mem_no'],
                -$reward['final_points'],
                $reasonCd,
                Mileage::MODE_RECOMMEND,
                'referral_cancel',
                $orderNo,
                '추천 리워드 취소 (주문취소: ' . $orderNo . ')',
                false
            );

            // 상태 업데이트
            $updateQuery = 'UPDATE referral_rewards SET status = \'취소\' WHERE order_no = ? AND receiver_mem_no = ?';
            $this->db->query($updateQuery, [$orderNo, $reward['receiver_mem_no']]);
        }

        return true;
    }

    // ─────────────────────────────────────────
    // 유틸
    // ─────────────────────────────────────────

    /**
     * 추천 관계가 1년 이내인지 확인
     */
    private function isWithinValidPeriod($createdDt)
    {
        $createdTime = strtotime($createdDt);
        return (time() - $createdTime) <= self::REWARD_VALID_SECONDS;
    }

    /**
     * 직접 추천인(depth=1) 추출
     */
    private function getDirectReferrer($ancestors)
    {
        foreach ($ancestors as $ancestor) {
            if ((int)$ancestor['depth'] === 1) {
                return $ancestor['ancestor_mem_no'];
            }
        }
        return null;
    }
}
