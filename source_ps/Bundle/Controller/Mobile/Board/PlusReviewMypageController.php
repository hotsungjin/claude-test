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

namespace Bundle\Controller\Mobile\Board;


use Component\PlusShop\PlusReview\PlusReviewArticleFront;

class PlusReviewMypageController extends \Controller\Mobile\Controller
{
    public function index()
    {
        $getValue = \Request::get()->all();

        $plusReviewArticle = new PlusReviewArticleFront();
        $plusReviewConfig = $plusReviewArticle->getConfig();
        $goodsNo = $orderGoodsNo = null;
        if ($plusReviewConfig['authWriteExtra'] === 'all' && empty($getValue['goodsNo']) === false) {
            $goodsNo = $getValue['goodsNo'];
        } else {
            $orderGoodsNo = $getValue['orderGoodsNo'];
        }

        if(\App::load('\\Component\\Goods\\Goods')->getGoodsDeleteFl($getValue['goodsNo']) === 'y') {
            $js = "
            alert('" . __('해당 상품은 현재 구매가 불가한 상품입니다.') . "');
            location.href = '" . \App::getInstance('request')->getReferer() . "';
            ";
            $this->js($js);
        }

        $buyData = $plusReviewArticle->getWritableOrderList($goodsNo, null, false, false, $orderGoodsNo);

        $data['buyGoodsData'] = $buyData[0];
        $this->setData('req',$getValue);
        $this->setData('plusReviewConfig',$plusReviewConfig);
        $this->setData('data' , $data);
        $this->setData('writer' , \Session::get('member.memNm'));

    }
}
