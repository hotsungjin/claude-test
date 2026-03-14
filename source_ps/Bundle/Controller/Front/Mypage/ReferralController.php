<?php
/**
 * 설성목장 추천 리워드 시스템
 * 마이페이지 - 추천 현황 컨트롤러
 */

namespace Controller\Front\Mypage;

use Component\Referral\ReferralService;
use Component\Member\Util\MemberUtil;
use Framework\Debug\Exception\AlertRedirectException;

class ReferralController extends \Controller\Front\Controller
{
    public function index()
    {
        // 비로그인 차단
        if (!MemberUtil::isLogin()) {
            throw new AlertRedirectException(__('로그인이 필요합니다.'), 200, null, '../member/login.php');
        }

        // 고도몰 SESSION에서 memNo 조회
        $memberInfo = \SESSION::get('member');
        $memNo = $memberInfo['memNo'] ?? null;

        try {
            $referralService = ReferralService::getInstance();

            $stats = $referralService->getMemberStats($memNo);

            if (!$stats) {
                $referralService->registerNewMember($memNo);
                $stats = $referralService->getMemberStats($memNo);
            }

            if ($stats) {
                // 사용 가능 마일리지 조회
                $db = \App::load('DB');
                $mileageRow = $db->query_fetch(
                    'SELECT mileage FROM ' . DB_MEMBER . ' WHERE memNo = ?',
                    [$memNo],
                    false
                );
                $availableMileage = (int)($mileageRow['mileage'] ?? 0);

                // 네트워크 데이터 템플릿용 포맷
                $rates = [1 => '5%', 2 => '2%', 3 => '1%', 4 => '0.5%', 5 => '0.2%'];
                $network = [];
                for ($depth = 1; $depth <= 5; $depth++) {
                    $cnt = (int)($stats['network_by_depth'][$depth] ?? 0);
                    $network[] = [
                        'depth' => $depth,
                        'rate'  => $rates[$depth],
                        'cnt'   => $cnt,
                        'pct'   => $cnt > 0 ? min((int)(($cnt / 20) * 100), 100) : 0,
                    ];
                }

                // 마일스톤 진행률 사전 계산
                foreach ($stats['milestones'] as &$m) {
                    $m['progress_pct'] = $m['required'] > 0
                        ? min((int)(($m['progress'] / $m['required']) * 100), 100)
                        : 100;
                }
                unset($m);

                $this->setData('stats', $stats);
                $this->setData('available_mileage', $availableMileage);
                $this->setData('network', $network);
                $this->setData('milestones', $stats['milestones']);
            } else {
                $this->setData('stats', null);
            }

        } catch (\Throwable $e) {
            $this->setData('stats', null);
        }
    }
}
