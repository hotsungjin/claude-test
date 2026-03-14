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

namespace Bundle\Controller\Front\Member;

use Bundle\Component\Apple\AppleLogin;
use Bundle\Component\Godo\GodoKakaoServerApi;
use Bundle\Component\Member\Exception\MemberValidationException;
use Bundle\Component\Policy\KakaoLoginPolicy;
use Component\Facebook\Facebook;
use Component\Godo\GodoPaycoServerApi;
use Component\Godo\GodoNaverServerApi;
use Component\Godo\GodoWonderServerApi;
use Component\Member\MemberSnsService;
use Component\Member\MemberValidation;
use Component\Member\Util\MemberUtil;
use Component\Coupon\Coupon;
use Component\Policy\SnsLoginPolicy;
use Component\SiteLink\SiteLink;
use Component\Storage\Storage;
use Framework\Debug\Exception\DatabaseException;
use Framework\Object\SimpleStorage;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\PlusShop\PlusShopWrapper;
use Framework\Http\Request;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Origin\Exception\Member\KakaoSync\KakaoSyncException;
use Origin\Service\Oauth\Google\GoogleLoginService;
use Origin\Service\Statistics\Analytics\Member\MemberJoinCompleteAnalyticsCollectorService;
use Origin\Service\Member\KakaoSync\KakaoSyncJoinService;
use Origin\Service\WebHook\MemberWebHookService;
use Validation;

