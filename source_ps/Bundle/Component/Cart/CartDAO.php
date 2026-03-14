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
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Component\Cart;

class CartDAO
{
    private $db;

    public function __construct()
    {
        $this->db = \App::load('DB');
    }

    public function countByMemberNo(int $memberNo): int
    {
        $arrBind = null;

        $strWhere = "memNo = ? AND directCart = 'n'";
        $this->db->bind_param_push($arrBind, 'i', $memberNo);
        $strSQL = 'SELECT count(*) as cnt FROM ' . DB_CART . ' WHERE ' . $strWhere;
        $getData = $this->db->query_fetch($strSQL, $arrBind, false);

        return $getData['cnt'];
    }

    public function countBySiteKey(string $siteKey): int
    {
        $arrBind = null;

        $strWhere = "siteKey = ? AND directCart = 'n'";
        $this->db->bind_param_push($arrBind, 's', $siteKey);
        $strSQL = 'SELECT count(*) as cnt FROM ' . DB_CART . ' WHERE ' . $strWhere;
        $getData = $this->db->query_fetch($strSQL, $arrBind, false);

        return $getData['cnt'];
    }

    public function getCartListByMemberNo(int $memberNo): array
    {
        $arrBind = $arrJoin = [];

        $arrJoin[] = 'LEFT JOIN ' . DB_GOODS . ' as g ON c.goodsNo = g.goodsNo';
        $arrJoin[] = 'LEFT JOIN ' . DB_GOODS_OPTION . ' as go ON c.optionSno = go.sno AND c.goodsNo = go.goodsNo';

        $this->db->strField = 'c.goodsNo, g.goodsNm, c.goodsCnt, g.goodsPrice, go.optionPrice, g.optionName, go.optionValue1, go.optionValue2, go.optionValue3, go.optionValue4, go.optionValue5';
        $this->db->strJoin = implode(' ', $arrJoin);
        $this->db->strWhere = "c.memNo = ? AND c.directCart = 'n'";
        $this->db->bind_param_push($arrBind, 's', $memberNo);
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_CART . ' as c ' . implode(' ', $query);

        return $this->db->secondary()->query_fetch($strSQL, $arrBind);
    }

    public function getCartListBySiteKey(string $siteKey): array
    {
        $arrBind = $arrJoin = [];

        $arrJoin[] = 'LEFT JOIN ' . DB_GOODS . ' as g ON c.goodsNo = g.goodsNo';
        $arrJoin[] = 'LEFT JOIN ' . DB_GOODS_OPTION . ' as go ON c.optionSno = go.sno AND c.goodsNo = go.goodsNo';

        $this->db->strField = 'c.goodsNo, g.goodsNm, c.goodsCnt, g.goodsPrice, go.optionPrice, g.optionName, go.optionValue1, go.optionValue2, go.optionValue3, go.optionValue4, go.optionValue5';
        $this->db->strJoin = implode(' ', $arrJoin);
        $this->db->strWhere = "c.siteKey = ? AND c.directCart = 'n'";
        $this->db->bind_param_push($arrBind, 's', $siteKey);
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_CART . ' as c ' . implode(' ', $query);

        return $this->db->secondary()->query_fetch($strSQL, $arrBind);
    }
}
