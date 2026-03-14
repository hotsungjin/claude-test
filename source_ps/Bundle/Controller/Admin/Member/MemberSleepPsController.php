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

namespace Bundle\Controller\Admin\Member;

use Exception;
use Framework\Utility\GodoUtils;
use Framework\Debug\Exception\LayerException;
use Origin\Exception\Member\MemberSleepWakeException;

/**
 * Class 휴면 회원 처리
 * @package Bundle\Controller\Admin\Member
 * @author  yjwee
 */
class MemberSleepPsController extends \Controller\Admin\Controller
{
    /**
     * @var \Bundle\Component\Member\MemberSleep.php $memberSleep
     */
    private $memberSleep;

    /**
     * @inheritdoc
     *
     * @throws LayerException
     */
    public function index()
    {
        $logger = \App::getInstance('logger');
        $request = \App::getInstance('request');
        $this->memberSleep = \App::load('Component\\Member\\MemberSleep');

        try {
            switch ($request->post()->get('mode')) {
                case 'sleep_member':
                    // 휴면회원 전환
                    $memNo = $request->post()->get('chk');
                    if (GodoUtils::isGodoIp()) {
                        /** @var \Bundle\Component\Member\Member $member */
                        $member = \App::load('Component\\Member\\Member');
                        $member->updateMemberByMembersNo($memNo, ['lastLoginDt'], ['2013-10-10 01:03:00']);
                    }
                    $this->memberSleep->sleep($memNo);
                    $this->json(__('휴면회원 전환이 완료되었습니다.'));
                    break;
                case 'wake_member':
                    // 휴면회원 해제
                    $this->wakeMember([$request->post()->get('sleepNo')]);
                    break;
                case 'wake_sleep_member':
                    // 선택 휴면회원 해제
                    $this->wakeMember($request->post()->get('chk'));
                    break;
                case 'delete_sleep_member':
                    // 휴면회원 삭제
                    $check = $request->post()->get('chk');
                    $this->memberSleep->deleteSleepMember($check);
                    $this->json(__('삭제 되었습니다.'));
                    break;
                case 'sleep_send_mail':
                    // 휴면회원 메일 전송
                    $memNo = $request->post()->get('chk');
                    if ($request->getRemoteAddress() === '127.0.0.1' || $request->getRemoteAddress() === '61.36.175.161' || $request->getRemoteAddress() === '61.36.175.181') {
                        /** @var \Bundle\Component\Member\Member $member */
                        $member = \App::load('\\Component\\Member\\Member');
                        $member->updateMemberByMembersNo($memNo, ['lastLoginDt'], ['2013-10-10 01:03:00']);
                    }
                    $result = $this->memberSleep->sendSleepMail($memNo);
                    if ($result > 0) {
                        $this->json(sprintf(__('%s 명에게 메일발송을 실패하였습니다.'), $result));
                    } else {
                        $this->json(__('휴면회원 안내 메일이 발송되었습니다.'));
                    }
                    break;
                default:
                    throw new \Exception(__('요청을 처리할 페이지를 찾을 수 없습니다.'), 404);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $trace = $e->getTrace();
            $code = $e->getCode();
            $logger->error(__METHOD__ . ' ' . $code . ' ' . $message, $trace);
            if ($request->isAjax()) {
                $this->json(
                    [
                        'code'    => gd_isset($code, 500),
                        'message' => gd_isset($message, 'Exception'),
                    ]
                );
            } else {
                throw new LayerException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    private function wakeMember(array $memberNoList)
    {
        $totalCnt = count($memberNoList);
        $successCnt = $this->memberSleep->wakeMultipleBySleepNo($memberNoList, true);

        $isSuccess = false;
        if ($successCnt === $totalCnt) {
            $isSuccess = true;
        }

        if ($isSuccess) {
            $message = __('휴면회원 해제가 완료되었습니다.');
        } else {
            if ($totalCnt > 1) {
                $message = __('휴면회원 해제에 실패하였습니다.');
            } else {
                $message = MemberSleepWakeException::SLEEP_WAKE_FAILED;
            }
        }

        if ($totalCnt > 1) {
            $message .= sprintf('<br>완료: %d 명, 실패: %d 명', $successCnt, ($totalCnt-$successCnt));
        }

        if (!$isSuccess) {
            $message .= '<br><br>' . MemberSleepWakeException::GUIDE_MESSAGE;
        }

        $this->json($message);
    }
}
