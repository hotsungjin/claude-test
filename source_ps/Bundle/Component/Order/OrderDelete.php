<?php

namespace Bundle\Component\Order;

use App;
use Request;
use Session;
use Globals;
use Component\Database\DBTableField;
use Component\Mall\MallDAO;
use Component\Member\Manager;
use Framework\Utility\ArrayUtils;

class OrderDelete
{
    protected $_db;

    private $_logger;

    /**
     * @var array 쿼리 조건 바인딩
     */
    protected $arrBind = [];

    /**
     * @var array 리스트 검색 조건
     */
    protected $arrWhere = [];

    /**
     * @var array 검색
     */
    protected $search = [];

    /**
     * @var array 주문상태 기본정책
     */
    public $statusPolicy = [];

    /**
     * @var boolean 결제수단체크
     */
    protected $isSettleKind = false;

    /**
     * @var array 결제방법
     */
    public $settleKind = [];

    protected $orderGoodsOrderBy = 'og.orderNo desc, og.scmNo asc, og.orderDeliverySno asc, og.goodsDeliveryCollectFl asc, og.deliveryMEthodFl asc, og.regDt desc, og.orderCd asc';


    /**
     * 내부에서 사용하기 위해 만들어진 결제수단 코드 (마일리지/예치금)
     */
    const SETTLE_KIND_ZERO = 'gz'; // 0원으로 결제된 경우
    const SETTLE_KIND_MILEAGE = 'gm'; // 마일리지 사용
    const SETTLE_KIND_DEPOSIT = 'gd'; // 예치금 사용
    const SETTLE_KIND_REST = 'gr'; // 기타. 네이버페이에서 "나중에결제"로 결제된 경우
    const SETTLE_KIND_FINTECH_UNKNOWN = 'fu'; // 페이코/네이버페이에서 상품상세에서 주문시 결제수단을 알수 없는 경우

    /**
     * 생성자
     */
    public function __construct()
    {
        if (!is_object($this->_db)) {
            $this->_db = App::load('DB');
        }

        $this->_logger = App::getInstance('logger');

        $dbUrl = App::load('\\Component\\Marketing\\DBUrl');
        $this->delivery = App::load('\\Component\\Delivery\\Delivery');
        $this->orderStatus = $this->getAllOrderStatus();
        $this->paycoConfig = $dbUrl->getConfig('payco', 'config');
        $this->useMyapp = gd_policy('myapp.config')['useMyapp']; // 마이앱 사용유무
        $this->couponConfig = gd_policy('coupon.config');
    }

    /**
     * 특정 주문 상태만 출력
     *
     * @param array $statusCodeArray _getOrderStatus 의 return 값
     * @param array $includeStatusCode orderStatus 의 앞 문자만
     *
     * @return string 주문 상태 출력
     */
    public function getIncludeOrderStatus($statusCodeArray, $includeStatusCode = null)
    {
        if (gd_isset($statusCodeArray)) {
            foreach ($statusCodeArray as $key => $val) {
                $compareCode = substr($key, 0, 1);
                if (!in_array($compareCode, $includeStatusCode)) {
                    unset($statusCodeArray[$key]);
                }
            }
        }

        return $statusCodeArray;
    }

    /**
     * 모든 주문 상태 출력
     *
     * @return mixed 주문 상태 혹은 리스트 전체 출력
     */
    protected function getAllOrderStatus()
    {
        foreach ($this->statusPolicy as $key => $val) {
            if ($key == 'autoCancel') {
                continue;
            }
            foreach ($val as $oKey => $oVal) {
                if (strlen($oKey) != 2) {
                    continue;
                }
                $codeArr[$oKey] = $oVal['admin'];
            }
        }

        return gd_isset($codeArr);
    }

    /**
     * 네이버페이 환경설정 가져오기
     *
     * @param null $key
     * @return array
     */
    protected function getNaverPayConfig($key = null)
    {
        if (empty($this->naverpayConfig)) {
            $this->naverpayConfig = gd_policy('naverPay.config');
        }
        if ($key) {
            return $this->naverpayConfig[$key];
        }
        return $this->naverpayConfig;
    }

    /**
     * 주문 상태 출력를 출력하며 주문정책에서 사용여부를 n으로 설정한 경우 출력 안됨)
     *
     * @param string $statusCode 주문상태 코드
     * @param string $statusMode 모드 ('admin','user')
     *
     * @return mixed 주문 상태 혹은 리스트 전체 출력
     */
    protected function _getOrderStatus($statusCode = null, $statusMode = 'user')
    {
        if ($statusMode != 'user') {
            $statusMode = 'admin';
        }
        foreach ($this->statusPolicy as $key => $val) {
            if ($key == 'autoCancel') {
                continue;
            }
            foreach ($val as $oKey => $oVal) {
                if (strlen($oKey) != 2 || ($statusCode === null && $oVal['useFl'] != 'y')) {
                    continue;
                }
                if ($statusCode === null) {
                    $codeArr[$oKey] = $oVal[$statusMode];
                } elseif ($oKey == $statusCode) {
                    $codeArr = $oVal[$statusMode];
                    break;
                }
            }
        }
        if ($this->channel == 'naverpay' && is_array($codeArr)) {
            //내부 반폼보류 삭제(네이버페로 대체)
            //발송지연
            //반품거부
            //반품보류
            //반품보류해제
            if (array_key_exists('b3', $codeArr)) {
                unset($codeArr['b3']);
            }
            if (array_key_exists('e3', $codeArr)) {
                unset($codeArr['e3']);
            }
            if (array_key_exists('e4', $codeArr)) {
                unset($codeArr['e4']);
            }
            $codeArr = array_merge($codeArr, (array)$this->getNaverPayClaimStatus());
        }

        return gd_isset($codeArr);
    }

    /**
     * 주문 상태 출력 (관리자 모드용)
     *
     * @param string $statusCode 주문상태 코드
     *
     * @return string 주문 상태 출력
     */
    public function getOrderStatusAdmin($statusCode = null)
    {
        return $this->_getOrderStatus($statusCode, 'admin');
    }

