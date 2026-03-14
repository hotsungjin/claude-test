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

use Component\Sms\Sms;
use Framework\Utility\ComponentUtils;

/**
 * 재고알림 설정 관리
 *
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class GoodsStockNotificationController extends \Controller\Admin\Controller
{

    /**
     * index
     *
     */
    public function index()
    {
        $request = \App::getInstance('request');
        // --- 메뉴 설정
        $this->callMenu('goods', 'goods', 'stockNotification');
        $policy = ComponentUtils::getPolicy('goods.stock_notification');


        $this->setData('goodsStock', $policy['goodsStock']);

    }
}
