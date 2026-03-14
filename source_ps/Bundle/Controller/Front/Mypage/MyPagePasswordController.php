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

namespace Bundle\Controller\Front\Mypage;


use Bundle\Component\Policy\AppleLoginPolicy;
use Component\Apple\AppleLogin;
use Component\Facebook\Facebook;
use Component\Godo\GodoWonderServerApi;
use Component\Member\Member;
use Component\Member\MyPage;
use Component\Member\Util\MemberUtil;
use Component\Policy\SnsLoginPolicy;
use Framework\Utility\ArrayUtils;
use Origin\Enum\Oauth\SnsConnectionRequestModeType;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappUtil;

/**
 * Class MyPagePasswordController
 * @package Bundle\Controller\Front\Mypage
 * @author  yjwee
 */
class MyPagePasswordController extends \Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');
        $scripts = ['gd_payco.js'];
        $scripts[] = 'gd_naver.js';
        $scripts[] = 'gd_kakao.js';
        $scripts[] = 'gd_google.js';

        $memberUtil = \App::load('Component\\Member\\Util\\MemberUtil');
        $memberUtil::logoutPayco();
        $memberUtil::logoutNaver();
        $memberUtil::logoutKakao();
        $memberUtil::logoutWonder();
        $memberUtil::logoutGoogle();
        $session->del(AppleLogin::SESSION_APPLE_HACK);
        $facebook = \App::load('Component\\Facebook\\Facebook');
        $facebook->clearSession();

        $session->del(MyPage::SESSION_MY_PAGE_PASSWORD);
        $messages['info'] = __('회원님의 정보를 안전하게 보호하기 위해 비밀번호를 다시 한번 확인해 주세요.');

        $joinField = MemberUtil::getJoinField();
        $memberService = new Member();
        $member = $memberService->getMyPagePassword($session->get(Member::SESSION_MEMBER_LOGIN . '.memNo'));

        $usePayco = $member['snsTypeFl'] == 'payco';
        $useNaver = $member['snsTypeFl'] == 'naver';
        $useFacebook = $member['snsTypeFl'] == SnsLoginPolicy::FACEBOOK;
        $useKakao = $member['snsTypeFl'] == 'kakao';
        $useWonder = $member['snsTypeFl'] == 'wonder';
        $useApple = $member['snsTypeFl'] == 'apple';
        $useGoogle = ($member['snsTypeFl'] == 'google') || ((empty($member['snsTypeFl']) && (substr($member['memId'], -7) === '_google') && (strlen($member['memId']) === 28)));
        if ($usePayco || $useNaver || $useFacebook || $useKakao || $useWonder || $useApple || $useGoogle) {
            $messages['info'] = __('회원님의 정보를 안전하게 보호하기 위해 계정을 %s재인증%s 해주세요.', '<span class="c-red">', '</span>');
        }
        if ($useFacebook) {
            $scripts[] = 'gd_sns.js';
            $facebook = new Facebook();
            if (\Component\Policy\SnsLoginPolicy::getInstance()->useGodoAppId()) {
                $facebookReAuthenticationUrl = $facebook->getGodoReAuthenticationUrl();
            } else {
                $facebookReAuthenticationUrl =$facebook->getReAuthenticationUrl();
            }

            $request = \App::getInstance('request');
            $userAgent = $request->server()->get('HTTP_USER_AGENT');
            if ($request->isFromMyapp() && MyappUtil::isAndroid($userAgent)) {
                $facebookReAuthenticationUrl = MyappUtil::getCustomTabsUrl('facebook', 're-authentication');
            }

            $this->setData('facebookReAuthenticationUrl', $facebookReAuthenticationUrl);
        }

        if ($useWonder) {
            $wonder = new GodoWonderServerApi();
            $scripts[] = 'gd_wonder.js';
            $this->setData('wonderReturnUrl', $wonder->getAuthUrl('login', 'my_page_password'));
        }

        // apple login use check
        if ($useApple === true) {
            $appleLoginPolicy = new AppleLoginPolicy();
            if ($appleLoginPolicy->useAppleLogin() === true) {
                $this->setData('useAppleLogin', $appleLoginPolicy->useAppleLogin());
                $this->setData('client_id', $appleLoginPolicy->getClientId());
                $this->setData('redirectURI', $appleLoginPolicy->getRedirectURI());

                // 버튼 상태값
                if (\Request::get()->get('type') == 'hack_out') {
                    $this->setData('state', 'hack_out');
                } else {
                    $this->setData('state', 'change_password');
                }
            }
        }

        if ($useGoogle === true) {
            $googleReturnUrl = "/mypage/my_page.php";
            $request = \App::getInstance('request');
            if ($request->isFromMyapp()) {
                $tokenData = ['googleType' => $request->get()->get('type', SnsConnectionRequestModeType::MY_PAGE_PASSWORD)];
                $token = MyappUtil::encryptCustomTabToken($tokenData);
                $googleReturnUrl = MyappUtil::getCustomTabUrl('google', MyappAuth::CUSTOM_TAB_RE_AUTH_ACTIVE, $token);
            }
            $this->setData('googleReturnUrl', $googleReturnUrl);
        }

        ArrayUtils::unsetDiff($joinField, ['memPw']);
        ArrayUtils::unsetDiff($member, ['memId']);

        $this->setData('joinField', $joinField);
        $this->setData('data', $member);
        $this->setData('usePayco', $usePayco);
        $this->setData('useNaver', $useNaver);
        $this->setData('useFacebook', $useFacebook);
        $this->setData('useKakao', $useKakao);
        $this->setData('useWonder', $useWonder);
        $this->setData('useGoogle', $useGoogle);
        $this->setData('messages', $messages);
        $this->setData('naverType', gd_isset(\Request::get()->get('type'), SnsConnectionRequestModeType::MY_PAGE_PASSWORD));
        $this->setData('kakaoType', gd_isset(\Request::get()->get('type'), SnsConnectionRequestModeType::MY_PAGE_PASSWORD));
        $this->setData('wonderType', gd_isset(\Request::get()->get('type'), SnsConnectionRequestModeType::MY_PAGE_PASSWORD));
        $this->setData('googleType', gd_isset(\Request::get()->get('type'), SnsConnectionRequestModeType::MY_PAGE_PASSWORD));
        $this->addScript($scripts);

        //        debug(\Session::get('member'));
    }
}
