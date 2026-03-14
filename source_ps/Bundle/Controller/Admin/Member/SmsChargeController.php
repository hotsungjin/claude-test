<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Controller\Admin\Member;

use Bundle\Component\Sms\SmsUtil;
use Component\Member\Manager;
use Component\Sms\SmsAdmin;
use Component\Sms\Sms;
use Core\Base\Interceptor\AdminCertification;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\ConfigUrlUtils;

/**
 * SMS 포인트 충전
 *
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class SmsChargeController extends \Controller\Admin\Controller
{

    /**
     * index
     *
     */
    public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $globals = \App::getInstance('globals');
        // --- 메뉴 설정
        $this->callMenu('member', 'sms', 'charge');
        if ($request->get()->get('popupMode', '') === 'yes') {
            $this->getView()->setDefine('layout', 'layout_blank.php');
        } elseif ($session->has(Manager::SESSION_TEMP_MANAGER)
            && $session->has(AdminCertification::SESSION_TEMP_CERTIFICATION)) {
            if (strpos($request->getReferer(), '/base/login') === false) {
                $session->del(Manager::SESSION_TEMP_MANAGER);
                $session->del(AdminCertification::SESSION_TEMP_CERTIFICATION);
                throw new AlertRedirectException('잘못된 경로로 접근되었습니다.', 403, null, '/base/login.php', 'top');
            }
            $this->getView()->setDefine('layout', 'layout_layer.php');
            $this->setData('guestCharge', true);
        }

        $nowSmsPoint = (float) Sms::getPoint();
        $availableCount = [
            'sms' => SmsUtil::getAvailableCount('sms', $nowSmsPoint),
            'lms' => SmsUtil::getAvailableCount('lms', $nowSmsPoint),
            'kakaoAlrimTalk' => SmsUtil::getAvailableCount('kakaoAlrimTalk', $nowSmsPoint),
            'kakaoFriendTalk' => SmsUtil::getAvailableCount('kakaoFriendTalk', $nowSmsPoint),
        ];

        // SMS 포인트 Sync
        Sms::saveSmsPoint();
        $smsAdmin = \App::load(SmsAdmin::class);
        $this->setData('lmsPoint', Sms::LMS_POINT);
        $this->setData('kakaoPoint', Sms::KAKAO_POINT);
        $this->setData('kakaoFriendTalkPoint', Sms::KAKAO_FRIEND_TALK_POINT);
        $this->setData('smsPriceList', $smsAdmin->getSmsPriceList());
        $this->setData('shopSno', $globals->get('gLicense.godosno'));
        $this->setData('availableCount', $availableCount);
        $this->setData('commerceShopMainUrl', ConfigUrlUtils::getNhnCommerceMyGodoUrl('shop-main'));
    }
}
