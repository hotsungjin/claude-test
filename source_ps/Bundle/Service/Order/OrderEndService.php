<?php

namespace Bundle\Service\Order;

use Bundle\Component\Order\OrderAdmin;
use Framework\Log\Logger;

class OrderEndService
{
    protected $orderAdmin;
    protected $logger;

    public function __construct(
        OrderAdmin $orderAdmin,
        Logger $logger
    )
    {
        $this->logger = $logger;
        $this->orderAdmin = $orderAdmin;
    }

    /**
     * pg 에서의 주문 실패 시
     * 각 PG PgReturn class 에서 pgUserStop , pgTimeOver, pgDbError 오류로 넘어오는 경우
     * @param string $mode
     * @param string $orderNo
     * @return string
     * @throws \Exception
     */
    public function failProcess(string $mode, string $orderNo): string
    {
        $this->logger->channel('order')->info(__METHOD__. ' 주문 실패 mode : '. $mode . ' 주문번호 : '. $orderNo);
        switch ($mode) {
            case 'pgUserStop' :
                $pgFailReason = __('고객님의 결제 중단에 의해서 주문이 취소 되었습니다.');
                $this->orderAdmin->setStatusChangePgStop($orderNo);
                break;
            case 'pgTimeOver' :
                $pgFailReason = __('고객님의 결제창의 인증시간이 초과되어 주문이 취소 되었습니다.');
                $this->orderAdmin->setStatusChangePgStop($orderNo);
                break;
            case 'pgDbError' :
                $pgFailReason = __('결제 시도 시 저장 실패로 인해 주문이 취소 되었습니다.');
                $goodsData = $this->orderAdmin->getOrderGoods($orderNo);
                $this->orderAdmin->updateStatusPreprocess($orderNo, $goodsData, 'f', 'f3', '중계서버 DB 오류로 인해 ', true);
                break;
            case 'pgWaitingCallback' :
                $pgFailReason = __('결제가 정상적으로 완료되지 않았을 수 있습니다. 잠시 후 자동으로 확인되며, 결제 내역이 없다면 다시 시도해 주세요.');
                $this->orderAdmin->setStatusChangePgWaitingCallback($orderNo);
                break;
            default :
                $pgFailReason = '';
                break;
        }

        return $pgFailReason;
    }
}
