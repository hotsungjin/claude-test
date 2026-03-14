<?php

namespace Controller\Front\Order;

use App;
use Request;

use Session;
use Component\Goods\Goods;
use Component\Cart\Cart;
use Framework\Debug\Exception\AlertRedirectException;

class OrderController extends \Bundle\Controller\Front\Order\OrderController
{
	public function index() {
		parent::index();
		//2020-12-04 brown 서버시간 기준으로 배송문구 변경
		$today = date("Y-m-d");
		$time = date("H");
		$week = array("su" , "mo"  , "tu" , "we" , "th" , "fr" ,"sa") ;
		$weekday = $week[date('w', strtotime($today))];
		if(($weekday == "fr" And 	$time > 14) || $weekday == "sa" || $weekday == "su"){
			$message = "지금 결제 시, 화요일 아침 8시 도착";
		}else {
			$message = "오후 2시 이전 결제 시, 내일 오전 8시 도착";
		}
		$this->setData('message', $message);
		//2020-12-04 brown 서버시간 기준으로 배송문구 변경

		$b2bOrderFl = false;
		$cartInfo = $this->getData('cartInfo');
		foreach($cartInfo as $scmNo => $scmCartInfo) {
			foreach($scmCartInfo as $deliveryNo => $deliveryCartInfo) {
				foreach($deliveryCartInfo as $goodsKey => $cartGoods) {
					if($cartGoods['price']['customSaleDcPrice']) {
						$totalCustomSaleDcPrice = $cartGoods['price']['customSaleDcPrice'];
					}

					if($cartGoods['price']['customSaleText']) {
						$totalCustomSaleText = $cartGoods['price']['customSaleText'];
					}

					if($cartGoods['b2bOrderFl'] === true) {
						$b2bOrderFl = true;
					}
				}
			}
		}

		$this->setData('b2bOrderFl', $b2bOrderFl);
		$this->setData('totalCustomSaleDcPrice', $totalCustomSaleDcPrice);
		$this->setData('totalCustomSaleText', $totalCustomSaleText);
		$this->setData('totalGoodsDcPrice', $this->getData('totalGoodsDcPrice') - $totalCustomSaleDcPrice);


		//결제 총액 20220610 하믿음 추가
		$totalSettlePrice = $this->getData('totalSettlePrice');
		//$totalGoodsPrice  = $this->getData('totalGoodsPrice');
		//$totalGoodsDcPrice  = $this->getData('totalGoodsDcPrice');
 
 		foreach($cartInfo as $scmNo => $scmCartInfo) {
			foreach($scmCartInfo as $deliveryNo => $deliveryCartInfo) {
				foreach($deliveryCartInfo as $goodsKey => $cartGoods) {

			        $goods = new Goods();
			        $getGoods = $goods->getGoodsInfo($cartGoods['goodsNo']);
					//신규회원 혜택상품이라면 
					if($getGoods['maxEntryDt'] == 'y'){
						
						$meminfo = Session::get('member');
 				        if(!$meminfo['memNo']){
							throw new AlertRedirectException(__('본 상품은 신규 회원의 첫 구매에 한해서 구매가 가능합니다. 장바구니에서 삭제됩니다.'), null, null, '../order/cart.php', 'top');
 				        }
			
				        //신규화원인지
						$db = \App::load('DB');
					    $getMemberQuery = "SELECT * FROM " . DB_MEMBER . " AS m  WHERE m.memNo = ".$meminfo['memNo'];
					    $getMember = $db->query_fetch($getMemberQuery, null, false);
					    $now_time  = strtotime(date('Y-m-d')); //현재
					    $limit_time =  strtotime(substr($getMember['entryDt'],0,10)." +7 days"); // 혜택 주문마감일 		       
				        if( $now_time > $limit_time){
 							$Cart = new Cart();
 							$Cart->setCartDelete($cartGoods );
  							throw new AlertRedirectException(__('본 혜택은 회원가입 7일 전까지만 사용할 수 있습니다. 혜택 기간이 만료되었음을 안내드립니다. '), null, null, '../order/cart.php', 'top');
				        }			        


			 	 	    $strSQL = 'SELECT * FROM es_config WHERE  groupCode="custom" AND code = "maxEntryDtFilter" ';
				 		$maxEntryDtFilter =  $db->query_fetch($strSQL, null, false);  
				  		$maxEntryDtFilter = json_decode($maxEntryDtFilter['data'],true); 
						if(!empty($maxEntryDtFilter['maxEntryDtFilter'])){
							$WHERE_BOG = "AND bog.goodsNo NOT IN({$maxEntryDtFilter['maxEntryDtFilter']})";
						}	 

						//취소 : 자동,품절,관리자 반품완료 , 환불 완료외에 주문이 있는지
					    $getMemberOrderQuery = "
					    	SELECT COUNT( * ) AS cnt FROM ".DB_ORDER." AS bo
								INNER JOIN  " . DB_ORDER_GOODS . " AS bog ON bog.orderNo = bo.orderNo  
							WHERE
								bo.memNo = ".$meminfo['memNo']."  AND  bog.orderStatus NOT IN('f1','f2','f3','f4','c1','c2','c3','c4','b4','r3') $WHERE_BOG
						";
					    $getMemberOrder = $db->query_fetch($getMemberOrderQuery, null, false);
			 	        //첫구매 회원인지$getMember['saleCnt'] &&
				        if( !empty($getMemberOrder['cnt'])  ){
 							$Cart = new Cart();
 							$Cart->setCartDelete($cartGoods);
							throw new AlertRedirectException(__('본 상품은 신규 회원의 첫 구매에 한해서 구매가 가능합니다. 장바구니에서 삭제됩니다. '), null, null, '../order/cart.php', 'top');
 				        }			        

				        if( $cartGoods['goodsCnt'] > 1){
					        $cartChange['cartSno'] = $cartGoods['sno'];
					        $cartChange['goodsNo'] = $cartGoods['goodsNo'];
					        $cartChange['goodsCnt'] = 1;
 							$Cart = new Cart();
 							$Cart->setCartCnt($cartChange);
					        throw new AlertRedirectException(__('이벤트 상품은 한 번에 1개만 구매 가능합니다, 장바구니에서 일부 수량이 삭제됩니다.'), null, null, '../order/cart.php', 'top');
				        }
				        				        
				        //신규회원 혜택상품이 장바구니에 없는지
			 			$getCartQuery = "
			 			SELECT SUM(goodsCnt) AS cnt  FROM ".DB_CART." AS ct 
			 				INNER JOIN ".DB_GOODS." AS g ON ct.goodsNo = g.goodsNo
			 			WHERE ct.memNo='{$getMember['memNo']}' AND g.maxEntryDt = 'y' 
			 			GROUP BY ct.memNo
			 			";
						$getCart = $db->query_fetch($getCartQuery, null, false);
						if($getCart['cnt']>1){
 							$Cart = new Cart();
 							$Cart->setCartDelete($cartGoods );
							throw new AlertRedirectException(__('혜택 상품이 장바구니에 담겨있습니다. 이벤트 상품은 1개만 구매 가능합니다.'), null, null, '../order/cart.php', 'top');
						}
						
						if( $totalSettlePrice < 20000){
							throw new AlertRedirectException(__('신규회원 이벤트 상품 외 다른 상품을 2만 원 이상 담아주시면 구매 가능합니다.'), null, null, '../order/cart.php', 'top');
						}
		 
					}
 					
				}
			}
		}

	}
}