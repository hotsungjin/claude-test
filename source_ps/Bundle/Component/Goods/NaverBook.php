<?php
namespace Bundle\Component\Goods;

use Component\Database\DBTableField;
use App;

class NaverBook
{
    public function __construct()
    {
        $this->db = App::load('DB');
    }

    /**
     * 네이버 도서 설정정보 저장
     */
    public function setNaverBook($goodsNoArr, $naverbookFlag, $naverbookIsbn, $naverbookGoodsType) {
        if (!is_array($goodsNoArr)) {
            $goodsNoArr = [$goodsNoArr];
        }

        if (empty($naverbookIsbn) == false && (!is_numeric($naverbookIsbn) || (strlen($naverbookIsbn) != 10 && strlen($naverbookIsbn) != 13))) {
            $naverbookIsbn = null;
        }

        foreach ($goodsNoArr as $goodsNo) {
            $hasData = $this->getNaverBook($goodsNo);

            $arrData = [
                'goodsNo' => $goodsNo,
                'naverbookFlag' => $naverbookFlag,
                'naverbookIsbn' => $naverbookIsbn,
                'naverbookGoodsType' => $naverbookGoodsType,
            ];

            if (empty($hasData)) { // 등록
                $arrBind = $this->db->get_binding(DBTableField::tableNaverBook(), $arrData, 'insert');
                $this->db->set_insert_db(DB_NAVER_BOOK, $arrBind['param'], $arrBind['bind'], 'y');
            } else { // 수정
                $exclude = ['goodsNo'];
                if (empty($naverbookFlag)) {
                    $exclude[] = 'naverbookFlag';
                    unset($arrData['naverbookFlag']);
                }
                if (empty($naverbookIsbn)) {
                    $exclude[] = 'naverbookIsbn';
                    unset($arrData['naverbookIsbn']);
                }
                if (empty($naverbookGoodsType)) {
                    $exclude[] = 'naverbookGoodsType';
                    unset($arrData['naverbookGoodsType']);
                }
                $arrBind = $this->db->get_binding(DBTableField::tableNaverBook(), $arrData, 'update', null, $exclude);
                $strWhere = 'goodsNo =' . $goodsNo;
                $this->db->set_update_db(DB_NAVER_BOOK, $arrBind['param'], $strWhere, $arrBind['bind']);
            }
        }
    }

    /**
     * 네이버 도서 설정정보 가져오기
     */
    public function getNaverBook($goodsNo) {
        $arrBind = [];
        $this->db->strField = "*";
        $this->db->strWhere = "goodsNo=?";
        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $query = $this->db->query_complete();
        $strSQL = "SELECT " . array_shift($query) . " FROM " . DB_NAVER_BOOK . " " . implode(" ", $query);
        $data = $this->db->query_fetch($strSQL, $arrBind, false);
        unset($arrBind);

        return $data;
    }
}