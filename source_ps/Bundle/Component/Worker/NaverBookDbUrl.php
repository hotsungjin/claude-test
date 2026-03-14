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

namespace Bundle\Component\Worker;


use Framework\Utility\ComponentUtils;
use Framework\Utility\NumberUtils;
use Framework\Utility\StringUtils;

/**
 * Class NaverDbUrl3
 *
 * @package Bundle\Component\Worker
 * @author  minji-lee <minji-lee@nhn-commerce.com>
 */
class NaverBookDbUrl extends \Component\Worker\NaverDbUrl
{
    //@formatter:on
    /** @var array $mobileConfig mobile.config policy */
    protected $mobileConfig = [];
    /** @var null|\Bundle\Component\Goods\DefineGoods */
    private $_defineGoods = null;

    /**
     * init
     *
     * @throws \Exception
     */
    protected function init()
    {
        parent::init();
        $this->mobileConfig = ComponentUtils::getPolicy('mobile.config');
        $this->setNaverGradeCount();
    }

    /**
     * 등급에 맞는 네이버 EP생성 상품 최대수를 설정한다.
     */
    protected function setNaverGradeCount()
    {
        //등급설정값이 없다면 새싹으로 정의
        if (empty($this->config['naverGrade']) === true)
        {
            $naverGrade = '1';
        } else {
            $naverGrade = $this->config['naverGrade'];
        }

        //등급에 맞는 limit를 가지고 온다.
        $naverMaxLimit = $this->gatGradeMaxLimit($naverGrade);

        //max count를 재정의 한다.
        $this->setMaxCount($naverMaxLimit);
    }

    protected function loadConfig()
    {
        parent::loadConfig();
        $this->_defineGoods = \App::load('Component\\Goods\\DefineGoods');
    }

    /**
     * selectGoods
     *
     * @param int $goodsNo
     * @param int $sgoodsNo start goods no
     *
     * @return \Generator
     */

    protected function selectGoodsGenerator(int $goodsNo, int $sgoodsNo): \Generator
    {
        if (($goodsNo - $this->limit) < $sgoodsNo) {
            $startGoodsNo = $sgoodsNo;
        } else {
            $startGoodsNo = $goodsNo - $this->limit;
        }

        //상품혜택 사용여부
        $goodsBenefitUse = $this->componentBenefit->getConfig();

        $db = \App::getInstance('DB');
        if($goodsBenefitUse == 'y') {
            $db->strField = $this->getFieldsGoods() . ',' . $this->getFieldsGoodsLinkBenefit() . ", GROUP_CONCAT( gi.imageNo SEPARATOR '^|^') AS imageNo, GROUP_CONCAT( gi.imageKind SEPARATOR '^|^') AS imageKind, IF(gi.goodsImageStorage = 'obs', GROUP_CONCAT( gi.imageUrl SEPARATOR '^|^'), GROUP_CONCAT( gi.imageName SEPARATOR '^|^')) AS imageName, nb.naverbookIsbn, nb.naverbookGoodsType";
            $addJoin = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' AS gbl ON g.goodsNo = gbl.goodsNo  and ((gbl.benefitUseType=\'periodDiscount\' AND gbl.linkPeriodStart < NOW() AND gbl.linkPeriodEnd > NOW()) OR gbl.benefitUseType=\'nonLimit\' OR gbl.benefitUseType=\'newGoodsDiscount\')';
        }else{
            $db->strField = $this->getFieldsGoods() . ", GROUP_CONCAT( gi.imageNo SEPARATOR '^|^') AS imageNo, GROUP_CONCAT( gi.imageKind SEPARATOR '^|^') AS imageKind, IF(gi.goodsImageStorage = 'obs', GROUP_CONCAT( gi.imageUrl SEPARATOR '^|^'), GROUP_CONCAT( gi.imageName SEPARATOR '^|^')) AS imageName, nb.naverbookIsbn, nb.naverbookGoodsType";
        }
        $db->strWhere = implode(' AND ', $this->goodsWheres) . " AND g.goodsNo between " . $startGoodsNo . " AND " . $goodsNo;
        //        $db->strWhere = implode(' AND ', $this->goodsWheres);
        $db->strOrder = 'g.goodsNo DESC';
        $db->strGroup = "g.goodsNo";

        $db->strJoin = 'INNER JOIN es_goodsImage gi on gi.goodsNo = g.goodsNo AND  gi.imageKind IN ("magnify", "detail") AND gi.imageNo < 10' .$addJoin;

        // 도서 상품만 추출
        $db->strJoin .= ' LEFT JOIN es_naverBook nb on g.goodsNo = nb.goodsNo ';
        $db->strWhere .= ' AND IFNULL(nb.naverbookFlag, "n") = "y"';

        //        $db->strLimit = ($goodsNo * $this->offset) . ', ' . $this->offset;
        $this->goodsQuery = $db->query_complete();
        $strSQL = 'SELECT ' . array_shift($this->goodsQuery) . ' FROM ' . DB_GOODS . ' as g' . implode(' ', $this->goodsQuery);

        return $db->query_fetch_generator($strSQL);
    }

