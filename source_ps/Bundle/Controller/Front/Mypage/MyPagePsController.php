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

namespace Bundle\Controller\Front\Mypage;

use App;
use Bundle\Component\Member\Util\MemberUtil;
use Component\Member\History;
use Component\Member\Member;
use Component\Member\MyPage;
use Component\SiteLink\SiteLink;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Security\Digester;
use Framework\Security\Token;
use Origin\Service\MyApp\MyappAuth;
use Origin\Service\WebHook\MemberWebHookService;
use Request;
use Session;

/**
 * Class 프론트-마이페이지 컨트롤러
 * @package Bundle\Controller\Front\Mypage
 * @author  yjwee
 */
class MyPagePsController extends \Controller\Front\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');

        try {
            /** @var  \Bundle\Component\Member\MyPage $myPage */
            $myPage = \App::load('\\Component\\Member\\MyPage');
            $mode = $request->post()->get('mode', '');
            switch ($mode) {
                case 'modify': // 회원정보수정
                    $beforeSession = Session::get(Member::SESSION_MEMBER_LOGIN);

                    // 스킨패치 미적용시 세션에 생성된 CSRF 토큰 적용
                    if (!$request->post()->get('myPageToken')) {
                        $request->post()->set('myPageToken', Session::get('token.myPageToken'));
                    }
                    $requestParams = array_merge($request->post()->xss()->all(), $request->files()->toArray());

                    // CSRF 토큰 체크
                    if (!Token::check('myPageToken', $requestParams)) {
                        throw new Exception(__('잘못된 경로로 접근하셨습니다.'));
                    }

                    //회원 번호는 세션에 저장 되어 있는 회원 번호로 가져옴
                    $requestParams['memNo'] = Session::get(MyPage::SESSION_MY_PAGE_MEMBER_NO, 0);
                    $beforeMemberInfo = $myPage->getDataByTable(DB_MEMBER, $requestParams['memNo'], 'memNo');
                    $beforeSession['recommId'] = $beforeMemberInfo['recommId'];
                    $beforeSession['recommFl'] = $beforeMemberInfo['recommFl'];
                    // 회원정보 이벤트
                    $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
                    $mallSno = \SESSION::get(SESSION_GLOBAL_MALL)['sno'] ? \SESSION::get(SESSION_GLOBAL_MALL)['sno'] : DEFAULT_MALL_NUMBER;
                    $activeEvent = $modifyEvent->getActiveMemberModifyEvent($mallSno, 'life');
                    $memberLifeEventCnt = $modifyEvent->checkDuplicationModifyEvent($activeEvent['sno'], $requestParams['memNo'], 'life'); // 이벤트 참여내역
                    $getMemberLifeEventCount = $modifyEvent->getMemberLifeEventCount($requestParams['memNo']); // 평생회원 변경이력

                    try {
                        Session::set(Member::SESSION_MODIFY_MEMBER_INFO, $beforeMemberInfo);
                        \DB::begin_tran();
                        $myPage->modify($requestParams, $beforeSession);
                        $history = new History();
                        $history->setMemNo($requestParams['memNo']);
                        $history->setProcessor('member');
                        $history->setProcessorIp($request->getRemoteAddress());
                        $history->initBeforeAndAfter();
                        $history->addFilter(array_keys($requestParams));
                        $history->writeHistory();
                        \DB::commit();
                    } catch (Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    $myPage->sendEmailByPasswordChange($requestParams, Session::get(Member::SESSION_MEMBER_LOGIN));
                    $myPage->sendSmsByAgreementFlag($beforeSession, Session::get(Member::SESSION_MEMBER_LOGIN));

                    // 회원정보 수정 이벤트
                    $afterSession = Session::get(Member::SESSION_MEMBER_LOGIN);
                    if (strtotime($afterSession['changePasswordDt']) > strtotime($beforeSession['changePasswordDt'])) {
                        $requestParams['changePasswordFl'] = 'y';

                        MemberUtil::notifyPasswordChanged($requestParams['memNo']);
                        if ($request->isFromMyapp()) {
                            Session::set(MyappAuth::MYAPP_PASSWORD_CHANGED, true);
                        }
                    }
                    $resultModifyEvent = $modifyEvent->applyMemberModifyEvent($requestParams, $beforeMemberInfo);
                    if (empty($resultModifyEvent['msg']) == false) {
                        $msg = 'alert("' . $resultModifyEvent['msg'] . '");';
                    }

                    // 평생회원 이벤트
                    if (!$memberLifeEventCnt && $getMemberLifeEventCount == 0 && $requestParams['expirationFl'] === '999') {
                        $resultLifeEvent = $modifyEvent->applyMemberLifeEvent($beforeMemberInfo, 'life');
                        if (empty($resultLifeEvent['msg']) == false) {
                            $msg = 'alert("' . $resultLifeEvent['msg'] . '");';
                        }
                    }

                    $sitelink = new SiteLink();
                    $returnUrl = $sitelink->link($request->getReferer());

                    // 애플 관련 링크 처리
                    if ($afterSession['snsTypeFl'] == 'apple' && $afterSession['connectFl'] == 'y' && !strpos($returnUrl, 'mypage')) {
                        $returnUrl = './my_page.php';
                    }

                    // 회원 정보 변경 시 웹훅 발송
                    $memberWebHookService = \App::getInstance(MemberWebHookService::class);
                    $memberWebHookService->sendInfoChangedWebHook($afterSession);

                    $this->js('alert(\'' . __('회원정보 수정이 성공하였습니다.') . '\');' . $msg . 'parent.location.href=\'' . $returnUrl . '\'');
                    break;
                case 'verifyPassword':
                    $memberSession = \Session::get(Member::SESSION_MEMBER_LOGIN);
                    if (Digester::isValid(\Encryptor::decrypt($memberSession['memPw']), $request->post()->get('memPw')) == false) {
                        if (App::getInstance('password')->verify($request->post()->get('memPw'), \Encryptor::decrypt($memberSession['memPw'])) === false) {
                            throw new Exception(__('비밀번호를 정확하게 입력해 주세요.'));
                        }
                    }
                    Session::set(MyPage::SESSION_MY_PAGE_PASSWORD, true);
                    $this->json(__('비밀번호를 정확하게 입력하셨습니다.'));
                    break;
                default:
                    throw new AlertRedirectException(__('해당 요청을 수행할 수 없습니다.'), 501, null, '/', 'top');
                    break;
            }
        } catch (Exception $e) {
            if ($request->isAjax()) {
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertOnlyException($e->getMessage());
            }
        }
    }
}
