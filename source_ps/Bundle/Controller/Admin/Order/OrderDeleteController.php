<?php

namespace Bundle\Controller\Admin\Order;

use Framework\Debug\Exception\AlertOnlyException;
use Component\Order\OrderDelete;
use Globals;
use App;

class OrderDeleteController extends \Controller\Admin\Controller
{
    public function Index()
    {

        try {
            // --- 메뉴 설정
            $this->callMenu('order', 'order', 'delete');

            $gGlobal = Globals::get('gGlobal');
            $orderDelete = new OrderDelete();

            // 5년 경과 주문 삭제 건 메소드 체크해서 따로 페이지 보여줌
            $filePath = SYSSRCPATH . '/Bundle/Component/Order/OrderDelete.php';
            if (is_file($filePath) === false) {
                $this->getView()->setDefine('layoutContent', 'order/_admin_tuning_error.php');
            }else {
                if (method_exists($orderDelete, 'getDeleteOrderList') === false) {
                    $this->getView()->setDefine('layoutContent', 'order/_admin_tuning_error.php');
                    return false;
                }
            }

            $getValue = \Request::get()->toArray();

            /* 기본값 */
            // 상점
            $checked['mallFl'][1] = 'checked="checked"';

            // 예외 공급사
            if ($getValue['scmNo']) {
                $search['scmFl'] = 'y';
                $search['scmNo'] = $getValue['scmNo'];
                $search['scmNoNm'] = $getValue['scmNoNm'];
            }

            // 삭제 대상 내역
            $getData = $orderDelete->getDeleteOrderList();

            // 기간만료 체크
            $orderDelete->lateDate();

            $page = App::load('\\Component\\Page\\Page'); // 페이지 재설정

            $this->setData('checked', $checked);
            $this->setData('search', $search);
            $this->setData('page', $page);
            $this->setData('getData', $getData['data']);
            $this->setData('gGlobal', $gGlobal);

        } catch (AlertOnlyException $e) {
            throw new AlertOnlyException($e->getMessage());
        }
    }
}