<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Component\Policy;

use Framework\Object\StorageInterface;

class GoogleLoginPolicy extends \Component\Policy\Policy
{
    const KEY = 'member.googleLogin';
    const GOOGLE = 'google';
    protected $currentPolicy;

    /**
     * @param StorageInterface|null $storage
     */
    public function __construct(StorageInterface $storage = null)
    {
        parent::__construct($storage);
        $this->currentPolicy = $this->getValue(self::KEY);
    }

    /**
     * 구글 로그인 사용 여부
     *
     * @return bool
     */
    public function useGoogleLogin(): bool
    {
        return $this->getDefaultValue(self::KEY)['useFl'] == 'y';
    }

    /**
     * 구글 로그인설정 저장
     *
     * @param $policy
     * @return bool
     */
    public function save(array $policy = []): bool
    {
        if (empty($policy)) {
            return false;
        }

        $this->currentPolicy['useFl'] = $policy['useFl'];
        $this->currentPolicy['clientId'] = $policy['clientId'];
        $this->currentPolicy['clientSecret'] = $policy['clientSecret'];
        $this->currentPolicy['redirectUri'] = 'member/google/google_login.php'; // fixed
        $this->currentPolicy['simpleLoginFl'] = $policy['simpleLoginFl'];

        if ($policy['simpleLoginFl'] !== 'y') {
            if ($policy['mallSno'] == DEFAULT_MALL_NUMBER) {
                $this->currentPolicy['baseInfo'] =       gd_isset($policy['baseInfo'], 'y');
                $this->currentPolicy['supplementInfo'] = gd_isset($policy['supplementInfo'], 'n');
                $this->currentPolicy['additionalInfo'] = gd_isset($policy['additionalInfo'], 'n');

            } else {
                $globalConfigPolicy = [];
                $globalConfigPolicy['baseInfo'] = gd_isset($policy['baseInfo'],'y');
                $globalConfigPolicy['supplementInfo'] = gd_isset($policy['supplementInfo'], 'n');
                $globalConfigPolicy['additionalInfo'] = gd_isset($policy['additionalInfo'],'n');

                return $this->setValue(self::KEY, $globalConfigPolicy, $policy['mallSno']);
            }
        }

        return $this->setValue(self::KEY, $this->currentPolicy);
    }

    /**
     * 구글 로그인 정책 return
     *
     * @return array
     */
    public function getPolicy(): array
    {
        return $this->currentPolicy;
    }

}
