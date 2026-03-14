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

namespace Bundle\Controller\Admin\Base;

use Bundle\Component\Validator\AdminInquiryDataValidator;
use Framework\Debug\Exception\LayerNotReloadException;
use Framework\Debug\Exception\AlertRedirectException;
use Message;
use Request;

/**
 * Grow 상점 1:1문의 저장 처리 페이지
 * @author nari-jo <nari-jo@nhn-commerce.com>
 */
class IndexGrowQnaPsController extends \Controller\Admin\Controller
{
    /**
     * index
     *
     */
    public function index()
    {
        //--- 모듈 호출
        $growApi = \App::load('\\Component\\Godo\\GodoGrowServerApi');
        try {
            $postData = Request::post()->toArray();
            switch ($postData['mode']) {
                //--- 1:1문의 전송
                case 'grow_qna_register':
                    AdminInquiryDataValidator::validateInquiry($postData);
                    $growApi->sendGodoQna($postData);
                    $this->layer(__('고객님의 문의가 접수되었습니다.'));
                    break;

                // 2차 카테고리 선택
                case 'select_category':
                    // 샵바이로 이전 만 2차 depth 존재
                    if ($postData['categoryNo'] == 573) {
                        $data = $growApi->getCategorySubNo($postData);
                    }

                    echo json_encode(
                        [
                            "state" => true,
                            "info"  => $data,
                        ]
                    );
                    exit;
            }
        } catch (\Exception $e) {
            throw new LayerNotReloadException($e->getMessage());
        }
        exit();
    }
}
