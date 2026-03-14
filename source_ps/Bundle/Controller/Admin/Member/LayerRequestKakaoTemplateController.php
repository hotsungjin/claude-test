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
namespace Bundle\Controller\Admin\Member;
use Exception;

/**
 * 카카오 알림톡 템플릿 검수요청
 * [관리자 모드] 카카오 알림톡 템플릿 검수요청 레이어
 *
 * @package Bundle\Controller\Admin\Member
 */
class LayerRequestKakaoTemplateController extends \Controller\Admin\Controller
{
    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function index()
    {
        try {
            $templateCode = \Request::post()->get('templateCode'); // 카카오 템플릿 코드
            $this->getView()->setPageName('member/layer_request_kakao_template.php');
            $this->getView()->setDefine('layout', 'layout_layer.php');
            $this->setData('templateCode', $templateCode);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
