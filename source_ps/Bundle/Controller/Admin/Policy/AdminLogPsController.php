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
namespace Bundle\Controller\Admin\Policy;

use Component\Agreement\BuyerInformCode;
use Component\Mail\MailUtil;
use Component\Storage\Storage;
use Component\File\StorageHandler;
use Framework\Debug\Exception\LayerException;
use Request;
use Session;

/**
 * 개인정보접속기록 처리 페이지
 * @author haky <haky2@godo.co.kr>
 */
class AdminLogPsController extends \Controller\Admin\Controller
{
    public function index()
    {
        // 개인정보접속기록 권한 체크
        $adminMenu = \App::load('Component\\Admin\\AdminMenu');
        if (method_exists($adminMenu, 'setAccessWriteEnabledMenu') && method_exists($adminMenu, 'getAccessMenuStatus')) {
            $adminMenu->setAccessWriteEnabledMenu(Session::get('manager.sno'));
            $adminLogPresentationAccess = $adminMenu->getAccessMenuStatus('policy', 'management', 'adminLogList', gd_is_provider());
            if (!$adminLogPresentationAccess) {
                $this->json(
                    ['errorMsg' => '개인정보접속기록 조회 권한이 없습니다.']
                );
            }
        }

        // --- POST 값 처리
        $post = Request::post()->toArray();

        switch ($post['mode']) {
            // 상세 로그 노출
            case 'detail_log':
                try {
                    // 모듈 호출
                    $adminLog = \App::load('\\Component\\Admin\\AdminLogDAO');
                    $result = $adminLog->getDetailAdminLogInfo($post['sno']);
                    $this->json($result);
                } catch (\Exception $e) {
                    throw new LayerException($e->getMessage());
                }
                break;
        }
        exit;
    }
}
