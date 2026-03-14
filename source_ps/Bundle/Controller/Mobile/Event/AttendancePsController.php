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

namespace Bundle\Controller\Mobile\Event;


use Component\Attendance\AttendanceCheck;
use Component\Member\Util\MemberUtil;
use Component\Sms\Sms;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\SimpleCache\SimpleCache;
use Request;

/**
 * Class AttendStampPsController
 * @package Bundle\Controller\Mobile\Event
 * @author  seonghu
 */
class AttendancePsController extends \Controller\Mobile\Controller
{
    const INSERT_STAMP = 'INSERT_STAMP';
    const UPDATE_STAMP = 'UPDATE_STAMP';
    const INSERT_REPLY = 'INSERT_REPLY';
    const INSERT_LOGIN = 'INSERT_LOGIN';

    /**
     * @inheritdoc
     */
    public function index()
    {
        try {
            if (!MemberUtil::isLogin()) {
                throw new AlertRedirectException(__('로그인 후 참여하실 수 있습니다.'), null, null, '../member/login.php');
            }
            $attendanceCheckSno = Request::post()->get('sno');
            if ($attendanceCheckSno == '') {
                throw new \Exception(__('참여하실 이벤트 번호가 없습니다.'));
            }

            $check = new AttendanceCheck();
            try {
                \DB::begin_tran();
                $mode = Request::post()->get('mode');
                $redisKey = sprintf("%s_%s", $attendanceCheckSno, \Session::get('member.memNo'));
                $result = SimpleCache::init(SimpleCache::REDIS)->executeWithLock($redisKey, 'global', [$check, 'attendance'], $mode);
                \DB::commit();
            } catch (Exception $e) {
                $result = $e->getMessage();
                \DB::rollback();
            }

            $smsReceivers = $check->getSmsReceiversByCouponBenefit();
            if (count($smsReceivers) > 0) {
                $sms = new Sms();
                foreach ($smsReceivers as $index => $smsReceiver) {
                    $sms->smsAutoSend('promotion', $smsReceiver['smsCode'], $smsReceiver);
                }
            }

            $this->json($result);
        } catch (Exception $e) {
            if (Request::isAjax() === true) {
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertOnlyException($e->getMessage());
            }
        }
    }
}
