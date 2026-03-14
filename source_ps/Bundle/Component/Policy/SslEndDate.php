<?php

namespace Bundle\Component\Policy;

use Component\Database\DBTableField;
use Framework\Debug\Exception\DatabaseException;

class SslEndDate
{
    /** @var \Framework\Database\DBTool $db */
    protected $db;

    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }

    /**
     * 만료일이 $restDay 미만으로 남은 보안서버 리스트 가져오기
     *
     * @param int|null $restDay
     * @throws DatabaseException
     */
    public function getSslEndDateArr(int $restDay = null)
    {
        $this->db->strField = 'DATEDIFF(sslConfigEndDate, NOW()) as dateDiff, ' . implode(', ', DBTableField::setTableField('tableSslConfig'));
        $this->db->arrBind = $this->db->arrWhere = [];
        $this->db->arrWhere[] = 'sslConfigUse = ?';
        $this->db->bind_param_push($this->db->arrBind, 's', 'y');

        if ($restDay !== null) {
            $this->db->arrWhere[] = 'DATEDIFF(sslConfigEndDate, NOW()) < ?';
            $this->db->bind_param_push($this->db->arrBind, 'd', $restDay);
        }

        $this->db->strWhere = implode(' AND ', gd_isset($this->db->arrWhere));
        $this->db->strOrder = 'dateDiff ASC';

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_SSL_CONFIG . implode(' ', $query);
        $sslConfigArr = $this->db->query_fetch($strSQL, $this->db->arrBind);

        return $sslConfigArr;
    }
}
