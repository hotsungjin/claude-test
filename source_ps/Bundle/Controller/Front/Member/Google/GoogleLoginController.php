<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Front\Member\Google;

use Bundle\Component\Attendance\Exception\AttendanceGlobalMallException;
use Bundle\Component\Attendance\Exception\AttendanceValidationException;
use Bundle\Component\Member\Member;
use Bundle\Component\Member\Util\MemberUtil;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\MemberSnsDAO;
use Component\Member\MemberSnsService;
use Component\Member\MyPage;
use Exception;
use Framework\Debug\Exception\AlertCloseException;
use Framework\Debug\Exception\AlertRedirectCloseException;
use Framework\Debug\Exception\AlertRedirectException;
use Origin\Exception\Member\MissingMemberDataException;
use Origin\Enum\Oauth\SnsConnectionRequestModeType;
use Origin\Exception\Oauth\GoogleLoginException;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;
use Origin\Service\Oauth\Google\GoogleLoginService;

/**
 * 구글 로그인 및 회원가입
 * @package Bundle\Controller\Front\Member\Google
 */

class GoogleLoginController extends \Controller\Front\Controller
{
    const MY_PAGE_PASSWORD_PAGE_URL = 'mypage/my_page_password.php';
    const MYAPP_SCHEME_REQUIRED_GOOGLE_TYPES = [
        SnsConnectionRequestModeType::JOIN,
        SnsConnectionRequestModeType::LOGIN,
    ];

    public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $logger = \App::getInstance('logger');
        $logger->channel('googleLogin')->info(sprintf('start controller: %s', __METHOD__));

