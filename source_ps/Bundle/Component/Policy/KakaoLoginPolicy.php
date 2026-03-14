<?php
/**
 * Created by PhpStorm.
 * User: godo
 * Date: 2018-08-10
 * Time: 오후 5:34
 */

namespace Bundle\Component\Policy;

use Framework\Object\StorageInterface;

class KakaoLoginPolicy extends \Component\Policy\Policy
{
    const KEY = 'member.kakaoLogin';
    const KAKAO_SYNC = 'member.kakaoSync';
    const KAKAO = 'kakao';

    /**
     * 카카오 싱크 사용했다면 카카오 로그인 사용이 불가함
     *
     * @var bool
     */
    protected $installedKakaoSync;
    protected $currentPolicy;

    public function __construct(StorageInterface $storage = null)
    {
        parent::__construct($storage);
        $kakaoSyncPolicy = $this->getValue(self::KAKAO_SYNC);
        if (empty($kakaoSyncPolicy)) {
            $this->installedKakaoSync = false;
            $this->currentPolicy = $this->getValue(self::KEY);
        } else {
            $this->installedKakaoSync = true;
            $this->currentPolicy = $kakaoSyncPolicy;
        }
    }

    public function save($policy)
    {
        // 카카오 싱크 설정은 중개서버에서 관리
        if ($this->installedKakaoSync) {
            return false;
        }

        $this->currentPolicy['useFl'] = $policy['useFl'];
        $this->currentPolicy['restApiKey'] = $policy['restApiKey'];
        $this->currentPolicy['adminKey'] = $policy['adminKey'];

        if($policy['useFl'] == 'y') {
            $this->currentPolicy['simpleLoginFl'] = $policy['simpleLoginFl'];
            $this->currentPolicy['baseInfo'] = gd_isset($policy['baseInfo'],'y');
            $this->currentPolicy['supplementInfo'] = gd_isset($policy['supplementInfo'], 'n');
            $this->currentPolicy['additionalInfo'] = gd_isset($policy['additionalInfo'],'n');
            $this->currentPolicy['businessInfo'] = gd_isset($policy['businessInfo'], 'n');
        }
            return $this->setValue(self::KEY, $this->currentPolicy);
    }

    /**
     * 카카오 로그인 사용 여부
     * (카카오 싱크 설치 여부 상관 없이 로그인 가능한가)
     * @return bool
     */
    public function useKakaoLogin()
    {
        return $this->currentPolicy['useFl'] == 'y';
    }

    /**
     * 카카오 로그인 사용 여부 
     * (카카오 싱크 설치 X)
     * @return bool
     */
    public function useKakaoLoginWithOutKakaoSync(): bool
    {
        return $this->useKakaoLogin() && $this->installedKakaoSync() === false;
    }


    /**
     * 카카오 싱크 사용 여부
     * @return bool
     */
    public function useKakaoSync(): bool
    {
        return $this->useKakaoLogin() && $this->installedKakaoSync();
    }

    /**
     * 카카오 싱크 설치 여부
     * @return bool
     */
    public function installedKakaoSync(): bool
    {
        return $this->installedKakaoSync;
    }

    /**
     * 카카오 로그인 & 싱크 정책 return
     * @return array
     */
    public function getPolicy(): array
    {
        return $this->currentPolicy;
    }
}
