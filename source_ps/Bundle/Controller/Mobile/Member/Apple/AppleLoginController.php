<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Controller\Mobile\Member\Apple;


use Bundle\Component\Apple\AppleLogin;
use Bundle\Component\Member\MyPage;
use Bundle\Component\Member\Util\MemberUtil;
use Framework\Debug\Exception\AlertRedirectCloseException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertOnlyException;
use Exception;
use Bundle\Component\Member\Member;
use Bundle\Component\Member\MemberSnsService;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Session;


/**
 * Class AppleLoginController
 * @package Bundle\Controller\Mobile\Member\Myapp
 * @author <sirume92@godo.co.kr>
 */
class AppleLoginController extends \Controller\Mobile\Controller
{
    /**
     * index
     */
    public function index()
    {
        $request = \App::getInstance('request');
        $logger = \App::getInstance('logger')->channel('appleLogin');

        $functionName = 'popup';
        if (gd_is_skin_division()) {
            $functionName = 'gd_popup';
        }

        $memberSnsService = new MemberSnsService();
        $appleLogin = new AppleLogin();
        try {
            if ($request->post()->get('error')) {
                throw new AlertRedirectException(__('로그인이 취소되었습니다.'), null, null, '../login.php');
            }

            # web: post , myapp: get
            $authorizationCode = $request->post()->get('code') ? $request->post()->get('code') : $request->get()->get('code');
            $state = $request->post()->get('state') ? $request->post()->get('state') : $request->get()->get('state');
            $client_id = $request->get()->get('client_id'); // ios 13이상 옵션

            # 마이앱 자동로그인 옵션
            $saveAutoLogin = $request->get()->get('saveAutoLogin');
            if ($saveAutoLogin == 'y') {
                \Cookie::set($appleLogin::MYAPP_APPLE_AUTO_LOGIN_FLAG, $saveAutoLogin);
            }

            if ($state != 'disconnect') {
                /** $tokenArray
                 * "access_token": "xxxxxxxxxxxxxxx",
                 * "token_type": "Bearer",
                 * "expires_in": 3600,
                 * "refresh_token": "yyyyyyyyyyyy",
                 * "id_token": "zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz"
                 */
                $tokenArray = $appleLogin->getAccessToken($authorizationCode, $client_id);
                Session::set(AppleLogin::SESSION_ACCESS_TOKEN, $tokenArray);

                /** $userInfo
                 * [iss] => https://appleid.apple.com
                 * [aud] => applesignintest.myapp.godo.com
                 * [exp] => 1596679196
                 * [iat] => 1596678596
                 * [sub] => xxxxxx.yyyyyyyyyy.zzzz
                 * [at_hash] => Y_XNxkTzes-W6EusuJDHLQ
                 * [email] => abc@def.com
                 * [email_verified] => true
                 * [auth_time] => 1596678594
                 * [nonce_supported] => 1
                 */
                $userInfo = $tokenArray['user_info'];
                $uuid = $userInfo['uuid'];
                unset($tokenArray['user_info']);

                $logger->info('success login with apple');

                $snsMemberData = $memberSnsService->getMemberSnsByUUID($uuid);

                $user = $request->post()->get('user') ? $request->post()->get('user') : $request->get()->get('user');
                if($user) {
                    $user= json_decode($user, true);
                    Session::set(AppleLogin::SESSION_USER_NAME, $user['name']['lastName'].$user['name']['firstName']);
                }

                // 로그인
                if ($state == 'sign_in') {
                    $isMember = $memberSnsService->validateMemberSns($snsMemberData);
                    $loginReturnURL = MemberUtil::getLoginReturnURL();

                    if (strpos($loginReturnURL, 'appleid') > -1) {
                        $loginReturnURL = URI_MOBILE;
                    }

                    // 회원가입 안된 경우 처리
                    if (!$isMember) {
                        Session::set(AppleLogin::SESSION_USER_PROFILE, $userInfo);

                        $js = "
                        if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                            if (confirm('" . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . "')) {
                                location.href = '../join_agreement.php';
                            } else {
                                location.href='" . $loginReturnURL . "';
                            }
                        } else {
                            if(confirm('" . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . "')) {
                                window.opener.location.href='../join_agreement.php';
                            }
                            self.close();
                        }
                        ";
                        $this->js($js);

                        // 로그인 처리
                    } else {
                        $memberSnsService->saveToken($uuid, $tokenArray['access_token'], $tokenArray['refresh_token']);
                        $memberSnsService->loginBySns($uuid);
                        $this->bridgeScriptIfMyapp($loginReturnURL);

                        // 마이앱 bridge 전달
                        if ($request->isMyapp()) {
                            Session::set($appleLogin::MYAPP_APPLE_USER_ID_TOKEN, $tokenArray['id_token']);
                        }

                        $js = "location.href='" . $loginReturnURL . "';";
                        $this->js($js);
                    }

                    // 회원가입
                } else if ($state == 'sign_up') {
                    $isMember = $memberSnsService->validateMemberSns($snsMemberData);

                    // 회원가입 안된 경우 처리
                    if (!$isMember) {
                        Session::set(AppleLogin::SESSION_USER_PROFILE, $userInfo);

                        $js = "
                        if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                            location.href = '../join_agreement.php';
                        } else {
                            window.opener.location.href='../join_agreement.php';
                            self.close();
                        }
                        ";
                        $this->js($js);

                        // 이미 회원가입 한 경우 처리
                    } else {
                        $loginReturnURL = MemberUtil::getLoginReturnURL();

                        if (strpos($loginReturnURL, 'appleid') > -1) {
                            $loginReturnURL = URI_MOBILE;
                        }

                        $memberSnsService->saveToken($uuid, $tokenArray['access_token'], $tokenArray['refresh_token']);
                        $memberSnsService->loginBySns($uuid);
                        $this->bridgeScriptIfMyapp($loginReturnURL);

                        // 마이앱 bridge 전달
                        if ($request->isMyapp()) {
                            Session::set($appleLogin::MYAPP_APPLE_USER_ID_TOKEN, $tokenArray['id_token']);
                        }

                        $js = "
                        alert('" . __('이미 가입한 회원입니다.') . "');
                        location.href='" . $loginReturnURL . "';
                        ";
                        $this->js($js);
                    }

                    // 회원 정보 수정
                } else if ($request->post()->get('state') == 'change_password') {
                    Session::set(MyPage::SESSION_MY_PAGE_PASSWORD, true);
                    $returnURL = URI_MOBILE . 'mypage/my_page.php';
                    $memberSnsService->saveToken($uuid, $tokenArray['access_token'], $tokenArray['refresh_token']);
                    $memberSnsService->loginBySns($uuid);
                    $this->bridgeScriptIfMyapp($returnURL);
                    $js = "location.href='" . $returnURL . "';";
                    $this->js($js);

                    // 회원 탈퇴
                } else if ($request->post()->get('state') == 'hack_out') {
                    Session::set(AppleLogin::SESSION_APPLE_HACK, true);
                    $returnURL = URI_MOBILE . 'mypage/hack_out.php';
                    $js = "location.href='" . $returnURL . "';";
                    $this->js($js);

                    // 회원 연동
                } else if ($state == 'connect') {
                    // 기존 연동 여부 체크
                    if ($memberSnsService->hasSnsMember($uuid)) {
                        $js = "
                        alert('" . __('이미 다른 회원정보와 연결된 계정입니다. 다른 계정을 이용해주세요.') . "');
                        if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                            location.href='../../mypage/my_page.php';
                        } else {
                            self.close();
                        }
                        ";
                        $this->js($js);
                    } else {
                        $memberSnsService->connectSns(Session::get(Member::SESSION_MEMBER_LOGIN . '.memNo'), $uuid, $tokenArray['access_token'], 'apple');
                        $js = "
                        alert('" . __('계정 연결이 완료되었습니다. 로그인 시 연결된 계정으로 로그인 하실 수 있습니다.') . "');
                        if (window.opener === null || Object.keys(window.opener).indexOf('" . $functionName . "') < 0) {
                            location.href='../../mypage/my_page.php';
                        } else {
                            opener.location.href='../../mypage/my_page.php';
                            self.close();
                        }
                        ";
                        $this->js($js);
                    }
                }
            }

            // if state == disconnect
            else {
                // 아이디 연동해제 ajax 처리
                $snsMemberData = Session::get(Member::SESSION_MEMBER_LOGIN);
                if ($snsMemberData['snsJoinFl'] == 'y') {
                    $logger->info('Impossible disconnect member joined by Apple');
                    $this->json(['error' => 'apple', 'message' => __('애플로 가입한 회원님은 연결을 해제 할 수 없습니다.'),]);
                } else if (Session::has(AppleLogin::SESSION_ACCESS_TOKEN)) {
                    Session::del(AppleLogin::SESSION_ACCESS_TOKEN);
                    $memberSnsService->disconnectSns($snsMemberData['memNo']);
                    Session::set(Member::SESSION_MEMBER_LOGIN . '.snsTypeFl', '');
                    Session::set(Member::SESSION_MEMBER_LOGIN . '.accessToken', '');
                    Session::set(Member::SESSION_MEMBER_LOGIN . '.snsJoinFl', '');
                    Session::set(Member::SESSION_MEMBER_LOGIN . '.connectFl', '');

                    $this->json(['message' => __('애플 연결이 해제되었습니다.'), 'url' => '../mypage/my_page.php',]);
                } else {
                    $this->json(['error' => 'apple', 'message' => __('애플 연결해제에 필요한 정보를 찾을 수 없습니다.'), 'url' => '../mypage/my_page_password.php',]);
                }
            }
        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (AlertRedirectCloseException $e) {  // 휴면회원 처
            throw new AlertRedirectException($e->getMessage(), $e->getCode(), null, $e->getUrl());
        } catch (Exception $e) {
            throw new AlertOnlyException($e->getMessage(), $e->getCode(), $e);
        }
        $this->redirect(URI_MOBILE);
        return;
    }

    private function bridgeScriptIfMyapp($returnURL)
    {
        $request = \App::getInstance('request');
        if ($request->isFromMyapp()) {
            $snsProvider = 'apple';
            $session = \App::getInstance('session');
            $memberData = $session->get(Member::SESSION_MEMBER_LOGIN);

            $myappAuth = \App::load(MyappAuth::class);
            $solutionAuthenticationKey = $myappAuth->createAuthenticationKey($memberData);

            $myappBridge = \App::load(MyappBridge::class);
            $this->js($myappBridge->login($memberData, $solutionAuthenticationKey, $request->getReferer(), $returnURL, $snsProvider));
            exit;
        }
    }
}
