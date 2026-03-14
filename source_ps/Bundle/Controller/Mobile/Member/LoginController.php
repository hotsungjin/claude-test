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

namespace Bundle\Controller\Mobile\Member;

use Bundle\Component\Attendance\Exception\AttendanceValidationException;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\Util\MemberUtil;
use Component\SiteLink\SiteLink;
use Bundle\Component\Apple\AppleLogin;
use Exception;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;

/**
 * Class LoginController
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class LoginController extends \Controller\Mobile\Controller
{
    private $message = '';
    const SECRET_KEY = '!#dbpassword'; // 로그인 계정 정보 암호화 처리 (암호키)

    public function index()
    {
        $request = \App::getInstance('request');
        $endpoint= parse_url($request->server()->get('REQUEST_URI'), PHP_URL_PATH);
        $userAgent = $request->server()->get('HTTP_USER_AGENT');
        $referer = $request->getReferer();

        $session = \App::getInstance('session');

        $myappBridge = \App::load(MyappBridge::class);
        $myappAuth = \App::load(MyappAuth::class);

        // 마이앱 로그인뷰 스크립트
        $myappBuilderInfo = gd_policy('myapp.config')['builder_auth'];
        $myappUseQuickLogin = gd_policy('myapp.config')['useQuickLogin'];
        if (\Request::isMyapp() && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false && !MemberUtil::isLogin() && $myappUseQuickLogin === 'true') {
            $myapp = \App::load('Component\\Myapp\\Myapp');
            echo $myapp->getAppBridgeScript('loginView', \Request::get()->get('goodsNo'), \Request::get()->get('guestOrder'));

            $this->js('parent.history.back()');
            exit;
        }

        /** @var \Bundle\Controller\Front\Member\LoginController $front */
        $front = \App::load('\\Controller\\Front\\Member\\LoginController');
        $front->index();

        if ($request->isFromMyapp()) {
            $referer = urldecode($front->getData('returnUrl'));
            \Logger::channel('myapp')->info("Myapp add loginView(NONE) returnUrl is : " . $referer);

            $loginViewType = 'NONE';
            $showGuestOrder = false;
            if (strpos($referer, '/order/order.php')) {
                $session = \App::getInstance('session');
                if (!$session->has('guest')) {
                    $showGuestOrder = true;
                }

                if (!empty($queryParams)) {
                    $referer .= '&myappGuestOrder=y';
                } else {
                    $referer .= '?myappGuestOrder=y';
                }
            }

            \Logger::channel('myapp')->info("Myapp add loginView(NONE) bridge script and referer : " . $referer);
            $isQuickLoginEnabled = MyappUtil::isQuickLoginEnabled();
            \Logger::channel('myapp')->info("Myapp LoginController add loginView isQuickLoginEnabled: " . ($isQuickLoginEnabled ? 'true' : 'false'));
            $skipBack = !$isQuickLoginEnabled; // 퀵 로그인 사용 안함인 경우, history.back() 스킵 처리
            $loginViewScript = $myappBridge->loginView($referer, $loginViewType, $showGuestOrder, $skipBack);

            if ($isQuickLoginEnabled) {
                $this->js($loginViewScript);
                exit;
            }

            echo $myappBridge->wrapScriptTag($loginViewScript);
        }

        $this->setData($front->getData());
        $scripts = ['gd_payco.js'];
        if ($front->getData('useFacebookLogin') === true) {
            $scripts[] = 'gd_sns.js';
        }
        if ($front->getData('useNaverLogin') === true) {
            $scripts[] = 'gd_naver.js';
        }
        if ($front->getData('useKakaoLogin') === true) {
            $scripts[] = 'gd_kakao.js';
        }
        if ($front->getData('useWonderLogin') === true) {
            $scripts[] = 'gd_wonder.js';
        }
        if ($front->getData('useGoogleLogin') === true) {
            $scripts[] = 'gd_google.js';
        }

        $this->addScript($scripts);

        // 로그인 계정 정보 암호화 처리 (보안 이슈)
        $this->setData('secretKey', md5(self::SECRET_KEY));
        $this->addScript(['../../../../crypto-js/pbkdf2.js', '../../../../crypto-js/aes.js', '../../../../crypto-js/sha512.js']);

        if (MemberUtil::isLogin()) {
            try {
                \DB::begin_tran();
                $check = new AttendanceCheckLogin();
                $message = $check->attendanceLogin();
                \DB::commit();
                if ($message) {
                    $this->js('alert(\'' . $message . '\');parent.location.href=\'' . URI_MOBILE . '\';');
                }
            } catch (AttendanceValidationException $e) {
                \DB::rollback();
                \Logger::warning(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
            } catch (Exception $e) {
                \DB::rollback();
                \Logger::info(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
            }

            \Logger::info(__METHOD__ . ', isLogin attendance message=[' . $this->message . ']');
            if ($this->message != '') {
                $this->js('alert(\'' . $this->message . '\');location.href=\'' . URI_MOBILE . '\'');
            } else {
                $this->redirect(URI_MOBILE);
            }
        }
    }
}