    /**
     * loadGoodsLinkCategory
     *
     * @return \Generator
     */
    protected function selectGoodsLinkCategoryGenerator(): \Generator
    {
        $db = \App::getInstance('DB');
        $strSQL = 'SELECT g.goodsNo,GROUP_CONCAT( glc.cateCd SEPARATOR "^|^") AS cateCd FROM ' . DB_GOODS . ' as g INNER JOIN ' . DB_GOODS_LINK_CATEGORY . ' glc on glc.goodsNo = g.goodsNo  LEFT JOIN ' . DB_NAVER_BOOK . ' nb on nb.goodsNo = g.goodsNo ' . $this->goodsQuery['where'] . ' GROUP BY g.goodsNo' . $this->goodsQuery['order'];

        return $db->query_fetch_generator($strSQL);
    }

    /**
     * makeDbUrl
     *
     * @param \Generator $goodsGenerator
     * @param int        $pageNumber
     *
     * @return bool
     * @throws \Exception
     */
    protected function makeDbUrl(\Generator $goodsGenerator, int $pageNumber): bool
    {
        $this->totalDbUrlPage++;
        $goodsGenerator->rewind();

        //상품혜택 진행중인 배열
        $goodsBenefitIngData = $this->componentBenefit->goodsBenefitIng();

        while ($goodsGenerator->valid()) {
            $result = [];
            if ($this->greaterThanMaxCount()) {
                break;
            }
            $goods = $goodsGenerator->current();
            $goodsGenerator->next();

            //상품 혜택 정보
            $goods = $this->componentBenefit->goodsDataFrontConvert($goods,$goodsBenefitIngData);

            if ($goods['goodsNmPartner']) {
                $goods['goodsNm'] = $goods['goodsNmPartner'];
            }
            if (StringUtils::strIsSet($goods['salesUnit'], 0) > $goods['minOrderCnt']) {
                $goods['minOrderCnt'] = $goods['salesUnit'];
            }

            $cateListNm = [];
            if ($goods['cateCd']) {
                if (empty($this->categoryStorage[$goods['cateCd']]) === true) {
                    $cateList = $this->componentCategory->getCategoriesPosition($goods['cateCd'])[0];
                    $this->categoryStorage[$goods['cateCd']] = $cateList;

                }
                $cateList = $this->categoryStorage[$goods['cateCd']];
                if ($cateList) {
                    $cateListNm = array_values($cateList);
                }
                unset($cateList);
            }

            $minOrderCnt = StringUtils::strIsSet($goods['minOrderCnt'], 1);
            $goodsPrice = $goods['goodsPrice'];

            //타임세일 판매가 추가
            if ($this->config['dcTimeSale'] == 'y' && $goodsPrice > 0 && gd_is_plus_shop(PLUSSHOP_CODE_TIMESALE) === true) {
                $timeSale = \App::load('\\Component\\Promotion\\TimeSale');
                $timeSaleInfo = $timeSale->getGoodsTimeSale($goods['goodsNo']);
                if ($timeSaleInfo) {
                    $truncPolicy = gd_policy('basic.trunc.goods'); // 절사 내용
                    $goodsPrice = gd_number_figure($goodsPrice - (($timeSaleInfo['benefit'] / 100) * $goodsPrice), $truncPolicy['unitPrecision'], $truncPolicy['unitRound']);
                    $goods['goodsPrice'] = $goodsPrice;
                }
            }

            if ($this->config['dcGoods'] == 'y' && $goods['goodsPrice'] > 0 && $goods['goodsDiscountFl'] == 'y') {
                $goodsPrice = $goodsPrice - $this->getGoodsDcPrice($goods);
            }

            $goodsPrice = $goodsPrice * $minOrderCnt;
            $deliveryPrice = $this->setDeliveryPrice($goods);

            $couponPrice = StringUtils::strIsSet($this->setGoodsCoupon($goods), 0);

            if ($this->config['dcCoupon'] == 'y' && $couponPrice > 0 && $goodsPrice - $couponPrice >= 0) {
                $goodsPrice = $goodsPrice - $couponPrice;
            } else {
                if ($goodsPrice - $couponPrice < 0) {
                    $goodsPrice = 0;
                }
                $couponPrice = 0;
            }

            $goodsImages = $this->getGoodsImageSrcWithAddImageSrc($goods);
            $goodsImageSrc = $goodsImages[0];

            if (empty($goodsImageSrc) || !is_numeric($deliveryPrice) || $goodsPrice <= 0 || empty($goods['goodsPriceString']) === false) {
                continue;
            }

            $result['id'] = $goods['goodsNo']; // [필수] 쇼핑몰 상품ID
            $result['goods_type'] = $goods['naverbookGoodsType']; // [필수] 상품 타입 지류도서: P E북: E 오디오북: A (반드시 대문자여야 함)
            $result['isbn'] = $goods['naverbookIsbn']; // [해당상품 필수] ISBN코드 (10자리 또는 11자리)
            $result['title'] = StringUtils::htmlSpecialCharsStripSlashes($goods['goodsNm']).'^^'; // [필수] 상품명
            $result['normal_price'] = (int)$goods['fixedPrice']; // [필수] 도서 원가 - 판매가 사용(옵션 별로 옵션 추가 금액 포함)
            $result['price_pc'] = (int)$goodsPrice; // [필수] 판매가 - ( 판매가 - 즉시할인금액 - 추가할인금액 - PC 발급가능한 최대상품쿠폰금액 )
            $result['link'] = 'http://' . $this->policy['basic']['info']['mallDomain'] . '/goods/goods_view.php?goodsNo=' . $goods['goodsNo'] . '&inflow=naver'; // [필수] 상품의 상세페이지 주소
            if ($this->mobileConfig['mobileShopFl'] == 'y') {
                $result['mobile_link'] = 'http://m.' . str_replace('www.', '', $this->policy['basic']['info']['mallDomain']) . '/goods/goods_view.php?goodsNo=' . $goods['goodsNo'] . '&inflow=naver' . chr(9); // [선택] 상품의 모바일 상세페이지 주소
            }
            $result['category_name1'] = StringUtils::strIsSet($cateListNm[0]); // [필수] 대분류 카테고리 코드
            if (StringUtils::strIsSet($cateListNm[1])) $result['category_name2'] = StringUtils::strIsSet($cateListNm[1]); // [선택] 중분류 카테고리 코드
            if (StringUtils::strIsSet($cateListNm[2])) $result['category_name3'] = StringUtils::strIsSet($cateListNm[2]); // [선택] 소분류 카테고리 코드
            if (StringUtils::strIsSet($cateListNm[3])) $result['category_name4'] = StringUtils::strIsSet($cateListNm[3]); // [선택] 세분류 카테고리 코드
            $result['image_link'] = $goodsImageSrc; // [필수] 큰 커버 이미지 경로
            $result['shipping'] = (int)$deliveryPrice; // [필수] 배송비

            $result = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->writeDbUrl($result);
            $this->totalDbUrlData++;

        }

        return true;
    }

