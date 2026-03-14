<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Controller\Admin\Share;

use Component\Excel\ExcelForm;
use Framework\Debug\Exception\LayerException;
use Framework\Utility\ArrayUtils;
use Framework\Security\Token;
use Session;

class LayerExcelServicePrivacyController extends \Controller\Admin\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $getValue = $request->get()->toArray();

        try {
            $excelForm = \App::load('Component\\Excel\\ExcelForm');

            if ($session->get('manager.functionAuthState') == 'check' && $session->get('manager.functionAuth.memberExcelDown') != 'y') {
                throw new LayerException(__('권한이 없습니다. 권한은 대표운영자에게 문의하시기 바랍니다.'));
            }

            $menu = 'member';
            $location = 'service_privacy_down';
            $downloadReasonList = gd_code(ExcelForm::EXCEL_DOWNLOAD_REASON_CODE_MEMBER);

            $excelForm = \App::load('\\Component\\Excel\\ExcelForm');
            $formSno = $excelForm->getsServicePrivacyDownSno($location);// 엑셀 폼 sno값

            $this->getView()->setDefine('layout', 'layout_layer.php');

            $this->setData('menu', 'member');
            $this->setData('location', $location);
            $this->setData('formSno', $formSno);
            $this->setData('targetListForm', $getValue['targetListForm']);
            $this->setData('targetListSno', $getValue['targetListSno']);
            $this->setData('targetForm', $getValue['targetForm']);
            $this->setData('searchCount', $getValue['searchCount']);
            $this->setData('totalCount', $getValue['totalCount']);

            $this->setData('excelFileName', urlencode($excelForm->locationList[$menu][$location]));
            $this->setData('managerId', $session->get(\Component\Member\Manager::SESSION_MANAGER_LOGIN . '.managerId'));
            $this->setData('reasonList', ArrayUtils::changeKeyValue($downloadReasonList));

            $request->get()->set('menu', $menu);
            $request->get()->set('location', $location);

            // 변경기간 설정
            $policy = \App::load('\\Component\\Policy\\Policy');
            $currentServicePrivacy = $policy->getValue('member.servicePrivacy');
            gd_isset($currentServicePrivacy['period'], 7);
            $checked['period'][$currentServicePrivacy['period']] = 'checked="checked"';
            $this->setData('checked', $checked);
            $this->setData('layerExcelToken', Token::generate('layerExcelToken')); // CSRF 토큰 생성
        } catch (\Exception $e) {
            throw $e;
        }
    }
}