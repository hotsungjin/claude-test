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
 * @link http://www.godo.co.kr
 */

namespace Bundle\Service;

use Framework\StaticProxy\Proxy\Encryptor;
use Framework\Utility\ArrayUtils;
use Globals;

class GDUtils
{
    /**
     * 구매한 플러스샵 리스트 가져오기
     *
     * @static
     * @return array
     *  {
     *      [godosno]	=> 상점번호
    [appKeyCode]	=> 앱 키코드
    [appType]	=> 앱 구분 ( c: 설정 / i :설치)
    [appMaker]	=> 앱 개발사
    [appCode]	=> 앱코드
    [appName]	=> 앱 이름
    [appUseFl]	=> 사용여부 (y/n)
    [appRegData]	=> 등록일 (20160319)
    [appExpireData]	=> 만료일 (20201231)
    [appInstallFl]	=> 설치여부 (y/n)
    [adminUrl]	=> 어드민경로
     *
     *
     * {
     */
    public static function getPlusShopList()
    {
        $plusShopFile = \UserFilePath::config('plus_shop_info.php');
        $plusShopData = [];
        if (\FileHandler::isExists($plusShopFile) === true) {
            if ($plusShopInfo = \FileHandler::read($plusShopFile)) {
                $plusShopInfo = explode(PHP_EOL, $plusShopInfo);
                if (empty($plusShopInfo[2]) === false) {
                    $plusShopData = \Encryptor::decrypt($plusShopInfo[2]);
                    if (empty($plusShopData) === false) {
                        $plusShopData = json_decode($plusShopData);
                        $plusShopData = ArrayUtils::objectToArray($plusShopData);

                        foreach ($plusShopData as $key => $val) {
                            if (!$val['appInstallFl']) {
                                $plusShopData[$key]['appInstallFl'] = 'y';
                            }

                            if (!$val['appBuyDate']) {
                                $plusShopData[$key]['appBuyDate'] = $val['appRegData'];
                            }
                        }
                    }
                }
            }
        }

        $apps = \App::getConfig('plusshop.configure')->toArray();
        $appList = [];
        foreach ($apps as $val) {
            $appList[$val['solutionCode']] = $val;
        }
        foreach ($plusShopData as $key => &$val) {
            $val['adminUrl'] = $appList[$key]['adminUrl'];

        }

        return $plusShopData;
    }

    /**
     * DB객체 리턴
     *
     * @static
     * @return object
     */
    public static function getDB()
    {
        $db = \App::load('DB');
        return $db->dbConnection();
    }

    /**
     * 암호화
     *
     * @static
     * @param $password
     * @return boolean
     */
    public static function encodePassword($password)
    {
        return \App::getInstance('password')->hash($password);
    }

    /**
     * 비밀번호 체크
     *
     * @static
     * @param 비교할 비밀번호(비암호화 상태)
     * @param 비교대상 비밀번호(암호화 상태)
     * @return boolean
     */
    public static function verifyPassword($inputPassword, $targetPassword)
    {
        return \App::getInstance('password')->verify($inputPassword, $targetPassword);
    }

    /**
     * Returns the decrypted string.
     *
     * @param string $value
     * @param string $salt
     *
     * @return mixed
     */
    public static function decrypt($value , $salt = '')
    {
        return Encryptor::decrypt($value,$salt);
    }

    /**
     * Returns the encrypted string.
     *
     * @param mixed $value
     * @param string $salt
     *
     * @return string
     */
    public static function encrypt($value, $salt = '')
    {
        return Encryptor::encrypt($value,$salt);
    }
}
