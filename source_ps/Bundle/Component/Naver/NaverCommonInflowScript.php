<?php
/**
 * 네이버 공통 유입 스크립트 class
 *
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright ⓒ 2016, NHN godo: Corp.
 */

namespace Bundle\Component\Naver;

use Request;

class NaverCommonInflowScript
{
    protected $db;

    private $naverCommonInflowScriptFl;                // 네이버 공통 유입 스크립트 사용 여부
    private $accountId;                                // 네이버 공통 인증키
    private $isEnabled                    = false;    // 네이버 공통 유입 스크립트 실제 여부
    private $whiteList;                                 // 네이버 공통 유입 스크립트 white list

    /** @var array 네이버 쇼핑 전환 분석 Script (wcs, trans 버전 - 전환 이벤트 TYPE) */
    protected $naverConversionTrackingType = [
        'view_product',
        'add_to_wishlist',
        'add_to_cart',
        'begin_checkout',
        'purchase',
    ];

    /**
     * 생성자
     */
    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db         = \App::load('DB');
        }

        // 설정 로드
        $data = gd_policy('naver.common_inflow_script');
        $this->naverCommonInflowScriptFl    = gd_isset($data['naverCommonInflowScriptFl'], 'n');
        $this->accountId                    = gd_isset($data['accountId']);
        $this->whiteList                    = gd_isset($data['whiteList']);

        // 사용 여부
        if (empty($this->accountId) === false && $this->naverCommonInflowScriptFl == 'y') {
            $this->isEnabled                = true;
        }
    }

    /**
     * 실행 조건 체크
     * @return bool
     */
    public function enabledCheck(): bool
    {
        return $this->isEnabled;
    }

    /**
     * 네이버 쇼핑 전환 분석 Script (wcs, trans 버전 - 공통)
     * @return array
     */
    public function naverConversionTrackingCommonScript(): array
    {
        $script = [];
        $script[] = '<!-- 네이버 전환 추적용 추가 스크립트 블록 시작 -->';
        $script[] = '<script type="text/javascript" src="'.Request::getScheme().'://wcs.naver.net/wcslog.js"></script>';
        $script[] = '<script type="text/javascript">';
        $script[] = 'if (window.wcs) {';
        $script[] = 'if (!wcs_add) var wcs_add = {};';
        $script[] = 'wcs_add["wa"] = "' . addslashes($this->accountId) . '"';
        $script[] = 'wcs.inflow("' . addslashes(Request::getDefaultHost()) . '");';
        $script[] = 'wcs_do();';
        $script[] = '}';
        $script[] = '</script>';
        $script[] = '<!-- 네이버 전환 추적용 추가 스크립트 블록 끝 -->';
        return $script;
    }

    /**
     * 네이버 쇼핑 전환 분석 Script (wcs, trans 버전 - 전환 이벤트 TYPE 별)
     * @param array $naverPuchasePaymentData (주문 번호 / 결제 금액)
     * @param string $naverEpGoodsNo 상품 번호
     * @param string $naverScriptType 전환 이벤트 TYPE
     * @return array
     */
    public function naverConversionTrackingScript(array $naverPuchasePaymentData, string $naverEpGoodsNo, string $naverScriptType): array
    {
        $script = [];

        // 전환 이벤트 TYPE 일치 하는 경우 실행
        if (in_array($naverScriptType, $this->naverConversionTrackingType) === true) {
            $script[] = '<!-- 네이버 전환 추적용 추가 스크립트 블록 시작 -->';
            $script[] = '<script type="text/javascript">';
            $script[] = 'if (window.wcs) {';
            $script[] = 'if (!wcs_add) var wcs_add = {};';
            $script[] = 'wcs_add["wa"] = "' . addslashes($this->accountId) . '";';
            $script[] = 'var _conv = {};';
            $script[] = '_conv.type = "' . addslashes($naverScriptType) . '";';

            // TYPE 별 SCRIPT 생성
            switch ($naverScriptType) {
                case 'begin_checkout':
                case 'add_to_cart':
                case 'add_to_wishlist':
                case 'view_product':
                    $script[] = '_conv.items = ' . str_replace('"', "'", $naverEpGoodsNo) . ';';
                    break;
                case 'purchase':
                    $script[] = '_conv.id = "' . $naverPuchasePaymentData[0][0] .'";';
                    $script[] = '_conv.value = "' . $naverPuchasePaymentData[0][1] .'";';
                    $script[] = '_conv.items = ' . str_replace('"', "'", $naverEpGoodsNo) . ';';
                    break;
            }

            $script[] = 'wcs.trans(_conv);';
            $script[] = '}';
            $script[] = '</script>';
            $script[] = '<!-- 네이버 전환 추적용 추가 스크립트 블록 끝 -->';
        }

        return $script;
    }

    // 공통유입스크립트 반환
    public function getCommonInflowScript()
    {
        $naverScritStr    = '';
        if ($this->isEnabled === true) {
            $param            = [];
            $removeDir        = str_replace('/', '\/', URI_HOME);
            $patternRootDir    = '~^'.$removeDir.'~';
            $param[]        = 'Path='.Request::getFullFileUri();
            $param[]        = 'Referer='.Request::getReferer();
            $param[]        = 'AccountID='.$this->accountId;
            $param[]        = 'Inflow='.preg_replace('/^www\./', '',Request::getHostNoPort());
            if($this->whiteList) {
                foreach($this->whiteList as $whiteList)
                {
                    if(strlen(trim($whiteList))>0) $param[] = 'WhiteList[]='.$whiteList;
                }
            }
            $naverScritStr    .= '<script type="text/javascript" src="'.Request::getScheme().'://wcs.naver.net/wcslog.js"></script>'.chr(10);
            $naverScritStr    .= '<script type="text/javascript" src="'.PATH_SKIN.'js/naver/naverCommonInflowScript.js?'.implode('&amp;', $param).'" id="naver-common-inflow-script"></script>'.chr(10);
        }

        return $naverScritStr;
    }

    // CPA주문수집 데이터 반환
    public function getOrderCompleteData($orderNo)
    {
        $orderCompleteData = [];

        // 공통 유입 스크립트가 설정되어 있을때 주문 상품 정보를 태그로 생성하여 반환
        if ($this->isEnabled === true) {
            $orderItemSet = [];    // 주문상품정보

            // 주문 상품 정보 조회
            $strSQL    = 'SELECT sno, orderNo, goodsNo, goodsNm, goodsCnt, optionSno,optionInfo, optionTextInfo, goodsPrice ,optionPrice , optionTextPrice , optionSno , orderCd , addGoodsCnt
                FROM '.DB_ORDER_GOODS.'
                WHERE orderNo = ? ORDER BY sno ASC';
            $arrBind    = ['s',$orderNo];
            $getData    = $this->db->query_fetch($strSQL, $arrBind);
            unset($arrBind);

            // 전송 정보
            $orderCompleteData    = [];
            $orderItemSet        = [];

            foreach ($getData as $key => $orderRepItem) {
                // 옵션 정보
                $optionInfo        = '';
                if (empty($orderRepItem['optionInfo']) === false) {

                    $option = json_decode(gd_htmlspecialchars_stripslashes($orderRepItem['optionInfo']), true);
                    if (empty($option) === false) {
                        foreach ($option as $oKey => $oVal) {
                            $tmpOption[] = $oVal[0]." : ".$oVal[1];
                        }
                    }
                    $optionInfo    = '['.implode(' , ',$tmpOption).']';
                    unset($tmpOption);
                }

                $goodsPrice        = ($orderRepItem['goodsPrice']+$orderRepItem['optionPrice']+$orderRepItem['optionTextPrice'])*$orderRepItem['goodsCnt'];

                $orderItemSet[] = [
                    'sno'        => $orderRepItem['sno'],
                    'ordno'        => $orderRepItem['orderNo'],
                    'goodsno'    => $orderRepItem['goodsNo'],
                    'optno'    => $orderRepItem['optionSno'],
                    'goodsnm'    => gd_htmlspecialchars_addslashes($orderRepItem['goodsNm'].$optionInfo),
                    'ea'        => $orderRepItem['goodsCnt'],
                    'price'        => $goodsPrice,
                    'is_parent'    => 'true',
                ];


                if($orderRepItem['addGoodsCnt'] > 0 ) {
                    //추가상품 검색
                    $strSQL    = 'SELECT sno, orderNo, addGoodsNo, goodsNm, goodsCnt, goodsPrice  FROM '.DB_ORDER_ADD_GOODS.' WHERE orderNo = ? and orderCd = ? ORDER BY sno ASC';
                    $arrBind    = ['ss',$orderNo,$orderRepItem['orderCd']];
                    $addGoodsData    = $this->db->query_fetch($strSQL, $arrBind);
                    unset($arrBind);

                    if ($addGoodsData) {
                        foreach ($addGoodsData as $iKey => $iVal) {
                            $addGoodsPrice        = ($iVal['goodsPrice'])*$orderRepItem['goodsCnt'];
                            $orderItemSet[] = [
                                'sno'        => $orderRepItem['sno'],
                                'ordno'        => $orderRepItem['orderNo'],
                                'goodsno'    => $orderRepItem['goodsNo'],
                                'optno'    => $iVal['addGoodsNo'],
                                'goodsnm'    => gd_htmlspecialchars_addslashes($iVal['goodsNm']),
                                'ea'        => $iVal['goodsCnt'],
                                'price'        => $addGoodsPrice,
                                'is_parent'    => 'false',
                            ];
                        }
                    }
                }
            }


            // 완성된 주문상품정보를 태그로 변환
            foreach ($orderItemSet as $orderItem) {
                $orderItemField = [];
                foreach ($orderItem as $field => $value) {
                    switch ($field) {
                        case 'price':
                            $orderItemField[] = $field.":". gd_money_format($value,false);
                            break;
                        case 'ea':  case 'is_parent':
                        $orderItemField[] = $field.":".$value;
                        break;
                        case 'goodsnm':
                            $orderItemField[] = $field.":'".addslashes($value)."'";
                            break;
                        default:
                            $orderItemField[] = $field.":'".$value."'";
                            break;
                    }
                }
                $orderCompleteData[] = '<input type="hidden" name="naver-common-inflow-script-order-item" value="{'.implode(',', $orderItemField).'}"/>';
            }

            // 주문 정보 조회
            $strSQL        = 'SELECT totalGoodsPrice AS goodsprice FROM '.DB_ORDER.' WHERE orderNo = ?';
            $arrBind    = ['s',$orderNo];
            $getData    = $this->db->query_fetch($strSQL, $arrBind, false);
            unset($arrBind);
            foreach ($getData as $field => $name) {
                switch ($field) {
                    case 'goodsprice':
                        $orderField[] = $field.":". gd_money_format($name,false);
                        break;
                    default:
                        $orderField[] = $field.":'".$name."'";
                        break;
                }
            }
            if (count($orderField) > 0) {
                $orderCompleteData[] = '<input type="hidden" id="naver-common-inflow-script-order" value="{'.implode(',', $orderField).'}"/>';
            }
        }

        if (count($orderCompleteData) > 0) {
            return implode($orderCompleteData);
        } else {
            return '';
        }
    }
}