    /**
     * 이미지 경로, 추가 이미지 경로 반환
     *
     * @param array $goods
     *
     * @return array
     * @throws \Exception
     */
    protected function getGoodsImageSrcWithAddImageSrc($goods)
    {
        $addImage = [];
        $addImageSrc = $imageSrc = "";
        if ($goods['imageName']) {
            $names = explode(STR_DIVISION, $goods['imageName']);
            $kinds = explode(STR_DIVISION, $goods['imageKind']);
            $numbers = explode(STR_DIVISION, $goods['imageNo']);

            foreach ($names as $imageNameKey => $imageName) {
                $image = $this->getGoodsImage($imageName, $goods['imagePath'], $goods['imageStorage']);
                if ($kinds[$imageNameKey] == 'magnify' && $numbers[$imageNameKey] == 0) {
                    $imageSrc = $image;
                } else {
                    $addImage[] = $image;
                }
                unset($image);
            }
            unset($names, $kinds, $numbers);

            if (empty($imageSrc) === true) {
                $imageSrc = $addImage[0];
                unset($addImage[0]);
            }

            if ($addImage) {
                $addImageSrc = implode("|", array_slice($addImage, 0, 10));
            }
        }

        unset($addImage);

        return [
            $imageSrc,
            $addImageSrc,
        ];
    }

    /**
     * getOnlyAdultFl
     *
     * @param array $goods
     *
     * @return string
     */
    protected function getOnlyAdultFl(array $goods)
    {
        $flag = "";
        if ($goods['onlyAdultFl'] == 'y') {
            $flag = "Y";
        }

        return $flag;
    }
}
