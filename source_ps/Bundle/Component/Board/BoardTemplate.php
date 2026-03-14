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

namespace Bundle\Component\Board;

use Component\Database\DBTableField;
use Framework\Debug\Exception\Except;
use Component\Validator\Validator;
use Framework\Utility\ProducerUtils;
use Framework\Utility\StringUtils;
use App;
use Framework\Utility\DateTimeUtils;

class BoardTemplate
{
    const ECT_INVALID_ARG = '%s.ECT_INVALID_ARG';
    const ECT_WRONG_MODE = '%s.ECT_WRONG_MODE';
    const TEXT_WRONG_MODE = '잘못된 설정값입니다.';

    protected $db;
    protected $fieldTypes = null;

    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = App::load('DB');
        }
        $this->fieldTypes = DBTableField::getFieldTypes('tableBoardTemplate');
    }

    public function getList($req)
    {
        $arrWhere = null;
        $arrBind = null;
        if (gd_isset($req['searchField']) && gd_isset($req['searchWord'])) {
            if ($req['searchField'] == 'all') {
                $arrWhere[] = "(subject LIKE concat('%',?,'%') or contents LIKE concat('%',?,'%') )";
                $this->db->bind_param_push($arrBind, 's', $req['searchWord']);
                $this->db->bind_param_push($arrBind, 's', $req['searchWord']);
            } else if ($req['searchField'] == 'subject') {
                $arrWhere[] = " subject LIKE concat('%',?,'%') ";
                $this->db->bind_param_push($arrBind, $this->fieldTypes['subject'], $req['searchWord']);
            } else if ($req['searchField'] == 'contents') {
                $arrWhere[] = "contents LIKE concat('%',?,'%')";
                $this->db->bind_param_push($arrBind, $this->fieldTypes['contents'], $req['searchWord']);
            }
        }

        if($req['templateType']){
            $arrWhere[] = "templateType = ?";
            $this->db->bind_param_push($arrBind, 's', $req['templateType']);
        }

        //--- 페이지 설정
        gd_isset($req['page'], 1);
        gd_isset($req['pageNum'], 10);
        gd_isset($req['sort'], 'sno desc');


        $countQuery = "SELECT COUNT(sno) as cnt FROM " . DB_BOARD_TEMPLATE . " WHERE 1  ";
        if ($arrWhere) {
            $countQuery .= 'AND ' . implode(' and ', $arrWhere);
        }
        $cnt = $this->db->query_fetch($countQuery, $arrBind, false)['cnt'];
        // 총 레코드수
        $totalCountQuery = "SELECT COUNT(sno) as cnt FROM " . DB_BOARD_TEMPLATE;
        $amountCnt = $this->db->query_fetch($totalCountQuery, $arrBind, false)['cnt'];

        //--- 목록
        $this->db->strField = 'sno,modDt,regDt,' . implode(',', DBTableField::setTableField('tableBoardTemplate', null, ['contents']));;;
        $this->db->strJoin = DB_BOARD_TEMPLATE;
        if (is_array($arrWhere) === true) {
            $this->db->strWhere = implode(' and ', $arrWhere);
        }
        $this->db->strLimit = '?, ?';
        $this->db->strOrder = $req['sort'];

        $this->db->bind_param_push($arrBind, 'i', (($req['page'] - 1) * $req['pageNum']));
        $this->db->bind_param_push($arrBind, 'i', $req['pageNum']);
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . array_shift($query) . ' ' . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL, $arrBind);
        $data = gd_htmlspecialchars_stripslashes($data);

        foreach ($data as &$row) {
            if ($row['templateType'] == 'front') {
                $row['templateTypeText'] = '쇼핑몰 게시글 양식';
            } else {
                $row['templateTypeText'] = '관리자 게시글 양식';
            }
            $row['regDtDate'] = DateTimeUtils::dateFormat("Y-m-d",$row['regDt']);
            $row['modDtDate'] = DateTimeUtils::dateFormat("Y-m-d",$row['modDt']);
        }


        $getData['sort'] = [
            'sno desc' => '번호↓',
            'sno asc' => '번호↑',
            'regDt desc' => '등록일↓',
            'regDt asc' => '등록일↑',
        ];

        //--- 각 데이터 배열화
        $getData['data'] = $data;
        $getData['amountCnt'] = $amountCnt;
        $getData['totalCnt'] = $cnt;

        return $getData;
    }

    public function getSelectData($templateType = null)
    {
        $list = $this->getList(['templateType'=>$templateType,'pageNum'=>100])['data'];
        foreach($list as $row){
            $result[$row['sno']]= $row['subject'];
        }
        $result[0] = '=선택없음=';
        ksort($result);

        return $result;
    }


    public function getData($sno = null,$templateType = null)
    {
        if (!$sno) {
            return ['mode' => 'write', 'templateType' => 'front'];
        }
        //--- 목록
        $arrBind = null;
        $this->db->strField = 'sno,regDt,modDt,'.implode(',', DBTableField::setTableField('tableBoardTemplate'));;
        $this->db->strJoin = DB_BOARD_TEMPLATE;
        $this->db->strWhere = 'sno=?';
        $this->db->bind_param_push($arrBind, 'i', $sno);

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . array_shift($query) . ' ' . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL, $arrBind, false);
        $data = gd_htmlspecialchars_stripslashes($data);
        if($templateType){
            if($data['templateType'] != $templateType){
                $data['contents'] = '';
            }
        }

        $data['mode'] = 'modify';
        return $data;
    }

    public function saveData(&$req)
    {
        // Validation
        $mode = gd_isset($req['mode']);
        $validator = new Validator();
        switch ($mode) {
            case 'write' : {
                break;
            }
            case 'modify' : {
                $validator->add('sno', 'number', true); // 아이디
                break;
            }
            default: {
                throw new \Exception(__('잘못된 모드값입니다.'));
                break;
            }
        }

        $validator->add('templateType', true); // 템플릿분류
        $validator->add('subject', '', true); // 제목
        $validator->add('contents', ''); // 내용

        if ($validator->act($req, true) === false) {
            throw new \Exception(sprintf(__('%s 잘못된 인자값입니다.'),implode("\n", $validator->errors)));
        }

        $req['contents'] = preg_replace("!<script(.*?)<\/script>!is", "", $req['contents']);
        // 글 내용 보안검증 체크
        $req['contents'] = StringUtils::xssClean($req['contents']);


        if ($mode == 'write') {
            $arrBind = $this->db->get_binding(DBTableField::tableBoardTemplate(), $req, 'insert');
            $this->db->set_insert_db(DB_BOARD_TEMPLATE, $arrBind['param'], $arrBind['bind'], 'y');
            $req['sno'] = $this->db->insert_id();
        } else {
            $arrBind = $this->db->get_binding(DBTableField::tableBoardTemplate(), $req, 'update');
            $this->db->bind_param_push($arrBind['bind'], 'i', $req['sno']);
            $this->db->set_update_db(DB_BOARD_TEMPLATE, $arrBind['param'], 'sno = ?', $arrBind['bind'], false);
        }

        // 에디터 이미지 컨버트 토픽 발행
        $kafka = new ProducerUtils();
        $contentsInfo = ['contentsKey' => $req['sno'], 'tableName' => DB_BOARD_TEMPLATE];
        $result = $kafka->send($kafka::TOPIC_CONVERTED_EDITOR_IMAGE, $kafka->makeData($contentsInfo, 'cei'), $kafka::MODE_RESULT_CALLLBACK, true);
        \Logger::channel('kafka')->info('process sendMQ - return :', [$result, $contentsInfo]);

        unset($arrBind);
    }

    public function deleteData($sno)
    {
        $this->db->bind_param_push($arrBind, 'i', $sno);
        $this->db->set_delete_db(DB_BOARD_TEMPLATE, 'sno=?', $arrBind);

        // 에디터 이미지 삭제 토픽 발행
        $kafka = new ProducerUtils();
        $contentsInfo = ['contentsKey' => $sno, 'tableName' => DB_BOARD_TEMPLATE];
        $result = $kafka->send($kafka::TOPIC_DELETED_EDITOR_IMAGE, $kafka->makeData($contentsInfo, 'dei'), $kafka::MODE_RESULT_CALLLBACK, true);
        \Logger::channel('kafka')->info('process sendMQ - return :', [$result, $contentsInfo]);
    }
}
