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

namespace Bundle\Component\Policy;

use Framework\Object\StorageInterface;
use Exception;

class AppleLoginPolicy extends \Component\Policy\Policy
{
    const KEY = 'member.appleLogin';
    const APPLE = 'apple';
    const CONTROLLER_PATH = 'member/apple/apple_login.php';
    protected $currentPolicy;

    public function __construct(StorageInterface $storage = null)
    {
        parent::__construct($storage);
        $this->currentPolicy = $this->getValue(self::KEY);
    }

    /**
     * 설정값 저장
     * save
     *
     * @param array $policy
     *
     * @return boolean
     *
     * @throws Exception
     */
    public function save($policy)
    {

        \Logger::channel('apple')->info("AppleLogin Policy Request", [$policy]);

        $this->currentPolicy['useFl'] = gd_isset($policy['useFl'], 'n');
        $this->currentPolicy['team_id'] = $policy['team_id'];
        $this->currentPolicy['client_id'] = $policy['client_id'];
        $this->currentPolicy['key_id'] = $policy['key_id'];
        $this->currentPolicy['simpleLoginFl'] = $policy['simpleLoginFl'];
        $this->currentPolicy['baseInfo'] = gd_isset($policy['baseInfo'], 'y');
        $this->currentPolicy['additionalInfo'] = gd_isset($policy['additionalInfo'], 'n');
        $this->currentPolicy['supplementInfo'] = gd_isset($policy['supplementInfo'], 'n');

        if (empty($policy['key_file']) === false) {
            // 파일 확장자 체크
            if ($this->isAllowUploadExtention($policy['key_file_name']) === true) {
                $this->currentPolicy['key_file'] = $policy['key_file'];
                $this->currentPolicy['key_file_name'] = $policy['key_file_name'];
            } else {
                return false;
            }
        }

        return $this->setValue(self::KEY, $this->currentPolicy);
    }


    /**
     * 애플로그인 사용함 / 사용안함
     * useAppleLogin
     *
     * @return boolean
     */
    public function useAppleLogin()
    {
        return $this->currentPolicy['useFl'] == 'y';
    }

    /**
     * getClientId
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->currentPolicy['client_id'];
    }

    /**
     * isKeyFileSaved
     *
     * @return  boolean
     */
    public function isKeyFileSaved()
    {
        return empty($this->currentPolicy['key_file']) === false;
    }

    /**
     * isAllowUploadExtention
     *
     * @param $filename
     *
     * @return bool
     */
    public function isAllowUploadExtention($filename)
    {
        $allowUploadExtension = [
            'p8'
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowUploadExtension) === false) {
            return false;
        }

        return true;
    }

    /**
     * getRedirectURI
     *
     * @return  string
     */
    public function getRedirectURI()
    {
        $domain = '';
        if (\Request::isMobile()) {
            $domain = URI_MOBILE;
            if (\Request::isMyapp()) {
                $url_array = parse_url($domain);
                $domain = $url_array['scheme'] . '://' . $url_array['host'] . '/';
                unset($url_array);
            }
        } else {
            $domain = gd_policy('basic.info')['mallDomain'];
            $domain = 'https://' . $domain . '/';
        }

        return $domain . self::CONTROLLER_PATH;
    }

    /**
     * 마이앱 api용 uri
     *
     * getMyappRedirectURI
     *
     * @return  string
     */
    public function getMyappRedirectURI()
    {
        $domain = URI_MOBILE;
        $url_array = parse_url($domain);
        # ssl only
        $domain = 'https://' . $url_array['host'] . '/';
        unset($url_array);

        return $domain . self::CONTROLLER_PATH;
    }
}