    /**
     * 결제 방법 정렬
     *
     * @param array $getData 결제정보
     * @author artherot
     */
    protected function sortSettleKind(&$getData)
    {
        $sort['gb'] = 1; // 무통장입금
        $sort['gz'] = 2; // 전액결제
        $sort['gm'] = 3; // 마일리지
        $sort['gd'] = 4; // 예치금
        $sort['pc'] = 5; // 카드결제
        $sort['pb'] = 6; // 계좌이체
        $sort['pv'] = 7; // 가상계좌
        $sort['ph'] = 8; // 핸드폰결제
        $sort['ec'] = 9; // 계좌이체
        $sort['eb'] = 10; // 계좌이체
        $sort['ev'] = 11; // 가상계좌
        $sort['fa'] = 12; // 무통장입금
        $sort['fc'] = 13; // 계좌이체
        $sort['fb'] = 14; // 계좌이체
        $sort['fv'] = 15; // 가상계좌
        $sort['fh'] = 16; // 핸드폰결제
        $sort['pn'] = 17; // 네이버페이
        $sort['pk'] = 18; // 카카오페이
        $sort['fp'] = 19; // 포인트결제
        $sort['op'] = 20; // PAYPAL
        $sort['ov'] = 21; // VISA / MASTER
        $sort['oj'] = 22; // JCB / AMEX
        $sort['oa'] = 23; // ALIPAY
        $sort['ot'] = 24; // TENPAY
        $sort['ou'] = 25; // UNIONPAY
        $sort['gr'] = 26; // 기타
        // 정렬 번호 추가
        foreach ($getData as $key => $val) {
            $getData[$key]['sort'] = $sort[$key];
        }
        // sort 에 의한 정렬
        ArrayUtils::subKeySort($getData, 'sort', true);
        // sort 삭제
        foreach ($getData as $key => $val) {
            if (isset($getData[$key]['sort'])) {
                unset($getData[$key]['sort']);
            }
        }
    }

    /**
     * 결제 방법 출력
     *
     * @param string $settleKind 결제방법 코드
     *
     * @return array  결제방법
     * @author artherot
     *
     */
    public function getSettleKind($settleKind = null)
    {
        // 정책에서 결제수단 가져오기
        $getData = gd_policy('order.settleKind', 1);
        // 전액할인 추가
        $getData[self::SETTLE_KIND_ZERO] = [
            'name' => __('전액할인'),
            'mode' => 'general',
            'useFl' => 'y',
        ];
        // 예치금 추가 (리스트 검색테이블)
        $getDeposit = gd_policy('member.depositConfig');
        $getData[self::SETTLE_KIND_DEPOSIT] = [
            'name' => $getDeposit['name'],
            'mode' => 'general',
            'useFl' => $getDeposit['payUsableFl'],
        ];
        // 마일리지 추가 (리스트 검색테이블)
        $getMileage = Globals::get('gSite.member.mileageBasic');
        $getData[self::SETTLE_KIND_MILEAGE] = [
            'name' => $getMileage['name'],
            'mode' => 'general',
            'useFl' => 'y',
        ];
        // 기타 추가
        $getData[self::SETTLE_KIND_REST] = [
            'name' => __('기타'),
            'mode' => 'general',
            'useFl' => 'y',
        ];
        // 결제수단에 추가된 모바일PG 설정 화면에 출력되지 않도록 unset 처리
        unset($getData['mobilePgConfFl']);

        // 순서에 따른 정렬
        $this->sortSettleKind($getData);
        if (is_null($settleKind)) {
            return $getData;
        } else {
            foreach ($getData as $key => $val) {
                if ($key == $settleKind) {
                    return $getData[$key]['name'];
                }
            }

            return false;
        }
    }

    /**
     * 결제 방법 출력
     *
     * @param string $settleKind 결제방법 코드
     *
     * @return array 결제방법
     */
    public function printSettleKind($settleKind)
    {
        if ($this->isSettleKind === false) {
            $this->settleKind = $this->getSettleKind();
            $this->isSettleKind = true;
        }
        foreach ($this->settleKind as $key => $val) {
            if ($key == $settleKind) {
                return $this->settleKind[$key]['name'];
            }
        }
    }

    /**
     * 주문 사은품 정보 출력
     *
     * @param array $orderNo 주문 번호
     * @param null $scmNo 공급사 번호
     * @param intger|int $imageSize 사은품 이미지 사이즈
     *
     * @return array 해당 주문 사은품 정보
     */
    public function getOrderGift($orderNo, $scmNo = null, $imageSize = 50)
    {
        $strWhere[] = 'og.orderNo = ?';
        $this->_db->bind_param_push($arrBind, 's', $orderNo);
        // 공급사 번호가 있으면 해당 공급사의 사은품만 출력
        if (Manager::isProvider() && $scmNo !== null) {
            $strWhere[] = 'og.scmNo = ?';
            $this->_db->bind_param_push($arrBind, 'i', $scmNo);
        }
        $join[] = ' LEFT JOIN ' . DB_GIFT_PRESENT . ' gp ON og.presentSno = gp.sno ';
        $join[] = ' LEFT JOIN ' . DB_GIFT . ' g ON og.giftNo = g.giftNo ';
        $arrExcludeOg = [
            'orderNo',
            'presentSno',
        ];
        $arrIncludeG = [
            'giftNm',
            'giftCd',
            'giftDescription',
        ];
        $arrIncludeGp = [
            'presentTitle',
        ];
        $arrFieldOg = DBTableField::setTableField('tableOrderGift', null, $arrExcludeOg, 'og');
        $arrFieldG = DBTableField::setTableField('tableGift', $arrIncludeG, null, 'g');
        $arrFieldGp = DBTableField::setTableField('tableGiftPresent', $arrIncludeGp, null, 'gp');
        $this->_db->strField = 'og.sno, ' . implode(', ', $arrFieldOg) . ', ' . implode(', ', $arrFieldG) . ', ' . implode(', ', $arrFieldGp);
        $this->_db->strWhere = implode(' AND ', $strWhere);
        $this->_db->strJoin = implode('', $join);
        $this->_db->strOrder = 'og.sno ASC';
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GIFT . ' og ' . implode(' ', $query);
        $getData = $this->_db->query_fetch($strSQL, $arrBind);
        // 사은품 데이터 보기 좋게 가공 처리
        $setData = [];
        foreach ($getData as $key => $val) {
            $setData[$key]['multiGiftNo'] = $val['giftNo'];
        }
        $gift = App::load('\\Component\\Gift\\Gift');
        $gift->viewGiftData($setData, $imageSize);
        foreach ($setData as $key => $val) {
            $data = $val['multiGiftNo'][0];
            $getData[$key]['giftNm'] = $data['giftNm'];
            $getData[$key]['imageUrl'] = $data['imageUrl'];
        }

        // 사은품 내역 출력
        if (count($getData) > 0) {
            return gd_htmlspecialchars_stripslashes($getData);
        } else {
            return false;
        }
    }

    /**
     * 전체 국가 리스트 가져오기
     *
     * @return mixed
     * @author Jong-tae Ahn <qnibus@godo.co.kr>
     */
    public function getCountriesList()
    {
        return MallDAO::getInstance()->selectCountries();
    }

