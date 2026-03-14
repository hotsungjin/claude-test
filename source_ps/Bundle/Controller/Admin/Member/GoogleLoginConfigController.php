<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Member;

use Exception;
use Framework\Debug\Exception\AlertBackException;
use Bundle\Component\Policy\GoogleLoginPolicy;
use Bundle\Component\Policy\JoinItemPolicy;
use Bundle\Component\Mall\Mall;

class GoogleLoginConfigController extends \Controller\Admin\Controller
{
    public function index()
    {
        try {
            $this->callMenu('member', 'sns', 'googleLoginConfig');

            $mallSno = gd_isset(\Request::get()->get('mallSno'), DEFAULT_MALL_NUMBER);
            $this->setData('mallSno', $mallSno);
            $this->setData('mallInputDisp', ($mallSno != DEFAULT_MALL_NUMBER));
            $mall = new Mall();

            $mallList = $mall->getListByUseMall();
            if (count($mallList) > 1) {
                $this->setData('mallCnt', count($mallList));
                $this->setData('mallList', $mallList);
                if ($mallSno > DEFAULT_MALL_NUMBER) {
                    $defaultData = gd_policy('basic.info', DEFAULT_MALL_NUMBER);
                    foreach ($defaultData as $key => $value) {
                        if (in_array($key, Mall::GLOBAL_MALL_BASE_INFO) === true) {
                            $data[$key] = $value;
                        }
                    }

                    $disabled = ' disabled = "disabled"';
                    $readonly = ' readonly = "readonly"';
                    $this->setData('disabled', $disabled);
                    $this->setData('readonly', $readonly);
                }
            }

            $policy = gd_policy(GoogleLoginPolicy::KEY);
            gd_isset($policy['useFl'], 'n');
            gd_isset($policy['simpleLoginFl'], 'y');
            gd_isset($policy['baseInfo'], 'y');
            gd_isset($policy['supplementInfo'], 'n');
            gd_isset($policy['additionalInfo'], 'n');

            if ($mallSno > 1){
                $globalPolicy = gd_policy(GoogleLoginPolicy::KEY, $mallSno);

                $policy['baseInfo']       = gd_isset($globalPolicy['baseInfo'], 'y');
                $policy['supplementInfo'] = gd_isset($globalPolicy['supplementInfo'], 'n');
                $policy['additionalInfo'] = gd_isset($globalPolicy['additionalInfo'], 'n');
            }

            $checked['useFl'][$policy['useFl']] = 'checked="checked"';
            $checked['simpleLoginFl'][$policy['simpleLoginFl']] = 'checked="checked"';

            // 회원 가입 항목 정보
            $policyService = new JoinItemPolicy();
            $policy['items'] = $policyService->getJoinPolicyDisplay($mallSno);

            // 도메인 정보
            $policy['mallDomain'] = gd_policy('basic.info', $mallSno)['mallDomain'];

            $this->setData('checked', $checked);
            $this->setData('data', $policy);

        } catch (Exception $e) {
            throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
        }
    }

}
