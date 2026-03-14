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

namespace Bundle\Controller\Mobile\Member\Myapp;

use Bundle\Component\Apple\AppleLogin;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\Util\MemberUtil;
use Component\SiteLink\SiteLink;
use Bundle\Component\Policy\SnsLoginPolicy;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertOnlyException;
use Bundle\Component\Member\MemberSnsService;
use Exception;
use Session;

/**
 * Class LoginController
 * @package Bundle\Controller\Mobile\Member
 * @author  Jongchan Na
 */
class MyappLoginController extends \Controller\Mobile\Controller
{
    /**
     * index
     *
     */
    public function index()
    {
        $request = \App::getInstance('request');
        $myapplogger = \App::getInstance('logger')->channel('myapp');
        $myapp = \App::load('Component\\Myapp\\Myapp');
        $myappInfo = gd_policy('myapp.config');
        if ($request->isMyapp() && empty($myappInfo['builder_auth']['clientId']) === false && empty($myappInfo['builder_auth']['secretKey']) === false) {
            $myappLogin = true;
        } else {
            $myappLogin = false;
        }
        try {
            if (empty($request->post()->get('encryptLoginId')) == false) {
                $request->post()->set('loginId', $request->post()->get('encryptLoginId'));
            }
            if (empty($request->post()->get('encryptLoginPwd')) == false) {
                $request->post()->set('loginPwd', $request->post()->get('encryptLoginPwd'));
            }
            if (MemberUtil::isLogin()) {
                MemberUtil::logoutWithCookie();
            }
            if ($myappLogin === false) {
                throw new Exception("올바른 로그인 경로가 아닙니다.");
            }

            // 페이스북 로그인 처리
            if($request->get()->get('socialLogin') == 'facebook') {
                // 페이스북 로그인 url
                $facebookLoginPolicy = new SnsLoginPolicy();
                $facebookLoginUseFl = $facebookLoginPolicy->getSnsLoginUse()['facebook'];

                if ($facebookLoginUseFl == 'y') {
                    $snsLoginPolicy = new SnsLoginPolicy();

                    $facebook = \App::load('Bundle\\Component\\Facebook\\Facebook');
                    $useFacebook = $snsLoginPolicy->useFacebook();

                    if ($request->request()->has('returnUrl')) {
                        $returnUrl = $request->getReturnUrl();
                    } else {
                        $returnUrl = urlencode(URI_MOBILE);
                    }

                    if ($useFacebook) {
                        if ($snsLoginPolicy->useGodoAppId()) {
                            $facebookLogin = $facebook->getGodoLoginUrl($returnUrl);
                        } else {
                            $facebookLogin = $facebook->getLoginUrl($returnUrl);
                        }
                        throw new AlertRedirectException(null, 456, null, $facebookLogin);
                    }
                }
            }

            // 애플 자동 로그인 처리
            if ($request->get()->get('socialLogin') == 'apple') {
                $memberSnsService = new MemberSnsService();
                $appleLogin = new AppleLogin();

                $idToken = $request->get()->get('id_token');
                if (!$idToken) {
                    throw new AlertRedirectException("토큰이 없습니다.\n다시 로그인해주세요.", $myapp::APP_LOGIN_ERROR_CODE, null, '/');
                }

                $userInfo = $appleLogin->decryptIdToken($idToken);
                if (!$userInfo) {
                    throw new AlertRedirectException("토큰이 잘못되었습니다.\n다시 로그인해주세요.", $myapp::APP_LOGIN_ERROR_CODE, null, '/');
                }

                $client_id = $request->get()->get('client_id');
                $memberData = $memberSnsService->getMemberSnsByUUID($userInfo['sub']);
                if ($appleLogin->validateRefreshToken($memberData['refreshToken'], $client_id) === false) {
                    throw new AlertRedirectException("자동 로그인이 해제되었습니다.\n다시 로그인해주세요.", $myapp::APP_LOGIN_ERROR_CODE, null, '/');
                }

                $memberSnsService->loginBySns($memberData['uuid']);

                $saveAutoLogin = $request->get()->get('saveAutoLogin');
                if ($saveAutoLogin == 'y') {
                    // 마이앱 bridge 전달
                    \Cookie::set($appleLogin::MYAPP_APPLE_AUTO_LOGIN_FLAG, $saveAutoLogin);
                    Session::set($appleLogin::MYAPP_APPLE_USER_ID_TOKEN, $idToken);
                }

                // 로그인 성공
                throw new AlertRedirectException(null, 300, null, '/');
            }

            // 로그인 jwt 처리
            $serverAuthCode = $request->post()->get('code');
            if ($serverAuthCode) {
                $memberInfo = $myapp->checkAuthCode($serverAuthCode);

                if (empty($memberInfo) === false && $memberInfo['error'] == null) {
                    $request->post()->set('loginId', $memberInfo['loginId']);
                    $request->post()->set('loginPwd', $memberInfo['loginPwd']);
                } elseif ($memberInfo['error'] != null) {
                    throw new AlertRedirectException($memberInfo['error'], $myapp::APP_LOGIN_ERROR_CODE, null, '/');
                }
                else {
                    throw new Exception("일시적 서버에러입니다.\n잠시후 다시 시도해주세요.");
                }
            }

            // 마이앱 솔루션 자동로그인
            if ($request->post()->get('saveAutoLogin') == 'y' && $myappLogin === true && $myappInfo['useQuickLogin'] != 'true') {
                $myapp = \App::load('Component\\Myapp\\Myapp');
                $authcode = $myapp->getAuthCode();
                $refresh = $myapp->getRefreshCode($authcode);
                if (!$refresh) {
                    throw new AlertRedirectException("로그인 토큰이 만료되었습니다.\n다시 시도해주세요.", $myapp::APP_LOGIN_ERROR_CODE, null, '/');
                }
                \Session::set($myapp::LOGIN_REFRESH_TOKEN, $refresh);
                if ($serverAuthCode) {
                    $request->post()->set('saveAutoLogin', 'n');
                }
            }
            \Cookie::set($myapp::MYAPP_SOLUTION_LOGIN_FLAG, $serverAuthCode);

            $front = \App::load('\\Controller\\Front\\Member\\LoginPsController');
            $front->index();

        } catch (AlertRedirectException $e) {
            $myapplogger->error("redirect", [$e]);
            if($e->getCode() == 456) {
                throw new AlertRedirectException($e->getMessage(), null, null, $facebookLogin);
            } else if($e->getCode() == $myapp::APP_LOGIN_ERROR_CODE) {
                $bridge = $myapp->getAppBridgeScript('initLoginInfo');
                echo $bridge;
                throw new AlertRedirectException($e->getMessage(), $e->getCode(), $e, '../' . $e->getUrl(), 'parent');
            } else {
                throw new AlertRedirectException($e->getMessage(), $e->getCode(), $e, $e->getUrl(), 'parent');
            }
        } catch (Exception $e) {
            $myapplogger->error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
            if($e->getCode() == 456) {
                throw new AlertRedirectException($e->getMessage(), null, null, $facebookLogin);
            } else if($e->getCode() == $myapp::APP_LOGIN_ERROR_CODE) {
                $bridge = $myapp->getAppBridgeScript('initLoginInfo');
                echo $bridge;
                throw new AlertRedirectException($e->getMessage(), null, null, '/');
            } else {
                throw new AlertOnlyException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }
}