    /**
     * 주문 추가상품 출력
     *
     * @param array $orderNo 주문 번호
     * @param array $orderCd 주문상품 코드
     * @param null $arrInclude
     * @param array|null $arrExclude
     *
     * @return array 해당 주문 추가상품 정보
     * @deprecated 추후 삭제 예정
     */
    public function getOrderAddGoods($orderNo, $orderCd = null, $arrInclude = null, $arrExclude = ['orderNo', 'orderCd'])
    {
        $arrIncludeAg = [
            'imageStorage',
            'imagePath',
            'imageNm',
        ];
        $arrExcludeOa = [
            'orderNo',
            'orderCd',
        ];
        $arrFieldAg = DBTableField::setTableField('tableAddGoods', $arrIncludeAg, null, 'ag');
        $arrFieldOa = DBTableField::setTableField('tableOrderAddGoods', $arrInclude, $arrExclude, 'oa');
        $arrJoin[] = ' LEFT JOIN ' . DB_ADD_GOODS . ' ag ON ag.addGoodsNo = oa.addGoodsNo ';
        $arrWhere[] = 'oa.orderNo = ?';
        $this->_db->bind_param_push($arrBind, 's', $orderNo);
        if ($orderCd) {
            $arrWhere[] = 'oa.orderCd = ?';
            $this->_db->bind_param_push($arrBind, 'i', $orderCd);
        }
        $this->_db->strJoin = implode('', $arrJoin);
        $this->_db->strField = 'oa.sno, ' . implode(', ', $arrFieldAg) . ', ' . implode(', ', $arrFieldOa) . ', oa.regDt ';
        $this->_db->strWhere = implode(' AND ', gd_isset($arrWhere));
        $this->_db->strOrder = 'oa.sno DESC';
        if (empty($arrBind)) {
            $arrBind = null;
        }
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_ADD_GOODS . ' oa ' . implode(' ', $query);
        $getData = $this->_db->query_fetch($strSQL, $arrBind);
        foreach ($getData as $key => $val) {
            // 상품이미지 처리 이미지($val['imageNm']) 없을 경우 noimage 노출
            $getData[$key]['goodsImage'] = gd_html_add_goods_image($val['addGoodsNo'], $val['imageNm'], $val['imagePath'], $val['imageStorage'], 50, $val['goodsNm'], '_blank');
        }

        // 데이타 출력
        if (count($getData) > 0) {
            return gd_htmlspecialchars_stripslashes($getData);
        } else {
            return false;
        }
    }

    public function getUserHandleInfo($orderNo, $orderGoodsNo = null, $handleMode = [])
    {
        $getData = $this->getOrderUserHandle($orderNo, $orderGoodsNo, $handleMode, 'r');
        $data = [];
        foreach ($getData as $val) {
            $data[] = $this->userHandleText[$val['userHandleMode']];
        }

        return $data;
    }

    public function getOrderUserHandle($orderNo, $orderGoodsNo = null, $handleMode = [], $handleFl = null, $handleNo = null)
    {
        $arrBind = $arrWhere = [];

        $arrWhere[] = 'ouh.orderNo = ?';
        $this->_db->bind_param_push($arrBind, 's', $orderNo);
        if (empty($orderGoodsNo) === false) {
            $arrWhere[] = 'ouh.userHandleGoodsNo = ?';
            $this->_db->bind_param_push($arrBind, 'i', $orderGoodsNo);
        }
        if (empty($handleNo) === false) {
            $arrWhere[] = 'ouh.sno = ?';
            $this->_db->bind_param_push($arrBind, 'i', $handleNo);
        }
        if (empty($handleMode[0]) === false && count($handleMode) > 0) {
            $arrWhere[] = 'ouh.userHandleMode IN (' . @implode(',', array_fill(0, count($handleMode), '?')) . ')';
            foreach ($handleMode as $value) {
                $this->_db->bind_param_push($arrBind, 's', $value);
            }
        }
        if (empty($handleFl) === false) {
            $arrWhere[] = 'ouh.userHandleFl = ?';
            $this->_db->bind_param_push($arrBind, 's', $handleFl);
        }
        if (Manager::isProvider()) {
            $arrWhere[] = 'EXISTS (SELECT 1 FROM ' . DB_ORDER_GOODS . ' WHERE (userHandleSno = ouh.sno OR sno = ouh.userHandleGoodsNo) AND scmNo = ? )';
            $this->_db->bind_param_push($arrBind, 'i', Session::get('manager.scmNo'));
        }
        $this->_db->strField = '*';
        $this->_db->strWhere = implode(' AND ', gd_isset($arrWhere));
        $this->_db->strOrder = 'sno asc';
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_USER_HANDLE . ' ouh ' . implode(' ', $query);
        $data = $this->_db->query_fetch($strSQL, $arrBind);

        return $data;
    }

    /**
     * 주문 쿠폰 정보
     *
     * @param integer $orderNo 주문번호
     * @param array $orderCd 상품주문번호 (null이면 주문쿠폰을 포함해 가져옴)
     * @param boolean $isRefunded 복원쿠폰 여부 (false인 경우 쿠폰 전체가 출력)
     *
     * @return array 해당 주문 쿠폰 정보
     */
    public function getOrderCoupon($orderNo, $orderCd = null, $isRefunded = false)
    {
        $arrBind = [];
        $arrWhere[] = 'oc.orderNo = ?';
        $this->_db->bind_param_push($arrBind, 's', $orderNo);
        // join 문
        $join[] = ' LEFT JOIN ' . DB_MEMBER_COUPON . ' mc ON mc.memberCouponNo = oc.memberCouponNo ';
        $join[] = ' LEFT JOIN ' . DB_COUPON . ' c ON c.couponNo = mc.couponNo ';
        // 환불여부에 따른 쿠폰 데이터
        if ($isRefunded) {
            $arrWhere[] = '(oc.minusRestoreCouponFl = \'y\' OR oc.plusRestoreCouponFl = \'y\') AND og.orderStatus = \'r3\'';
            $join[] = ' LEFT OUTER JOIN ' . DB_ORDER_GOODS . ' og ON oc.orderNo = og.orderNo ';
            $join[] = ' LEFT OUTER JOIN ' . DB_ORDER_HANDLE . ' oh ON og.orderNo = oh.orderNo AND og.handleSno = oh.sno ';
        } else {
            $join[] = ' LEFT OUTER JOIN ' . DB_ORDER_GOODS . ' og ON oc.orderNo = og.orderNo AND oc.orderCd = og.orderCd ';
        }
        $arrField = DBTableField::setTableField('tableOrderCoupon', null, null, 'oc');
        $this->_db->strField = 'og.sno, og.goodsNm, og.goodsType, og.timeSaleFl, og.optionInfo, og.optionTextInfo, c.couponKindType, c.couponBenefit, c.couponBenefitType, ' . implode(', ', $arrField);
        $this->_db->strWhere = implode(' AND ', $arrWhere);
        $this->_db->strJoin = implode('', $join);
        $this->_db->strOrder = 'oc.sno DESC';
        $this->_db->strGroup = 'oc.sno';
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_COUPON . ' oc ' . implode(' ', $query);
        $getData = $this->_db->query_fetch($strSQL, $arrBind);

        // 해당 주문상품번호가 있는 경우 해당 상품의 쿠폰과 주문쿠폰만 나오도록 필터링
        if ($orderCd !== null && is_array($orderCd)) {
            foreach ($getData as $key => & $val) {
                if ($val['orderCd'] != 0 && !in_array($val['orderCd'], $orderCd)) {
                    unset($getData[$key]);
                }
            }
        }
        // 데이타 출력
        if (count($getData) > 0) {
            return gd_htmlspecialchars_stripslashes($getData);
        } else {
            return false;
        }
    }

