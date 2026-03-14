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
namespace Bundle\Controller\Mobile\Intro;

use Bundle\Component\Godo\GodoNaverServerApi;
use Bundle\Component\Godo\GodoWonderServerApi;
use Bundle\Component\Policy\AppleLoginPolicy;
use Bundle\Component\Policy\GoogleLoginPolicy;
use Bundle\Component\Policy\NaverLoginPolicy;
use Bundle\Component\Policy\KakaoLoginPolicy;
use Bundle\Component\Policy\WonderLoginPolicy;
use Bundle\Component\Member\Util\MemberUtil;
use Component\Facebook\Facebook;
//use Component\Member\Util\MemberUtil;
use Component\Policy\PaycoLoginPolicy;
use Component\Policy\SnsLoginPolicy;
use Framework\Debug\Exception\AlertOnlyException;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;

/**
 * 인트로 - 회원 전용
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class MemberController extends \Controller\Mobile\Controller
{
    /**
     * index
     */
    public function index()
    {
        $request = \App::getInstance('request');
        if ($request->isFromMyapp() && !MemberUtil::isLogin()) {

            $isQuickLoginEnabled = MyappUtil::isQuickLoginEnabled();
            \Logger::channel('myapp')->info("Myapp MemberController add loginView isQuickLoginEnabled: " . ($isQuickLoginEnabled ? 'true' : 'false'));

            $returnUrl = $request->getReferer();
            $myappBridge = \App::load(MyappBridge::class);
            $loginViewScript = $myappBridge->loginView($returnUrl, 'ONLY_MEMBER', false, true);

            if ($isQuickLoginEnabled) {
                $this->js($loginViewScript);
                exit;
            }

            echo $myappBridge->wrapScriptTag($loginViewScript);
        }

        // 마이앱 로그인뷰 스크립트
        $myappBuilderInfo = gd_policy('myapp.config')['builder_auth'];
        $myappUseQuickLogin = gd_policy('myapp.config')['useQuickLogin'];
        if (\Request::isMyapp() && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false && !MemberUtil::isLogin() && $myappUseQuickLogin === 'true') {
            $myapp = \App::load('Component\\Myapp\\Myapp');
            echo $myapp->getAppBridgeScript('loginView');
            exit;
        }

        $scripts = ['gd_payco.js'];
        try {
            $returnUrl = MemberUtil::getLoginReturnURL();
            $this->setData('returnUrl', $returnUrl);

            $paycoPolicy = new PaycoLoginPolicy();
            $this->setData('usePaycoLogin', $paycoPolicy->usePaycoLogin());

            $snsLoginPolicy = new SnsLoginPolicy();
            $useFacebook = $snsLoginPolicy->useFacebook();
            $this->setData('useFacebookLogin', $useFacebook);

            if ($useFacebook) {
                $facebook = new Facebook();
                $scripts[] = 'gd_sns.js';
                if ($snsLoginPolicy->useGodoAppId()) {
                    $facebookUrl = $facebook->getGodoLoginUrl($returnUrl);
                } else {
                    $facebookUrl = $facebook->getLoginUrl($returnUrl);
                }

                $userAgent = $request->server()->get('HTTP_USER_AGENT');
                if ($request->isFromMyapp() && MyappUtil::isAndroid($userAgent)) {
                    $tokenData = ['next' => $returnUrl];
                    $token = MyappUtil::encryptCustomTabToken($tokenData);
                    $facebookUrl = MyappUtil::getCustomTabUrl('facebook', MyappAuth::CUSTOM_TAB_LOGIN_ACTIVE, $token);
                }

                $this->setData('facebookUrl', $facebookUrl);
            }

            $naverLoginPolicy = new NaverLoginPolicy();
            $useNaver = $naverLoginPolicy->useNaverLogin();
            if($useNaver) {
                $naver = new GodoNaverServerApi();
                $scripts[] = 'gd_naver.js';
                $this->setData('naverUrl', $naver->getLoginURL());
            }
            $this->setData('useNaverLogin', $useNaver);

            $kakaoPolicy = \App::getInstance(KakaoLoginPolicy::class);
            $useKakao = $kakaoPolicy->useKakaoLogin();
            if($useKakao) {
                $scripts[] = 'gd_kakao.js';
            }
            $this->setData('useKakaoLogin', $useKakao);

            $wonderPolicy = new WonderLoginPolicy();
            $useWonderLogin = $wonderPolicy->useWonderLogin();
            if ($useWonderLogin) {
                $wonder = new GodoWonderServerApi();
                $scripts[] = 'gd_wonder.js';
                $this->setData('useWonderLogin', $useWonderLogin);
                $this->setData('wonderReturnUrl', $wonder->getAuthUrl('login'));
            }

            // apple login use check
            $appleLoginPolicy = new AppleLoginPolicy();
            if ($appleLoginPolicy->useAppleLogin() === true) {
                $this->setData('useAppleLogin', $appleLoginPolicy->useAppleLogin());
                $this->setData('client_id', $appleLoginPolicy->getClientId());
                $this->setData('redirectURI', $appleLoginPolicy->getRedirectURI());
                $this->setData('state', 'sign_in');
            }

            $googlePolicy = new GoogleLoginPolicy();
            $useGoogleLogin = $googlePolicy->useGoogleLogin();
            if ($useGoogleLogin) {
                $scripts[] = 'gd_google.js';

                $googleReturnUrl = urlencode($returnUrl);
                if ($request->isFromMyapp()) {
                    $tokenData = ['next' => $returnUrl];
                    $token = MyappUtil::encryptCustomTabToken($tokenData);
                    $googleReturnUrl = MyappUtil::getCustomTabUrl('google', MyappAuth::CUSTOM_TAB_LOGIN_ACTIVE, $token);
                }
                $this->setData('googleReturnUrl', $googleReturnUrl);
            }
            $this->setData('useGoogleLogin', $useGoogleLogin);

            // 마이앱 로그인 Url
            $loginActionUrl = '../member/login_ps.php';
            if (\Request::isMyapp()) {
                $loginActionUrl ='../member/myapp/myapp_login.php';
                $this->setData('returnUrl', '/');
            }
            $this->setData('loginActionUrl', $loginActionUrl);

        } catch (\Exception $e) {
            \Logger::error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
            throw new AlertOnlyException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $this->addScript($scripts);
        }
    }
}
