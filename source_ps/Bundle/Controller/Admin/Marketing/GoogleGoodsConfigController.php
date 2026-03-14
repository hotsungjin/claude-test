<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Marketing;

use Exception;
use Framework\Debug\Exception\LayerException;
use Request;

class GoogleGoodsConfigController extends \Controller\Admin\Controller
{
    public function index()
    {
        // --- 상품 데이터
        try {
            // --- 메뉴 설정
            $this->callMenu('marketing', 'googleAds', 'googleGoodsConfig');

            // --- 모듈 호출
            $cate = \App::load('\\Component\\Category\\CategoryAdmin');
            $brand = \App::load('\\Component\\Category\\CategoryAdmin', 'brand');
            $goods = \App::load('\\Component\\Goods\\GoodsAdmin');

            /* 운영자별 검색 설정값 */
            $searchConf = \App::load('\\Component\\Member\\ManagerSearchConfig');
            $searchConf->setGetData();

            $getData = $goods->getAdminListBatch('image');
            $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

            $this->getView()->setDefine('goodsSearchFrm', Request::getDirectoryUri() . '/goods_list_search.php');

            $this->addScript([
                'jquery/jquery.multi_select_box.js',
            ]);

            //정렬 재정의
            $getData['search']['sortList'] = array(
                'g.goodsNo desc' => sprintf(__('등록일 %1$s'), '↓'),
                'g.goodsNo asc' => sprintf(__('등록일 %1$s'), '↑'),
                'goodsNm asc' => sprintf(__('상품명 %1$s'), '↓'),
                'goodsNm desc' => sprintf(__('상품명 %1$s'), '↑'),
                'companyNm asc' => sprintf(__('공급사 %1$s'), '↓'),
                'companyNm desc' => sprintf(__('공급사 %1$s'), '↑'),
                'goodsPrice asc' => sprintf(__('판매가 %1$s'), '↓'),
                'goodsPrice desc' => sprintf(__('판매가 %1$s'), '↑'),
            );

            // 연동대상 값 추가
            foreach ($getData['data'] as &$val) {
                $isLinked = (
                    $val['goodsDisplayFl'] == 'y' &&
                    !($val['stockFl'] == 'y' && $val['totalStock'] == 0) &&
                    $val['soldOutFl'] == 'n' &&
                    (is_null($val['goodsOpenDt']) || $val['goodsOpenDt'] < date('Y-m-d H:i:s')) &&
                    $val['googleFl'] == 'y'
                );
                $val['isLinked'] = $isLinked ? 'Y' : 'N';
            }

            $this->setData('goods', $goods);
            $this->setData('cate', $cate);
            $this->setData('brand', $brand);
            $this->setData('data', $getData['data']);
            $this->setData('search', $getData['search']);
            $this->setData('checked', $getData['checked']);
            $this->setData('totalSearchGoodsNoList', $getData['totalSearchGoodsNoList']);
            $this->setData('page', $page);
        } catch (Exception $e) {
            throw new LayerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
