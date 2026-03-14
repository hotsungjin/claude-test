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
 * 게시판 신고 Class
 */
namespace Bundle\Component\Board;

use Component\Member\Util\MemberUtil;
use Component\Storage\Storage;
use Component\Database\DBTableField;
use Component\PlusShop\PlusReview\PlusReviewDao;

class BoardReport extends \Component\AbstractComponent
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

    public function reportGet($req)
    {
        if($req['listType'] == 'memo') {
            $strWhere = " AND memoSno=? ";
        } else {
            $strWhere = " AND bdSno=? AND memoSno=0 ";
        }

        $strSQL = "SELECT *  FROM " . DB_BOARD_REPORT . "   WHERE bdId=?".$strWhere."ORDER BY sno DESC LIMIT 1";
        $arrBind = [];
        $this->db->bind_param_push($arrBind, 's', $req['bdId']);
        $this->db->bind_param_push($arrBind, 'i', $req['sno']);
        $result = $this->db->query_fetch($strSQL, $arrBind,false);

        if($result && empty($result['memId'])) {
            $query = "SELECT memId FROM ". DB_MEMBER ." WHERE memNo=?";
            $memId = $this->db->query_fetch($query, ['i', $result['memNo']], false)['memId'];
            $result['memId'] = $memId;
        }
        return $result;
    }

    /**
     * 게시글 신고하기
     *
     * @return array data
     */
    public function getWrite($req)
    {
        $arrBind = [];
        $getData = $this->getData($req);
        $strSQL = "SELECT isShow FROM " . $getData['tableName'] . " WHERE sno=?";
        $this->db->bind_param_push($arrBind, 'i', $getData['sno']);
        $isShow = $this->db->query_fetch($strSQL, $arrBind, false)['isShow'];
        if($isShow == 'n') {
            throw new \Exception(__('이미 신고된 게시글입니다.'));
        }

        $req['memNo'] = \Session::get('member.memNo');
        $req['memId'] = \Session::get('member.memId');
        $req['isShow'] = 'n';
        $arrBind = $this->db->get_binding(DBTableField::tableBoardReport(), $req, 'insert');
        $this->db->set_insert_db(DB_BOARD_REPORT, $arrBind['param'], $arrBind['bind'], 'y');

        $result =  $this->updateIsShow($req, false);
        return $result;
    }
    /**
     * 게시글 신고 해제하기
     *
     * @return array data
     */
    public function reportModify($req)
    {
        if (!is_array($req['sno'])) {
            $req['sno'] = [$req['sno']];
        }

        $manager = \Session::get('manager');

        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $manager['sno']);
        $this->db->bind_param_push($arrBind, 's', $manager['lastLoginIp']);
        $this->db->bind_param_push($arrBind, 's', 'y');
        $this->db->bind_param_push($arrBind, 's', $req['bdId']);

        foreach($req['sno'] as $val) {
            $bindQuery[] = '?';
            $this->db->bind_param_push($arrBind, 'i', $val);
        }
        $where = " AND bdSno IN (". implode(',', $bindQuery) .") AND memoSno = 0";
        if ($req['listType'] == 'memo') {
            $where = " AND memoSno IN (". implode(',', $bindQuery) .")";
        }
        $query = "UPDATE ". DB_BOARD_REPORT ." SET managerNo=?, managerIp=?, isShow=?,modDt=now() WHERE bdId=?". $where;
        $this->db->bind_query($query, $arrBind);

        foreach ($req['sno'] as $sno) {
            $setData['bdId'] = $req['bdId'];
            $setData['bdSno'] = $sno;
            if (empty($req['goodsNo'][$sno]) == false) {
                $setData['goodsNo'] = $req['goodsNo'][$sno];
            } else if (empty($req['goodsNoArry'][$sno]) == false) {
                $setData['goodsNo'] = $req['goodsNoArry'][$sno];
            }
            if ($req['listType'] == 'memo') {
                $arrBind = [];
                $this->db->bind_param_push($arrBind, 's', $req['bdId']);
                $this->db->bind_param_push($arrBind, 'i', $sno);
                $strSQL = "SELECT bdSno FROM ".DB_BOARD_REPORT." WHERE bdId=? AND memoSno=? ORDER BY sno DESC LIMIT 1";
                $getData = $this->db->query_fetch($strSQL, $arrBind, false);
                $setData['bdSno'] = $getData['bdSno'];
                $setData['memoSno'] = $sno;
            }
            $this->updateIsShow($setData, true);
        }

        return true;
    }

    public function updateIsShow($req, $isShow = false) {
        $arrBind = [];
        $set = ($isShow) ? "isShow='y'" : "isShow='n'";

        $getData = $this->getData($req);
        $this->db->bind_param_push($arrBind, 'i', $getData['sno']);
        $result = $this->db->set_update_db($getData['tableName'], $set, 'sno = ? ', $arrBind, false, false);

        // 신고로 인한 memoCnt, plusReviewCnt, goodsCnt 업데이트
        if($result) {
            if ($req['memoSno'] > 0) {
                // 메모일 경우 게시글 memoCnt 업데이트
                if ($req['bdId'] == 'plusReview') {
                    $tableName = DB_PLUS_REVIEW_ARTICLE;
                } else {
                    $tableName = 'es_bd_' . $req['bdId'];
                }
                $arrBind = null;
                $setQuery = $isShow == true ? 'memoCnt = memoCnt + 1' : 'memoCnt = memoCnt - 1';
                $query = "UPDATE " . $tableName . " SET " . $setQuery . " WHERE sno = ?";
                $this->db->bind_param_push($arrBind, 'i', $req['bdSno']);
                $this->db->bind_query($query, $arrBind);
            } elseif ($req['goodsNo'] > 0) {
                // 등록한 게시글의 채널 정보
                $arrBind = [];
                $strSQL = "SELECT channel FROM " . $getData['tableName'] . " WHERE sno = ?";
                $this->db->bind_param_push($arrBind, 'i', $getData['sno']);
                $channel = $this->db->query_fetch($strSQL, $arrBind, false)['channel'];
                unset($arrBind);

                $goods = \App::load('\\Component\\Goods\\Goods');
                if ($req['bdId'] === 'goodsreview') {
                    // 상품 리뷰 카운트 업데이트
                    $goods->setGoodsReviewCnt([
                        'goodsNo' => $req['goodsNo'],
                        'channel' => $channel,
                        'isAdd' => $isShow,
                    ]);
                } elseif ($req['bdId'] === 'plusReview') {
                    // 플러스 리뷰 카운트 업데이트
                    $goods->setPlusReviewCnt([
                        'goodsNo' => $req['goodsNo'],
                        'channel' => $channel,
                        'isAdd' => $isShow,
                    ]);
                }
            }
        }

        return $result;
    }

    public function getData($getData)
    {
        if($getData['bdId'] == 'plusReview') {
            if($getData['memoSno'] > 0) {
                $result['tableName'] = DB_PLUS_REVIEW_MEMO;
                $result['sno'] = $getData['memoSno'];
                $result['listType'] = 'memo';
            } else {
                $result['tableName'] = DB_PLUS_REVIEW_ARTICLE;
                $result['sno'] = $getData['bdSno'];
                $result['listType'] = 'board';
            }
        } else {
            if($getData['memoSno'] > 0) {
                $result['tableName'] = DB_BOARD_MEMO;
                $result['sno'] = $getData['memoSno'];
                $result['listType'] = 'memoSno';
            } else {
                $result['tableName'] = 'es_bd_' . $getData['bdId'];
                $result['sno'] = $getData['bdSno'];
                $result['listType'] = 'board';
            }
        }
        return $result;
    }
}
