<?php
/**
 * 설성목장 추천 리워드 시스템
 * 관리자 대시보드 컨트롤러
 */

namespace Bundle\Controller\Admin\Referral;

use Bundle\Component\Referral\ReferralDAO;
use Bundle\Component\Referral\ReferralService;

class IndexController extends \Controller\Admin\Controller
{
    public function index()
    {
        $dao = ReferralDAO::getInstance();

        // 핵심 지표
        $summary = [
            'total_members'           => $dao->getTotalMemberCount(),
            'new_members_this_month'  => $dao->getThisMonthNewMemberCount(),
            'reward_this_month'       => $dao->getThisMonthTotalReward(),
            'reward_last_month'       => $dao->getLastMonthTotalReward(),
        ];

        // 리워드 비율 계산 (이번달 주문 매출 대비)
        $thisMonthSales = $this->getThisMonthSales();
        $summary['reward_ratio'] = $thisMonthSales > 0
            ? round(($summary['reward_this_month'] / $thisMonthSales) * 100, 2)
            : 0;
        $summary['total_sales'] = $thisMonthSales;

        // 등급 분포
        $gradeRows = $dao->getGradeDistribution();
        $gradeDistribution = [];
        foreach ($gradeRows as $row) {
            $gradeDistribution[$row['grade']] = (int)$row['cnt'];
        }

        // 상위 추천인
        $topReferrers = $dao->getTopReferrers(10);

        // 최근 리워드 내역
        $recentRewards = $dao->getRecentRewards(20);

        // 어뷰징 의심 회원
        $abuseAlerts = $dao->getSuspiciousMembers();

        $this->setData('summary', $summary);
        $this->setData('gradeDistribution', $gradeDistribution);
        $this->setData('topReferrers', $topReferrers);
        $this->setData('recentRewards', $recentRewards);
        $this->setData('abuseAlerts', $abuseAlerts);
    }

    /**
     * 이번 달 주문 매출 합계 조회
     */
    private function getThisMonthSales()
    {
        $query = "SELECT COALESCE(SUM(settlePrice), 0) AS total
                  FROM gd_order
                  WHERE DATE_FORMAT(regDt, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                    AND orderStatus NOT IN ('f', 'c')";
        $result = $this->db->query_fetch($query, [], false);
        return (int)($result['total'] ?? 0);
    }
}
