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
 * @link http://www.godo.co.kr
 */
namespace Bundle\Controller\Mobile\Member;

use Vendor\Captcha\Captcha as CaptchaModule;
use Request;

/**
 * Class CaptchaController 회원가입 자동등록방지
 * @package Bundle\Controller\Mobile\Member
 * @author  hwangbok-jung
 */
class CaptchaController extends \Controller\Mobile\Controller
{
    public function index()
    {
        try {
            $captcha = new CaptchaModule();
            $getData = array();

            if (Request::get()->get('bgColor')) {
                $getData['bdCaptchaBgClr'] = Request::get()->get('bgColor');
            }

            if (Request::get()->get('color')) {
                $getData['bdCaptchaClr'] = Request::get()->get('color');
            }

            $captcha->output($getData['bdCaptchaBgClr'], $getData['bdCaptchaClr']);
            exit;
        } catch (\Exception $e) {
            debug($e->getMessage());
        }
    }
}
