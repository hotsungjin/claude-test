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
namespace Bundle\Controller\Admin\Share;

use Request;

class LayerAdminLogExcelController extends \Controller\Admin\Controller
{

    /**
     * 개인정보접속기록 엑셀 다운로드 내역 레이어
     *
     * @author cjb3333

     */
    public function index()
    {
        // --- 모듈 호출
        $getValue = Request::get()->toArray();
        $adminLog = \App::load('Component\\Admin\\AdminLogDAO');
        $fields = ['regDt','ip','managerId','data'];
        $data = $adminLog->getList($getValue,$fields,30);
        $page = \App::load('\\Component\\Page\\Page');

        // --- 관리자 디자인 템플릿
        $this->getView()->setDefine('layout', 'layout_layer.php');
        $this->setData('layerFormID', $getValue['layerFormID']);
        $this->setData('view',$getValue['view']);
        $this->setData('data', $data);
        $this->setData('page', $page);
    }
}
