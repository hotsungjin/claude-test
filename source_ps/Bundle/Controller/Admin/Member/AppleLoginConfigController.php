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

namespace Bundle\Controller\Admin\Member;

use Bundle\Component\Policy\AppleLoginPolicy;
use Component\Mall\Mall;
use Component\Policy\JoinItemPolicy;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Component\Policy\Policy;

/**
 * 애플 아이디 로그인 설정
 * Class AppleLoginConfigController
 * @package Bundle\Controller\Admin\Member
 */
class AppleLoginConfigController extends \Controller\Admin\Controller
{
    public function index()
    {
        try {
            $this->callMenu('member', 'sns', 'appleLoginConfig');

            $mallSno = gd_isset(\Request::get()->get('mallSno'), 1);
            $this->setData('mallInputDisp', $mallSno == 1 ? false : true);
            $policy = gd_policy(AppleLoginPolicy::KEY);
            gd_isset($policy['team_id']);
            gd_isset($policy['client_id']);
            gd_isset($policy['key_id']);
            gd_isset($policy['key_file_name']);
            gd_isset($policy['key_file']);
            gd_isset($policy['useFl'], 'n');
            gd_isset($policy['simpleLoginFl'], 'y');
            gd_isset($policy['baseInfo'], 'y');
            gd_isset($policy['supplementInfo'], 'n');
            gd_isset($policy['additionalInfo'], 'n');

            $checked['simpleLoginFl'][$policy['simpleLoginFl']] =
            $checked['baseInfo'][$policy['baseInfo']] =
            $checked['supplementInfo'][$policy['supplementInfo']] =
            $checked['additionalInfo'][$policy['additionalInfo']] =
            $checked['useFl'][$policy['useFl']] = 'checked="checked"';

            //회원가입항목정보
            $policyService = new JoinItemPolicy();
            $joinItemPolicy = $policyService->getJoinPolicyDisplay($mallSno);
            $policy['items'] = $joinItemPolicy;

            //도메인 정보
            $policyInfo = new Policy();
            $getPolicy = $policyInfo->getValue('basic.info', $mallSno);
            $policy['mallDomain'] = $getPolicy['mallDomain'];

            $this->setData('checked', $checked);
            $this->setData('data', $policy);
            $this->setData('appleNotificationUrl', URI_API . 'member/apple/apple_notification.php');
        }catch (Exception $e) {
            throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
        }
    }

}
