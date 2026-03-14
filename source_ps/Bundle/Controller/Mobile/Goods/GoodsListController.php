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
namespace Bundle\Controller\Mobile\Goods;

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Framework\Debug\Exception;
use Message;
use Globals;
use Request;
use Cookie;

class GoodsListController extends \Controller\Mobile\Controller
{

    /**
     * 상품목록
     *
     * @author artherot, sunny
     * @version 1.0
     * @since 1.0
     * @copyright Copyright (c), Godosoft
     * @throws Except
     */
    public function index()
    {
        $checkParameter = ['cateCd', 'brandCd'];
        $getValue = Request::get()->length($checkParameter)->toArray();

        // 모듈 설정
        $goods = \App::load('\\Component\\Goods\\Goods');
        if($getValue['brandCd']) $cate = \App::load('\\Component\\Category\\Brand');
        else $cate = \App::load('\\Component\\Category\\Category');

        try {
            // 카테고리 정보
            if($getValue['brandCd'])  {
                $cateCd =  $getValue['brandCd'];
                $cateType = "brand";
                $cateMode = "brand";
                $naviDisplay = gd_policy('display.navi_brand');
            } else {
                $cateCd = $getValue['cateCd'];
                $cateType = "cate";
                $cateMode = "category";
                $naviDisplay = gd_policy('display.navi_category');
            }

            $cateInfo = $cate->getCategoryGoodsList($cateCd,'y');
            $goodsCategoryList = $cate->getCategories($cateCd,'y');
            $goodsCategoryListNm = array_column($goodsCategoryList, 'cateNm');

            // 품절상품 노출 여부
            $cate->setCateSoldOutFl($cateInfo['soldOutFl']);

            // 서브 카테고리 리스트 (2022.06 상품리스트 및 상세 성능개선)
            $categoryCodeInfo = $cate->getCategoryCodeInfo($cateCd, 2, false, true, true, false);
            if (empty($categoryCodeInfo[0]['children']) === false) {
                $goodsDataSubCategory = $categoryCodeInfo[0]['children'];
            } else {
                $parentCateCd = substr($cateCd, 0, -DEFAULT_LENGTH_CATE);
                if ($parentCateCd) {
                    $categoryCodeInfo = $cate->getCategoryCodeInfo($parentCateCd, 2, false, true, true, true);
                    $goodsDataSubCategory = gd_isset($categoryCodeInfo[0]['children'], []);
                }
            }

            if(gd_isset($cateInfo['themeCd']) ===null) {
                throw new \Exception(__('상품의 테마 설정을 확인해주세요.'));
            }

            // 마일리지 정보
            $mileage = gd_mileage_give_info();

            $this->setData('gPageName', $goodsCategoryList[$cateCd]['cateNm']);
            $this->setData('cateInfo', gd_isset($cateInfo));

            Request::get()->set('page',$getValue['page']);
            Request::get()->set('sort',$getValue['sort']);

            if($cateInfo['recomDisplayMobileFl'] =='y' && $cateInfo['recomGoodsNo'])
            {
                $recomTheme = $cateInfo['recomTheme'];
                if ($recomTheme['detailSet']) {
                    $recomTheme['detailSet'] = unserialize($recomTheme['detailSet']);
                }

                gd_isset($recomTheme['lineCnt'],4);
                $imageType		= gd_isset($recomTheme['imageCd'],'list');						// 이미지 타입 - 기본 'main'
                $soldOutFl		= $recomTheme['soldOutFl'] == 'y' ? true : false;			// 품절상품 출력 여부 - true or false (기본 true)
                $brandFl		= in_array('brandCd',array_values($recomTheme['displayField']))  ? true : false;	// 브랜드 출력 여부 - true or false (기본 false)
                $couponPriceFl	= in_array('coupon',array_values($recomTheme['displayField']))  ? true : false;		// 쿠폰가격 출력 여부 - true or false (기본 false)
                $optionFl = in_array('option',array_values($recomTheme['displayField']))  ? true : false;

                if($cateInfo['recomSortAutoFl'] =='y') $recomOrder = $cateInfo['recomSortType'].",g.goodsNo desc";
                else $recomOrder = "FIELD(g.goodsNo," . str_replace(INT_DIVISION, ",", $cateInfo['recomGoodsNo']) . ")";
                if ($recomTheme['soldOutDisplayFl'] == 'n') $recomOrder = "soldOut asc," . $recomOrder;
                $recomTheme['goodsDiscount'] = explode(',', $recomTheme['goodsDiscount']);
                $recomTheme['priceStrike'] = explode(',', $recomTheme['priceStrike']);
                $recomTheme['displayAddField'] = explode(',', $recomTheme['displayAddField']);

                $goods->setThemeConfig($recomTheme);
                $goodsRecom	= $goods->goodsDataDisplay('goods', $cateInfo['recomGoodsNo'], (gd_isset($recomTheme['lineCnt']) * gd_isset($recomTheme['rowCnt'])), $recomOrder, $imageType, $optionFl, $soldOutFl, $brandFl, $couponPriceFl);

                if($goodsRecom) $goodsRecom = array_chunk($goodsRecom,$recomTheme['lineCnt']);

                $this->setData('widgetGoodsList', gd_isset($goodsRecom));
                $this->setData('widgetTheme', $recomTheme);
            }


            if($cateInfo['soldOutDisplayFl'] =='n')  $displayOrder[] = "soldOut asc";

            // 품절 상품 진열 여부 (2022.06 상품리스트 및 상세 성능개선)
            $goods->setSoldOutDisplayFl($cateInfo['soldOutDisplayFl']);

            // 카테고리 및 브랜드 고정/상품 정렬 존재할 경우에만 추가 (2022.06 상품리스트 및 상세 성능개선)
            $goodsListSortLinkFl = $goods->getGoodsListSortLinkFl($cateType, $cateCd);
            if ($goodsListSortLinkFl['fixSortCnt']) {
                $displayOrder[] = "gl.fixSort desc";
            }

            if ($cateInfo['sortAutoFl'] == 'y') {
                $displayOrder[] = gd_isset($cateInfo['sortType'], 'gl.goodsNo desc');
            } else {
                if ($goodsListSortLinkFl['goodsSortCnt']) {
                    $goods->setGoodsSortFl('y');
                    $displayOrder[] = "gl.goodsSort desc";
                }
            }

            // 상품 정보
            $displayCnt = gd_isset($cateInfo['lineCnt']) * gd_isset($cateInfo['rowCnt']);
            $pageNum = gd_isset($getValue['pageNum'],$displayCnt);
            $optionFl = in_array('option',array_values($cateInfo['displayField']))  ? true : false;
            $soldOutFl = (gd_isset($cateInfo['soldOutFl']) == 'y' ? true : false); // 품절상품 출력 여부
            $brandFl =  in_array('brandCd',array_values($cateInfo['displayField']))  ? true : false;
            $couponPriceFl =in_array('coupon',array_values($cateInfo['displayField']))  ? true : false;	 // 쿠폰가 출력 여부
            $goods->setThemeConfig($cateInfo);
            $goodsData = $goods->getGoodsList($cateCd, $cateMode, $pageNum,$displayOrder, gd_isset($cateInfo['imageCd']), $optionFl, $soldOutFl, $brandFl, $couponPriceFl,null,$displayCnt);


            if($goodsData['listData']) $goodsList = array_chunk($goodsData['listData'],$cateInfo['lineCnt']);
            $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정
            unset($goodsData['listData']);
            //품절상품 설정
            $soldoutDisplay = gd_policy('soldout.mobile');

            if ($soldoutDisplay['soldout_icon_img']) {
                $fileSplit = explode(DIRECTORY_SEPARATOR, $soldoutDisplay['soldout_icon_img']);
                $soldout_icon_img = array_splice($fileSplit, -1, 1, DIRECTORY_SEPARATOR);
                $soldoutDisplay['soldout_icon_img_filename'] = $soldout_icon_img[0];
            }

            if ($soldoutDisplay['soldout_price_img']) {
                $fileSplit = explode(DIRECTORY_SEPARATOR, $soldoutDisplay['soldout_price_img']);
                $soldout_price_img = array_splice($fileSplit, -1, 1, DIRECTORY_SEPARATOR);
                $soldoutDisplay['soldout_price_img_filename'] = $soldout_price_img[0];
            }

            // 카테고리 노출항목 중 상품할인가
            if (in_array('goodsDcPrice', $cateInfo['displayField'])) {
                foreach ($goodsList as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $goodsList[$key][$key2]['goodsDcPrice'] = $goods->getGoodsDcPrice($val2);
                    }
                }
            }

            // 장바구니 설정
            if ($cateInfo['displayType'] == '11') {
                $cartInfo = gd_policy('order.cart');
                $this->setData('cartInfo', gd_isset($cartInfo));
            }


            // 웹취약점 개선사항 카테고리 에디터 업로드 이미지 alt 추가
            if ($cateInfo['cateHtml1Mobile']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml1Mobile'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml1Mobile'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml1Mobile']);
                }
            }

