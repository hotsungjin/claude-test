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
 * @author  yjwee <yeongjong.wee@godo.co.kr>
 */
class NaverDbUrl3 extends \Component\Worker\NaverDbUrl
{
    /** @var array $header naver dburl 3.0 header */
    //@formatter:off
    protected $header = ['id','title','price_pc','normal_price','link','image_link','add_image_link','category_name1','category_name2','category_name3','category_name4','naver_category','naver_product_id','condition','import_flag','parallel_import','order_made','product_flag','rental_info','adult','brand','maker','origin','event_words','partner_coupon_download','interest_free_event',' point','installation_costs','search_tag','minimum_purchase_quantity','review_count','shipping','delivery_grade','delivery_detail','attribute','age_group','gender','npay_unable','npay_unable_acum','brand_certification','shipping_settings'];
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

        // 조건에 따른 header 추가
        // 쿠폰적용 조건을 사용하는 경우 benefit_price 헤더 추가
        if ($this->config['dcCoupon'] === 'y') {
            $this->header[] = 'benefit_price';
        }

        $this->mobileConfig = ComponentUtils::getPolicy('mobile.config');
        $naverHeader = $this->header;
        if ($this->mobileConfig['mobileShopFl'] == 'y') {
            array_splice($naverHeader, 5, 0, 'mobile_link');
        }

        $this->setNaverGradeCount();