    /**
     * 관리자 주문 리스트를 위한 검색 정보 세팅
     *
     * @param string $searchData 검색 데이타
     *
     * @throws AlertBackException
     */
    protected function _setSearch($searchData)
    {
        // --- 검색 설정
        $this->search['mallFl'] = gd_isset($searchData['mallFl'], 'all');
        //$this->search['key'] = 'o.orderNo';
        $this->search['sort'] = 'og.orderNo desc';
        $this->search['treatDateFl'] = gd_isset($searchData['treatDateFl'], 'og.regDt');
        $this->search['treatDate'][] = gd_isset($searchData['treatDate'][0]);
        $this->search['treatTime'][] = gd_isset($searchData['treatTime'][0], '00:00:00');
        $this->search['scmFl'] = gd_isset($searchData['scmFl'], 'all');
        // 공급사 선택 후 공급사가 없는 경우
        if ($searchData['scmNo'] == 0 && $searchData['scmFl'] == 1) {
            $this->search['scmFl'] = 'all';
        }
        $this->search['scmNo'] = gd_isset($searchData['scmNo']);
        $this->search['scmNoNm'] = gd_isset($searchData['scmNoNm']);
        $this->search['view'] = 'order';
        // 멀티상점 선택
        if ($this->search['mallFl'] !== 'all') {
            $this->arrWhere[] = 'o.mallSno = ?';
            $this->_db->bind_param_push($this->arrBind, 's', $this->search['mallFl']);
        }
        if ($this->search['scmFl'] == '1') {
            if (is_array($this->search['scmNo'])) {
                foreach ($this->search['scmNo'] as $val) {
                    $tmpWhere[] = 'og.scmNo = ?';
                    $this->_db->bind_param_push($this->arrBind, 's', $val);
                }
                $this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
                unset($tmpWhere);
            } else if ($this->search['scmNo'] > 1) {
                $this->arrWhere[] = 'og.scmNo = ?';
                $this->_db->bind_param_push($this->arrBind, 'i', $this->search['scmNo']);
            }
        } elseif ($this->search['scmFl'] == '0') {
            $this->arrWhere[] = 'og.scmNo = 1';
        }
        $dateField = str_replace(['Dt.r', 'Dt.b', 'Dt.e'], 'Dt', $this->search['treatDateFl']);
        $this->arrWhere[] = $dateField . ' <= ?';
        $this->_db->bind_param_push($this->arrBind, 's', $searchData['treatDate'][0]);
        if (empty($this->arrBind)) {
            $this->arrBind = null;
        }
    }

