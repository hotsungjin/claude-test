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

use App;
use Bundle\Component\Member\Exception\MemberValidationException;
use Bundle\Component\Member\Util\MemberUtil;
use Component\Member\Member;
use Component\Member\MemberValidation;
use Request;
use Session;
use Component\Member\MemberCertificationValidation as MCV;

/**
 * Class FindPasswordResetPsController
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class FindPasswordResetPsController extends \Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');
        $request = \App::getInstance('request');
        $logger = \App::getInstance('logger');

        try {
            /** @var  \Bundle\Component\Member\Member $member */
            $member = App::load('\\Component\\Member\\Member');

            if ($session->has(Member::SESSION_USER_CERTIFICATION) == false) {
                $this->json(
                    [
                        'error' => [
                            'message' => __('잘못된 경로로 접근하셨습니다.'),
                            'url'     => '../member/find_password.php',
                        ],
                    ]
                );
            }

            $sessionByCertification = $session->get(Member::SESSION_USER_CERTIFICATION);

            if (MCV::isApply()){
                $mcv = new MCV($sessionByCertification['memId']);

                if ($mcv->validateToken($request->post()->get(MCV::TOKEN_NAME)) == false){
                    $this->json(
                        [
                            'error' => [
                                'message' => __('정상적인 요청이 아닙니다.'),
                                'url'     => '../member/find_password.php',
                            ],
                        ]
                    );
                }
            }

            $memId = $sessionByCertification['memId'];
            $memNo = $sessionByCertification['memNo'];
            $memPw = $request->post()->get('memPw');
            MemberValidation::validateMemberPassword($memPw);
            if($member->checkPassword($memNo, $memPw)){
                $this->json(
                    [
                        'error' => [
                            'message' => __("현재 비밀번호와 동일한 비밀번호는 사용할 수 없습니다."),
                            'url' => 'javascript:void(0)',
                        ],
                    ]
                );
            } else {
                $member->updatePassword($memId, $memPw);
                $session->del(Member::SESSION_USER_CERTIFICATION);
                MemberUtil::notifypasswordChanged($memNo);

                $this->json(
                    [
                        'message' => __('비밀번호가 변경되었습니다.'),
                        'url'     => '../member/find_password_complete.php',
                    ]
                );
            }
        } catch (MemberValidationException $e) {
            $logger->warning(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
        } catch (\Exception $e) {
            $logger->error(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage(), $e->getTrace());
        }
    }
}