        $this->writeDbUrl(implode(chr(9), $naverHeader));
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
            $db->strField = $this->getFieldsGoods() . ',' . $this->getFieldsGoodsLinkBenefit() . ", GROUP_CONCAT( gi.imageNo SEPARATOR '^|^') AS imageNo, GROUP_CONCAT( gi.imageKind SEPARATOR '^|^') AS imageKind, IF(gi.goodsImageStorage = 'obs', GROUP_CONCAT( gi.imageUrl SEPARATOR '^|^'), GROUP_CONCAT( gi.imageName SEPARATOR '^|^')) AS imageName";
            $addJoin = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' AS gbl ON g.goodsNo = gbl.goodsNo  and ((gbl.benefitUseType=\'periodDiscount\' AND gbl.linkPeriodStart < NOW() AND gbl.linkPeriodEnd > NOW()) OR gbl.benefitUseType=\'nonLimit\' OR gbl.benefitUseType=\'newGoodsDiscount\')';
        }else{
            $db->strField = $this->getFieldsGoods() . ", GROUP_CONCAT( gi.imageNo SEPARATOR '^|^') AS imageNo, GROUP_CONCAT( gi.imageKind SEPARATOR '^|^') AS imageKind, IF(gi.goodsImageStorage = 'obs', GROUP_CONCAT( gi.imageUrl SEPARATOR '^|^'), GROUP_CONCAT( gi.imageName SEPARATOR '^|^')) AS imageName";
        }
        $addJoin .= ' LEFT JOIN ' . DB_GOODS_NAVER . ' AS gn ON g.goodsNo = gn.goodsNo ';
        $addJoin .= ' LEFT JOIN ' . DB_GOODS_DELIVERY_SCHEDULE . ' AS gds ON g.goodsNo = gds.goodsNo ';
        $db->strField .= ' ,gn.naverProductFlagRentalPay, gn.naverProductFlagRentalPeriod, gn.shoppingPcRentalPay, gn.shoppingTotalRentalPay, gn.naverMonthlyPointRate';
        $db->strField .= ' ,gds.deliveryScheduleFl, gds.deliveryScheduleType, gds.deliveryScheduleTime';
        $db->strWhere = implode(' AND ', $this->goodsWheres) . " AND g.goodsNo between " . $startGoodsNo . " AND " . $goodsNo;
        //        $db->strWhere = implode(' AND ', $this->goodsWheres);
        $db->strOrder = 'g.goodsNo DESC';
        $db->strGroup = "g.goodsNo";

        $db->strJoin = 'INNER JOIN es_goodsImage gi on gi.goodsNo = g.goodsNo AND  gi.imageKind IN ("magnify", "detail") AND gi.imageNo < 10' .$addJoin;

        // 도서 제외
        $db->strJoin .= ' LEFT JOIN es_naverBook nb on g.goodsNo = nb.goodsNo ';
        $db->strWhere .= " AND IFNULL(nb.naverbookFlag, 'n') != 'y' ";

        //        $db->strLimit = ($goodsNo * $this->offset) . ', ' . $this->offset;
        $this->goodsQuery = $db->query_complete();
        $strSQL = 'SELECT ' . array_shift($this->goodsQuery) . ' FROM ' . DB_GOODS . ' as g' . implode(' ', $this->goodsQuery);

        return $db->query_fetch_generator($strSQL);
    }

    /**
     * selectGoodsLinkCategoryGenerator
     *
     * @return \Generator
     */
    protected function selectGoodsLinkCategoryGenerator(): \Generator
    {
        $db = \App::getInstance('DB');
        $strSQL = 'SELECT g.goodsNo,GROUP_CONCAT( glc.cateCd SEPARATOR "^|^") AS cateCd FROM ' . DB_GOODS . ' as g INNER JOIN ' . DB_GOODS_LINK_CATEGORY . ' glc on glc.goodsNo = g.goodsNo  LEFT JOIN ' . DB_NAVER_BOOK . ' nb on nb.goodsNo = g.goodsNo ' . $this->goodsQuery['where'] . ' GROUP BY g.goodsNo' . $this->goodsQuery['order'];

        return $db->query_fetch_generator($strSQL);
    }

    protected function selectSslConfigGenerator()
    {
        $db = \App::getInstance('DB');
        $strSQL = 'SELECT sslConfigDomain, sslConfigUse, sslConfigPosition  FROM ' . DB_SSL_CONFIG . ' WHERE sslConfigUse = "y" AND sslConfigMallFl = "kr" ';

        return $db->query_fetch($strSQL);
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

        //보안서버 설정값
        $arrSslConfigPosition = [
            'pc' => 'http',
            'mobile' => 'http',
        ];

        $arrSslConfigUse = $this->selectSslConfigGenerator();
        foreach ($arrSslConfigUse as $arrSslConfigUseKey => $arrSslConfigUseVal) {
            $arrSslConfigPosition[$arrSslConfigUseVal['sslConfigPosition']] = $arrSslConfigUseVal['sslConfigUse'] == 'y' ? 'https' : 'http';
        }

        while ($goodsGenerator->valid()) {
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
            $goodsPrice = $originalGoodsPrice = $goods['goodsPrice'];
            $originalGoodsPrice *= $minOrderCnt;

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
            $mileage = $this->setGoodsMileage($goods);
            $goodsPrice = $goodsPrice * $minOrderCnt;
            $deliveryPrice = $this->setDeliveryPrice($goods);

            $couponPrice = StringUtils::strIsSet($this->setGoodsCoupon($goods, 'pc'), 0);
            if ($couponPrice == 0) { // 적용 가능 pc 및 pc+mobile 쿠폰이 없는 경우 mobile 쿠폰 적용 
                $couponPrice = StringUtils::strIsSet($this->setGoodsCoupon($goods, 'mobile'), 0);
            }

            if ($couponPrice > 0) { // 적용 가능한 쿠폰이 있는 경우 
                // 쿠폰 적용 최대 할인 가격
                $benefitPrice = $goodsPrice - $couponPrice;
                // 쿠폰 다운로드 여부
                $partnerCouponDownFl = 'Y';

                // 쿠폰가가 0 이하이면, 상품가로 대체
                if ($benefitPrice <= 0) {
                    $benefitPrice = $goodsPrice;
                }
            } else {
                $benefitPrice = $goodsPrice;
                $partnerCouponDownFl = 'N';
            }

            $goodsImages = $this->getGoodsImageSrcWithAddImageSrc($goods);
            $goodsImageSrc = $goodsImages[0];
            $goodsAddImageSrc = $goodsImages[1];

            if (empty($goodsImageSrc) || !is_numeric($deliveryPrice) || $goodsPrice <= 0 || empty($goods['goodsPriceString']) === false) {
                continue;
            }

            if ($arrSslConfigPosition['pc'] == 'https') {
                $checkGoodsImageSrc = filter_Var($goodsImageSrc, FILTER_VALIDATE_URL);

                if ($checkGoodsImageSrc && strpos(strtolower($goodsImageSrc), 'http://') !== false) {
                    $goodsImageSrc = preg_replace("(^http?://)", "", $goodsImageSrc);
                    $goodsImageSrc = $arrSslConfigPosition['pc'] . '://' . $goodsImageSrc;
                }

                $arrGoodsAddImageSrc = explode('|', $goodsAddImageSrc);
                $tmpGoodsAddImageSrc = [];

                foreach ($arrGoodsAddImageSrc as $arrKey => $arrValue) {
                    $checkGoodsAddImageSrc = filter_Var($arrValue, FILTER_VALIDATE_URL);
                    if ($checkGoodsAddImageSrc && strpos(strtolower($checkGoodsAddImageSrc), 'http://') !== false) {
                        $tmpGoodsAddImageSrc[] = preg_replace("(^http?://)", $arrSslConfigPosition['pc'] . '://', $checkGoodsAddImageSrc);
                    }
                }

                if (!empty($tmpGoodsAddImageSrc)) {
                    $goodsAddImageSrc = implode('|', $tmpGoodsAddImageSrc);
                }
                unset($tmpGoodsAddImageSrc);
            }

            $goodsMustInfo = $this->getGoodsMustInfo($goods);
            $installationCosts = $goodsMustInfo[0];
            $deliveryGrade = $goodsMustInfo[1];
            $deliveryDetail = $goodsMustInfo[2];

            $isRental = $goods['naverProductFlag'] == 'r';
            if($isRental) {
                $rentalDependent = [
                    'pricePc' => NumberUtils::moneyFormat($goods['shoppingPcRentalPay'], false).chr(9) ,
                    'rentalInfo' => NumberUtils::moneyFormat($goods['shoppingTotalRentalPay'], false).'^'.$goods['naverProductFlagRentalPeriod'].'^'.gd_isset(gd_policy('member.mileageBasic')['name'], '마일리지').'^'.$goods['naverMonthlyPointRate'].chr(9),
                ];
            } else {
                $rentalDependent = [
                    'pricePc' => NumberUtils::moneyFormat($goodsPrice, false).chr(9),
                    'rentalInfo' => ''.chr(9)
                ];
            }

            $result = '';
            $result .= $goods['goodsNo'] . chr(9);
            $this->selectCategoryBrand($goods);
            $goods['brandNm'] = $this->brandStorage[$goods['brandCd']];
            $goods['goodsNm'] = $this->replaceGoodsName($goods);
            $result .= StringUtils::htmlSpecialCharsStripSlashes($goods['goodsNm']) . chr(9);

            $eventDesc = $this->getEventDescription($goods);

            $result .= $rentalDependent['pricePc'];

            $result .= NumberUtils::moneyFormat($goods['fixedPrice'] * $minOrderCnt, false) . chr(9);
            $result .= $arrSslConfigPosition['pc'] . '://' . $this->policy['basic']['info']['mallDomain'] . '/goods/goods_view.php?goodsNo=' . $goods['goodsNo'] . '&inflow=naver' . chr(9); // [필수] 상품의 상세페이지 주소
            if ($this->mobileConfig['mobileShopFl'] == 'y') {
                $result .= $arrSslConfigPosition['mobile'] . '://m.' . str_replace('www.', '', $this->policy['basic']['info']['mallDomain']) . '/goods/goods_view.php?goodsNo=' . $goods['goodsNo'] . '&inflow=naver' . chr(9); // [필수] 상품의 모바일페이지 주소
            }

            $result .= $goodsImageSrc . chr(9); // [필수] 이미지 URL
            $result .= $goodsAddImageSrc . chr(9); // [필수] 이미지 URL

            for ($i = 0; $i < 4; $i++) {
                $result .= StringUtils::strIsSet($cateListNm[$i]) . chr(9); // [필수] 대분류 카테고리 코드
            }
            unset($cateListNm);

            switch ($goods['naverImportFlag']) {
                case 'f':
                    $importFlag = "Y";
                    $parallelImport = "";
                    $orderMade = "";
                    break;
                case 'd':
                    $importFlag = "";
                    $parallelImport = "Y";
                    $orderMade = "";
                    break;
                case 'o':
                    $importFlag = "";
                    $parallelImport = "";
                    $orderMade = "Y";
                    break;
                default:
                    $importFlag = "";
                    $parallelImport = "";
                    $orderMade = "";
                    break;
            }

            if($goods['naverNpayAble'] == 'all') $naverNpayAble = null;
            else if($goods['naverNpayAble'] == 'pc') $naverNpayAble = '^Y';
            else if($goods['naverNpayAble'] == 'mobile') $naverNpayAble = 'Y^';
            else if($goods['naverNpayAble'] == 'no') $naverNpayAble = 'Y^Y';

            if($goods['naverNpayAcumAble'] == 'all') $naverNpayAcumAble = null;
            else if($goods['naverNpayAcumAble'] == 'pc') $naverNpayAcumAble = '^Y';
            else if($goods['naverNpayAcumAble'] == 'mobile') $naverNpayAcumAble = 'Y^';
            else if($goods['naverNpayAcumAble'] == 'no') $naverNpayAcumAble = 'Y^Y';

            $naverBrandCertification = \App::load('\\Component\\Goods\\NaverBrandCertification');
            $certInfo = $naverBrandCertification->getCertFl($goods['goodsNo']);
            gd_isset($certInfo['brandCertFl'], 'n');

            $brandCertData = $certInfo['brandCertFl'];
            if ($brandCertData == 'y') $brandCertData = 'Y';
            else if ($brandCertData == 'n') $brandCertData = null;

            $onlyAdultFl = $this->getOnlyAdultFl($goods);

            // 배송부가정보 생성
            $shippingSettings = $this->getShippingSettings($goods, $deliveryPrice);

            // $result 시작
            $result .= StringUtils::strIsSet($goods['naverCategory']) . chr(9); // 네이버카테고리
            $result .= StringUtils::strIsSet($goods['naverProductId']) . chr(9); // 가격비교페이지ID
            $result .= $this->_defineGoods->getGoodsStateList()[$goods['goodsState']] . chr(9); // 상품상태
            $result .= $importFlag . chr(9); // 해외구매대행여부
            $result .= $parallelImport . chr(9); // 병행수입여부
            $result .= $orderMade . chr(9); // 주문제작상품여부
            $result .= $this->_defineGoods->getGoodsSellType()[$goods['naverProductFlag']] . chr(9); // 판매방식구분
            $result .= $rentalDependent['rentalInfo']; // 렌탈 필수 정보
            $result .= $onlyAdultFl . chr(9); // 미성년자구매불가상품여부

            $result .= StringUtils::strIsSet($goods['brandNm']) . chr(9); // [선택] 모델명
            $result .= StringUtils::strIsSet($goods['makerNm']) . chr(9); // [선택] 모델명
            $result .= StringUtils::strIsSet($goods['originNm']) . chr(9); // [선택] 모델명
            $result .= $eventDesc . chr(9); // [선택] 이벤트문구

            $result .= StringUtils::strIsSet($partnerCouponDownFl). chr(9); // [선택] 쿠폰다운로드필요 여부

            $result .= $this->config['nv_pcard'] . chr(9); // [선택] 무이자
            if ($mileage > 0) {
                $result .= '쇼핑몰자체포인트^' . $mileage . chr(9); // [선택] 마일리지
            } else {
                $result .= '' . chr(9); // [선택] 마일리지
            }
            $result .= StringUtils::strIsSet($installationCosts) . chr(9); //추가설치비용
            $goods['naverTag'] = implode("|", array_slice(explode("|", $goods['naverTag']), 0, 10));
            $result .= StringUtils::strIsSet($goods['naverTag']) . chr(9); //기본정보-검색키워드 항목 사용
            $result .= $minOrderCnt . chr(9); //판매정보-구매수량설정 항목 사용

            // 두레이 2279 : 크리마리뷰 네이버EP 생성 조건 변경 (크리마리뷰 사용시 상품테이블의 카운트로 EP 생성)
            $crema = \App::load('Component\\Service\\Crema');

            $totalcnt = 0;
            if($this->config['onlyMemberReviewUsed'] === "y" && $crema->getUseEpFl() != 'y') {
                $totalcnt = StringUtils::strIsSet($this->goodsReviewCnt($goods['goodsNo']), 0) . chr(9);
            } else {
                $totalcnt = StringUtils::strIsSet($this->getNaverReviewCount($goods), 0) . chr(9);
            }
            $result .= $totalcnt;

            $result .= StringUtils::strIsSet($deliveryPrice) . chr(9); // [선택] 배송비
            $result .= StringUtils::strIsSet($deliveryGrade) . chr(9); // 배송 · 설치비용
            $result .= StringUtils::strIsSet($deliveryDetail) . chr(9); // 배송 · 설치비용 내용
            $result .= StringUtils::strIsSet($goods['naverAttribute']) . chr(9); //추가 : 입력기능 추가
            $result .= $this->_defineGoods->getGoodsAgeType()[StringUtils::strIsSet($goods['naverAgeGroup'], 'a')] . chr(9); //(주 사용 연령대)
            $result .= $this->_defineGoods->getGoodsGenderType()[$goods['naverGender']] .  chr(9); //(주 사용 성별)
            $result .= gd_isset($naverNpayAble) .  chr(9); //네이버페이 사용불가
            $result .= gd_isset($naverNpayAcumAble) .  chr(9); //네이버페이 적립불가
            $result .= gd_isset($brandCertData) .  chr(9); // 브랜드 인증상품 여부
            $result .= gd_isset($shippingSettings); // [선택] 배송부가정보

            if ($this->config['dcCoupon'] === 'y') {
                $result .= chr(9) . NumberUtils::moneyFormat($benefitPrice, false); // [선택] 최대 할인 가격
            }

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

    /**
     * 배송부가정보 생성
     * 배송일정 사용이 'y'이고 배송일정 타입이 'time'인 경우에만 생성
     *
     * @param array $goods 상품 정보
     * @param int   $deliveryPrice 배송비
     *
     * @return string|null
     * @throws \Exception
     */
    protected function getShippingSettings(array $goods, int $deliveryPrice)
    {
        $defaultResult = str_repeat('^', 11);

        // 배송일정 사용이 'y'이고 배송일정 타입이 'time'인 경우에만 생성
        if (empty($goods['deliveryScheduleFl']) || $goods['deliveryScheduleFl'] !== 'y') {
            // 값이 없어도 구분자(^) 11개 반환 (네이버 EP 규격)
            return $defaultResult;
        }

        if (empty($goods['deliveryScheduleType']) || $goods['deliveryScheduleType'] !== 'time') {
            // 값이 없어도 구분자(^) 11개 반환 (네이버 EP 규격)
            return $defaultResult;
        }

        // 배송비 조건 정보 가져오기
        if (empty($goods['deliverySno'])) {
            // 값이 없어도 구분자(^) 11개 반환 (네이버 EP 규격)
            return $defaultResult;
        }

        $this->addDeliveryStorage($goods['deliverySno']);
        $deliveryData = $this->deliveryStorage[$goods['deliverySno']];

        if (empty($deliveryData)) {
            // 값이 없어도 구분자(^) 11개 반환 (네이버 EP 규격)
            return $defaultResult;
        }

        // 배송유형: '오늘출발'
        $deliveryType = '오늘출발';

        // 주문마감시간: 네이버EP 요구스펙은 'hh:mm' 임. deliveryScheduleTime 값은 이미 'hh:mm' 형식으로 들어오기 때문에 값 여부만 검증
        $orderDeadline = StringUtils::strIsSet($goods['deliveryScheduleTime'], '');
        if (empty($orderDeadline)) {
            // 값이 없어도 구분자(^) 11개 반환 (네이버 EP 규격)
            return $defaultResult;
        }

        // 배송방법: 배송비 조건의 배송방식에 따라 매핑
        $deliveryMethod = $this->getDeliveryMethodForNaver($deliveryData['basic']['deliveryMethodFl']);

        // 택배사: null
        $deliveryCompany = null;

        // 방문수령가능여부: 배송방식에 '방문수령'이 있는 경우 'Y', 없으면 null
        $visitAvailable = $this->checkDeliveryMethodExists($deliveryData['basic']['deliveryMethodFl'], 'visit') ? 'Y' : null;

        // 퀵서비스가능여부: 배송방식에 '퀵배송'이 있는 경우 'Y', 없으면 null
        $quickAvailable = $this->checkDeliveryMethodExists($deliveryData['basic']['deliveryMethodFl'], 'quick') ? 'Y' : null;

        // 반품/교환배송비: 고도몰 내에 반품/교환배송비를 따로 정하는 기능이 없으므로 null로 설정
        $returnDeliveryPrice = null;
        $exchangeDeliveryPrice = null;

        // 예상배송일, 평균배송일, 발송불가요일, 배송지역정보: null 로 표기하기 (정책)
        $expectedDeliveryDate = null;
        $averageDeliveryDate = null;
        $unavailableDays = null;
        $deliveryAreaInfo = null;

        // 구분자(^)로 연결하여 반환 (총 11개 구분자)
        // null 값은 빈 문자열로 변환하여 구분자만 남도록 처리
        $shippingSettings = implode('^', [
            $deliveryType,
            $orderDeadline,
            $deliveryMethod,
            $deliveryCompany,
            $visitAvailable,
            $quickAvailable,
            $returnDeliveryPrice,
            $exchangeDeliveryPrice,
            $expectedDeliveryDate,
            $averageDeliveryDate,
            $unavailableDays,
            $deliveryAreaInfo,
        ]);

        return $shippingSettings;
    }

    /**
     * 배송방식을 네이버 EP 형식으로 변환
     *
     * @param string $deliveryMethodFl 배송방식 (STR_DIVISION으로 구분된 문자열)
     *
     * @return string|null
     */
    protected function getDeliveryMethodForNaver(string $deliveryMethodFl)
    {
        if (empty($deliveryMethodFl)) {
            return null;
        }

        // 배송방식 배열로 변환
        $deliveryMethodArr = array_values(array_filter(explode(STR_DIVISION, $deliveryMethodFl)));

        if (empty($deliveryMethodArr)) {
            return null;
        }

        // 우선순위: 택배 > 등기/소포 > 화물배송 (네이버EP용 표기는 각 택배 / 소포,등기 / 화물택배)
        // 배송방식 매핑
        $priority = ['delivery', 'packet', 'cargo'];
        foreach ($priority as $method) {
            if (in_array($method, $deliveryMethodArr)) {
                switch ($method) {
                    case 'delivery':
                        return '택배';
                    case 'packet':
                        return '소포,등기';
                    case 'cargo':
                        return '화물택배';
                }
            }
        }

        // 우선순위에 없는 배송방법인 경우엔 null로 리턴
        return null;
    }

    /**
     * 배송방식에 특정 방식이 포함되어 있는지 확인
     *
     * @param string $deliveryMethodFl 배송방식 (STR_DIVISION으로 구분된 문자열)
     * @param string $method           확인할 배송방식
     *
     * @return bool
     */
    protected function checkDeliveryMethodExists(string $deliveryMethodFl, string $method): bool
    {
        if (empty($deliveryMethodFl)) {
            return false;
        }

        $deliveryMethodArr = array_values(array_filter(explode(STR_DIVISION, $deliveryMethodFl)));

        return in_array($method, $deliveryMethodArr);
    }
}
