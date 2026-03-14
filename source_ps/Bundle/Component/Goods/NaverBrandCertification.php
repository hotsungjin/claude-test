<?php
namespace Bundle\Component\Goods;

use Component\Database\DBTableField;
use App;

class NaverBrandCertification
{
    public function __construct()
    {
        $this->db = App::load('DB');
    }

    /**
     * 브랜드 인증상품 여부 저장
     */
    public function setCertFl($goodsNo, $useFl) {
        $hasData = $this->getCertFl($goodsNo);
        $arrData = [
            'goodsNo' => $goodsNo,
            'brandCertFl' => $useFl,
        ];
        if (empty($hasData)) { // 등록
            $arrBind = $this->db->get_binding(DBTableField::tableNaverBrandCertification(), $arrData, 'insert');
            $this->db->set_insert_db(DB_NAVERBRANDCERTIFICATION, $arrBind['param'], $arrBind['bind'], 'y');
            unset($arrData);
        } else { // 수정
            $arrData = [
                'brandCertFl' => $useFl,
            ];
            $exclude = ['goodsNo'];
            $arrBind = $this->db->get_binding(DBTableField::tableNaverBrandCertification(), $arrData, 'update', null, $exclude);
            $strWhere = 'goodsNo =' . $goodsNo;
            $this->db->set_update_db(DB_NAVERBRANDCERTIFICATION, $arrBind['param'], $strWhere, $arrBind['bind']);
        }
    }

    /**
     * 브랜드 인증상품 설정정보 가져오기
     */
    public function getCertFl($goodsNo) {
        $arrBind = [];
        $this->db->strField = "*";
        $this->db->strWhere = "goodsNo=?";
        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $query = $this->db->query_complete();
        $strSQL = "SELECT " . array_shift($query) . " FROM " . DB_NAVERBRANDCERTIFICATION . " " . implode(" ", $query);
        $data = $this->db->query_fetch($strSQL, $arrBind, false);
        unset($arrBind);

        return $data;
    }
}