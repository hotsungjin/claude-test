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
 * 회원 신고 Class
 */
namespace Bundle\Component\Member;

use Component\Board\Board;
use Component\Board\BoardView;
use Component\Member\Util\MemberUtil;
use Component\Database\DBTableField;
use Session;

class MemberReport extends \Component\AbstractComponent
{
    /**
     * 생성자
     */
    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }

    /**
     * 회원 신고 데이터 가져오기
     *
     * @return array data
     */
    public function getReportData($writerMemNo)
    {
        $memNo = Session::get('member.memNo');
        $arrBind = [];
        $strSQL = 'SELECT * FROM ' . DB_MEMBER_REPORT . ' WHERE writerMemNo=? AND memNo = ? AND blockAllBoardFl = ?';
        $this->db->bind_param_push($arrBind, 's', $writerMemNo);
        $this->db->bind_param_push($arrBind, 's', $memNo);
        $this->db->bind_param_push($arrBind, 's', 'y');
        $data = $this->db->query_fetch($strSQL, $arrBind, false);

        return $data;
    }

    /**
     * 회원 신고하기
     *
     * @return bool
     */
    public function getWrite($req)
    {
        $req['memNo'] = \Session::get('member.memNo');
        $req['memId'] = \Session::get('member.memId');
        if (!gd_isset($req['blockAllBoardFl'])) {
            $req['blockAllBoardFl'] = 'n';
        }
        if ($req['memoSno'] > 0) { // 댓글인 경우
            $req['writerMemNo'] = $req['memoWriterMemNo'];
        }
        $arrBind = $this->db->get_binding(DBTableField::tableMemberReport(), $req, 'insert');
        $this->db->set_insert_db(DB_MEMBER_REPORT, $arrBind['param'], $arrBind['bind'], 'y');

        return true;
    }

    /**
     * 탈퇴여부 조회
     *
     * @return bool
     */
    public function isHackout($memNo)
    {
        if ($memNo > 0) {
            $getHackout = $this->db->getData(DB_MEMBER_HACKOUT, $memNo, 'memNo');
            if ($getHackout) {
                return true;
            }
        }
        return false;
    }

    /**
     * 휴면여부 조회
     *
     * @return bool
     */
    public function isSleep($memNo)
    {
        if ($memNo > 0) {
            $getSleep = $this->db->getData(DB_MEMBER_SLEEP, $memNo, 'memNo');
            if ($getSleep) {
                return true;
            }
        }
        return false;
    }
}