            if ($cateInfo['cateHtml2Mobile']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml2Mobile'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml2Mobile'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml2Mobile']);
                }
            }

            if ($cateInfo['cateHtml3Mobile']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml3Mobile'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml3Mobile'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml3Mobile']);
                }
            }

            $this->setData('cateCd', $cateCd);
            $this->setData('goodsCategoryListNm', gd_isset($goodsCategoryListNm));
            $this->setData('brandCd', $getValue['brandCd']);
            $this->setData('cateType', $cateType);
            $this->setData('themeInfo', gd_isset($cateInfo));
            $this->setData('goodsList', gd_isset($goodsList));
            $this->setData('page', gd_isset($page));
            $this->setData('naviDisplay', gd_isset($naviDisplay));
            $this->setData('soldoutDisplay', gd_isset($soldoutDisplay));
            $this->setData('mileageData', gd_isset($mileage['info']));
            $this->setData('currency', Globals::get('gCurrency'));
            $this->setData('goodsDataSubCategory', gd_isset($goodsDataSubCategory));

            if($getValue['mode'] == 'data') {
                $this->getView()->setPageName('goods/list/list_'.$getValue['displayType']);
            } else {
                $this->getView()->setDefine('goodsTemplate', 'goods/list/list_'.$cateInfo['displayType'].'.html');
            }

        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AlertRedirectException($e->getMessage(),null,null,"/");
        }
    }
}
