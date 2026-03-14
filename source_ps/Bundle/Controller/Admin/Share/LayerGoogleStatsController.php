<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Share;

use Framework\Debug\Exception\LayerException;
use Globals;
use Origin\Service\Marketing\Google\GoogleGoodsFeedService;
use Request;
use Exception;

class LayerGoogleStatsController extends \Controller\Admin\Controller
{
    public function index()
    {
        //--- 모듈 호출
        $googleGoodsFeedService = \App::getInstance(GoogleGoodsFeedService::class);
        $cate = \App::load('\\Component\\Category\\CategoryAdmin');
        $getValue = Request::get()->toArray();

        //--- 상품 데이터
        try {
            $statsData = $googleGoodsFeedService->getGoogleStats();

            // 카테고리 지정인 경우 상품 검색
            if ($getValue['categoryNoneFl'] != 'y' && (empty($getValue['cateNm']) === false || empty($getValue['cateGoods'][0]) === false)) {
                $getData = $cate->getAdminSeachCategory('layer', 10);
                $page = \App::load('Component\\Page\\Page');    // 페이지 재설정
                $goodsGoogleStats = $googleGoodsFeedService->getGoodsGoogleStats(array_column($getData['data'], 'cateCd'));
            }

            // 카테고리 미지정인 경우 상품 검색
            if ($getValue['categoryNoneFl'] == 'y' && empty($getValue['cateNm']) === true && empty($getValue['cateGoods'][0]) === true) {
                $goodsGoogleStats = $googleGoodsFeedService->getGoodsGoogleStats();
            }

            //--- 관리자 디자인 템플릿
            $this->getView()->setDefine('layout', 'layout_layer.php');

            $this->setData('cate', $cate);
            $this->setData('search', gd_isset($getValue));
            $this->setData('statsData', gd_isset($statsData));
            $this->setData('data', gd_isset($getData['data']));
            $this->setData('useMallList', gd_isset($getData['useMallList']));
            $this->setData('page', gd_isset($page));
            $this->setData('goodsGoogleStats', gd_isset($goodsGoogleStats));
        } catch (Exception $e) {
            throw new LayerException($e->getMessage());
        }
    }
}
