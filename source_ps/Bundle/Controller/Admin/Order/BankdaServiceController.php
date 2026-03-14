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
namespace Bundle\Controller\Admin\Order;

use App;
use Exception;

class BankdaServiceController extends \Controller\Admin\Controller
{

    /**
     * 자동입금확인 서비스 신청
     *
     * @author cjb3333
     * @version 1.0
     * @since 1.0
     * @copyright ⓒ 2016, NHN godo: Corp.
     */
    public function index()
    {
        // --- 메뉴 설정
        $this->callMenu('order', 'bankda', 'service');

        // --- 페이지 데이터
        try {

            /** @var \Bundle\Component\Bankda\Bankda $bankda */
            $bankda = App::load('\\Component\\Bankda\\Bankda');
            $bankdaSetInfo = $bankda -> getBankdaSetInfo();
            $bankda->getIsUseBankda(true);

            $MID = $bankdaSetInfo['MID']; // 상점아이디
            $ceoName = $bankdaSetInfo['ceoNm']; // 대표자명
            $resDomain = $bankdaSetInfo['resDomain']; //리턴도메인
            /**
             * 뱅크다는 현재 리얼과 연결만 가능한 상황, 이후 stage 구분이 될 경우 조건 처리 필요
             */
            $ifrsrc = sprintf('https://godomall.bankda.com/index.asp?Upid=%s&Upname=%s&Updomain=%s', $MID, $ceoName, $resDomain);

        } catch (Exception $e) {
            echo ($e->ectMessage);
        }

        // --- 관리자 디자인 템플릿
        $this->setData('ceoName', gd_isset($ceoName));
        $this->setData('ifrsrc', gd_isset($ifrsrc));
    }
}