/**
 * Class 프론트 회원 요청 처리 컨트롤러
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class MemberPsController extends \Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');
        $request = \App::getInstance('request');
        $logger = \App::getInstance('logger');
        try {
            if (($request->getReferer() == $request->getDomainUrl()) || empty($request->getReferer()) === true){
                $logger->error(__METHOD__ .' Access without referer');
                throw new Exception(__("요청을 찾을 수 없습니다."));
            }
            /** @var  \Bundle\Component\Member\Member $member */
            $member = \App::load('\\Component\\Member\\Member');

            // returnUrl 데이타 타입 체크
            if ($request->post()->has('returnUrl')) {
                try {
                    Validation::setExitType('throw');
                    Validation::defaultCheck(gd_isset($request->post()->get('returnUrl')), 'url');
                } catch (\Exception $e) {
                    $request->post()->set('returnUrl', $request->getReferer());
                }

                // 웹 치약점 개선사항
                $scheme = $request->getScheme() . '://';
                $getHost = $scheme . $request->getHost();
                $getReturnUrl = explode('returnUrl=', $request->getReferer());
                $getReturnUrl = urldecode($getReturnUrl[1]);
                if (strpos($getReturnUrl, '://') !== false && strpos($getReturnUrl, $getHost) === false) {
                    $request->post()->set('returnUrl', '../member/login.php');
                }
            }

            $returnUrl = urldecode($request->post()->get('returnUrl'));
            if (empty($returnUrl) || strpos($returnUrl, "member_ps") !== false) {
                $returnUrl = $request->getReferer();
            }

            $mode = $request->post()->get('mode', $request->get()->get('mode'));

            switch ($mode) {
                case 'guest':
                    MemberUtil::guest();
                    $this->redirect($returnUrl, null, 'top');
                    break;
                case 'guestOrder':
                    $order = \App::load('\\Component\\Order\\Order');

                    $orderNm = $request->post()->get('orderNm');
                    $orderNo = $request->post()->get('orderNo');
                    $aResult = $order->isGuestOrder($orderNo, $orderNm);
                    if ($aResult['result']) {
                        $orderNo = $aResult['orderNo'];
                        MemberUtil::guestOrder($orderNo, $orderNm);

                        // 마이앱 로그인뷰 스크립트
                        $myappBuilderInfo = gd_policy('myapp.config')['builder_auth'];
                        if (\Request::isMyapp() && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false) {
                            $this->redirect($returnUrl . '?orderNo='.$orderNo, null, 'top');
                            break;
                        }

                        $this->json(
                            [
                                'result'  => '0',
                                'message' => __('주문조회에 성공했습니다.'),
                                'orderNo' => $orderNo,
                            ]
                        );
                    } else {
                        $this->json(
                            [
                                'result'  => '-1',
                                'message' => __('주문자명과 주문번호가 일치하는 주문이 존재하지 않습니다. 다시 입력해 주세요. 주문번호와 비밀번호를 잊으신 경우, 고객센터로 문의하여 주시기 바랍니다.'),
                            ]
                        );
                    }
                    break;
                case 'adultGuest':
                    if (empty($returnUrl)) {
                        $returnUrl = $request->getReferer();
                    }
                    MemberUtil::adultGuest($request->post()->toArray());
                    $this->redirect($returnUrl, null, 'top');
                    break;
                case 'join':
                    $memberVO = null;
                    try {
                        if ($session->has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                            \Component\Member\Util\MemberUtil::saveJoinInfoBySession($request->post()->all());
                        }
                        $memberSnsService = \App::load('Component\\Member\\MemberSnsService');
                        \DB::begin_tran();
                        $session->set('isFront', 'y');
                        if ($session->has('pushJoin')) {
                            $request->post()->set('simpleJoinFl','push');
                        }
                        $memberVO = $member->join(
                            $this->getMemberForJoin($request)
                        );
                        $session->del('isFront');
                        if ($session->has(GodoPaycoServerApi::SESSION_USER_PROFILE)) {
                            $paycoToken = $session->get(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoPaycoServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoPaycoServerApi::SESSION_USER_PROFILE);
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $paycoToken['idNo'], $paycoToken['access_token'], 'payco');
                            $paycoApi = new GodoPaycoServerApi();
                            $paycoApi->logByJoin();
                        } elseif ($session->has(Facebook::SESSION_USER_PROFILE)) {
                            $userProfile = $session->get(Facebook::SESSION_USER_PROFILE);
                            $accessToken = $session->get(Facebook::SESSION_ACCESS_TOKEN);
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $accessToken, SnsLoginPolicy::FACEBOOK);
                        } elseif ($session->has(GodoNaverServerApi::SESSION_USER_PROFILE)) {
                            $naverToken = $session->get(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoNaverServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoNaverServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoNaverServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['id'], $naverToken['access_token'], 'naver');
                            $naverApi = new GodoNaverServerApi();
                            $naverApi->logByJoin();
                        } elseif ($session->has(GodoKakaoServerApi::SESSION_USER_PROFILE)) {
                            $kakaoToken = $session->get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                            $kakaoProfile = $session->get(GodoKakaoServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoKakaoServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $kakaoProfile['id'], $kakaoToken['access_token'], 'kakao');
                        } elseif ($session->has(GodoWonderServerApi::SESSION_USER_PROFILE)) {
                            $wonderToken = $session->get(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                            $userProfile = $session->get(GodoWonderServerApi::SESSION_USER_PROFILE);
                            $session->del(GodoWonderServerApi::SESSION_ACCESS_TOKEN);
                            $session->del(GodoWonderServerApi::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['mid'], $wonderToken['access_token'], 'wonder');
                            $wonderApi = new GodoWonderServerApi();
                            $wonderApi->logByJoin();
                        } elseif ($session->has(AppleLogin::SESSION_USER_PROFILE)) {
                            $userProfile = $session->get(AppleLogin::SESSION_USER_PROFILE);
                            $access_token = $session->get(AppleLogin::SESSION_ACCESS_TOKEN);
                            $session->del(AppleLogin::SESSION_USER_PROFILE);
                            $session->del(AppleLogin::SESSION_ACCESS_TOKEN);

                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $userProfile['uuid'], $access_token['access_token'], 'apple');
                        } elseif ($session->has(GoogleLoginService::SESSION_USER_PROFILE)) {
                            $googleToken = $session->get(GoogleLoginService::SESSION_ACCESS_TOKEN);
                            $googleProfile = $session->get(GoogleLoginService::SESSION_USER_PROFILE);
                            $session->del(GoogleLoginService::SESSION_ACCESS_TOKEN);
                            $session->del(GoogleLoginService::SESSION_USER_PROFILE);
                            $memberSnsService = new MemberSnsService();
                            $memberSnsService->joinBySns($memberVO->getMemNo(), $googleProfile['id'], $googleToken['access_token'], 'google');
                        }

                        \DB::commit();

                    } catch (\Throwable $e) {
                        \DB::rollback();

                        if (get_class($e) == Exception::class && $request->isAjax()) {
                            $this->json(['result' => 'fail', 'message' => $e->getMessage()]);
                        } elseif (get_class($e) == Exception::class) {
                            if ($e->getMessage()) {
                                $this->js("alert('".$e->getMessage()."');
                                    // 회원가입 버튼 활성화 처리
                                    try {
                                        window.parent.callback_not_disabled();
                                    } catch (err) { // cors 오류 발생 시
                                        window.parent.postMessage('callback_not_disabled', '*');
                                    }");
                            }
                        } else {
                            throw $e;
                        }
                    }
                    if($session->get('ps_event')) {
                        PlusShopWrapper::event($session->get('ps_event'),['memNo'=>$memberVO->getMemNo()]);
                    }
                    if ($memberVO != null) {
                        $smsAutoConfig = ComponentUtils::getPolicy('sms.smsAuto');
                        $kakaoAutoConfig = ComponentUtils::getPolicy('kakaoAlrim.kakaoAuto');
                        $kakaoLunaAutoConfig = ComponentUtils::getPolicy('kakaoAlrimLuna.kakaoAuto');
                        $kakaoCloudAutoConfig = ComponentUtils::getPolicy('kakaoAlrimCloud.kakaoAuto');
                        if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true && $kakaoLunaAutoConfig['useFlag'] == 'y' && $kakaoLunaAutoConfig['memberUseFlag'] == 'y') {
                            $smsDisapproval = $kakaoLunaAutoConfig['member']['JOIN']['smsDisapproval'];
                        } elseif ($kakaoCloudAutoConfig['useFlag'] == 'y' && $kakaoCloudAutoConfig['memberUseFlag'] == 'y') {
                            $smsDisapproval = $kakaoCloudAutoConfig['member']['JOIN']['smsDisapproval'];
                        } elseif ($kakaoAutoConfig['useFlag'] == 'y' && $kakaoAutoConfig['memberUseFlag'] == 'y') {
                            $smsDisapproval = $kakaoAutoConfig['member']['JOIN']['smsDisapproval'];
                        } else {
                            $smsDisapproval = $smsAutoConfig['member']['JOIN']['smsDisapproval'];
                        }
                        StringUtils::strIsSet($smsDisapproval, 'n');
                        $sendSmsJoin = ($memberVO->getAppFl() == 'n' && $smsDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                        $mailAutoConfig = ComponentUtils::getPolicy('mail.configAuto');
                        $mailDisapproval = $mailAutoConfig['join']['join']['mailDisapproval'];
                        StringUtils::strIsSet($smsDisapproval, 'n');
                        $sendMailJoin = ($memberVO->getAppFl() == 'n' && $mailDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                        if ($sendSmsJoin) {
                            /** @var \Bundle\Component\Sms\SmsAuto $smsAuto */
                            $smsAuto = \App::load('\\Component\\Sms\\SmsAuto');
                            $smsAuto->notify();
                        }
                        if ($sendMailJoin) {
                            $member->sendEmailByJoin($memberVO);
                        }
                        if ($session->has('pushJoin')) {
                            $memNo = $memberVO->getMemNo();
                            $memberData = $member->getMember($memNo, 'memNo', 'memNo, memId, appFl, groupSno, mileage');
                            $coupon = new Coupon();
                            $getData = $coupon->getMemberSimpleJoinCouponList($memNo);
                            $member->setSimpleJoinLog($memNo, $memberData, $getData, 'push');
                            $session->del('pushJoin');
                        }
                    }

                    // 회원 가입 완료 시 웹훅 발송
                    $memberWebHookService = \App::getInstance(MemberWebHookService::class);
                    $memberWebHookService->sendCreatedWebHook($memberVO);

                    // 회원 가입 완료 통계 수집
                    try {
                        $memberStatisticsCollectorService = \App::getInstance(MemberJoinCompleteAnalyticsCollectorService::class);
                        $memberStatisticsCollectorService->collectAnalytics($memberVO->toArray());
                    } catch (\Throwable $e) {
                        \Logger::channel('global')->error(__METHOD__ . ' analytics 수집 실패: ' . $e->getMessage(), [$e->getTraceAsString()]);
                    }

                    // 평생회원 이벤트
                    if ($request->post()->get('expirationFl') === '999') {
                        $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
                        $memberData = $member->getMember($memberVO->getMemNo(), 'memNo', 'memNo, memNm, mallSno, groupSno'); // 회원정보
                        $resultLifeEvent = $modifyEvent->applyMemberLifeEvent($memberData, 'life');
                        if (empty($resultLifeEvent['msg']) == false) {
                            $msg = 'alert("' . $resultLifeEvent['msg'] . '");';
                        }
                    }

                    // request가 ajax일 때 (카카오 싱크)
                    if ($request->isAjax()) {

                        // 카카오싱크 회원 가입 후 로그인 처리 ($this->json())
                        /** @var KakaoLoginPolicy $kakaoLoginPolicy */
                        if (\App::getInstance(KakaoLoginPolicy::class)->useKakaoSync() && !empty($kakaoProfile)) {
                            $this->loginBySns($kakaoProfile['id'], $returnUrl ?? '');
                        }

                        $this->json(['result' => 'success', 'returnUrl' => $returnUrl]);
                    }

                    $sitelink = new SiteLink();
                    $returnUrl = $sitelink->link('../member/join_ok.php');

                    if ($wonderToken && $userProfile && $session->has(GodoWonderServerApi::SESSION_PARENT_URI)) {
                        $returnUrl = $session->get(GodoWonderServerApi::SESSION_PARENT_URI) . '../member/wonder_join_ok.php';
                        $this->js($msg . 'location.href=\'' . $returnUrl . '\';');
                    } else {
                        $this->js($msg . 'parent.location.href=\'' . $returnUrl . '\'');
                    }
                    break;
                case 'simpleJoin':
                    $memberVO = null;
                    try {
                        \DB::begin_tran();
                        $session->set('isFront', 'y');
                        $session->set('simpleJoin', 'y');
                        $data = $request->post()->toArray();
                        $request->post()->set('simpleJoinFl','order');
                        $request->post()->set('appFl','y');
                        $memberVO = $member->join($request->post()->xss()->all());
                        $session->del('isFront');
                        \DB::commit();
                    } catch (\Throwable $e) {
                        \DB::rollback();
                        if (get_class($e) == Exception::class) {
                            if ($e->getMessage()) {
                                $this->json(['result' => 'false', 'message' => $e->getMessage()]);
                            }
                        } else {
                            throw $e;
                        }
                    }
                    if ($memberVO != null) {
                        $mailAutoConfig = ComponentUtils::getPolicy('mail.configAuto');
                        $mailDisapproval = $mailAutoConfig['join']['join']['mailDisapproval'];
                        StringUtils::strIsSet($smsDisapproval, 'n');
                        $sendMailJoin = ($memberVO->getAppFl() == 'n' && $mailDisapproval == 'y') || $memberVO->getAppFl() == 'y';
                        if ($sendMailJoin) {
                            $member->sendEmailByJoin($memberVO);
                        }

                        $request->post()->set('loginId',$data['email']);
                        $request->post()->set('loginPwd',$data['memPw']);
                        $member->login($data['email'], $data['memPw']);
                        $storage = new SimpleStorage($request->post()->all());
                        MemberUtil::saveCookieByLogin($storage);

                        // 회원 가입 완료 통계 수집
                        try {
                            $memberStatisticsCollectorService = \App::getInstance(MemberJoinCompleteAnalyticsCollectorService::class);
                            $memberStatisticsCollectorService->collectAnalytics($memberVO->toArray());
                        } catch (\Throwable $e) {
                            \Logger::channel('global')->error(__METHOD__ . ' analytics 수집 실패: ' . $e->getMessage(), [$e->getTraceAsString()]);
                        }

                        $memNo = $memberVO->getMemNo();
                        $memberData = $member->getMember($memNo, 'memNo', 'memNo, memId, appFl, groupSno, mileage');
                        $coupon = new Coupon();
                        $getData = $coupon->getMemberSimpleJoinCouponList($memNo, null, 'c.couponBenefitType ASC, c.couponBenefit DESC, c.regDt DESC');
                        $member->setSimpleJoinLog($memNo, $memberData, $getData, 'order');
                        $couponCnt = count($getData);
                        if($couponCnt == 1) {
                            $c = '쿠폰: ['.$getData[0]['couponNm'].']';
                        } elseif ($couponCnt > 1) {
                            $c = '쿠폰: ['.$getData[0]['couponNm'].'] 외 '.($couponCnt - 1).'장';
                        } else {
                            $c = 'false';
                        }
                        $this->json(['result' => 'true', 'mileage' => $memberData['mileage'], 'coupon' => $c]);
                    } else {
                        $this->json(['result' => 'false', 'message' => '요청을 찾을 수 없습니다.']);
                    }
                    exit;
                    break;
                case 'overlapEmail':
                    $memId = $request->post()->get('memId');
                    if (\App::load('Component\\Member\\Util\\MemberUtil')->simpleOverlapEmail($request->post()->get('email'), $memId) === false) {
                        $this->json(__('사용가능한 이메일입니다.'));
                    } else {
                        throw new Exception(__("이미 등록된 이메일 주소입니다.") . " " . __("다른 이메일 주소를 입력해 주세요."));
                    }
                    break;
                case 'overlapMemId':
                    $memId = $request->post()->get('memId');
                    if (MemberUtil::overlapMemId($memId) === false) {
                        $this->json(__("사용가능한 아이디입니다."));
                    } else {
                        throw new Exception(__("이미 등록된 아이디입니다.") . " " . __("다른 아이디를 입력해 주세요."));
                    }
                    break;
                case 'overlapNickNm':
                    $memId = $request->post()->get('memId');
                    $nickNm = $request->post()->get('nickNm');

                    if (MemberUtil::overlapNickNm($memId, $nickNm) === false) {
                        $this->json(__('사용가능한 닉네임입니다.'));
                    } else {
                        throw new Exception(__("이미 등록된 닉네임입니다.") . " " . __("다른 닉네임을 입력해 주세요."));
                    }
                    break;
                case 'overlapBusiNo':
                    $memId = $request->post()->get('memId');
                    $busiNo = $request->post()->get('busiNo');
                    $busiLength = $request->post()->get('charlen');

                    if (strlen($busiNo) != $busiLength) {
                        throw new Exception(sprintf(__("사업자번호는 %s자로 입력해야 합니다."), $busiLength));
                    }
                    if (MemberUtil::overlapBusiNo($memId, $busiNo) === false) {
                        $this->json(__("사용가능한 사업자번호입니다."));
                    } else {
                        throw new Exception(__("이미 등록된 사업자번호입니다."));
                    }
                    break;
                case 'checkRecommendId':
                    if (MemberUtil::checkRecommendId($request->post()->get('recommId'), $request->post()->get('memId'))) {
                        $this->json(__('아이디가 정상적으로 확인되었습니다.'));
                    } else {
                        throw new Exception(__('추천인 아이디를 다시 확인해 주세요.'));
                    }
                    break;
                case 'validateMemberPassword':
                    $memberPassword = $request->post()->get('memPw');
                    $result = MemberValidation::validateMemberPassword($memberPassword);
                    $this->json($result);
                    break;
                case 'under14Download':
                    $downloadPath = Storage::disk(Storage::PATH_CODE_COMMON)->getDownLoadPath('under14sample.docx');
                    $this->download($downloadPath, __('만14세미만회원가입동의서(샘플).docx'));
                    break;
                case 'rejectMailing':
                    $member->rejectMailing($request->post()->get('rejectEmail'));
                    $this->json(__('이메일 수신거부가 정상 처리되었습니다. 다시 메일을 수신하시려면 마이페이지>회원정보변경에서 광고성 이메일 수신에 체크해주시기 바랍니다.'));
                    break;
                case 'getJoinPolicy':
                    $joinitem = \Component\Policy\JoinItemPolicy::getInstance()->getPolicy();
                    $joinitem = MemberUtil::unsetDiffByPaycoLogin($joinitem);
                    if ($joinitem['birthDt']) {
                        $joinitem['birthYear'] = $joinitem['birthDt'];
                        $joinitem['birthMonth'] = $joinitem['birthDt'];
                        $joinitem['birthDay'] = $joinitem['birthDt'];
                    }
                    if ($joinitem['marriDate']) {
                        $joinitem['marriYear'] = $joinitem['marriDate'];
                        $joinitem['marriMonth'] = $joinitem['marriDate'];
                        $joinitem['marriDay'] = $joinitem['marriDate'];
                    }
                    $this->json($joinitem);
                    break;
                case 'applyMemberLifeEvent':
                    $memNo = gd_isset($request->post()->get('memNo'), $session->get('member.memNo'));
                    $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
                    $memberData = $member->getMember($memNo, 'memNo', 'memNo, memNm, mallSno, groupSno, expirationFl'); // 회원정보
                    $modifyEvent->updateExpirationFl($memberData);
                    $resultLifeEvent = $modifyEvent->applyMemberLifeEvent($memberData, 'life');
                    if (empty($resultLifeEvent['msg']) == false) {
                        $this->json(str_replace('\n', "\n", $resultLifeEvent['msg']));
                    }
                    break;
                case 'setSimpleJoinPushClosed':
                    $session->set('joinEventPush.joinEventPushClose', 'y');
                    exit;
                case 'setSimpleJoinPushLog':
                    $session->set('pushJoin', 'y');
                    $eventType = $request->post()->get('eventType');
                    $member->setSimpleJoinPushLog($eventType);
                    exit;
                default:
                    $logger->error(__METHOD__ . ', ' . $mode);
                    throw new Exception(__('요청을 찾을 수 없습니다.'));
                    break;
            }
        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (DatabaseException $e) {
            if ($e->getCode() == '1062') {
                throw new AlertBackException('이미 등록된 회원입니다.', $e->getCode(), $e);
            } else {
                throw new AlertBackException($e->getCode(), $e->getCode(), $e);
            }
        } catch (MemberValidationException $e) {
            if ($request->isAjax() === true) {
                $logger->warning(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
            }
        } catch (\Throwable $e) {
            if ($request->isAjax() === true) {
                $logger->error($e->getTraceAsString());
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    protected function getMemberForJoin(Request $request): array
    {
        $joinType = $request->post()->get('joinType');

        if ($joinType === 'kakaoSync') {
            try {
                /** @var KakaoSyncJoinService $kakaoSyncJoinService */
                $kakaoSyncJoinService = \App::getInstance(KakaoSyncJoinService::class);
                return $kakaoSyncJoinService->getMemberForJoin();
            } catch (KakaoSyncException $e) {
                if ($e->getMessage() === KakaoSyncException::KAKAO_USER_HAS_NOT_BIRTHDATE) {
                    \Logger::channel('kakaoLogin')->info($e->getMessage(), [$e->getTraceAsString()]);
                    throw new \Exception(__($e->getMessage()), $e->getCode(), $e);
                }

                \Logger::channel('kakaoLogin')->warning($e->getMessage(), [$e->getTraceAsString()]);
                throw new \Exception(__('카카오 회원가입에 실패했습니다.'), $e->getCode(), $e);
            }
        }

        return array_merge($request->post()->xss()->all(), $request->files()->toArray());
    }

    protected function loginBySns(string $uuid, string $returnUrl)
    {
        try {
            \DB::begin_tran();
            /** @var MemberSnsService $memberSnsService */
            $memberSnsService = \App::load('Component\\Member\\MemberSnsService');
            $memberSnsService->loginBySns($uuid);

            $attendanceCheckLogin = \App::load('Component\\Attendance\\AttendanceCheckLogin');
            $attendanceMessage = $attendanceCheckLogin->attendanceLogin();
            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollback();
            \Logger::warning(__METHOD__, [$e->getMessage()]);
            $this->json(['result' => 'fail', 'returnUrl' => $returnUrl, 'message' => $e->getMessage()]);
        }

        // 이벤트 없으면 false 반환됨
        if ($attendanceMessage) {
            $this->json(['result' => 'success', 'returnUrl' => $returnUrl, 'message' => $attendanceMessage]);
        }
    }
}
