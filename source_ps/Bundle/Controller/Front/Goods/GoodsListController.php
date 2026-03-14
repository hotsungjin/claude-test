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
namespace Bundle\Controller\Front\Goods;

use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\Framework\Debug\Exception;
use Framework\Utility\ProducerUtils;
use Message;
use Globals;
use Request;
use Cookie;
use Framework\Utility\StringUtils;
use Framework\Utility\SkinUtils;

class GoodsListController extends \Controller\Front\Controller
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

            if ($getValue['brandCd']) {
                $cateCd = $getValue['brandCd'];
                $cateType = "brand";
                $naviDisplay = gd_policy('display.navi_brand');
            } else {
                $cateCd = $getValue['cateCd'];
                $cateType = "cate";
                $naviDisplay = gd_policy('display.navi_category');
            }

            $cateInfo = $cate->getCategoryGoodsList($cateCd);
            $goodsCategoryList = $cate->getCategories($cateCd);
            $goodsCategoryListNm = array_column($goodsCategoryList, 'cateNm');

            // 품절상품 노출 여부
            $cate->setCateSoldOutFl($cateInfo['soldOutFl']);

            // 서브 카테고리 리스트 (2022.06 상품리스트 및 상세 성능개선)
            $categoryCodeInfo = $cate->getCategoryCodeInfo($cateCd, 2, false, true, true, false);

            // 현재 카테고리 상품등록 수 (goodsCnt 필드 값)
            $currentCateGoodsCnt = $categoryCodeInfo[0]['goodsCnt'];
            if (empty($categoryCodeInfo[0]['children']) === false) {
                $goodsDataSubCategory = $categoryCodeInfo[0]['children'];
            } else {
                $parentCateCd = substr($cateCd, 0, -DEFAULT_LENGTH_CATE);
                if ($parentCateCd) {
                    $categoryCodeInfo = $cate->getCategoryCodeInfo($parentCateCd, 2, false, true, true, true);
                    $goodsDataSubCategory = gd_isset($categoryCodeInfo[0]['children'], []);
                }
            }

            if (gd_isset($cateInfo['themeCd']) === null) {
                throw new \Exception(__('상품의 테마 설정을 확인해주세요.'));
            }

            if ($cateInfo['recomDisplayFl'] == 'y' && $cateInfo['recomGoodsNo']) {
                $recomTheme = $cateInfo['recomTheme'];
                if ($recomTheme['detailSet']) {
                    $recomTheme['detailSet'] = unserialize($recomTheme['detailSet']);
                }
                gd_isset($recomTheme['lineCnt'], 4);
                $imageType = gd_isset($recomTheme['imageCd'], 'list');                        // 이미지 타입 - 기본 'main'
                $soldOutFl = $recomTheme['soldOutFl'] == 'y' ? true : false;            // 품절상품 출력 여부 - true or false (기본 true)
                $brandFl = in_array('brandCd', array_values($recomTheme['displayField'])) ? true : false;    // 브랜드 출력 여부 - true or false (기본 false)
                $couponPriceFl = in_array('coupon', array_values($recomTheme['displayField'])) ? true : false;        // 쿠폰가격 출력 여부 - true or false (기본 false)
                $optionFl = in_array('option', array_values($recomTheme['displayField'])) ? true : false;

                if ($cateInfo['recomSortAutoFl'] == 'y') $recomOrder = $cateInfo['recomSortType'];
                else $recomOrder = "FIELD(g.goodsNo," . str_replace(INT_DIVISION, ",", $cateInfo['recomGoodsNo']) . ")";
                if ($recomTheme['soldOutDisplayFl'] == 'n') $recomOrder = "soldOut asc," . $recomOrder;
                $recomTheme['goodsDiscount'] = explode(',', $recomTheme['goodsDiscount']);
                $recomTheme['priceStrike'] = explode(',', $recomTheme['priceStrike']);
                $recomTheme['displayAddField'] = explode(',', $recomTheme['displayAddField']);

                $goods->setThemeConfig($recomTheme);
                $goodsRecom = $goods->goodsDataDisplay('goods', $cateInfo['recomGoodsNo'], (gd_isset($recomTheme['lineCnt']) * gd_isset($recomTheme['rowCnt'])), $recomOrder, $imageType, $optionFl, $soldOutFl, $brandFl, $couponPriceFl);


                if ($goodsRecom) $goodsRecom = array_chunk($goodsRecom, $recomTheme['lineCnt']);

                $this->setData('widgetGoodsList', gd_isset($goodsRecom));
                $this->setData('widgetTheme', $recomTheme);
            }

            if ($cateInfo['soldOutDisplayFl'] == 'n') $displayOrder[] = "soldOut asc";

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
            $pageNum = gd_isset($getValue['pageNum'], $displayCnt);
            $optionFl = in_array('option', array_values($cateInfo['displayField'])) ? true : false;
            $soldOutFl = (gd_isset($cateInfo['soldOutFl']) == 'y' ? true : false); // 품절상품 출력 여부
            $brandFl = in_array('brandCd', array_values($cateInfo['displayField'])) ? true : false;
            $couponPriceFl = in_array('coupon', array_values($cateInfo['displayField'])) ? true : false;     // 쿠폰가 출력 여부
            if ($cateType == 'brand') $cateMode = 'brand';
            else $cateMode = "category";

            $goods->setThemeConfig($cateInfo);
            $goodsData = $goods->getGoodsList($cateCd, $cateMode, $pageNum, $displayOrder, gd_isset($cateInfo['imageCd']), $optionFl, $soldOutFl, $brandFl, $couponPriceFl);

            $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

            // 현재 카테고리의 상품등록수 (goodsCnt 필드 값)과 표시 될 상품 수가 맞지 않는다면 마이그레이션 시도
            if ($cateType == 'cate' && $currentCateGoodsCnt != $page->getTotal()) {
                // 현재 카테고리 포함 하위 카테고리 리스트 가져오기
                $arrCateCd = $cate->getSubCategoryList($cateCd);

                // 카프카 전송 시도
                $kafka = new ProducerUtils();
                $result = $kafka->send($kafka::TOPIC_CATEGORY_GOODS_COUNT, $kafka->makeData($arrCateCd, 'cg'), $kafka::MODE_RESULT_CALLLBACK, true);
                \Logger::channel('kafka')->info('process sendMQ - return :', $result);
            }

            if ($goodsData['listData']) $goodsList = array_chunk($goodsData['listData'], $cateInfo['lineCnt']);

            unset($goodsData['listData']);

             //장바구니설정
            $cartInfo = gd_policy('order.cart');

            //품절상품 설정
            $soldoutDisplay = gd_policy('soldout.pc');

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

            // 마일리지 정보
            $mileage = gd_mileage_give_info();

            //상품 이미지 사이즈
            $cateInfo['imageSize'] = SkinUtils::getGoodsImageSize($imageType)['size1'];

            // 카테고리 노출항목 중 상품할인가
            if (in_array('goodsDcPrice', $cateInfo['displayField'])) {
                foreach ($goodsList as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $goodsList[$key][$key2]['goodsDcPrice'] = $goods->getGoodsDcPrice($val2);
                    }
                }
            }

            // 웹취약점 개선사항 카테고리 에디터 업로드 이미지 alt 추가
            if ($cateInfo['cateHtml1']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml1'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml1'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml1']);
                }
            }

            if ($cateInfo['cateHtml2']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml2'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml2'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml2']);
                }
            }

            if ($cateInfo['cateHtml3']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $cateInfo['cateHtml3'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $cateInfo['cateHtml3'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $cateInfo['cateHtml3']);
                }
            }
        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AlertRedirectException($e->getMessage(),null,null,"/");
        }

        $this->setData('goodsCategoryList', gd_isset($goodsCategoryList));
        $this->setData('goodsCategoryListNm', gd_isset($goodsCategoryListNm));
        $this->setData('cateCd', $cateCd);
        $this->setData('cateType', $cateType);
        $this->setData('themeInfo', gd_isset($cateInfo));
        $this->setData('goodsList', gd_isset($goodsList));
        $this->setData('page', gd_isset($page));
        $this->setData('goodsData', gd_isset($goodsData));
        $this->setData('pageNum', gd_isset($pageNum));
        $this->setData('soldoutDisplay', gd_isset($soldoutDisplay));
        $this->setData('naviDisplay', gd_isset($naviDisplay));
        $this->setData('sort', gd_isset($getValue['sort']));
        $this->setData('mileageData', gd_isset($mileage['info']));
        $this->setData('cartInfo', gd_isset($cartInfo));
        $this->setData('goodsDataSubCategory', gd_isset($goodsDataSubCategory));

        $this->getView()->setDefine('goodsTemplate', 'goods/list/list_'.$cateInfo['displayType'].'.html');

    }
}
