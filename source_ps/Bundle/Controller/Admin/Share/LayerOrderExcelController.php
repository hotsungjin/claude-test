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

namespace Bundle\Controller\Admin\Share;

use Component\Excel\ExcelForm;
use Framework\Utility\ArrayUtils;
use Component\Order\OrderDelete;
use Framework\Security\Token;
use App;

/**
 * 레이어 엑셀 다운로드
 * @package Bundle\Controller\Admin\Share
 * @author  choisueun
 */
class LayerOrderExcelController extends \Controller\Admin\Controller
{
    private $excelDownloadCode = [
        'order' => ExcelForm::EXCEL_DOWNLOAD_REASON_CODE_ORDER,
    ];

    public function index()
    {
        $request = App::getInstance('request');
        $postValue = $request->post()->toArray();
        $setData['sno'] = $postValue['data']['sno'];
        $setData['downloadFileName'] = 'order_delete' . time();

        try {
            $_tmp = pathinfo($request->getParserReferer()->path);
            $menu = basename($_tmp['dirname']);

            // 엑셀 다운로드 사유
            $downloadReasonList = gd_code($this->excelDownloadCode[$menu]);
            if ($downloadReasonList !== false) {
                $this->setData('reasonList', ArrayUtils::changeKeyValue($downloadReasonList));
                $this->setData('reasonUseFl', 'y');
            } else {
                $this->setData('reasonUseFl', 'n');
            }

            $excelForm = \App::load('Component\\Excel\\ExcelForm');
            $this->setData('setData', $setData);
            $this->setData('layerExcelToken', Token::generate('layerExcelToken')); // CSRF 토큰 생성
            $this->setData('excelFileName', urlencode($excelForm->locationList['order']['order_delete']));
            $this->setData('managerId', \Session::get('manager.managerId'));

            //--- 관리자 디자인 템플릿
            $this->getView()->setDefine('layout', 'layout_layer.php');
        } catch (\Exception $e) {
            throw $e;
        }

    }
}
