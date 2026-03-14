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

namespace Bundle\Controller\Admin\Goods;

use Component\Godo\GodoSmsServerApi;
use Component\Sms\Exception\PasswordException;
use Component\Sms\SmsAdmin;
use Framework\Debug\Exception\LayerException;
use Framework\Debug\Exception\LayerNotReloadException;

/**
 * 재고 알림 설정 관련 처리
 *
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class GoodsStockNotificationPsController extends \Controller\Admin\Controller
{

    /**
     * index
     *
     * @throws LayerException
     */
    public function index()
    {
        // --- 모듈 호출
        $request = \App::getInstance('request');
        $postData = $request->post()->toArray();

        gd_set_policy('goods.stock_notification', $postData);
        $message = '저장이 완료 되었습니다';
        $this->layer($message, 'parent.location.href="' . $request->getReferer() . '"');
        
        exit();
    }
}