    /**
     * 주문내역삭제 엑셀
     * 반품/교환/환불 정보까지 한번에 가져올 수 있게 되어있다.
     *
     * @param string $searchData 검색 데이타
     *
     * @return array 주문 리스트 정보
     */
    public function getOrderListForAdminExcel($searchData, $orderType, $excelField, $page, $pageLimit)
    {
        unset($this->arrWhere);
        unset($this->arrBind);

        $this->_setSearch($searchData);
        $searchData['sort'] = "og.orderNo desc";
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' ORDER EXCEL SEARCH DATA : ', [$searchData]);
        if ($searchData['statusCheck'] && is_array($searchData['statusCheck'])) {
            foreach ($searchData['statusCheck'] as $key => $val) {
                foreach ($val as $k => $v) {
                    $_tmp = explode(INT_DIVISION, $v);
                    if ($orderType == 'goods' && $searchData['view'] == 'order') unset($_tmp[1]);
                    if ($_tmp[1]) {
                        $tmpWhere[] = "(og.orderNo = ? AND og.sno = ?)";
                        $this->_db->bind_param_push($this->arrBind, 's', $_tmp[0]);
                        $this->_db->bind_param_push($this->arrBind, 's', $_tmp[1]);
                    } else {
                        $tmpWhere[] = "(og.orderNo = ?)";
                        $this->_db->bind_param_push($this->arrBind, 's', $_tmp[0]);
                    }
                }
            }
            $this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
            unset($tmpWhere);
        }
        // 정렬 설정
        $orderSort = gd_isset($searchData['sort'], $this->orderGoodsOrderBy);
        // 사용 필드
        $arrInclude = [
            'o.orderNo',
            'o.orderChannelFl',
            'o.apiOrderNo',
            'o.memNo',
            'o.orderGoodsNm',
            'o.orderGoodsCnt',
            'o.settlePrice as totalSettlePrice',
            'o.totalDeliveryCharge',
            'o.useDeposit as totalUseDeposit',
            'o.useMileage as totalUseMileage',
            '(o.totalMemberDcPrice + o.totalMemberDeliveryDcPrice) AS totalMemberDcPrice',
            'o.totalGoodsDcPrice',
            '(o.totalCouponGoodsDcPrice + o.totalCouponOrderDcPrice + o.totalCouponDeliveryDcPrice)as totalCouponDcPrice',
            'o.totalCouponOrderDcPrice',
            'o.totalCouponDeliveryDcPrice',
            'o.totalMileage',
            'o.totalGoodsMileage',
            'o.totalMemberMileage',
            '(o.totalCouponGoodsMileage+o.totalCouponOrderMileage) as totalCouponMileage',
            'o.settleKind',
            'o.bankAccount',
            'o.bankSender',
            'o.receiptFl',
            'o.pgResultCode',
            'o.pgTid',
            'o.pgAppNo',
            'o.paymentDt',
            'o.addField',
            'o.mallSno',
            'o.orderGoodsNmStandard',
            'o.overseasSettlePrice',
            'o.currencyPolicy',
            'o.exchangeRatePolicy',
            'o.totalEnuriDcPrice',
            '(o.realTaxSupplyPrice + o.realTaxVatPrice + o.realTaxFreePrice) AS totalRealSettlePrice',
            'o.checkoutData',
            'o.trackingKey',
            'o.fintechData',
            'o.checkoutData',
            'o.orderTypeFl',
            'o.appOs',
            'o.pushCode',
            'o.memberPolicy',
            'o.totalMyappDcPrice',
            'o.regDt as orderDt',
            'oi.orderName',
            'oi.orderEmail',
            'oi.orderPhone',
            'oi.orderCellPhone',
            'oi.receiverName',
            'oi.receiverPhone',
            'oi.receiverCellPhone',
            'oi.receiverUseSafeNumberFl',
            'oi.receiverSafeNumber',
            'oi.receiverSafeNumberDt',
            'oi.receiverZonecode',
            'oi.receiverZipcode',
            'oi.receiverAddress',
            'oi.receiverAddressSub',
            'oi.receiverCity',
            'oi.receiverState',
            'oi.receiverCountryCode',
            'oi.orderMemo',
            'oi.packetCode',
            'oi.orderInfoCd',
            'oi.visitName',
            'oi.visitPhone',
            'oi.visitMemo',
            '(og.orderDeliverySno) AS orderDeliverySno ',
            '(og.scmNo) AS scmNo ',
            '(og.apiOrderGoodsNo) AS apiOrderGoodsNo ',
            '(og.sno) AS orderGoodsSno ',
            '(og.orderCd) AS orderCd ',
            '(og.orderStatus) AS orderStatus ',
            '(og.goodsNo) AS goodsNo ',
            '(og.goodsCd) AS goodsCd ',
            '(og.goodsModelNo) AS goodsModelNo ',
            '(og.goodsNm) AS goodsNm ',
            '(og.optionInfo) AS optionInfo ',
            '(og.goodsCnt) AS goodsCnt ',
            '(og.goodsWeight) AS goodsWeight ',
            '(og.goodsVolume) AS goodsVolume ',
            '(og.cateCd) AS cateCd ',
            '(og.goodsCnt) AS goodsCnt ',
            '(og.brandCd) AS brandCd ',
            '(og.makerNm) AS makerNm ',
            '(og.originNm) AS originNm ',
            '(og.addGoodsCnt) AS addGoodsCnt ',
            '(og.optionTextInfo) AS optionTextInfo ',
            '(og.goodsTaxInfo) AS goodsTaxInfo ',
            '(og.goodsPrice) AS goodsPrice ',
            '(og.fixedPrice) AS fixedPrice ',
            '(og.costPrice) AS costPrice ',
            '(og.commission) AS commission ',
            '(og.optionPrice) AS optionPrice ',
            '(og.optionCostPrice) AS optionCostPrice ',
            '(og.optionTextPrice) AS optionTextPrice ',
            '(og.invoiceCompanySno) AS invoiceCompanySno ',
            '(og.invoiceNo) AS invoiceNo ',
            '(og.deliveryCompleteDt) AS deliveryCompleteDt ',
            '(og.visitAddress) AS visitAddress ',
            'og.goodsDeliveryCollectFl',
            'og.deliveryMethodFl',
            'og.goodsNmStandard',
            'og.goodsMileage',
            'og.memberMileage',
            'og.couponGoodsMileage',
            'og.divisionUseDeposit',
            'og.divisionUseMileage',
            'og.divisionCouponOrderDcPrice',
            'og.goodsDcPrice',
            '(og.memberDcPrice+og.memberOverlapDcPrice+od.divisionMemberDeliveryDcPrice) as memberDcPrice',
            'og.memberDcPrice as orgMemberDcPrice',
            'og.memberOverlapDcPrice as orgMemberOverlapDcPrice',
            'og.goodsDiscountInfo',
            'og.myappDcPrice',
            '(og.couponGoodsDcPrice + og.divisionCouponOrderDcPrice) as couponGoodsDcPrice',
            '(og.goodsTaxInfo) AS addGoodsTaxInfo ',
            '(og.commission) AS addGoodsCommission ',
            '(og.goodsPrice) AS addGoodsPrice ',
            'og.timeSalePrice',
            'og.finishDt',
            'og.deliveryDt',
            'og.deliveryCompleteDt',
            'og.goodsType',
            'og.hscode',
            'og.checkoutData AS og_checkoutData',
            'og.enuri',
            'oh.handleReason',
            'oh.handleDetailReason',
            'oh.refundMethod',
            'oh.refundBankName',
            'oh.refundAccountNumber',
            'oh.refundDepositor',
            'oh.refundPrice',
            'oh.refundDeliveryCharge',
            'oh.refundDeliveryInsuranceFee',
            'oh.refundUseDeposit',
            'oh.refundUseMileage',
            'oh.refundUseDepositCommission',
            'oh.refundUseMileageCommission',
            'oh.completeCashPrice',
            'oh.completePgPrice',
            'oh.completeCashPrice',
            'oh.completeDepositPrice',
            'oh.completeMileagePrice',
            'oh.refundCharge',
            'oh.refundUseDeposit',
            'oh.refundUseMileage',
            'oh.regDt as handleRegDt',
            'oh.handleDt',
            'od.deliveryCharge',
            'od.orderInfoSno',
            'od.deliveryPolicyCharge',
            'od.deliveryAreaCharge',
            'od.realTaxSupplyDeliveryCharge',
            'od.realTaxVatDeliveryCharge',
            'od.realTaxFreeDeliveryCharge',
            'od.divisionDeliveryUseMileage',
            'od.divisionDeliveryUseDeposit',
        ];
        if ($searchData['statusMode'] === 'o') {
            // 입금대기리스트에서 '주문상품명' 을 입금대기 상태의 주문상품명만으로 노출시키기 위해 개수를 구함
            $arrInclude[] = 'SUM(IF(LEFT(og.orderStatus, 1)=\'o\', 1, 0)) AS noPay';
        }
        // join 문
        $join[] = ' LEFT JOIN ' . DB_ORDER . ' o ON o.orderNo = og.orderNo ';
        $join[] = ' LEFT JOIN ' . DB_ORDER_HANDLE . ' oh ON og.handleSno = oh.sno AND og.orderNo = oh.orderNo ';
        $join[] = ' LEFT JOIN ' . DB_ORDER_DELIVERY . ' od ON og.orderDeliverySno = od.sno ';
        $join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON (og.orderNo = oi.orderNo) 
                    AND (CASE WHEN od.orderInfoSno > 0 THEN od.orderInfoSno = oi.sno ELSE oi.orderInfoCd = 1 END)';
        //매입처
        $arrIncludePurchase = [
            'pu.purchaseNm'
        ];
        $arrInclude = array_merge($arrInclude, $arrIncludePurchase);
        $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' pu ON og.purchaseNo = pu.purchaseNo ';
        unset($arrIncludePurchase);

        //공급사
        $arrIncludeScm = [
            'sm.companyNm as scmNm'
        ];
        $arrInclude = array_merge($arrInclude, $arrIncludeScm);
        $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON og.scmNo = sm.scmNo ';
        unset($arrIncludeScm);

        //회원
        $arrIncludeMember = [
            'IF(m.memNo > 0, m.memNm, oi.orderName) AS memNm',
            'm.memId',
            'mg.groupNm',
        ];
        $arrInclude = array_merge($arrInclude, $arrIncludeMember);
        $join[] = ' LEFT JOIN ' . DB_MEMBER . ' m ON o.memNo = m.memNo ';
        $join[] = ' LEFT OUTER JOIN ' . DB_MEMBER_GROUP . ' mg ON m.groupSno = mg.sno ';
        unset($arrIncludeMember);

        //사은품
        $arrIncludeGift = [
            'GROUP_CONCAT(ogi.presentSno SEPARATOR "/") AS presentSno ',
            'GROUP_CONCAT(ogi.giftNo SEPARATOR "/") AS giftNo '
        ];
        $arrInclude = array_merge($arrInclude, $arrIncludeGift);
        $join[] = ' LEFT JOIN ' . DB_ORDER_GIFT . ' ogi ON (ogi.orderNo = o.orderNo) AND (ogi.sno > 0) ';
        unset($arrIncludeGift);

