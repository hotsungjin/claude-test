<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Smart to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

/**
 * 게시판 컬럼 추가
 */
namespace Bundle\Component\Board;

use Component\Storage\Storage;
use Component\Database\DBTableField;

class BoardAddColumns extends \Component\AbstractComponent
{

    public function boardAddColumns() {
        $sqlQuery = 'SELECT bdId FROM ' . DB_BOARD . ' WHERE sno > 6';
        $boardList = $this->db->query_fetch($sqlQuery);

        $column = 'isShow';
        foreach($boardList as $val) {
            $strSQL  = "SHOW COLUMNS FROM es_bd_".$val['bdId']." WHERE Field = '".$column."';";
            $result = $this->db->query($strSQL);
            if(!$result->num_rows) {
                $strSQL = "alter table es_bd_" . $val['bdId'] . " add ".$column." enum ('y', 'n') default 'y' not null comment '신고여부 y신고해제 n신고';";
                $this->db->query($strSQL);
            }
        }
    }
}