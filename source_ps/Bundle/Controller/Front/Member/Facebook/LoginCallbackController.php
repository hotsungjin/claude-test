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

namespace Bundle\Controller\Front\Member\Facebook;

use Bundle\Component\Attendance\Exception\AttendanceValidationException;
use Component\Member\Member;
use Component\Attendance\AttendanceCheckLogin;
use Component\Facebook\Facebook;
use Component\Member\Util\MemberUtil;
use Facebook\Exceptions\FacebookSDKException;
use Framework\Debug\Exception\AlertCloseException;
use Framework\Debug\Exception\AlertRedirectCloseException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\UrlUtils;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\MyApp\MyappBridge;
use Origin\Service\MyApp\MyappUtil;


/**
 * Class LoginCallbackController
 * @package Bundle\Controller\Front\Member\Facebook
 * @author  yjwee
 */
class LoginCallbackController extends CallbackController
{
    public function index()
    {
        $logger = \App::getInstance('logger');
        $request = \App::getInstance('request');
        $facebook = \App::load('Component\\Facebook\\Facebook');
        $loginReturnURL = $facebook->getReturnUrl();
        StringUtils::strIsSet($loginReturnURL, MemberUtil::getLoginReturnURL());
        $logger->info(sprintf('Login return Url is %s', $loginReturnURL));
        try {
            parent::index();
            $session = \App::getInstance('session');
            /** @var \Facebook\Authentication\AccessTokenMetadata $tokenMetadata */
            $tokenMetadata = $session->get(Facebook::SESSION_METADATA, []);

            $memberSnsService = \App::load('Component\\Member\\MemberSnsService');
            $memberSnsService->setThirdPartyAppType($this->snsPolicy::FACEBOOK);
            $memberSns = $memberSnsService->getMemberSnsByUUID($tokenMetadata->getUserId());
            if ($memberSnsService->validateMemberSns($memberSns) === false) {
                // 성인인증에서의 로그인
                $accessPolicy = ComponentUtils::getPolicy('member.access');
                $useFrontAdultIntro = ($request->isMobile() === false) && ($accessPolicy['introFrontUseFl'] == 'y') && ($accessPolicy['introFrontAccess'] == 'adult');
                $useMobileAdultIntro = $request->isMobile() && ($accessPolicy['introMobileUseFl'] == 'y') && ($accessPolicy['introMobileAccess'] == 'adult');
                if ((StringUtils::contains($request->getReferer(), 'intro/adult') || $useFrontAdultIntro || $useMobileAdultIntro) && !$session->has(SESSION_GLOBAL_MALL)) {
                    $logger->info(sprintf('Referer has intro/adult. member information not registered. use front adult [%s], use mobile adult [%s]', $useFrontAdultIntro, $useMobileAdultIntro));
                    $js = 'if (opener) {' . PHP_EOL;
                    $js .= 'if(confirm(\'' . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . '\')) {window.opener.location.href=\'../../member/join_agreement.php\';} self.close();' . PHP_EOL;
                    $js .= '} else {' . PHP_EOL;
                    $js .= 'if (confirm(\'' . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . '\')) {' . PHP_EOL;
                    $js .= 'location.href = \'../../member/join_agreement.php\';' . PHP_EOL;
                    $js .= '} else {' . PHP_EOL;
                    $js .= 'location.href=\'' . UrlUtils::appendSubDomain('/intro/adult.php') . '\';' . PHP_EOL;
                    $js .= '}' . PHP_EOL;
                    $js .= '}' . PHP_EOL;
                    $this->js($js);
                }
                // 내정보수정에서의 로그인
                if (StringUtils::contains($request->getReferer(), 'mypage/my_page_password')) {
                    $logger->info('Referer has mypage/my_page_password. different from the login session.');
                    $js = 'if (opener) {' . PHP_EOL;
                    $js .= 'opener.alert(\'' . __('가입된 계정이 아닙니다. 가입하신 계정으로 재인증 진행해주세요.') . '\');self.close();' . PHP_EOL;
                    $js .= '} else {' . PHP_EOL;
                    $js .= 'alert(\'' . __('가입된 계정이 아닙니다. 가입하신 계정으로 재인증 진행해주세요.') . '\');location.href=\'' . $request->getReferer() . '\';' . PHP_EOL;
                    $js .= '}' . PHP_EOL;
                    $this->js($js);
                }
                $logger->info('Member information is not registered.');
                $js = 'if (opener) {' . PHP_EOL;
                $js .= 'if(confirm(\'' . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . '\')) {window.opener.location.href=\'../join_agreement.php\';} self.close();' . PHP_EOL;
                $js .= '} else {' . PHP_EOL;
                $js .= 'if (confirm(\'' . __('가입되지 않은 회원정보입니다. 회원가입을 진행하시겠습니까?') . '\')) {' . PHP_EOL;
                $js .= 'location.href = \'../join_agreement.php\';' . PHP_EOL;
                $js .= '} else {' . PHP_EOL;
                $js .= 'location.href = \'../login.php\';' . PHP_EOL;
                $js .= '}' . PHP_EOL;
                $js .= '}' . PHP_EOL;
                $this->js($js);
            }

            // 회원이 있는 경우
            $memberSnsService->saveToken($tokenMetadata->getUserId(), $session->get(Facebook::SESSION_ACCESS_TOKEN), '');
            $memberSnsService->loginBySns($tokenMetadata->getUserId());

            $request = \App::getInstance('request');
            if ($request->isFromMyapp()) {
                $snsProvider = 'facebook';
                $session = \App::getInstance('session');
                $memberData = $session->get(Member::SESSION_MEMBER_LOGIN);

                $myappAuth = \App::load(MyappAuth::class);
                $solutionAuthenticationKey = $myappAuth->createAuthenticationKey($memberData);

                $myappBridge = \App::load(MyappBridge::class);
                $this->js($myappBridge->login($memberData, $solutionAuthenticationKey, $request->getReferer(), $loginReturnURL, $snsProvider));
                exit;
            }

            try {
                \DB::begin_tran();
                $check = new AttendanceCheckLogin();
                $message = $check->attendanceLogin();
                \DB::commit();

                if ($message) {
                    $logger->info('Attendance check completed');
                    $js = '<!--facebook login callback attendance login result-->' . PHP_EOL;
                    $js .= 'if (opener) {' . PHP_EOL;
                    $js .= 'opener.alert(\'' . $message . '\');' . PHP_EOL;
                    $js .= 'opener.location.href=\'' . $loginReturnURL . '\';self.close();' . PHP_EOL;
                    $js .= '} else {' . PHP_EOL;
                    $js .= 'alert(\'' . $message . '\');' . PHP_EOL;
                    $js .= 'location.href=\'' . $loginReturnURL . '\';' . PHP_EOL;
                    $js .= '}' . PHP_EOL;
                    $this->js($js);
                }
            } catch (AttendanceValidationException $e) {
                \DB::rollback();
                $logger->warning($e->getMessage(), [__METHOD__, $e->getTraceAsString()]);
            } catch (\Throwable $e) {
                \DB::rollback();
                $logger->error($e->getTraceAsString());
            }

            $logger->info('Authentication has been successfully completed.');

            $js = 'if (opener) {' . PHP_EOL;
            $js .= 'opener.location.href=\'' . $loginReturnURL . '\';self.close();' . PHP_EOL;
            $js .= '} else {' . PHP_EOL;
            $js .= 'location.href=\'' . $loginReturnURL . '\';' . PHP_EOL;
            $js .= '}' . PHP_EOL;
            $this->js($js);
        } catch (\Component\Member\Exception\LoginLimitException $e) {
            $this->handleFacebookLoginErrorOnMyapp($e->getMessage());
            $e->throwException();
        } catch (FacebookSDKException $e) {
            $this->handleFacebookLoginErrorOnMyapp($e->getMessage());
            $logger->error($e->getTraceAsString());
            throw new AlertRedirectCloseException($e->getMessage(), $e->getCode(), $e, '../../member/login.php?returnUrl=' . $loginReturnURL, $request->isMobile() ? 'parent' : 'opener');
        } catch (AlertRedirectCloseException $e) {
            $this->handleFacebookLoginErrorOnMyapp($e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->handleFacebookLoginErrorOnMyapp($e->getMessage());

            $logger->error($e->getTraceAsString());
            if ($request->isMobile()) {
                $redirectUrl = '../../member/login.php';
                if ($request->isFromMyapp()) {
                    $error = $request->get()->get('error');
                    $errorReason = $request->get()->get('error_reason');
                    // 최초 로그인 및 회원 가입 시, 약관 페이지에서 취소 버튼 클릭 시 홈으로 이동 처리 (페이지 루프 이슈 개선)
                    if ($error == 'access_denied' && $errorReason == 'user_denied') {
                        $redirectUrl = URI_MOBILE;
                    }
                }
                throw new AlertRedirectException($e->getMessage(), $e->getCode(), $e, $redirectUrl, 'parent');
            } else {
                throw new AlertCloseException($e->getMessage(), $e->getCode(), $e, 'opener');
                    /*
                    if ($e->getTarget() == 'opener') {
                        throw $e;
                    } else {
                        throw new AlertCloseException($e->getMessage(), $e->getCode(), $e);
                    }
                    */
            }
        }
    }

    private function handleFacebookLoginErrorOnMyapp($message) {
        $session = \App::getInstance('session');
        $request = \App::getInstance('request');
        $userAgent = $request->server()->get('HTTP_USER_AGENT');
        if (MyappUtil::isAndroid($userAgent) && $session->has(MyappAuth::MYAPP_SCHEME)) {
            $myappBridge = \App::load(MyappBridge::class);
            $scheme = $session->get(MyappAuth::MYAPP_SCHEME);
            $this->js($myappBridge->schemeCloseCustomTabs($scheme, 'facebook', $message));
            $session->del(MyappAuth::MYAPP_SCHEME);
            exit;
        }
    }
}