        // 반품/교환/환불신청 사용에 따른 리스트 별도 처리 (조건은 검색 메서드 참고)
        $arrIncludeOuh = [
            'count(ouh.sno) as totalClaimCnt',
            'ouh.userHandleReason',
            'ouh.userHandleDetailReason',
            'ouh.userRefundAccountNumber',
            'ouh.adminHandleReason',
            'ouh.regDt AS userHandleRegDt'
        ];
        $join[] = ' LEFT JOIN ' . DB_ORDER_USER_HANDLE . ' ouh ON (og.orderNo = ouh.orderNo) AND (og.userHandleSno = ouh.sno) AND (ouh.sno > 0) ';
        $arrInclude = array_merge($arrInclude, $arrIncludeOuh);
        unset($arrIncludeOuh);

        // 현 페이지 결과
        if ($page == '0') {
            $this->_db->strField = 'og.orderNo';
            $this->_db->strJoin = implode('', $join);
            $this->_db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
            $this->_db->strGroup = "CONCAT(og.orderNo,og.orderCd,og.goodsNo)";
            //총갯수관련
            $query = $this->_db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' og ' . implode(' ', $query);
            $result['totalCount'] = $this->_db->query_fetch($strSQL, $this->arrBind);
        }
        $this->_db->strField = implode(', ', $arrInclude) . ", o.totalGoodsPrice";
        $this->_db->strJoin = implode('', $join);
        $this->_db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        $this->_db->strGroup = "CONCAT(og.orderNo,og.orderCd,og.goodsNo)";
        $this->_db->strOrder = $orderSort;
        if ($pageLimit) $this->_db->strLimit = (($page * $pageLimit)) . "," . $pageLimit;
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' og ' . implode(' ', $query);
        if (empty($excelField) === false) {
            $result['orderList'] = $this->_db->query_fetch_generator($strSQL, $this->arrBind);
        } else {
            $result = $this->_db->query_fetch($strSQL, $this->arrBind);
        }

