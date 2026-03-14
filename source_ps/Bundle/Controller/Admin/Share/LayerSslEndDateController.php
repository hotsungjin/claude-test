<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2022, NHN COMMERCE Corp.
 */
namespace Bundle\Controller\Admin\Share;

use Bundle\Component\Policy\SslEndDate;
use Session;

/**
 * 관리자페이지 메인 - 보안서버 만료일 안내
 */
class LayerSslEndDateController extends \Controller\Admin\Controller
{
    public function index()
    {
        // 공급사 운영자를 제외한, 본사 운영자에게만 노출
        if (Session::get('manager.scmNo') != DEFAULT_CODE_SCMNO) {
            exit;
        }

        $sslConfigArr = (new SslEndDate())->getSslEndDateArr(14);
        if (empty($sslConfigArr)) {
            exit;
        }

        $sslConfigPositionArr = [
            'pc' => 'PC 쇼핑몰',
            'admin' => '관리자',
            'api' => 'API',
            'mobile' => '모바일 쇼핑몰'
        ];

        $dataArr = [];
        foreach ($sslConfigArr as $sslConfig) {
            $dataArr[] = [
                'type' => $sslConfigPositionArr[$sslConfig['sslConfigPosition']],
                'expirationFl' => $sslConfig['dateDiff'] <= 0,
                'sslConfigDomain' => $sslConfig['sslConfigDomain'],
                'sslConfigEndDate' => $sslConfig['sslConfigEndDate'],
            ];
        }

        $this->getView()->setData('dataList', $dataArr);
        $this->getView()->setDefine('layout', 'layout_layer.php');
    }
}
