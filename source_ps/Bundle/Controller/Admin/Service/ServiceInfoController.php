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

namespace Bundle\Controller\Admin\Service;

use Component\Godo\GodoGongjiServerApi;
use Framework\Debug\Exception\AlertRedirectException;
use Request;
use Framework\Utility\StringUtils;

/**
 * 부가서비스 통합 안내 페이지
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class ServiceInfoController extends \Controller\Admin\Controller
{
    /**
     * index
     *
     */
    public $migrationMenu = [
        'member_auth_info' => 'security', 'member_ipin_info' => 'security', 'member_dbins_info' => 'security', 'member_nicekeeper_info' => 'security',
        'member_safenumber_info' => 'security', 'marketing_powermail_info' => 'consulting', 'marketing_realpacking_info' => 'consulting',
        'consulting_refusal_info' => 'consulting', 'marketing_livefeed_info' => 'consulting', 'member_kakao_alrim' => 'consulting', 'member_kakaocounsel_info' => 'consulting',
        'convenience_mobilecti_info' => 'consulting', 'convenience_packing_info' => 'purchase', 'marketing_printing_info' => 'purchase', 'convenience_shoplinker_info' => 'delivery',
        'design_postoffice_info' => 'delivery', 'design_logisticsInfo' => 'delivery', 'design_sellby' => 'goods',
        'marketing_smartcall_info' => 'consulting',
        'service_ssl' => 'security', 'marketing_livelog_info' => 'marketing', 'pg_naverPay_info' => 'pg', 'livecommerce_gripcloud_info' => 'livecommerce',
    ];
    public function index()
    {
        try {
            // _GET 데이터
            $getValue = StringUtils::xssArrayClean(Request::get()->toArray());

            if (empty($getValue['menu']) === true) {
                throw new \Exception('NO_MEMU');
            }

            //--- 메뉴 설정
            $menu = explode('_', $getValue['menu']);
            if(in_array($getValue['menu'], array_keys($this->migrationMenu))) $menu[0] = $this->migrationMenu[$getValue['menu']];
            $this->callMenu('service', $menu[0], $menu[1]);
            unset($menu);

        } catch (\Exception $e) {
            if($getValue['menu'] == 'convenience_payco_search_info') {
                throw $e;
            }
            throw new AlertRedirectException($e->getMessage(), 0, $e, "/service/service_info.php?menu=power_main");
        }

        // 공용 페이지 사용
        $this->getView()->setDefine('layoutContent', Request::getDirectoryUri() . '/service_info.php');
        $this->setData('menu', $getValue['menu']);
    }
}