        return $result;
    }

    /**
     * 5년 경과 주문 삭제건 생성 여부 확인(생성중, 생성완료)
     *
     * @return mixed
     */
    public function chkOrderDeleteList()
    {
        // 생성중, 생성완료, 삭제중 상태값이 있는지 체크
        $strSQL = "SELECT sno, status FROM " . DB_LAPSE_ORDER_DELETE . " WHERE status IN('g', 'c', 'd')";
        $result = $this->_db->query_fetch($strSQL, null);
        $result['cnt'] = count($result);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY RESULT : ', [$result]);

        return json_encode($result);
    }

    /**
     * 5년 경과 주문 삭제 내역 생성 시, 이전 내역 삭제
     *
     * @param $arrData
     */
    public function beforeOrderDataDelete($arrData)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE REQUEST DATA : ', [$arrData]);

        $strSQL = "DELETE FROM " . DB_LAPSE_ORDER_DELETE . " WHERE status = ? ";
        $arrFDeleteBind = [];
        $this->_db->bind_param_push($arrFDeleteBind, 's', $arrData['status']);
        $this->_db->bind_query($strSQL, $arrFDeleteBind);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE SQL QUERY BY DB_LAPSE_ORDER_DELETE : ', [$strSQL]);

        $strSQL = "DELETE FROM " . DB_LAPSE_ORDER_DELETE_ORDER_NO . " WHERE sno = ? ";
        $arrSDeleteBind = [];
        $this->_db->bind_param_push($arrSDeleteBind, 's', $arrData['sno']);
        $this->_db->bind_query($strSQL, $arrSDeleteBind);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE SQL QUERY BY DB_LAPSE_ORDER_DELETE_ORDER_NO : ', [$strSQL]);
    }

    /**
     * 5년 경과 주문 삭제 내역 추출
     *
     * @param $arrData
     * @return false|void
     */
    public function setDeleteLapseOrderData($arrData)
    {
        $setData = [];
        $data = explode('&', $arrData['data']);
        foreach ($data as $info) {
            $newData[] = explode('=', $info);
            foreach ($newData as $k => $v) {
                $key = str_replace(array('%5B', '%5D'), '', $v[0]);
                $val = str_replace('%2F', '/', $v[1]);
                $setData['data'][$key][$k] = $val;
                unset($setData['data']['mode']);
            }
        }
        $searchData = $setData['data'];
        $year = date("Y-m-d H:i:s", strtotime("-5 year"));
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SEARCH YEAR : ' . $year . ', REQUEST DATA : ' . $arrData . ', SET DATA : ', [$setData]);
        $arrBind = [];
        $arrWhere[] = 'o.regDt <= ? ';
        $this->_db->bind_param_push($arrBind, 's', $year);
        // 상점
        if ($searchData['mallFl']) {
            foreach ($searchData['mallFl'] as $mallKey => $mallFl) {
                $this->_db->bind_param_push($arrBind, 'i', $mallFl);
            }
            $arrWhere[] = 'o.mallSno = ?';
        }
        // 예외공급사
        if ($searchData['scmNo']) {
            $tmpScmNo = [];
            $scmNoStateFl = false;
            foreach ($searchData['scmNo'] as $sNKey => $scmNm) {
                if ($scmNm != 'all') {
                    $scmNoStateFl = true;
                    $tmpScmNo[] = '?';
                    $this->_db->bind_param_push($arrBind, 's', $scmNm);
                }
            }
            if ($scmNoStateFl) {
                $arrWhere[] = " NOT og.scmNo IN (" . implode(', ', $tmpScmNo) . ") ";
                unset($tmpScmNo);
            }
        }
        // join 문
        $join[] = ' LEFT JOIN ' . DB_ORDER_GOODS . ' og ON o.orderNo = og.orderNo';
        $this->_db->strField = 'o.orderNo as orderNo';
        $this->_db->strGroup = "o.orderNo";
        $this->_db->strWhere = implode(' AND ', $arrWhere);
        $this->_db->strJoin = implode('', $join);
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER . ' o' . implode(' ', $query);
        $res = $this->_db->query_fetch($strSQL, $arrBind);
        $cnt = count($res);
        $result['data'] = $res;
        $result['cnt'] = $cnt;
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY : ', [$this->_db->getBindingQueryString($strSQL, $arrBind)]);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY RESULT OrderNo: ', [$res]);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY RESULT OrderNo Cnt: ', [$cnt]);

        // 데이타 출력
        if ($cnt > 0) {
            $result['res'] = 'success';
        } else {
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY RESULT EMPTY ', [$cnt]);
            $result['res'] = 'fail';
        }
        unset($arrBind);

        return $result;
    }

    /**
     * 5년 경과 주문 삭제 생성 내역 저장
     *
     * @param $arrData
     * @return bool
     */
    public function saveDeleteOrder($arrData)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' OrderNo Data : ', [$arrData]);
        $setData = $arrData['data'];
        $insertData['deleteCnt'] = $setData['cnt'];
        $insertData['creator'] = (string)Session::get('manager.managerId');
        $insertData['creatorIp'] = Request::getRemoteAddress();
        $insertData['status'] = 'c';
        $insertData['regEndDt'] = gd_date_format('Y-m-d G:i:s', 'now');
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' Order INSERT DATA : ', [$insertData]);

        $arrBind = $this->_db->get_binding(DBTableField::tableLapseOrderDelete(), $insertData, 'insert');
        $this->_db->set_insert_db(DB_LAPSE_ORDER_DELETE, $arrBind['param'], $arrBind['bind'], 'y');
        $prevSno = $this->_db->insert_id();
        if (empty($prevSno)) {
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' INSERT FAIL : ', [$prevSno]);

            return false;
        } else {
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' INSERT DATA : ', [$insertData]);
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' LAST INSERT SNO : ', [$prevSno]);

            // 매핑 테이블 저장
            foreach ($setData['data'] as $key => $val) {
                $res = $this->saveDeleteOrderNo($prevSno, $val['orderNo']);
            }
            if ($res) {
                return true;
            }
        }
        unset($arrBind);
    }

    /**
     * 5년 경과 주문 삭제 내역 주문번호 매핑 저장
     *
     * @param $sno
     * @param $orderNo
     * @return bool
     */
    public function saveDeleteOrderNo($sno, $orderNo)
    {
        $arrBind['param'] = "sno, orderNo";
        $this->_db->bind_param_push($arrBind['bind'], 'i', $sno);
        $this->_db->bind_param_push($arrBind['bind'], 's', $orderNo);
        $this->_db->set_insert_db(DB_LAPSE_ORDER_DELETE_ORDER_NO, $arrBind['param'], $arrBind['bind'], 'y');
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' MAPPING INSERT DATA : ', [$sno, $orderNo]);

        return true;
    }

    /**
     * 5년 경과 주문 삭제 내역 리스트
     *
     * @return mixed
     */
    public function getDeleteOrderList()
    {
        $arrBind = [];

        // --- 페이지 기본설정
        $getValue = Request::get()->toArray();
        gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 20);

        $page = \App::load('\\Component\\Page\\Page', $getValue['page']);
        $page->page['list'] = $getValue['pageNum']; // 페이지당 리스트 수
        $page->recode['amount'] = $this->_db->getCount(DB_LAPSE_ORDER_DELETE, 'sno', ' WHERE sno > 0', 'row'); // 전체 레코드 수
        $page->setPage();
        $page->setUrl(\Request::getQueryString());

        $strSQL = 'SELECT * FROM ' . DB_LAPSE_ORDER_DELETE . ' ORDER BY sno DESC LIMIT ' . $page->recode['start'] . ', ' . $getValue['pageNum'];
        $data = $this->_db->query_fetch($strSQL, $arrBind);

        $strCntSQL = " SELECT COUNT(sno) AS cnt FROM " . DB_LAPSE_ORDER_DELETE;
        $page->recode['total'] = $this->_db->query_fetch($strCntSQL, $arrBind, false)['cnt'];
        $page->setPage();
        $result['data'] = $data;

        return $result;
    }

    /**
     * 5년 경과 주문 삭제 예정 상태 업데이트
     *
     * @param $arrData
     */
    public function deleteExpectedLapseOrderData($arrData)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE EXPECTED ORDER DATA : ', [$arrData]);
        $updateData['deleter'] = (string)Session::get('manager.managerId');
        $updateData['deleterIp'] = Request::getRemoteAddress();
        $updateData['status'] = 'd'; // 삭제예정
        $updateData['modDt'] = gd_date_format('Y-m-d G:i:s', 'now');
        $compareField = array_keys($updateData);
        $arrBind = $this->_db->get_binding(DBTableField::tableLapseOrderDelete(), $updateData, 'update', $compareField);
        $this->_db->bind_param_push($arrBind['bind'], 'i', $arrData['sno']);
        $this->_db->set_update_db(DB_LAPSE_ORDER_DELETE, $arrBind['param'], 'sno = ?', $arrBind['bind']);
    }

    /**
     * 5년 경과 주문 삭제 예정 건이 있는지 확인
     *
     * @return false
     */
    public function getDeleteExceptedLapseOrderData()
    {
        // 삭제중 상태값이 있는지 체크
        $arrBind = [];
        $strSQL = "SELECT sno FROM " . DB_LAPSE_ORDER_DELETE . " WHERE status = ?";
        $this->_db->bind_param_push($arrBind, 's', 'd');
        $result = $this->_db->query_fetch($strSQL, $arrBind, false);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE EXPECTED SQL RESULT : ', [$result]);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function deleteLapseOrderDataTS($functionName, $sno)
    {
        \DB::transaction(function () use ($functionName, $sno) {
            return $this->$functionName($sno);
        });
    }

    /**
     * 5년 경과 주문 삭제
     *
     * @param $arrData
     */
    public function deleteLapseOrderData($sno)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE REQUEST SNO DATA : ', [$sno]);

        $arrBind = [];
        $strSQL = 'SELECT orderNo FROM ' . DB_LAPSE_ORDER_DELETE_ORDER_NO . ' WHERE sno = ? ';
        $this->_db->bind_param_push($arrBind, 'i', $sno);
        $result = $this->_db->query_fetch($strSQL, $arrBind);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' SQL QUERY RESULT : ', [$result]);

        foreach ($result as $key => $val) {
            $strOrderInfoSQL = "DELETE FROM " . DB_ORDER_INFO . " WHERE orderNo = ? ";
            $arrOrderInfoBind = [];
            $this->_db->bind_param_push($arrOrderInfoBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderInfoSQL, $arrOrderInfoBind);

            $strOrderOriSQL = "DELETE FROM " . DB_ORDER_ORIGINAL . " WHERE orderNo = ? ";
            $arrOrderOriBind = [];
            $this->_db->bind_param_push($arrOrderOriBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderOriSQL, $arrOrderOriBind);

            $strOrderSQL = "DELETE FROM " . DB_ORDER . " WHERE orderNo = ? ";
            $arrOrderBind = [];
            $this->_db->bind_param_push($arrOrderBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderSQL, $arrOrderBind);

            $strOrderGoodsSQL = "DELETE FROM " . DB_ORDER_GOODS . " WHERE orderNo = ? ";
            $arrOrderGoodsBind = [];
            $this->_db->bind_param_push($arrOrderGoodsBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderGoodsSQL, $arrOrderGoodsBind);

            $strOrderGoodsOriSQL = "DELETE FROM " . DB_ORDER_GOODS_ORIGINAL . " WHERE orderNo = ? ";
            $arrOrderGoodsOriBind = [];
            $this->_db->bind_param_push($arrOrderGoodsOriBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderGoodsOriSQL, $arrOrderGoodsOriBind);

            $strOrderHandleSQL = "DELETE FROM " . DB_ORDER_HANDLE . " WHERE orderNo = ? ";
            $arrOrderHandleBind = [];
            $this->_db->bind_param_push($arrOrderHandleBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderHandleSQL, $arrOrderHandleBind);

            $strOrderUserHandleSQL = "DELETE FROM " . DB_ORDER_USER_HANDLE . " WHERE orderNo = ? ";
            $arrOrderUserHandleBind = [];
            $this->_db->bind_param_push($arrOrderUserHandleBind, 's', $val['orderNo']);
            $this->_db->bind_query($strOrderUserHandleSQL, $arrOrderUserHandleBind);

            $strMemberHackoutOrderSQL = "DELETE FROM " . DB_MEMBER_HACKOUT_ORDER . " WHERE orderNo = ? ";
            $arrMemberHackoutOrderBind = [];
            $this->_db->bind_param_push($arrMemberHackoutOrderBind, 's', $val['orderNo']);
            $this->_db->bind_query($strMemberHackoutOrderSQL, $arrMemberHackoutOrderBind);

            $strMemberHackoutOrderHandleSQL = "DELETE FROM " . DB_MEMBER_HACKOUT_ORDER_HANDLE . " WHERE orderNo = ? ";
            $arrMemberHackoutOrderHandleBind = [];
            $this->_db->bind_param_push($arrMemberHackoutOrderHandleBind, 's', $val['orderNo']);
            $this->_db->bind_query($strMemberHackoutOrderHandleSQL, $arrMemberHackoutOrderHandleBind);
        }

        // 상태 업데이트
        $this->deleteLapseOrderDataUpdate($sno);
    }

    /**
     * 5년 경과 삭제 대상 기간만료 체크
     *
     * @return bool
     */
    public function lateDate()
    {
        $strSQL = 'SELECT sno, timediff(now(), ifnull(regDt,regEndDt)) as timediff FROM ' . DB_LAPSE_ORDER_DELETE . ' WHERE status = "c" ';
        $data = $this->_db->query_fetch($strSQL);

        if ($data) {
            $limit24H = str_replace(':', '', '24:00:00');
            foreach ($data as $key => $val) {
                $dt = str_replace(':', '', $val['timediff']);
                if ($dt > $limit24H) {
                    $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DT: ' . $dt . ', LIMIT DATE: ' . $limit24H . ', AFTER 24 HOURS : ', [$data]);
                    $this->lateDateUpdate($val['sno']);

                    return true;
                } else {
                    $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DT: ' . $dt . ', LIMIT DATE: ' . $limit24H . ', BEFORE 24 HOURS : ', [$data]);

                    return false;
                }
            }
        }
    }

    /**
     * 5년 경과 삭제 대상 기간만료 업데이트
     *
     * @param $sno
     */
    public function lateDateUpdate($sno)
    {
        $arrData['status'] = 'l';
        $compareField = array_keys($arrData);
        $arrBind = $this->_db->get_binding(DBTableField::tableLapseOrderDelete(), $arrData, 'update', $compareField);
        $this->_db->bind_param_push($arrBind['bind'], 'i', $sno);
        $this->_db->set_update_db(DB_LAPSE_ORDER_DELETE, $arrBind['param'], 'sno = ?', $arrBind['bind']);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' STATUS UPDATE SQL QUERY : ', [$arrBind]);
    }

    /**
     * 5년 경과 삭제완료 건 처리 상태 업데이트
     *
     * @param $arrData
     */
    public function deleteLapseOrderDataUpdate($sno)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' DELETE ORDER UPDATE REQUEST DATA : ', [$sno]);
        $updateData['status'] = 'dc';
        $updateData['delDt'] = gd_date_format('Y-m-d G:i:s', 'now');
        $compareField = array_keys($updateData);
        $arrBind = $this->_db->get_binding(DBTableField::tableLapseOrderDelete(), $updateData, 'update', $compareField);
        $this->_db->bind_param_push($arrBind['bind'], 'i', $sno);
        $this->_db->set_update_db(DB_LAPSE_ORDER_DELETE, $arrBind['param'], 'sno = ?', $arrBind['bind']);
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' STATUS UPDATE SQL QUERY : ', [$arrBind]);
    }

    /**
     * 5년 경과 주문 건, 엑셀다운로드 시 주문번호 추출
     *
     * @param $sno
     * @return mixed
     */
    public function getDeleteLapseOrderExcelData($sno)
    {
        $strSQL = 'SELECT orderNo, regDt FROM ' . DB_LAPSE_ORDER_DELETE_ORDER_NO . ' WHERE sno = ' . $sno;
        $data = $this->_db->query_fetch($strSQL);

        if ($data) {
            $arrBind = [];
            foreach ($data as $key => $val) {
                $this->_db->bind_param_push($arrBind, 's', $val['orderNo']);
                $orderNoTmp[] = '?';
            }
            $arrWhere[] = 'o.orderNo IN (' . implode(',', $orderNoTmp) . ')';
            // join 문
            $join[] = ' LEFT JOIN ' . DB_ORDER_GOODS . ' og ON o.orderNo = og.orderNo';
            $this->_db->strField = 'o.orderStatus, o.orderNo, og.sno, o.regDt';
            $this->_db->strWhere = implode(' AND ', $arrWhere);
            $this->_db->strJoin = implode('', $join);
            $query = $this->_db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER . ' as o' . implode(' ', $query);
            $res = $this->_db->query_fetch($strSQL, $arrBind);

            $setData['mode_channel'] = 'order_delete';
            $setData['mallFl'] = 'all';
            $setData['scmFl'] = 'all';
            $year = date("Y-m-d H:i:s", strtotime("-5 year", strtotime($data[0]['regDt'])));
            $setData['treatDateFl'] = 'o.regDt';
            $setData['treatDate'][0] = $year;

            foreach ($res as $k => $v) {
                $orderStatus = substr($v['orderStatus'], 0, 1);
                if ($orderStatus == 'o') {
                    $setData['statusCheck'][$orderStatus][] = $v['orderNo'];
                } else {
                    $mixData = $v['orderNo'] . '||' . $v['sno'];
                    $setData['statusCheck'][$orderStatus][] = $mixData;
                }
            }
            $setData['applyPath'] = '/order/order_delete.php?view=order';
        }

        return $setData;
    }

    /**
     * 5년 경과 삭제 주문 건, 엑셀다운로드 번호 업데이트
     *
     * @param $insertSno
     * @param $sno
     */
    public function deleteLapseOrderExcelFormUpdate($insertSno, $sno)
    {
        $this->_logger->channel('orderDelete')->info(__METHOD__ . ' REQUEST DATA INSERT SNO : ' . $insertSno . ' SNO : ' . $sno);
        $updateData['formSno'] = $insertSno;
        $compareField = array_keys($updateData);
        $arrBind = $this->_db->get_binding(DBTableField::tableLapseOrderDelete(), $updateData, 'update', $compareField);
        $this->_db->bind_param_push($arrBind['bind'], 'i', $sno);
        $res = $this->_db->set_update_db(DB_LAPSE_ORDER_DELETE, $arrBind['param'], 'sno = ?', $arrBind['bind']);

        if ($res === false) {
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' EXCEL FORM SNO UPDATE SQL QUERY FAIL : ', [$arrBind]);
        } else {
            $this->_logger->channel('orderDelete')->info(__METHOD__ . ' EXCEL FORM SNO UPDATE SQL QUERY SUCCESS : ', [$arrBind]);
        }
    }
}