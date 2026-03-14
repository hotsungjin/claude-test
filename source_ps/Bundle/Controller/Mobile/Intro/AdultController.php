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
use Bundle\Component\Policy\NaverLoginPolicy;
use Bundle\Component\Policy\KakaoLoginPolicy;
use Bundle\Component\Policy\WonderLoginPolicy;
use Bundle\Component\Policy\GoogleLoginPolicy;
use Component\Policy\SnsLoginPolicy;
use Framework\Debug\Exception\AlertOnlyException;
use Component\Member\Util\MemberUtil;
use Component\SiteLink\SiteLink;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;

/**
 * 인트로 - 성인
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class AdultController extends \Controller\Mobile\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');
        if ($request->isFromMyapp() && !MemberUtil::isLogin()) {
            $returnUrl = $request->get()->get('returnUrl', $request->getReferer());
            if (!empty($returnUrl)) {
                $returnUrl = rtrim(URI_MOBILE, '/') . $returnUrl;
            }
            $isQuickLoginEnabled = MyappUtil::isQuickLoginEnabled();
            \Logger::channel('myapp')->info("Myapp AdultController add loginView isQuickLoginEnabled: " . ($isQuickLoginEnabled ? 'true' : 'false'));

            $myappBridge = \App::load(MyappBridge::class);
            $loginViewScript = $myappBridge->loginView($returnUrl, 'ONLY_ADULT');

            if ($isQuickLoginEnabled) {
                $this->js($loginViewScript);
                exit;
            }

            echo $myappBridge->wrapScriptTag($loginViewScript);
        }

        // 마이앱 로그인뷰 스크립트
        $myappBuilderInfo = gd_policy('myapp.config')['builder_auth'];
        $myappUseQuickLogin = gd_policy('myapp.config')['useQuickLogin'];

        try {
            $scripts = ['gd_payco.js'];
            /** @var \Bundle\Controller\Front\Controller $front */
            $front = \App::load('\\Controller\\Front\\Intro\\AdultController');
            $front->index();
            if (\Session::has('member')) {
                // 회원의 성인인증 뷰 스크립트 출력
                if (\Request::isMyapp() && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false && \Session::get('appLoginFlag') != 'y') {
                    \Session::set('appLoginFlag', 'y');
                    $myapp = \App::load('Component\\Myapp\\Myapp');
                    echo $myapp->getAppBridgeScript('login');
                }

                $this->getView()->setPageName('intro/adult_member');
            } else {
                $this->getView()->setPageName('intro/adult_guest');
            }

            //기존스크립트추가되지 않는 현상 수정
            $snsLoginPolicy = new SnsLoginPolicy();
            $useFacebook = $snsLoginPolicy->useFacebook();
            if ($useFacebook) {
                $scripts[] = 'gd_sns.js';
            }

            $naverLoginPolcy = new NaverLoginPolicy();
            $useNaver = $naverLoginPolcy->useNaverLogin();
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
                $this->setData('apple_redirectURI', $appleLoginPolicy->getRedirectURI());
                $this->setData('state', 'sign_in');
            }

            $googlePolicy = new GoogleLoginPolicy();
            $useGoogleLogin = $googlePolicy->useGoogleLogin();
            if ($useGoogleLogin) {
                $scripts[] = 'gd_google.js';
                $this->setData('returnUrl', $returnUrl);
            }
            $this->setData('useGoogleLogin', $useGoogleLogin);

            $this->setData($front->getData());
            $this->setData(
                [
                    'script' => [
                        ['url' => DEPRECATED_PATH_MODULE_SCRIPT . 'jquery.number_only.js']
                        ,
                        ['url' => DEPRECATED_PATH_MODULE_SCRIPT . 'identification.js'],
                    ],
                ]
            );
            $this->addScript($scripts);

            $siteLink = new SiteLink();
            // 마이앱 로그인
            if (\Request::isMyapp()) {
                $this->setData('loginActionUrl', $siteLink->link('../member/myapp/myapp_login.php', 'ssl'));
            } else {
                $this->setData('loginActionUrl', $siteLink->link('../member/login_ps.php', 'ssl'));
            }

            if (\Request::isMyapp() && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false && !MemberUtil::isLogin() && $myappUseQuickLogin === 'true') {
                $myapp = \App::load('Component\\Myapp\\Myapp');
                $this->setData('isMyapp', \Request::isMyapp());
                echo $myapp->getAppBridgeScript('loginView');
            }

        } catch (\Exception $e) {
            \Logger::error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
            throw new AlertOnlyException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
