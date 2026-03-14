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

use Component\Order\OrderMultiShipping;
use Component\Member\Manager;
use Framework\Debug\Exception\Except;
use Origin\Enum\Payment\Tosspay\SettingMode;

/**
 * Class OrderBasicController
 *
 * @package Controller\Admin\Policy
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class OrderBasicController extends \Controller\Admin\Controller
{
    /**
     * @inheritdoc
     */
    function index()
    {
        // --- 메뉴 설정
        $this->callMenu('policy', 'order', 'basic');

        try {
            // 주문업그레이드 체크를 확인하기 위한
            $orderUpgrade = '';
            if (file_exists(USERPATH . 'config/orderNew.php')) {
                $sFiledata = \FileHandler::read(\App::getUserBasePath() . '/config/orderNew.php');
                $orderNew = json_decode($sFiledata, true);

                if ($orderNew['flag'] == 'T') {
                    $orderUpgrade['flag'] = 'T';
                } else {
                    $orderUpgrade['flag'] = 'F';
                }
            } else {
                $upgradeFlag = gd_policy('order.upgrade');
                \FileHandler::write(\App::getUserBasePath() . '/config/orderNew.php', json_encode($upgradeFlag));
                if ($upgradeFlag['flag'] == 'T') {
                    $orderUpgrade['flag'] = 'T';
                } else {
                    $orderUpgrade['flag'] = 'F';
                }
            }
            // 주문 업그레이드가 안되어있을때 처리
            if ($orderUpgrade['flag'] == 'F') {
                $session = \App::getInstance('session');
                $managerSession = $session->get(Manager::SESSION_MANAGER_LOGIN, []);

                if ($managerSession['isSuper'] == 'y') {
                    // 복수배송지 여부
                    $orderMultiShipping = new OrderMultiShipping();
                    if ($orderMultiShipping->isUseMultiShipping() === true) {
                        $orderUpgrade['multiShipping'] = 'T';
                    } else {
                        $orderUpgrade['multiShipping'] = 'F';
                    }
                    // 튜닝파일 여부
                    $orderUpgrade['tunning'] = 'F';
                    if (file_exists(USERPATH . 'module/Controller/Front/Goods/NaverPayController.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Controller/Front/Goods/NaverPayController.php';
                    }
                    if (file_exists(USERPATH . 'module/Controller/Front/Order/OrderPsController.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Controller/Front/Order/OrderPsController.php';
                    }
                    if (file_exists(USERPATH . 'module/Controller/Mobile/Order/OrderPsController.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Controller/Mobile/Order/OrderPsController.php';
                    }
                    if (file_exists(USERPATH . 'module/Component/Order/Order.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Component/Order/Order.php';
                    }
                    if (file_exists(USERPATH . 'module/Component/Order/OrderAdmin.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Component/Order/OrderAdmin.php';
                    }
                    if (file_exists(USERPATH . 'module/Component/Order/OrderNew.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Component/Order/OrderNew.php';
                    }
                    if (file_exists(USERPATH . 'module/Component/Order/OrderAdminNew.php')) {
                        $orderUpgrade['tunning'] = 'T';
                        $orderUpgrade['tunningFiles'][] = '/Component/Order/OrderAdminNew.php';
                    }
                } else {
                    $orderUpgrade['flag'] = 'T';
                }
            }

            // --- 각 설정값 정보
            $data = gd_policy('order.basic');

            // --- 기본값 설정
            gd_isset($data['autoDeliveryCompleteFl'], 'n');
            gd_isset($data['autoDeliveryCompleteDay'], '7');
            gd_isset($data['autoOrderConfirmFl'], 'n');
            gd_isset($data['autoOrderConfirmDay'], '7');
            gd_isset($data['userHandleFl'], (gd_is_plus_shop(PLUSSHOP_CODE_USEREXCHANGE) ? 'y' : 'n'));
            gd_isset($data['reagreeConfirmFl'], 'y');
            gd_isset($data['handOrderType'], 'online');
            gd_isset($data['useMultiShippingFl'], 'n');
            gd_isset($data['safeNumberFl'], 'n');
            gd_isset($data['useSafeNumberFl'], 'n');
            gd_isset($data['c_returnStockFl'], 'y');
            gd_isset($data['c_returnCouponFl'], 'n');
            gd_isset($data['c_returnGiftFl'], 'y');
            gd_isset($data['e_returnCouponFl'], 'n');
            gd_isset($data['e_returnGiftFl'], 'y');
            gd_isset($data['e_returnMileageFl'], 'y');
            gd_isset($data['e_returnCouponMileageFl'], 'y');
            gd_isset($data['r_returnStockFl'], 'n');
            gd_isset($data['r_returnCouponFl'], 'n');
            gd_isset($data['refundReconfirmFl'], 'n');

            gd_isset($data['userHandleAutoFl'], 'n');
            gd_isset($data['userHandleAutoScmFl'], 'y');
            gd_isset($data['userHandleAutoSettle'], ['c']);
            gd_isset($data['userHandleAutoStockFl'], 'n');
            gd_isset($data['userHandleAutoCouponFl'], 'n');

            if ($data['autoOrderConfirmDay'] == 0) {
                $data['autoOrderConfirmFl'] = 'n';
            }

            if ($data['autoDeliveryCompleteDay'] == 0) {
                $data['autoDeliveryCompleteFl'] = 'n';
            }

            $checked = [];
            $checked['autoDeliveryCompleteFl'][$data['autoDeliveryCompleteFl']] =
            $checked['autoOrderConfirmFl'][$data['autoOrderConfirmFl']] =
            $checked['userHandleFl'][$data['userHandleFl']] =
            $checked['handOrderType'][$data['handOrderType']] =
            $checked['reagreeConfirmFl'][$data['reagreeConfirmFl']] =
            $checked['useMultiShippingFl'][$data['useMultiShippingFl']] =
            $checked['useSafeNumberFl'][$data['useSafeNumberFl']] =
            $checked['userHandleAdmFl'][$data['userHandleAdmFl']] =
            $checked['userHandleScmFl'][$data['userHandleScmFl']] =
            $checked['c_returnStockFl'][$data['c_returnStockFl']] =
            $checked['c_returnCouponFl'][$data['c_returnCouponFl']] =
            $checked['c_returnGiftFl'][$data['c_returnGiftFl']] =
            $checked['e_returnCouponFl'][$data['e_returnCouponFl']] =
            $checked['e_returnGiftFl'][$data['e_returnGiftFl']] =
            $checked['e_returnMileageFl'][$data['e_returnMileageFl']] =
            $checked['e_returnCouponMileageFl'][$data['e_returnCouponMileageFl']] =
            $checked['r_returnStockFl'][$data['r_returnStockFl']] =
            $checked['r_returnCouponFl'][$data['r_returnCouponFl']] =
            $checked['refundReconfirmFl'][$data['refundReconfirmFl']] =
            $checked['userHandleAutoFl'][$data['userHandleAutoFl']] =
            $checked['userHandleAutoScmFl'][$data['userHandleAutoScmFl']] =
            $checked['userHandleAutoStockFl'][$data['userHandleAutoStockFl']] =
            $checked['userHandleAutoCouponFl'][$data['userHandleAutoCouponFl']] = 'checked="checked"';

            foreach ($data['userHandleAutoSettle'] as $value) {
                $checked['userHandleAutoSettle'][$value] = 'checked="checked"';
            }
            $paycoPolicy = gd_policy('pg.payco');
            $kakaoPolicy = gd_policy('pg.kakaopay');
            $naverPolicy = gd_policy('naverEasyPay.config');
            if(!empty($paycoPolicy) && $paycoPolicy['useType'] != 'N' && $paycoPolicy['testYn'] != 'Y'){ //페이코 사용 할 경우
                $paycoUse = true;
                $this->setData('paycoAutoCancelable', $paycoUse);
            }
            if(!empty($kakaoPolicy) && $kakaoPolicy['testYn'] != 'Y'){ //카카오페이 사용 여부
                $kakaoUse = true;
                $this->setData('kakaoAutoCancelable', $kakaoUse);
            }
            if (!empty($naverPolicy) && $naverPolicy['useYn'] == 'y') { //네이버페이 결제형 사용 여부
                $naverUse = true;
                $this->setData('naverAutoCancelable', $naverUse);
            }

            // 토스페이 사용 여부
            $tossPolicy = gd_policy('pg.tosspay');
            if (!empty($tossPolicy) && $tossPolicy['useMode'] == SettingMode::USE) {
                $this->setData('tossAutoCancelable', true);
            }
        } catch (Except $e) {
            $e->actLog();
        }

        // --- 관리자 디자인 템플릿

        $this->setData('data', $data);
        $this->setData('checked', $checked);
        $this->setData('orderUpgrade', $orderUpgrade);
//        $this->setData('selected', $selected);
    }

}