        try {
            $googleLoginService = \App::getInstance(GoogleLoginService::class);
            $memberSnsService = new MemberSnsService();

            $getParams = $request->get()->toArray();
            $sessionData = $session->get(GoogleLoginService::SESSION_GOOGLE_LOGIN);

            // googleType 체크
            switch ($request->getMethod()) {
                case 'POST':
                    $postVal = $request->post()->get('googleType');
                    $googleTypeVal = $postVal !== 'undefined' ? $postVal : null;
                    break;
                case 'GET':
                default:
                    $getVal = $request->get()->get('googleType');
                    $googleTypeVal = $getVal !== 'undefined' ? $getVal : null;
                    break;
            }
            if (empty($googleTypeVal)) {
                $googleTypeVal = $sessionData['googleType'];
            }
            $googleType = SnsConnectionRequestModeType::isValid($googleTypeVal) ? $googleTypeVal : null;

            if (is_null($googleType)) {
                $logger->channel('googleLogin')->info('googleType is null, inputs [cache, post, get]: ', [$sessionData['googleType'], $request->post()->get('googleType'), $request->get()->get('googleType')]);
                throw new GoogleLoginException(__('잘못된 접근입니다.'));
            }

            // (마이앱) 구글 회원 가입, 로그인 - 커스텀 탭에서 Scheme 설정
            $scheme = $request->get()->get('pkgName');
            if (in_array($googleType, self::MYAPP_SCHEME_REQUIRED_GOOGLE_TYPES) && !empty($scheme)) {
                $session->set(MyappAuth::MYAPP_SCHEME, $scheme);
            }

            // 되돌아갈 URL
            $isMobile = $request->isMobile();
            if (empty($sessionData)) {
                $homeUrl = ($isMobile ? URI_MOBILE : URI_HOME);
            } else {
                $sessionStateCode = $sessionData[GoogleLoginService::GOOGLE_STATE_CODE];
                $globalMallPath = $sessionData['mallName'] ? $sessionData['mallName'] . DS : '';
                $homeUrl = ($isMobile ? URI_MOBILE : URI_HOME) . $globalMallPath;
            }

            // 연동 팝업 사용자 취소
            if ($getParams['error'] === 'access_denied') {
                $logger->channel('googleLogin')->info('로그인 정보가 없거나 잘못된 접속입니다. (사용자 취소)');
                $isGoogleJoinType = $googleType === SnsConnectionRequestModeType::JOIN;
                $this->handleGoogleLoginErrorOnMyapp('', $isGoogleJoinType ? 'member/join.php' : self::MY_PAGE_PASSWORD_PAGE_URL);
                $returnUrl = $isGoogleJoinType ?
                    $homeUrl . 'member/join_method.php' :
                    $homeUrl . 'mypage/my_page.php';
                throw new AlertRedirectCloseException(__('로그인 정보가 없거나 잘못된 접속입니다.'), null, null, $returnUrl, 'opener');
            }

            if (empty($getParams['code'])) {
                // 최초 연동 팝업
                $googleReturnUrl = rawurldecode($request->post()->get('returnUrl', $homeUrl));
                $googleReturnUrl = $googleReturnUrl !== 'undefined' ? $googleReturnUrl : $homeUrl;

                $session->set(GoogleLoginService::SESSION_GOOGLE_RETURN_URL, $googleReturnUrl);
                $getCodeURL = $googleLoginService->getLoginUrl($googleType);

                $logger->channel('googleLogin')->info('Code URI is: ', [$getCodeURL]);
                $logger->channel('googleLogin')->info('with returnUrl: ', [$googleReturnUrl]);

                $this->redirect($getCodeURL);
            } else {
                // 콜백
                if ($getParams['state']) {
                    $googleStateCode = json_decode(base64_decode($getParams['state']), true);
                }
                $logger->channel('googleLogin')->info('googleStateCode, sessionStateCode, googleType, isMobile : ', [$googleStateCode, $sessionStateCode, $googleType, $isMobile]);

                $googleReturnUrl = $session->get(GoogleLoginService::SESSION_GOOGLE_RETURN_URL, $homeUrl);  // 구글 창 닫히면 되돌아갈 url
                $googleReturnUrl = $googleReturnUrl !== 'undefined' ? $googleReturnUrl : $homeUrl;
                $session->del(GoogleLoginService::SESSION_GOOGLE_RETURN_URL);
                $logger->channel('googleLogin')->info('googleReturnUrl: ', [$googleReturnUrl]);

                // CSRF 체크
                if ($googleStateCode !== $sessionStateCode) {
                    \Logger::channel('googleLogin')->error('stateCode 가 다릅니다.', [$googleStateCode, $sessionStateCode]);
                    throw new \Exception('stateCode 가 다릅니다.');
                }

                // 토큰 정보
                $token = $googleLoginService->getToken($getParams['code']);

                // 사용자 정보
                $userInfo = $googleLoginService->getUserInfo($token['access_token']);
                $logger->channel('googleLogin')->info('getUserInfo: ', [$userInfo]);

                // 세션에 사용자 정보 저장
                $session->set(GoogleLoginService::SESSION_ACCESS_TOKEN, $token);
                $session->set(GoogleLoginService::SESSION_USER_PROFILE, $userInfo);

                $memberSns = $memberSnsService->getMemberSnsByUUID($userInfo['id']);

                // SNS 아이디로 회원가입한 회원인지 검증
                if ($memberSnsService->validateMemberSns($memberSns)) {
                    $logger->channel('googleLogin')->info('pass validationMemberSns, memNo/uuid: ', [$memberSns['memNo'], $memberSns['uuid']]);

                    if ($session->has(SESSION_GLOBAL_MALL)) {
                        $mallBySession = $session->get(SESSION_GLOBAL_MALL);
                        $logger->channel('googleLogin')->info(sprintf('has session %s', \Component\Member\Member::SESSION_MEMBER_LOGIN));
                        if ($memberSns['mallSno'] != $mallBySession['sno']) {
                            $logger->info(sprintf('member join mall number[%s], mall session sno[%d]', $memberSns['mallSno'], $mallBySession['sno']));
                            $this->redirectWithAlert(__('회원을 찾을 수 없습니다.'), $homeUrl . "main/index.php");
                        }
                    }

                    if ($session->has(Member::SESSION_MEMBER_LOGIN)) {
                        if ($memberSns['memNo'] != $session->get(Member::SESSION_MEMBER_LOGIN . '.memNo', 0)) {
                            $logger->channel('googleLogin')->info('different inform, userProfile: ', [$memberSns['memNo'], $session->get(Member::SESSION_MEMBER_LOGIN . '.memNo', 0)]);
                            $message = '다른 구글 연동 정보가 존재합니다. ' . __('로그인 시 인증한 정보와 다릅니다.');
                            $redirectPath = self::MY_PAGE_PASSWORD_PAGE_URL;
                            $this->handleGoogleLoginErrorOnMyapp($message, $redirectPath);
                            $this->redirectWithAlert(
                                $message,
                                $homeUrl . $redirectPath
                            );
                        }

                        if ($googleType === SnsConnectionRequestModeType::CONNECT) {
                            $logger->channel('googleLogin')->info('Deny app link');
                            $message = __('이미 다른 회원정보와 연결된 계정입니다. 다른 계정을 이용해주세요.');
                            $this->handleGoogleLoginErrorOnMyapp($message, self::MY_PAGE_PASSWORD_PAGE_URL);
                            $this->redirectWithAlert(
                                $message,
                                $homeUrl . "mypage/my_page.php"
                            );
                        }

                        // 마이페이지 회원정보 수정시 인증 정보 다를때 처리
                        $sessionMemNo = $session->get(Member::SESSION_MEMBER_LOGIN . '.memNo', 0);
                        if ($memberSns['memNo'] != $sessionMemNo) {
                            $logger->channel('googleLogin')->info('not equal memNo', [$memberSns['memNo'], $sessionMemNo]);
                            $message = __('로그인 시 인증한 정보와 다릅니다 .');
                            $redirectPath = self::MY_PAGE_PASSWORD_PAGE_URL;
                            $this->handleGoogleLoginErrorOnMyapp($message, $redirectPath);
                            $this->redirectWithAlert(
                                $message,
                                $homeUrl . $redirectPath
                            );
                        }

                        // 마이페이지 회원정보 수정시 인증
                        if ($googleType === SnsConnectionRequestModeType::MY_PAGE_PASSWORD) {
                            // 마이앱 CustomTab - 마이페이지 비밀번호 인증인 경우,
                            if ($session->has(MyappAuth::MYAPP_SCHEME)) {
                                $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
                                $provider = 'google';
                                $tokenData = [
                                    'id' => $userInfo['id'],
                                    'accessToken' => $token['access_token'],
                                    'refreshToken' => $token['refresh_token'],
                                ];
                                $token = MyappUtil::encryptCustomTabToken($tokenData);
                                $nextUrl = MyappUtil::getCustomTabUrl($provider, MyappAuth::CUSTOM_TAB_MY_PAGE_PASSWORD, $token);
                                $params = [
                                    'next' => $nextUrl
                                ];
                                $logger->channel('myapp')->info("Myapp scheme google auth scheme params: " . json_encode($params));
                                MemberUtil::logoutWithCookie();
                                $session->del(MyappAuth::MYAPP_SCHEME);
                                $myappBridge = \App::load(MyappBridge::class);
                                $this->js($myappBridge->schemeAuth($scheme, $provider, $params));
                                exit;
                            }

                            $memberSnsService->saveToken($userInfo['id'], $token['access_token'], $token['refresh_token']);
                            $logger->channel('googleLogin')->info('move my page, id: ', [$userInfo['id']]);
                            $session->set(MyPage::SESSION_MY_PAGE_PASSWORD, true);
                            $this->redirectWithAlertLayerCheck(null, $homeUrl . "mypage/my_page.php");
                        }

                        // 회원탈퇴
                        if ($googleType === SnsConnectionRequestModeType::HACK_OUT) {
                            // 마이앱 CustomTab - 마이페이지 회원 탈퇴인 경우,
                            if ($session->has(MyappAuth::MYAPP_SCHEME)) {
                                $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
                                $provider = 'google';
                                $nextUrl = MyappUtil::getCustomTabUrl($provider, MyappAuth::CUSTOM_TAB_HACK_OUT);
                                $params = [
                                    'next' => $nextUrl
                                ];
                                $logger->channel('myapp')->info("Myapp scheme google auth scheme params: " . json_encode($params));
                                MemberUtil::logoutWithCookie();
                                $session->del(MyappAuth::MYAPP_SCHEME);
                                $myappBridge = \App::load(MyappBridge::class);
                                $this->js($myappBridge->schemeAuth($scheme, $provider, $params));
                                exit;
                            }

                            $logger->channel('googleLogin')->info('hack out google id: ', [$userInfo['id']]);
                            $session->set(GoogleLoginService::SESSION_GOOGLE_HACK, true);
                            $this->redirectWithAlert(null, $homeUrl . "mypage/hack_out.php");
                        }

                        // 일반회원 마이페이지 구글 아이디 연동 해제
                        if ($googleType === SnsConnectionRequestModeType::DISCONNECT) {
                            if ($memberSns['snsJoinFl'] == 'y') {
                                $logger->channel('googleLogin')->info('Impossible disconnect member joined by google, id: ', [$userInfo['id']]);
                                $this->js("alert('" . __('구글로 가입한 회원님은 연결을 해제 할 수 없습니다.') . "');");
                            }
                            if ($session->has(GoogleLoginService::SESSION_ACCESS_TOKEN)) {
                                $googleToken = $session->get(GoogleLoginService::SESSION_ACCESS_TOKEN);
                                $logger->info('Has google access token: ', [$googleToken['access_token']]);
                                $logger->debug('token data:', [$token]);

                                // accessToken, refreshToken 토큰 폐기
                                $googleLoginService->revokeToken($googleToken['access_token']);
                                $googleLoginService->revokeToken($googleToken['refresh_token']);

                                $session->del(GoogleLoginService::SESSION_ACCESS_TOKEN);
                                $memberSnsService = new MemberSnsService();
                                $memberSnsService->disconnectSns($memberSns['memNo']);
                                $session->set(Member::SESSION_MEMBER_LOGIN . '.snsTypeFl', '');
                                $session->set(Member::SESSION_MEMBER_LOGIN . '.accessToken', '');
                                $session->set(Member::SESSION_MEMBER_LOGIN . '.snsJoinFl', '');
                                $session->set(Member::SESSION_MEMBER_LOGIN . '.connectFl', '');
                                $this->redirectWithAlert(
                                    __('구글 연결이 해제되었습니다.'),
                                    $homeUrl . "mypage/my_page.php"
                                );
                            } else {
                                $logger->info('Disconnect google fail. not found disconnect information');
                                $this->redirectWithAlert(
                                    __('구글 아이디 로그인 연결해제에 필요한 정보를 찾을 수 없습니다.'),
                                    $homeUrl . self::MY_PAGE_PASSWORD_PAGE_URL
                                );
                            }
                        }
                    }
                    if (isset($memberSns['accessToken'])) {
                        $logger->channel('googleLogin')->info('isset accessToken.. logout..');
                        $googleLoginService->logout($memberSns['accessToken']);
                        $logger->channel('googleLogin')->info('..success logout!');
                    }

                    // 구글 아이디 로그인
                    $memberSnsService->saveToken($userInfo['id'], $token['access_token'], $token['refresh_token']);
                    $memberSnsService->loginBySns($userInfo['id']);
                    $logger->channel('googleLogin')->info('success login by google, id: ', [$userInfo['id']]);

                    $db = \App::getInstance('DB');
                    try {
                        if ($session->has(SESSION_GLOBAL_MALL)) {
                            throw new AttendanceGlobalMallException('해외몰 입니다.' . $sessionData['mallName']);
                        }
                        $db->begin_tran();
                        $check = new AttendanceCheckLogin();
                        $message = $check->attendanceLogin();
                        $db->commit();

                        $logger->channel('googleLogin')->info('commit attendance login');
                        if ($message) {
                            $logger->channel('googleLogin')->info('has attendance message: ', [$message]);
                            $this->redirectWithAlertLayerCheck($message, $googleReturnUrl);
                        }
                    } catch (AttendanceGlobalMallException $e) {
                        $logger->channel('googleLogin')->info(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
                    } catch (AttendanceValidationException $e) {
                        $db->rollback();
                        $logger->channel('googleLogin')->warning(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
                    } catch (Exception $e) {
                        $db->rollback();
                        $logger->channel('googleLogin')->error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
                    }

                    if ($googleType === SnsConnectionRequestModeType::JOIN) {
                        $logger->channel('googleLogin')->info('already join member, move return url: ', [$googleReturnUrl]);
                        $this->redirectWithAlertLayerCheck(
                            __('이미 가입한 회원입니다.'),
                            $googleReturnUrl
                        );
                    }
                    $logger->channel('googleLogin')->info('move return url: ', [$googleReturnUrl]);
                    $this->redirectWithAlertLayerCheck(null, $googleReturnUrl);
                }

                // 일반회원 구글 아이디 연동 처리
                if ($googleType === SnsConnectionRequestModeType::CONNECT) {
                    if ($googleLoginService->hasError()) {
                        throw new GoogleLoginException($request->get()->get('error_description'));
                    }

                    $memberSnsService->connectSns($session->get(Member::SESSION_MEMBER_LOGIN . '.memNo'), $userInfo['id'], $token['access_token'], 'google');
                    $memberSnsService->saveToken($userInfo['id'], $token['access_token'], $token['refresh_token']);
                    $session->set(Member::SESSION_MEMBER_LOGIN . '.snsTypeFl', 'google');
                    $session->set(Member::SESSION_MEMBER_LOGIN . '.accessToken', $token['access_token']);
                    $session->set(Member::SESSION_MEMBER_LOGIN . '.snsJoinFl', 'n');
                    $session->set(Member::SESSION_MEMBER_LOGIN . '.connectFl', 'y');
                    $this->redirectWithAlert(
                        __('계정 연결이 완료되었습니다. 로그인 시 연결된 계정으로 로그인 하실 수 있습니다.'),
                        $homeUrl . "mypage/my_page.php"
                    );
                }

                $this->redirectConfirm(
                    __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?'),
                    $homeUrl . "member/join_agreement.php",
                    $homeUrl . "main/index.php"
                );
            }

        } catch (AlertRedirectException $e) {
            $logger->channel('googleLogin')->error($e->getTraceAsString());
            MemberUtil::logout();
            $this->handleGoogleLoginErrorOnMyapp($e->getMessage(), $e->getUrl());
            throw $e;
        } catch (AlertRedirectCloseException $e) {
            $logger->channel('googleLogin')->error($e->getTraceAsString());
            MemberUtil::logout();
            $this->handleGoogleLoginErrorOnMyapp($e->getMessage(), $e->getUrl());
            throw $e;
        } catch (MissingMemberDataException $e) {
            // 올바르지 않은 정보 제거
            $memberSnsService->removeSnsMissingMember($memberSns['memNo'], $userInfo['id']);
            $this->redirectConfirm(
                __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?'),
                $homeUrl . "member/join_agreement.php",
                $homeUrl . "main/index.php"
            );
        } catch (\Throwable $e) {
            $logger->channel('googleLogin')->error($e->getTraceAsString());
            MemberUtil::logout();
            $this->handleGoogleLoginErrorOnMyapp($e->getMessage(), '/member/login.php');
            if ($request->isMobile()) {
                throw new AlertRedirectException($e->getMessage(), $e->getCode(), $e, '../../member/login.php', 'parent');
            } else {
                throw new AlertCloseException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * alert 리다이렉션
     *
     * @param string|null $message 출력 alert 메시지
     * @param string $url URL
     * @param bool $isPopup 팝업 창 여부
     * @return void
     */
    private function redirectWithAlertLayerCheck($message, string $url)
    {
        $js = "
            if (typeof(window.top.layerSearchArea) == 'object') {
                parent.location.href='" . $url . "';
            } else if (window.opener === null) {
                location.href='" . $url . "';
            } else {
                opener.location.href='" . $url . "';
                self.close();
            }
        ";

        if ($message) {
            $js = "alert('" . $message . "');" . $js;
        }

        $this->js($js);
    }

    /**
     * 리다이렉션 처리
     *
     * @param string|null $message 출력 alert 메시지
     * @param string $url 리다이렉션할 URL
     * @return void
     */
    private function redirectWithAlert($message, string $url)
    {
        $js = "
            if (window.opener === null) {
                location.href='" . $url . "';
            } else {
                opener.location.href='" . $url . "';
                self.close();
            }
        ";

        if ($message) {
            $js = "alert('" . $message . "');" . $js;
        }

        $this->js($js);
    }

    /**
     * confirm 리다이렉션
     *
     * @param string $message 확인 대화상자에 표시할 메시지
     * @param string $confirmUrl 확인 시 리다이렉션할 URL
     * @param string $cancelUrl 취소 시 리다이렉션할 URL
     * @return void
     */
    private function redirectConfirm(string $message, string $confirmUrl, string $cancelUrl)
    {
        $js = "
            if (typeof(window.top.layerSearchArea) == 'object') {
                if (confirm('" . $message . "')) {
                    parent.location.href = '" . $confirmUrl . "';
                } else {
                    parent.location.reload();
                }
            } else if (window.opener === null) {
                if (confirm('" . $message . "')) {
                    location.href = '" . $confirmUrl . "';
                } else {
                    location.href='" . $cancelUrl . "';
                }
            } else {
                if (confirm('" . $message . "')) {
                    opener.location.href = '" . $confirmUrl . "';
                } else {
                    opener.location.href = '" . $cancelUrl . "';
                }
                self.close();
            }
        ";
        $this->js($js);
    }

    private function handleGoogleLoginErrorOnMyapp(string $message = '', string $nextUrl = '')
    {
        $session = \App::getInstance('session');
        if ($session->has(MyappAuth::MYAPP_SCHEME)) {
            $myappBridge = \App::load(MyappBridge::class);
            $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
            if (!empty($nextUrl)) {
                if (strpos($nextUrl, '../') !== false) {
                    $nextUrl = preg_replace('#(\.\./)+#', '', $nextUrl);
                }
                $nextUrl = urlencode(URI_MOBILE . ltrim($nextUrl));
            }
            MemberUtil::logoutWithCookie();
            $this->js($myappBridge->schemeCloseCustomTabs($scheme, 'google', $message, $nextUrl));
            $session->del(MyappAuth::MYAPP_SCHEME);
            exit;
        }
    }
}
