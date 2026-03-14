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
 * @link http://www.godo.co.kr
 */

namespace Bundle\Controller\Front\Member;

use App;
use Component\Member\Member;
use Component\Member\MemberSleep;
use Exception;
use Framework\Debug\Exception\AlertRedirectException;
use Origin\Enum\Oauth\SnsConnectionRequestModeType;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;
use Session;

/**
 * Class WakeController
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class WakeCompleteController extends \Controller\Front\Controller
{
    /**
     * @inheritdoc
     */
    public function index()
    {
        \Logger::info(__METHOD__);
        try {
            $wakeInfo = Session::get(MemberSleep::SESSION_WAKE_INFO);

            /** @var \Bundle\Component\Member\Member $member */
            $member = App::load('\\Component\\Member\\Member');
            $memberSns = App::load('\\Component\\Member\\MemberSnsService');
            $request = \App::getInstance('request');
            $request->get()->set('couponEventType', 'wake');
            $coupon = \App::load('\\Component\\Coupon\\Coupon');
            $wakeCoupon = $coupon->getMemberCouponList($wakeInfo['memNo']);
            $this->setData("wakeCoupon", $wakeCoupon['data']);
            $memberSnsData = $memberSns->getMemberSns($wakeInfo['memNo']);
            $loginFl = 'n';

            $userAgent = $request->server()->get('HTTP_USER_AGENT');
            $session = \App::getInstance('session');
            if ($request->isFromMyapp()) {
                \Logger::channel('myapp')->info("Myapp - [wake up] facebook auth start!");
                $myappBridge = \App::load(MyappBridge::class);
                $myappAuth = App::load(MyappAuth::class);

                $memberData = $myappAuth->loginByMemberNo($wakeInfo['memNo']);
                $solutionAuthenticationKey = $myappAuth->createAuthenticationKey($memberData);

                if ($memberSnsData['snsJoinFl'] != 'y') {
                    $snsProvider = '';
                } else {
                    $snsProvider = $memberSnsData['snsTypeFl'];
                }

                $this->js($myappBridge->login($memberData, $solutionAuthenticationKey, $request->getReferer(), URI_MOBILE, $snsProvider));
            } else if (MyappUtil::isAndroid($userAgent) && $memberSnsData['snsTypeFl'] == 'facebook' && $session->has(MyappAuth::MYAPP_SCHEME)) {
                $myappBridge = \App::load(MyappBridge::class);
                $myappAuth = App::load(MyappAuth::class);
                $memberData = $myappAuth->loginByMemberNo($wakeInfo['memNo']);
                $solutionAuthenticationKey = $myappAuth->createAuthenticationKey($memberData);

                $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
                $encryptor = \App::getInstance('encryptor');
                $tokenData = [
                    "memNo" => (int)$memberData['memNo'],
                    "next" => $session->get(MyappAuth::MYAPP_CUSTOM_TAB_LOGIN_NEXT_URL) ?? URI_MOBILE
                ];
                $authToken = base64_encode($encryptor->encrypt($tokenData));
                $nextUrl = MyappUtil::getMobileUri('/myapp/myapp_auth.php?customTabsAuthToken=' . $authToken);

                $bridge = $myappBridge->getLoginParams($memberData, $solutionAuthenticationKey, $request->getReferer(), '', 'facebook');
                $params = [
                    'bridge' => json_encode($bridge),
                    'next' => $nextUrl
                ];
                MemberUtil::logoutWithCookie();
                $session->del(MyappAuth::MYAPP_SCHEME);
                $this->js($myappBridge->schemeAuth($scheme, 'facebook', $params));
            } else if ($memberSnsData['snsTypeFl'] == 'google' && $session->has(MyappAuth::MYAPP_SCHEME)) {
                $provider = 'google';
                $myappBridge = \App::load(MyappBridge::class);
                $myappAuth = \App::load(MyappAuth::class);
                $memberData = $myappAuth->loginByMemberNo($wakeInfo['memNo']);
                $solutionAuthenticationKey = $myappAuth->createAuthenticationKey($memberData);

                $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
                $tokenData = [
                    "memNo" => (int)$memberData['memNo'],
                    "next" => $session->get(MyappAuth::MYAPP_CUSTOM_TAB_LOGIN_NEXT_URL) ?? URI_MOBILE
                ];
                $authToken = MyappUtil::encryptCustomTabToken($tokenData);
                $nextUrl = MyappUtil::getCustomTabUrl($provider, MyappAuth::CUSTOM_TAB_LOGIN, $authToken);

                $bridge = $myappBridge->getLoginParams($memberData, $solutionAuthenticationKey, $request->getReferer(), '', $provider);
                $params = [
                    'bridge' => json_encode($bridge),
                    'next' => $nextUrl
                ];
                MemberUtil::logoutWithCookie();
                $session->del(MyappAuth::MYAPP_SCHEME);
                $this->js($myappBridge->schemeAuth($scheme, $provider, $params));
            } else if ($memberSnsData['snsJoinFl'] != 'y') {
                if($wakeInfo['memPw']) { // 로그인 이후 휴면회원 해제할 경우에만 로그인 처리
                    $member->login($wakeInfo['memId'], $wakeInfo['memPw']);
                    $loginFl = 'y';
                }
            } else {
                $memberSns->loginBySns($memberSnsData['uuid']);
            }
            $this->setData("loginFl", $loginFl);
        } catch (Exception $e) {
            throw new AlertRedirectException($e->getMessage(), 0, null, '../member/login.php');
        } finally {
            Session::del(MemberSleep::SESSION_WAKE_INFO);
            Session::del(Member::SESSION_DREAM_SECURITY);
            Session::del(Member::SESSION_IPIN);
        }
    }
}
