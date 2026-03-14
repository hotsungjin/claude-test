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

use Bundle\Component\Myapp\Myapp;
use Bundle\Component\Policy\JoinItemPolicy;
use Bundle\Component\Policy\AppleLoginPolicy;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Request;
use Exception;

class AppleLoginConfigPsController extends \Controller\Admin\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');

        try{
            $redirectURL = '../member/apple_login_config.php';
            $redirectTarget = 'parent';

            $policy = new AppleLoginPolicy();
            $config_data = $request->post()->toArray();
            $key_file = $request->files()->get('key_file');

            if (empty($key_file["name"]) === false) {
                if ($policy->isAllowUploadExtention($key_file["name"]) === false) {
                    throw new AlertRedirectException(__('Key File은 .p8 확장자만 등록 가능합니다.'), 400, null, $redirectURL, $redirectTarget);
                }

                // .p8 -> text
                $key_file_text = file_get_contents($key_file["tmp_name"]);

                $config_data["key_file"] = $key_file_text;
                $config_data["key_file_name"] = $key_file["name"];
            } else if ($policy->isKeyFileSaved() === false){
                throw new AlertRedirectException(__('Key File을 등록해주세요.'), 400, null, $redirectURL, $redirectTarget);
            }

            $myappInfo = gd_policy('myapp.config');
            if (empty($myappInfo['builder_auth']['clientId']) === false && empty($myappInfo['builder_auth']['secretKey']) === false) {
                $myapp = new Myapp();
                $myapp->setMyappAppleLoginFlag($config_data['useFl']);
            }

            if($policy->save($config_data)){
                throw new AlertRedirectException(__('저장되었습니다.'), 200, null, $redirectURL, $redirectTarget);
            } else {
                throw new AlertRedirectException(__('처리중에 오류가 발생하여 실패되었습니다.'), 400, null, $redirectURL, $redirectTarget);
            }
        }catch (Exception $e) {
            throw $e;
        }
    }
}
