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


use Component\PlusShop\PlusReview\PlusReviewConfig;
use Component\Policy\Policy;
use Framework\Utility\GodoUtils;
use Framework\Utility\StringUtils;
use Request;

class NaverPayConfigController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu('policy', 'settle', 'naverPayConfig');

        $pgMode = StringUtils::xssClean(Request::get()->get('pgMode'));
        gd_isset($pgMode, 'order');

        $this->getView()->setDefine('layoutPgContent', Request::getDirectoryUri() . '/naver_pay_config_' . $pgMode . '.php');
        $this->addScript(['jquery/jquery.multi_select_box.js']);
        $this->addCss(['payco.css']);
        $this->setDataByPgMode($pgMode);
    }

    public function setDataByPgMode($pgMode)
    {
        $this->setData('pgMode', $pgMode);
        switch ($pgMode) {
            case 'order':
                $this->setDataOrder();
                break;
            case 'pay':
                $this->setDataPay();
                break;
            default:
                break;
        }
    }
    public function setDataOrder()
    {
        $policy = new Policy();
        $naverPayData = $policy->getNaverPaySetting();
        $this->setData('naverPayData', gd_isset($naverPayData));

        $delivery = \App::load(\Component\Delivery\Delivery::class);
        $tmpDelivery = $delivery->getDeliveryCompany(null, true);
        $deliveryCom[0] = '= ' . __('배송 업체') . ' =';
        $deliverySno = 0;
        if (empty($tmpDelivery) === false) {
            foreach ($tmpDelivery as $key => $val) {
                // 기본 배송업체 sno
                if ($key == 0) {
                    $deliverySno = $val['sno'];
                }
                $deliveryCom[$val['sno']] = $val['companyName'];
            }
            unset($tmpDelivery);
        }
        $plusReview = new PlusReviewConfig();
        $checked['areaDelivery'][$naverPayData['deliveryData'][\Session::get('manager.scmNo')]['areaDelivery']] = 'checked';

        $this->setData('checked', gd_isset($checked));
        $this->setData('scmNo', gd_isset(\Session::get('manager.scmNo')));
        $this->setData('deliveryCom', gd_isset($deliveryCom));
        $this->setData('deliverySno', gd_isset($deliverySno));
        $this->setData('isPlusReview', GodoUtils::isPlusShop(PLUSSHOP_CODE_REVIEW));
        $this->setData('disablePlusReview', $plusReview->getConfig('useFl') != 'y' ? 'disabled' : '');
    }
    public function setDataPay()
    {
        $policy = new Policy();
        $naverEasyPayData = $policy->getNaverEasyPaySetting();
        gd_isset($naverEasyPayData['useYn'], 'n');
        $checked['useYn'][$naverEasyPayData['useYn']]= 'checked';
        $this->setData('naverEasyPayData', $naverEasyPayData);
        $this->setData('checked', $checked);
    }
}
