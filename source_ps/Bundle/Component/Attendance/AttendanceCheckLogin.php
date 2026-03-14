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

namespace Bundle\Component\Attendance;

use Bundle\Component\Attendance\Exception\AttendanceValidationException;
use Bundle\Component\Attendance\Exception\AttendanceGlobalMallException;
use Component\Attendance\Attendance;
use Framework\Object\SimpleStorage;

/**
 * Class AttendanceCheckLogin
 * @package Bundle\Component\Attendance
 * @author  yjwee
 */
class AttendanceCheckLogin extends \Component\Attendance\AttendanceCheck
{
    public function attendanceLogin()
    {
        $logger = \App::getInstance('logger');
        $session = \App::getInstance('session');

        $this->setRequestStorage(new SimpleStorage());
        try {
            if ($session->has(SESSION_GLOBAL_MALL)) {
                throw new AttendanceGlobalMallException('해외몰 입니다.');
            }
            if (!\Component\Member\Util\MemberUtil::getInstance()->isDefaultMallMemberSession()) {
                throw new AttendanceValidationException(__('기준몰 회원이 아닙니다.'));
            }
            /** @var \Bundle\Component\Attendance\Attendance $attendance */
            $attendance = \App::load(Attendance::class);
            $this->attendanceStorage = $attendance->getDataByActive('login');
            if ($this->requestStorage->get('attendanceSno', '') === '') {
                $this->requestStorage->set('attendanceSno', $this->attendanceStorage->get('sno', ''));
            }
            if ($this->requestStorage->get('attendanceSno', '') === '') {
                return false;
            }
            $attendance->checkDevice($this->attendanceStorage->get('deviceFl'));
            $groupFl = $this->attendanceStorage->get('groupFl');
            $attendance->checkGroup($groupFl, $this->attendanceStorage->get('groupSno'));
            $this->attendanceMessage = $this->attendanceStorage->get('completeComment', __('출석이 완료되었습니다. 내일도 참여해주세요.'));

            $this->getAttendanceCheck($this->requestStorage->get('attendanceSno'), $session->get('member.memNo'));
            if ($this->hasBenefit()) {
                throw new AttendanceValidationException(__('이미 출석체크 이벤트 혜택을 지급 받으셨습니다.'));
            }
            $this->checkAttendance();

            if (\count($this->checkStorage->all()) > 0) {
                // 조건 달성 한 상태이지만 혜택을 받지 못한 경우
                if ($this->isComplete() && $this->isBenefitCondition()
                    && $this->attendanceStorage->get('benefitGiveFl') === 'auto') {
                    $logger->info(__METHOD__ . ' isComplete && isBenefitCondition && benefitGive auto');
                    $this->giveBenefitAutoByComplete();

                    return $this->attendanceMessage;
                }
                $this->updateAttendance();
            } else {
                $this->insertAttendance();
            }
        } catch (AttendanceGlobalMallException $e) {
            $exceptionMessage = __METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage();
            $logger->info($exceptionMessage);
            return false;
        } catch (AttendanceValidationException $e) {
            $exceptionMessage = __METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage();
            $logger->warning($exceptionMessage, $e->getTrace());
            throw $e;
        } catch (\Exception $e) {
            $exceptionMessage = __METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage();
            $logger->error($exceptionMessage, $e->getTrace());
            throw $e;
        }

        return $this->attendanceMessage;
    }
}
