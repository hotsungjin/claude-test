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

namespace Bundle\Component\Cart;

/**
 * Cart Functions Collect
 *
 * 기존 Cart.php의 생성자부분에 종속이 너무 심화되었으나
 * 이미 튜닝하여 사용중인 사용자가있어, 별도로 분리
 */
class CartHelper
{

    /**
     * 장바구니 상품 개수 체크
     *
     * @return int 상품 개수
     */
    public static function getCartCount(): int
    {
        $cartDao = \App::load('\Component\Cart\CartDAO');

        return gd_is_login()
            ? $cartDao->countByMemberNo(\Session::get('member.memNo'))
            : $cartDao->countBySiteKey(\Session::get('siteKey'));
    }

    public static function getCartGoodsList(): array
    {
        $cartDao = \App::load('\Component\Cart\CartDAO');

        return gd_is_login()
            ? $cartDao->getCartListByMemberNo(\Session::get('member.memNo'))
            : $cartDao->getCartListBySiteKey(\Session::get('siteKey'));
    }

}
