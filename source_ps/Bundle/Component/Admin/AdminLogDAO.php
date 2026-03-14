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

namespace Bundle\Component\Admin;

use App;
use Component\Member\Member;
use Component\Database\DBTableField;
use Component\Member\Manager;
use Framework\Utility\ArrayUtils;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\StringUtils;

/**
 * Class AdminLogDAO
 * @package Core\Base\Interceptor
 * @author Nam-ju Lee <lnjts@godo.co.kr>
 */
class AdminLogDAO
{
    protected $_db;
    const LOG_TYPE_P6M =  'p6m';
    const LOG_TYPE_P2Y =  'p2y';
    const LOG_TYPE_P5Y = 'p5y';
    const LOG_TYPE_NOMAL = 'n';
    const DELIMITER = '.';
    const MODE = 'mode';
    const KEY = 'key';
    const MENU = 'menu';
    const DEFAULT = 'default';
    const WILD_CARD = '*';
    const API_KIND = 'api_kind';

    const LOGIN = 'login';
    const LOGOUT = 'logout';

    const ALLOWED_MODES = ['authEmail', 'authSms', 'register', 'modify', 'delete', 'setManagePermission', 'getManagerListLayout'];

    public function __construct()
    {
        $this->_db = App::load('DB');;
    }

    public function save($arrData)
    {
        $arrBind = $this->_db->get_binding(DBTableField::tableAdminLog(), $arrData, 'insert', array_keys($arrData));
        $this->_db->set_insert_db(DB_ADMIN_LOG, $arrBind['param'], $arrBind['bind'], 'y');
        return $this->_db->insert_id();
    }

    public function getList($request, $arrFields = [], $pageNum = 20 )
    {
        $arrBind = [];
        if (gd_isset($request['pagelink'])) {
            $request['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($request['pagelink'])));
        }
        $request['page'] = $request['page']  ?? 1;

        if($arrFields == '*') {
            $queryField = 'a.*';
        }
        else if(is_array($arrFields)) {
            $queryField  = 'a.'.implode(',a.',$arrFields);
        }
        else {
            $queryField = 'a.*';
        }

        if ($request['managerId']) {
            if ($request['searchKind'] == 'equalSearch') {
                $where[] = "a.managerId = ? ";
            } else {
                $where[] = "a.managerId LIKE concat('%',?,'%') ";
            }
            $this->_db->bind_param_push($arrBind, 's', $request['managerId']);
        }

        if ($request['searchDate']) {
            $request['searchDate'][0] = $request['searchDate'][0] ? $request['searchDate'][0] : date('Y-m-d');
            $request['searchDate'][1] = $request['searchDate'][1] ? $request['searchDate'][1] : date('Y-m-d');
            // 최대 검색 일자 (6개월)
            $maximumSearchDays = 179;
            $diffDate = DateTimeUtils::intervalDay($request['searchDate'][0], $request['searchDate'][1]);
            if ($diffDate < 0) {
                $request['searchDate'][0] = $request['searchDate'][1];
            } elseif ($diffDate > $maximumSearchDays) {
                $request['searchDate'][0] = date('Y-m-d', strtotime($request['searchDate'][1] . ' -' . $maximumSearchDays . ' day'));
            }
            // 최대 검색기간
            if ($request['searchDate'][0] < date('Y-m-d', strtotime('-2 year'))){
                $request['searchDate'][0] = date('Y-m-d', strtotime('-2 year'));
            }
            $where[] = '(a.regDt BETWEEN ? AND ?)';
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][0]);
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][1] . ' 23:59:59');
        } else {
            if (empty($request['cremaFl'])) {
                if ($request['view'] != 'layer') {
                    $request['searchDate'][0] = date('Y-m-d', strtotime('-7 day'));
                    $request['searchDate'][1] = date('Y-m-d');
                    $where[] = '(a.regDt BETWEEN ? AND ?) ';
                    $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][0]);
                    $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][1] . ' 23:59:59');
                }
            }
        }

        // 기본 uri 조건
        if ($request['baseUri']) {
            $where[] = 'a.baseUri = ? ';
            $this->_db->bind_param_push($arrBind, 's', $request['baseUri']);
        }

        // 페이지 분기
        if($request['view'] == 'adminAccess') {
            $where[] = 'a.page = ? ';
            $this->_db->bind_param_push($arrBind, 's', 'adminInfo');
        } else if ($request['view'] == 'layer') {
            $where[] = 'a.page = ? ';
            $this->_db->bind_param_push($arrBind, 's', 'adminLogExcel');
        } else {
            $where[] = 'a.page NOT IN (?,?) ';
            $this->_db->bind_param_push($arrBind, 's', 'adminInfo');
            $this->_db->bind_param_push($arrBind, 's', 'adminLogExcel');

            // 기존 : 운영자정보 접속기록 탭에 관리자 노출, 공급사는 회원정보 접속기록에 노출
            // 변경 : 관리자, 공급사 두개 다 운영자정보 접속기록에 노출되도록 작업
            // 관리자에 추가된다면, 공급사도 기획측에 확인해서 추가 필요!
            $adminListPage = ['/policy/manage_list.php', '/policy/manage_register.php', '/policy/manage_ps.php', '/policy/manage_permission.php', '/share/layer_manage.php'];
            $providerListPage = ['/provider/policy/manage_list.php', '/provider/policy/manage_register.php', '/provider/policy/manage_ps.php', '/provider/policy/manage_permission.php', '/provider/share/layer_manage.php'];

            $adminListPage = array_merge($adminListPage, $providerListPage);

            if ($request['view'] == 'adminList') {
                $where[] = 'a.baseUri IN (?,?,?,?,?,?,?,?,?,?)';
            } else { //default
                $where[] = 'a.baseUri NOT IN (?,?,?,?,?,?,?,?,?,?)';
            }
            foreach($adminListPage as $adminListPageVal) {
                $this->_db->bind_param_push($arrBind, 's', $adminListPageVal);
            }
        }

        $offset = ($request['page'] - 1) * $pageNum;
        $strSQL = " SELECT ".$queryField.",m.isDelete  FROM " . DB_ADMIN_LOG . " as a LEFT OUTER JOIN " .DB_MANAGER. " as m ON a.managerNo = m.sno WHERE ";
        if ($where) {
            $strSQL .= implode(' AND ', $where);
        }
        $strSQL .= " ORDER BY a.regDt DESC LIMIT {$offset},{$pageNum} ";
        $result['list'] = $this->_db->query_fetch($strSQL, $arrBind, gd_isset($request['dataArray'], true));
        $result['list'] = $this->setDisplayAdminLogList($result['list']);
        Manager::displayListData($result['list']);
        $strCountSQL = " SELECT COUNT(sno) as cnt FROM " . DB_ADMIN_LOG . " as a WHERE ";
        if ($where) {
            $strCountSQL .= implode(' AND ', $where);
        }
        $result['count'] = $this->_db->query_fetch($strCountSQL, $arrBind, false)['cnt'];
        $page = \App::load('\\Component\\Page\\Page', $request['page']);
        $page->page['list'] = $pageNum; // 페이지당 리스트 수
        $page->recode['amount'] = $page->recode['total'] =$result['count'];
        $page->setPage();
        $page->setUrl(\Request::getQueryString());
        $result['page'] = $page->getPage();
        $result['request'] = $request;
        return $result;
    }

    /**
     * 수행업무 상세로그 노출 여부 확인
     *
     * @param array $data 로그데이터
     *
     * @return array
     */
    public function setDisplayAdminLogList($data){
        foreach ($data as $lKey => $lVal) {
            $data[$lKey]['displayDetailLogFl'] = 'n';
            $fileName = end(explode('/', $lVal['baseUri']));

            // 회원정보 조회를 할 경우에만 상세로그 노출
            switch ($fileName) {
                case 'member_crm.php':
                case 'member_modify.php':
                case 'member_list.php':
                case 'layer_excel_ps.php':
                case 'crema_ps.php':
                case 'layer_godo_sms_ps.php':
                case 'layer_godo_mail_ps.php':
                case 'layer_excel_auth_ps.php':
                case 'manage_register.php':
                case 'member_batch_approval_with_group.php':
                case 'layer_sms_send_list.php': // SMS 발송 내역 상세 보기
                case 'layer_kakao_send_list.php': // 카카오 알림톡 발송 내역 상세보기
                case 'mail_log_list.php': // 메일 발송 내역 보기
                case 'sms080_list.php': // 080 수신거부 리스트
                case 'crm_group_list.php': // CRM 그룹 리스트
                case 'crm_group_create.php': // CRM 그룹 등록
                    $data[$lKey]['displayDetailLogFl'] = 'y';
                    break;
                case 'member_ps.php':
                    $lVal['data'] = json_decode($lVal['data'], true);
                    // 상세로그 노출 가능모드
                    $allowMode = [
                        'modify',
                        'register',
                        'delete',
                    ];
                    if (in_array($lVal['data']['POST']['mode'], $allowMode) === true) {
                        $data[$lKey]['displayDetailLogFl'] = 'y';
                    }
                    break;
                case 'manage_ps.php':
                case 'manage_list.php':
                case 'layer_manage.php':
                    $lVal['data'] = json_decode($lVal['data'], true);
                    if ($lVal['page'] == 'adminInfo' || empty($lVal['data']['searchData']) === false) {
                        $data[$lKey]['displayDetailLogFl'] = 'y';
                    }
                if (in_array($lVal['data']['POST']['mode'], ['register', 'modify', 'delete', 'setManagePermission', 'getManagerListLayout'])) {
                    $data[$lKey]['displayDetailLogFl'] = 'y';
                }
                    break;
                case 'login_ps.php':
                    $lVal['data'] = json_decode($lVal['data'], true);
                    if (empty($lVal['data']['POST']['authTarget']) === false) {
                        $data[$lKey]['displayDetailLogFl'] = 'y';
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * 수행업무 상세로그
     *
     * @param integer $sno 로그 일련번호
     * @param array $arrFields 필드
     *
     * @return array
     */
    public function getDetailAdminLogInfo($sno, $arrFields = [])
    {
        $arrBind = [];

        if(is_array($arrFields) && count($arrFields) > 0) {
            $queryField  = 'a.'.implode(',a.',$arrFields);
        }
        else {
            $queryField = 'a.*';
        }

        $where[] = 'a.sno = ?';
        $this->_db->bind_param_push($arrBind, 'i', $sno);

        $strSQL = " SELECT ".$queryField.",m.isDelete  FROM " . DB_ADMIN_LOG . " as a LEFT OUTER JOIN " .DB_MANAGER. " as m ON a.managerNo = m.sno WHERE ";

        if ($where) {
            $strSQL .= implode(' AND ', $where);
        }

        $data = $this->_db->query_fetch($strSQL, $arrBind, false);
        Manager::displayListData($data);

        $data['data'] = json_decode($data['data'], true);
        $result = $this->setDisplayAdminLogInfo($data);

        return $result;
    }

    /**
     * 수행업무 상세로그 메시지 정의
     *
     * @param array $data 수행업무 상세로그 데이터
     *
     * @return array
     */
    public function setDisplayAdminLogInfo($data)
    {
        $fileUri = explode('/', $data['baseUri']);
        $fileName = end($fileUri);
        $result['searchCnt'] = $data['data']['searchCnt'];
        $result['searchData'] = $data['data']['searchData'];
        $result['key'] = $data['data']['GET']['key'];
        $result['keyword'] = $data['data']['GET']['keyword'];
        $result['viewCnt'] = count(explode(',', $data['data']['searchData']));

        if ($data['data']['searchCnt'] > 1) {
            if ($result['viewCnt'] > 1) {
                $searchData = $data['data']['searchData'];
            } else {
                $searchData = ' (' . $data['data']['searchData'] . ' 외 ' . (number_format($data['data']['searchCnt'] - 1)) . '명)';
            }
        } else if ($data['data']['searchCnt'] == '1') {
            $searchData = ' (' . $data['data']['searchData'] . ')';
        } else {
            $searchData = '';
        }

        if (empty($data['data']['GET']['keyword']) == false) {
            $keyword = ' (' . $data['data']['GET']['keyword'] . ')';
        } else {
            $keyword = '';
        }

        switch ($fileName) {
            case 'member_list.php':
            case 'member_batch_approval_with_group.php':
                $keyData = Member::COMBINE_SEARCH;
                if (empty($data['data']['GET']['key']) || $result['key'] = $data['data']['GET']['key'] == 'all') {
                    $result['keyValue'] = '통합검색';
                } else {
                    $result['keyValue'] = $keyData[$data['data']['GET']['key']];
                }
                break;
            case 'member_crm.php':
            case 'member_modify.php':
            case 'member_ps.php':
                $result['keyValue'] = '상세조회';
                break;
            case 'layer_excel_ps.php':
                $tmpFileName = $data['data']['POST']['fileName'];
                $dividedFileName = explode('.', $tmpFileName);
                // 확장자가 zip인경우 xls로 변환하여 노출
                if (end($dividedFileName) == 'zip') {
                    $dividedFileName[count($dividedFileName) - 1] = 'xls';
                    $tmpFileName = implode('.', $dividedFileName);
                }
                if ($data['data']['POST']['mode'] == 'lapse_order_delete_excel_download') {
                    $result['searchTargetMsg'] = '다운로드 양식명 : 주문내역삭제';
                    $result['downloadReason'] = '사유 : 주문관리';
                } else {
                    $result['searchTargetMsg'] = '다운로드 양식명 : ' . $data['data']['searchData'];
                }
                $result['searchConditionMsg'] = '파일대상 : ' . $data['data']['POST']['downloadFileName'] . ' (' . $tmpFileName . ')';
                if (empty($data['data']['POST']['excelDownloadReason']) === false) {
                    $result['downloadReason'] = '사유 : ' . $data['data']['POST']['excelDownloadReason'];
                }
                if (empty($data['data']['POST']['totalCount']) === false) {
                    $result['searchTargetMsg'] .= '<br>처리건수 : ' . number_format($data['data']['POST']['totalCount']) . '건';
                }
                break;
            case 'crema_ps.php':
                $result['searchTargetMsg'] = '크리마 간편리뷰 설정을 위한 CSV 파일 다운로드';
                if (empty($data['data']['GET']['excelDownloadReason']) === false) {
                    $result['downloadReason'] = '사유 : ' . $data['data']['GET']['excelDownloadReason'];
                }
                break;
            case 'manage_list.php':
            case 'layer_manage.php':
                $result['searchTargetMsg'] = '처리 대상 : '. $result['searchData'];
                break;
            case 'manage_register.php':
                $manager = \App::load('\\Component\\Member\\Manager');
                $managerIdArr = $manager->getManagerId($data['data']['GET']['sno']);
                foreach($managerIdArr as $val) {
                    $managerId[] = $val['managerId'];
                }
                $result['searchConditionMsg'] = '처리 대상 : '. implode(', ', $managerId);
                break;
            case 'layer_godo_sms_ps.php':
            case 'layer_godo_mail_ps.php':
            case 'layer_excel_auth_ps.php':
            case 'manage_ps.php':
            case 'login_ps.php':
                if (end($fileUri) == 'login_ps.php' && empty($data['data']['POST']['authTarget'])) {
                    break;
                }
                $mode = $data['data']['POST']['mode'];
                // 인증 절차 복호화
                if (end($fileUri) == 'manage_ps.php') {
                    if ($mode == 'authSms') {
                        $encryptData = $data['data']['POST']['cellPhone'];
                    } else {
                        $encryptData = $data['data']['POST']['email'];
                    }
                    $decryptAuthTarget = \Encryptor::decryptJson($encryptData);
                } else {
                    $decryptAuthTarget = \Encryptor::decryptJson($data['data']['POST']['authTarget']);
                }
                // 인증 절차 마스킹
                switch ($mode) {
                    case 'getSmsAuthKey':
                    case 'authSms':
                    case 'smsReSend':
                        $decryptAuthTarget = StringUtils::numberToCellPhone($decryptAuthTarget);
                        if (empty($decryptAuthTarget) === false) {
                            $tmpAuthTarget = explode('-', $decryptAuthTarget);
                            $tmpAuthTarget[1] = StringUtils::mask($tmpAuthTarget[1]);
                            $tmpAuthTarget[2] = StringUtils::mask($tmpAuthTarget[2], 0, 2);
                            $authTarget = implode('-', $tmpAuthTarget);
                        } else {
                            $authTarget = '고도 회원 휴대폰번호 인증';
                        }
                        break;
                    case 'getMailAuthKey':
                    case 'authEmail':
                    case 'emailSend':
                        $tmpAuthTarget = explode('@', $decryptAuthTarget);
                        $tmpAuthTarget[0] = StringUtils::mask($tmpAuthTarget[0], 2);
                        $tmpMailDomain = explode('.', $tmpAuthTarget[1]);
                        $tmpMailDomain[0] = StringUtils::mask($tmpMailDomain[0], 2);
                        $tmpAuthTarget[1] = implode('.', $tmpMailDomain);
                        $authTarget = implode('@', $tmpAuthTarget);
                        break;
                    case 'register':
                    case 'modify':
                        $result['searchConditionMsg'] = '권한 변경 사유 : '. $data['data']['POST']['accessControlReason'] . '<br>';
                        $result['searchConditionMsg'] .= '처리 대상 : '. $data['data']['POST']['managerId'];
                        break;
                    case 'delete':
                    case 'setManagePermission':
                        $adminLogDataPost = $data['data']['POST'];
                        if ($mode == 'delete') {
                            $managerIdData = $adminLogDataPost['chk'];
                        } else {
                            $managerIdData = $adminLogDataPost['manage_sno']; // 권한설정은 한명만 가능
                        }
                        $manager = \App::load('\\Component\\Member\\Manager');
                        $managerIdArr = $manager->getManagerId($managerIdData);
                        foreach($managerIdArr as $val) {
                            $managerId[] = $val['managerId'];
                        }

                        $cntList = $this->getAdminPermissionCntList($adminLogDataPost);

                        $resultMsg = $this->getAdminPermissionResultMsg($cntList);

                        $msgTotal = [];
                        $msgAvailable = [];
                        $msgReadWrite = [];

                        if ($resultMsg['readonly']) {
                            $msgReadWrite[] = '<li>- 권한 부여(읽기) :<br>' . $resultMsg['readonly'] . '</li>';
                        }
                        if ($resultMsg['writable']) {
                            $msgReadWrite[] = '<li>- 권한 부여(읽기+쓰기) :<br>' . $resultMsg['writable'] . '</li>';
                        }

                        $msgTotal[] = '<ul><li>* 권한 변경 사유 : '. $adminLogDataPost['accessControlReason'] . '</li>';
                        $msgTotal[] = '    <li>* 처리 대상(아이디) : '. implode(', ', $managerId) . '</li>';

                        if ($resultMsg['available']) {
                            $msgAvailable[] = '<li>* 권한 부여(읽기 또는 읽기+쓰기) :<br>' . $resultMsg['available'] . '</li>';
                            if ($msgReadWrite) {
                                $msgAvailable[] = implode('', $msgReadWrite);
                            }
                            $msgTotal[] = implode('', $msgAvailable);
                        }

                        if ($resultMsg['noncheck']) {
                            $msgTotal[] = '    <li>* 권한 삭제(권한없음) :<br>' . $resultMsg['noncheck'] . '</li>';
                        }
                        $msgTotal[] = '</ul>';

                        $result['searchConditionMsg'] = implode('<hr>', $msgTotal);

                        break;
                }
                if ($data['data']['GET']['mode'] == 'getManagerListLayout') {
                    $result['searchConditionMsg'] = '처리 대상 : '. $result['searchData'];
                    break;
                }
                // 인증 절차 작성
                if (empty($authTarget) === false) {
                    $result['searchTargetMsg'] = '인증 절차 : ' . $authTarget;
                }

                // 인증 정보
                $referer = end(explode('/', $data['referer']));
                $targetPage = substr($referer, 0, strrpos($referer, '.'));
                if ($fileName == 'layer_excel_auth_ps.php' || $fileName == 'login_ps.php') {
                    $targetPage = $fileName;
                }
                switch ($targetPage) {
                    case 'find_id':
                        $result['searchConditionMsg'] = '인증 정보 : 아이디 찾기 인증';
                        break;
                    case 'find_password':
                        $result['searchConditionMsg'] = '인증 정보 : 비밀번호 찾기 인증';
                        break;
                    case 'manage_register':
                    case 'layer_excel_auth_ps.php':
                    case 'login_ps.php':
                        if (empty($result['searchConditionMsg'])) {
                            $result['searchConditionMsg'] = '인증 정보 : 운영자 인증';
                        }
                        break;
                }
                break;
            case 'layer_sms_send_list.php': // SMS 발송 내역 상세 보기
            case 'layer_kakao_send_list.php': // 카카오 알림톡 발송 내역 상세보기
                if (empty($data['data']['GET']['key']) || $result['key'] = $data['data']['GET']['key'] == 'all') {
                    $result['keyValue'] = '통합검색';
                } else {
                    $result['keyValue'] = ($data['data']['GET']['key'] === 'receiverName') ? '이름' : '수신번호';
                }
                break;
            case 'mail_log_list.php': // 메일 발송 내역 보기
                if (empty($data['data']['GET']['key']) || $result['key'] = $data['data']['GET']['key'] == 'all') {
                    $result['keyValue'] = '통합검색';
                } else {
                    switch ($data['data']['GET']['key']) {
                        case 'sender':
                            $result['keyValue'] = '발송자';
                            break;
                        case 'receiver':
                            $result['keyValue'] = '발송대상';
                            break;
                        case 'subject':
                            $result['keyValue'] = '메일제목';
                            break;
                    }
                }
                break;
            case 'sms080_list.php': // 080 수신거부 리스트
                if (empty($data['data']['GET']['key']) || $result['key'] = $data['data']['GET']['key'] == 'all') {
                    $result['keyValue'] = '수신거부 번호';
                }
                break;
            case 'crm_group_list.php':
                $result['searchTargetMsg']      = '처리대상 : ' . $data['data']['searchData'];
                $result['searchViewCnt']        = '처리건수 : ' . $data['data']['searchCnt'] . '건';
                $result['searchConditionMsg']   = '처리사유 : ' . $data['accessControlReason'];
                break;
            case 'crm_group_create.php':
                $result['searchTargetMsg']      = '처리대상 : ' . $data['data']['searchData'];
                $result['searchViewCnt']        = '처리건수 : ' . $data['data']['searchCnt'] . '건';
                $result['searchConditionMsg']   = '처리사유 : ' . $data['accessControlReason'];
                break;
            default:
                break;
        }

        // 조회문구 제외 페이지
        $exceptPage = [
            'layer_excel_ps.php',
            'crema_ps.php',
            'layer_godo_sms_ps.php',
            'layer_godo_mail_ps.php',
            'layer_excel_auth_ps.php',
            'manage_ps.php',
            'login_ps.php',
            'crm_group_list.php',
            'crm_group_create.php'
        ];

        if (in_array(end($fileUri), $exceptPage) === false) {
            $searchTarget = $searchData;
            $searchCondition = $result['keyValue'] . $keyword;
            $searchViewCnt = $result['viewCnt'];
            $searchConditionMsgView = true;
            $searchViewCntView = true;

            switch ($data['page']) {
                case '회원관리화면':
                    $searchViewCntView = false;
                    break;
                case '회원등록':
                case '회원수정':
                    $searchConditionMsgView = false;
                    $searchViewCntView = false;
                    break;
                case '회원리스트':
                    if ($data['action'] === '탈퇴') {
                        $searchConditionMsgView = false;
                    }
                    break;
            }

            if (empty($searchCondition) == false && $searchConditionMsgView === true) {
                $result['searchConditionMsg'] = '처리조건 : ' . $searchCondition;
            }
            if (empty($searchViewCnt) == false && $searchViewCntView === true) {
                $result['searchViewCnt'] = '처리건수 : ' . $searchViewCnt . '건';
            }
            if ($searchTarget != '') {
                $result['searchTargetMsg'] = '처리대상 : ' . $searchTarget;
            }
        }
        // 수행업무 상세
        if (empty($result['searchConditionMsg']) == false) {
            $result['logContents'] = $result['searchConditionMsg'];
        }
        if (empty($result['searchViewCnt']) == false) {
            $result['logContents'] .= empty($result['logContents']) === false ? '<br />' . $result['searchViewCnt'] : $result['searchViewCnt'];
        }
        if (empty($result['searchTargetMsg']) == false) {
            $result['logContents'] .= empty($result['logContents']) === false ? '<br />' . $result['searchTargetMsg'] : $result['searchTargetMsg'];
        }
        if (empty($result['downloadReason']) == false) {
            $result['logContents'] .= empty($result['logContents']) === false ? '<br />' . $result['downloadReason'] : $result['downloadReason'];
        }

        return $result;
    }

    /**
     * 각 기능 타입별 지정 수량을 포매팅하여 배열 형태로 반환
     *
     * @param array $cntList
     * @param string $resultType
     * @return array
     */
    private function getAdminPermissionStrings(array $cntList, string $resultType): array
    {
        $permissionMsg = [];
        foreach ($cntList[$resultType] as $name => $cnt) {
            if ($resultType == 'noncheck' && $name == 'total') {
                continue;
            }
            if ($cnt > 0 && isset($cntList['base'][$name])) {
                $cntStr = sprintf('%s %s', $name, $this->getStringCountResult($cnt, $cntList['base'][$name]));
                array_push($permissionMsg, $cntStr);
            }
        }
        unset($name, $cnt);

        return $permissionMsg;
    }

    /**
     * 1depth 기준으로 얻어온 각 기능의 권한 배열을 문자열로 변환
     *
     * @param array $cntList
     * @return array
     */
    private function getAdminPermissionResultMsg(array $cntList = []): array
    {
        $resultMsg = [];
        $resultMsg['available'] = implode(', ', $this->getAdminPermissionStrings($cntList, 'available'));
        $resultMsg['readonly']  = implode(', ', $this->getAdminPermissionStrings($cntList, 'readonly'));
        $resultMsg['writable']  = implode(', ', $this->getAdminPermissionStrings($cntList, 'writable'));
        $resultMsg['noncheck']  = implode(', ', $this->getAdminPermissionStrings($cntList, 'noncheck'));

        return $resultMsg;
    }

    /**
     * 전체 권한 및 대상 관리자에게 부여된 권한 메뉴 수를 배열로 반환
     *
     * @param array $adminLogDataPost
     * @return array
     */
    private function getAdminPermissionCntList(array $adminLogDataPost = []): array
    {
        // 메뉴 리스트(본사,공급사)
        $adminMenu = new AdminMenu();
        $menuList = $adminMenu->getAdminMenuList();
        $menuTreeList = $adminMenu->getAdminMenuTreeList($menuList);
        $menuTreeList = $adminMenu->getMenuTreeListFilter($menuTreeList);
        $lastMenuNodes = $this->getTreeLastNodes($menuTreeList['top']);

        $cntList = [
            'base' => [
                'total' => 0
            ]
        ];

        foreach ($lastMenuNodes as $menu) {
            if (!isset($cntList['base'][$menu['topName']])) {
                $cntList['base'][$menu['topName']] = 1;
            } else {
                $cntList['base'][$menu['topName']]++;
            }
            $cntList['base']['total']++;
        }
        unset($menu);

        if ($adminLogDataPost['permissionFl'] == 's') {
            $cntList['available'] = $cntList['base'];
            $cntList['writable'] = $cntList['base'];
            unset($cntList['available']['total']);
            unset($cntList['writable']['total']);
        } else {
            foreach($cntList['base'] as $menuName => $val) {
                if ($menuName != 'total') {
                    $cntList['readonly'][$menuName] = 0;
                    $cntList['writable'][$menuName] = 0;
                    $cntList['available'][$menuName] = 0;
                }
            }
            unset($menuName, $val);

            $permission3List = [];
            array_walk_recursive($adminLogDataPost['permission_3'], function ($value, $key) use (&$permission3List) {
                $permission3List[$key] = $value;
            });

            foreach($permission3List as $menuNo => $permissionType) {
                $topName = $lastMenuNodes[$menuNo]['topName'];

                if ($permissionType === 'readonly') {
                    $cntList['readonly'][$topName]++;
                    $cntList['available'][$topName]++;
                } elseif ($permissionType === 'writable') {
                    $cntList['writable'][$topName]++;
                    $cntList['available'][$topName]++;
                }
            }
            unset($menuNo, $permissionType);

            foreach ($cntList['base'] as $name => $cnt) {
                $cntList['noncheck'][$name] = $cnt - $cntList['available'][$name];
            }
            unset($name, $cnt);
        }

        return $cntList;
    }

    /**
     * 두 수를 입력받아 '(전체)' 혹은 '(N 건)' 의 포매팅 된 문자열로 반환
     *
     * @param int $baseCount
     * @param int $totalCount
     * @return string
     */
    private function getStringCountResult(int $baseCount=0, int $totalCount=0): string
    {
        $resultString = '(전체)';
        if ($baseCount !== $totalCount) {
            $resultString = sprintf('(%d 건)', $baseCount);
        }
        return $resultString;
    }

    /**
     * getMenuTreeListFilter() 에서 반환한 다차원 트리 구조의 권한정보 배열에서 최하위 'last' 배열의 요소를 1차원 배열로 반환
     *
     * @param array $menuTreeList
     * @return array
     */
    private function getTreeLastNodes(array $menuTreeList): array
    {
        $result = [];
        $this->processNestedArrays('', $menuTreeList, function ($v, $topName) use (&$result) {
            foreach($v as $lastKey => $lastNode) {
                $result[$lastKey] = $lastNode;
                $result[$lastKey]['topName'] = $topName;
            }
            unset($lastKey, $lastNode);
        });
        return $result;
    }

    /**
     * 배열 하위 요소를 탐색하여 가장 아래 단계의 메뉴인 last 를 콜백으로 처리
     *
     * @param string $topName       최상위 메뉴의 이름
     * @param array $subArray       순회할 배열
     * @param callable $callback    조건에 부합할 시 수행하는 콜백
     * @return void
     */
    private function processNestedArrays(string $topName='', array $subArray=[], callable $callback)
    {
        foreach ($subArray as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            if ($k == 'last') {
                $callback($v, $topName);
            }
            if ($v['mid']) {
                $topName = $v['name'];
            }
            $this->processNestedArrays($topName, $v, $callback);
        }
        unset($k, $v);
    }

    /**
     * requestData 에 세팅된 mode 값을 얻어와 반환
     *
     * @param array $requestData
     * @return string|null
     */
    private function getRequestMode(array $requestData)
    {
        return $requestData['POST']['mode'] ?? $requestData['GET']['mode'] ?? $requestData['REQUEST']['mode'] ?? $requestData['POST']['api_kind'];
    }

    /**
     * DB_ADMIN_LOG 기록
     *
     * @param string $forceMode     interceptor 로그아웃 처리를 위한 기본모드 login/logout
     * @return void
     */
    public function setAdminLog(string $forceMode='')
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $baseDirName = $request->getDirectoryUri();
        if (empty($baseDirName)) {
            return;
        }
        $configClass = \App::getConfig('log.' . $baseDirName);
        $baseName = $request->getFileUri();
        $fileName = pathinfo($baseName, PATHINFO_FILENAME);

        $requestData = $this->getRequestData(false);
        $mode = $this->getRequestMode($requestData);
        if (empty($forceMode) === false) {
            $fileName = 'token_login';
            $mode = $forceMode;
        }
        $method = 'get' . ucwords($fileName);
        $pageData = null;
        $menu = '';
        if (is_object($configClass)) {
            $pageData = $configClass->$method();
            $menu = $configClass->get__menu__() ?? $baseDirName;   //메뉴명
            if (is_array($menu)) {
                $menu = $this->getMenuName($menu);
            }
        }

        $page = $this->getPageName($pageData);

        if ($this->isPrivate($pageData) === false || $page === null) {        //개인정보 로그가 아닌 개설로그면 파일로 로그생성
            $fullUrl = $request->getDomainUrl() . $request->getRequestUri();
            $logger = \App::getInstance('logger')->channel('adminLog');
            // 파일로그 생성시 개인정보 암호화
            if (isset($requestData['SESSION']['manager'])) {
                $encryptKey = ['cellPhone', 'email', 'managerNm'];
                foreach ($requestData['SESSION']['manager'] as $rKey => $rVal) {
                    if (in_array($rKey, $encryptKey)) {
                        $requestData['SESSION']['manager'][$rKey] = \Encryptor::encryptJson($rVal);
                    }
                }
            }
            $logger->info($fullUrl, $requestData);
            if ( (($fileName == 'manage_ps' && (in_array($mode, self::ALLOWED_MODES))) === false) &&
                 (empty($forceMode) === true) ) {
                return;
            }
        }

        $managerNo = $session->get('manager.sno', $requestData['POST']['sno']);
        $managerId = $session->get('manager.managerId', $requestData['POST']['managerId']);

        // 관리자 로그인/로그아웃 파일로그 개별 생성
        if ($mode === 'login' || $mode === 'logout') {
            if ($mode === 'login') {
                $adminLoginFl = $request->request()->get('adminLoginFl', false);
                $logData['adminLoginFl'] = $adminLoginFl;
                if ($adminLoginFl === true) {
                    $message = 'admin login success';
                } else {
                    $message = 'admin login fail';
                }
            } else {
                $message = 'admin logout';
            }
            if ($requestData['POST']['mobileappFl'] === true) {
                $message = 'mobileapp ' . $message;
            }
            $logData['ip'] = $requestData['REMOTE_ADDR'];
            $logData['managerId'] = $managerId;
            $logData['referer'] = $requestData['REFERER'];
            $logData['user_agent'] = $requestData['USER_AGENT'];
            \Logger::channel('adminAccess')->info($message, $logData);
        }

        $action = $this->getActionName($pageData);
        if (empty($forceMode) === false) {
            $action = $pageData['action']['mode.'.$forceMode];
        }

        if ($mode == 'lapse_order_delete_excel_download') {
            $page = '주문 내역 삭제';
        }

        $logType = $pageData['type'] ?? AdminLogDAO::LOG_TYPE_P2Y;
        // 개인정보접속기록 다운로드로그 5년보관
        if ($page == 'adminLogExcel') {
            $logType = AdminLogDAO::LOG_TYPE_P5Y;
        }

        $data['baseUri'] = strtok($request->getRequestUri(), '?');
        $data['uri'] = $request->getRequestUri();
        $data['data'] = $this->getRequestData();
        $data['menu'] = $menu;
        $data['referer'] = $request->getReferer();
        $data['type'] = $logType;
        $data['page'] = $page;
        $data['action'] = $action;
        $data['managerNo'] = $managerNo;
        $data['managerId'] = $managerId;
        $data['scmNo'] = $session->get('manager.scmNo');
        $data['ip'] = $request->getRemoteAddress();
        $data['accessControlReason'] = $requestData['POST']['accessControlReason'];

        if ($data['data']['POST']['mode'] === 'login') {
            unset($data['data']['POST']['managerPw']);
        }

        $this->save($data);
    }

    /**
     * 개인정보로그인지 여부체크
     *
     * @param $pageData
     *
     * @return bool
     */
    private function isPrivate($pageData)
    {
        if (!$pageData) {   //해당URI가 존재 하지 않으면
            return false;
        }

        $result = true;
        $page = $pageData['page'] ?? '';
        if (is_array($page)) {
            $value = $this->getArrayData($page);
            if ($value === null) {
                $result = false;
            }
        }
        $action = $pageData['action'] ?? '';
        if (is_array($action)) {
            $value = $this->getArrayData($action);
            if ($value === null) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * 기술지원지 필요한 정보들
     *
     * @param bool $isJson
     *
     * @return mixed
     */
    private function getRequestData($isJson = true)
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $cookie = \App::getInstance('cookie');
        $data['POST'] = $request->post()->toArray();
        $data['GET'] = $request->get()->toArray();
        $data['REQUEST']['mode'] = $request->request()->get('mode');
        $data['USER_AGENT'] = $request->getUserAgent();
        $data['SESSION'] = $session->all();
        $data['COOKIE'] = $cookie->all();
        $data['REFERER'] = $request->getReferer();
        $data['REMOTE_ADDR'] = $request->getRemoteAddress();

        $controller = \App::getController();
        $pageName = explode('/', $controller->getPageName());

        switch (end($pageName)) {
            case 'member_crm':
                $data['searchData'] = $controller->getData('memberData')['memId'];
                $data['searchCnt'] = 1;
                break;
            case 'member_list':
            case 'member_batch_approval_with_group':
                $arrSearchMemId = [];
                foreach ($controller->getData('data') as $searchData) {
                    $arrSearchMemId[] = $searchData['memId'];
                }
                $searchMemId = implode(', ', $arrSearchMemId);
                $data['searchData'] = $searchMemId;
                $data['searchCnt'] = $controller->getData('page')->recode['total'];
                break;
            case 'member_modify':
                $data['searchData'] = $controller->getData('data')['memId'];
                $data['searchCnt'] = 1;
                break;
            case 'member_ps':
                if ($data['POST']['mode'] == 'delete') {
                    $arrSearchMemId = [];
                    $member = \App::load('\\Component\\Member\\Member');
                    foreach ($data['POST']['chk'] as $memNo) {
                        $memberData = $member->getMember($memNo, 'memNo', 'memId');
                        $arrSearchMemId[] = $memberData['memId'];
                    }
                    $data['searchData'] = implode(', ', $arrSearchMemId);
                    $data['searchCnt'] = count(explode(',', $data['searchData']));
                } else {
                    $data['searchData'] = $data['POST']['memId'];
                    $data['searchCnt'] = 1;
                }
                break;
            case 'layer_excel_ps':
                if (empty($data['POST']['excelTitle']) == false) {
                    $data['searchData'] = $data['POST']['excelTitle'];
                } else {
                    $data['searchData'] = urldecode($data['POST']['excelFileName']);
                }
                break;
            case 'login_ps':
                if (empty($data['POST']['managerPw']) === false) {
                    unset($data['POST']['managerPw']);
                }
                if (empty($data['POST']['authTarget']) === false) {
                    $data['POST']['authTarget'] = \Encryptor::encryptJson($data['POST']['authTarget']);
                }
                break;
            case 'layer_godo_sms_ps':
            case 'layer_godo_mail_ps':
            case 'layer_excel_auth_ps':
                if (empty($data['POST']['authTarget']) === false) {
                    $data['POST']['authTarget'] = \Encryptor::encryptJson($data['POST']['authTarget']);
                }
                $manager = \App::load('\\Component\\Member\\Manager');
                $managerSno = 1;
                $data['POST']['managerId'] = $manager->getSpecificManagerInfo(['sno' => $managerSno], 'managerId')['managerId'];
                $data['POST']['sno'] = $managerSno;
                break;
            case 'manage_ps':
                if($data['GET']['mode'] == 'getManagerListLayout') {
                    $getValue = $data['GET'];
                    $_managerClass = new Manager();

                    // 레이어에서 자바스크립트 페이징 처리시 사용되는 구문
                    if (gd_isset($getValue['pagelink'])) {
                        $getValue['page'] = (int) str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
                    }
                    gd_isset($getValue['page'], 1);
                    gd_isset($getValue['pageNum'], 40);

                    $getData = $_managerClass->getManagerList($getValue);
                    foreach($getData['data'] as $cData) {
                        $managerId[] = $cData['managerId'];
                    }
                    $data['searchData'] = implode(', ', $managerId);
                }
                // 암호화 적용 필드
                $encryptTarget = [
                    'email',
                    'cellPhone',
                    'managerPw',
                    'managerPwRe',
                    'modManagerPw',
                    'modManagerPwRe',
                ];
                foreach ($encryptTarget as $target) {
                    if (empty($data['POST'][$target]) === false) {
                        $data['POST'][$target] = \Encryptor::encryptJson($data['POST'][$target]);
                    }
                }
                break;
            case 'manage_list':
            case 'layer_manage':
                foreach($controller->getData('data') as $cData) {
                    $managerId[] = $cData['managerId'];
                }
                $data['searchData'] = implode(', ', $managerId);
                break;
            case 'layer_sms_send_list': // SMS 발송내역 상세보기
            case 'layer_kakao_send_list': // 카카오 알림톡 발송 내역 상세보기
                $arrReceiverCellPhone = [];
                foreach ($controller->getData('data') as $searchData) {
                    $searchData['receiverCellPhone'] = preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3",$searchData['receiverCellPhone']);
                    $arrReceiverCellPhone[] = $searchData['receiverCellPhone'];
                }
                $receiverCellPhone = implode(', ', $arrReceiverCellPhone);
                $data['searchData'] = $receiverCellPhone;
                $data['searchCnt'] = $controller->getData('page')->recode['total'];
                break;
            case 'mail_log_list': // 메일 발송 내역 보기
                $arrReceiverMail = [];
                foreach ($controller->getData('data') as $searchData) {
                    $arrReceiverMail[] = $searchData['receiver'];
                }
                $receiverMail = implode(', ', $arrReceiverMail);
                $data['searchData'] = $receiverMail;
                $data['searchCnt'] = $controller->getData('page')->recode['total'];
                break;
            case 'sms080_list': // 080 수신거부 리스트
                $arrRejectCellPhone = [];
                foreach ($controller->getData('list') as $searchData) {
                    $searchData['rejectCellPhone'] = preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3",$searchData['rejectCellPhone']);
                    $arrRejectCellPhone[] = $searchData['rejectCellPhone'];
                }
                $rejectCellPhone = implode(', ', $arrRejectCellPhone);
                $data['searchData'] = $rejectCellPhone;
                $data['searchCnt'] = $controller->getData('page')->recode['total'];
                break;
            default:
                break;
        }

        if ($isJson) {
            return json_encode($data);
        }

        return $data;
    }

    private function getPageName($pageData)
    {
        return $this->getValue('page', $pageData);
    }

    private function getActionName($pageData)
    {
        return $this->getValue('action', $pageData, '조회');
    }

    private function getMenuName($menu)
    {
        return $this->getValue('menu', $menu);
    }

    private function getValue($key, $pageData, $default = '')
    {
        if (!$pageData) {
            return '';
        }

        if (($pageData[$key])) {
            $page = $pageData[$key];
            if (is_array($page)) {
                return $this->getArrayValue($page);
            }
        }

        $page = $page ? $page : $default;

        return $page;
    }

    /**
     * getArrayValue
     *
     * @param $page
     *
     * @return string
     * @internal param string $default
     */
    private function getArrayValue($page)
    {
        if (is_array($page) === false) {
            return '';
        }

        $result = $this->getArrayData($page);

        return $result;
    }

    /**
     * getArrayData
     *
     * @param $page
     *
     * @return bool
     * @internal param string $mode
     */
    private function getArrayData($page)
    {
        $defaultPage = $page[self::DEFAULT];
        $flag = null;
        $request = \App::getInstance('request');

        foreach ($page as $key => $val) {
            if (strpos($key, self::DELIMITER) !== false) {
                list($_key, $_val) = explode(self::DELIMITER, $key);
                switch ($_key) {
                    case self::MODE :
                        if ($request->request()->get('mode') == $_val) {
                            $flag = $val;
                        }
                        if (is_array($flag)) {
                            $flag = $this->getArrayData($flag);
                        }
                        break;
                    case self::KEY :
                        if ($_val == self::WILD_CARD) {
                            if (is_array($val)) {
                                $result = array_diff_key($request->request()->toArray(), array_flip($val['expect']));
                                if ($result) {
                                    $flag = $val[self::DEFAULT];
                                } else {
                                    $flag = $defaultPage;
                                }
                            } else {
                                if ($request->request()->all()) {
                                    $flag = $val;
                                }
                            }
                        } else {
                            if ($request->request()->get($_val)) {
                                $flag = $val;
                            }
                        }
                        break;
                    case self::MENU :
                        $referer = explode('/', $request->getParserReferer()->path);
                        $adminMenu = ArrayUtils::first($referer);
                        if ($adminMenu === 'provider') {
                            $adminMenu = ArrayUtils::removeEmpty($referer)[array_search($adminMenu, $referer) + 1];
                        }
                        if ($adminMenu == $_val) {
                            $flag = $val;
                        }
                        break;
                    case self::API_KIND :
                        if ($request->request()->get('api_kind') == $_val) {
                            $flag = $val;
                        }
                        if (is_array($flag)) {
                            $flag = $this->getArrayData($flag);
                        }
                        break;
                    default :
                        continue;
                }
            }
        }

        if ($flag === null) {
            $flag = $defaultPage ? $defaultPage : $flag;
        }

        return $flag;
    }

    /**
     * 로그인 시도(실패)한 IP의 마지막 로그인 실패 데이터
     *
     * @param string $ip 로그인시도 접속 IP
     * @param string $IsLimitFlag 접속제한 여부 조회 (Y : 제한조회, N : 미제한조회)
     *
     * @return array
     */
    public function selectLogIpLoginTry($ip, $IsLimitFlag)
    {
        if ($ip == '' || $ip == null) {
            return null;
        }
        $arrBind = [];
        $this->_db->strField = 'limitFlag, onLimitDt, loginFailDt';
        $this->_db->strWhere = 'loginFailIp = ? AND limitFlag = ? AND loginType = ?';
        $this->_db->strOrder = 'sno DESC';
        $this->_db->strLimit = '1';
        $this->_db->bind_param_push($arrBind, 's', $ip);
        $this->_db->bind_param_push($arrBind, 's', $IsLimitFlag);
        $this->_db->bind_param_push($arrBind, 's', 'admin');
        $query = $this->_db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_LOG_IPLOGINTRY . implode(' ', $query);
        $result = $this->_db->query_fetch($strSQL, $arrBind);

        return $result[0];
    }

    /**
     * 로그인 시도(실패)시 1분 동안 몇회 시도 했는지 확인
     *
     * @param string $ip 로그인시도 접속 IP
     *
     * @return int
     */
    public function getOneMinLoginFailCount($ip) {

        $loginFailDtStart = DateTimeUtils::dateFormat('Y-m-d H:i:s', '-1min');
        $loginFailDtEnd = DateTimeUtils::dateFormat('Y-m-d H:i:s', 'now');

        $arrBind = [];
        $arrWhere[] = 'loginFailDt BETWEEN ? AND ? ';
        $this->_db->bind_param_push($arrBind, 's', $loginFailDtStart);
        $this->_db->bind_param_push($arrBind, 's', $loginFailDtEnd);

        $arrWhere[] = 'loginFailIp = ?';
        $this->_db->bind_param_push($arrBind, 's', $ip);

        $arrWhere[] = 'limitFlag = ?';
        $this->_db->bind_param_push($arrBind, 's', 'N');

        $arrWhere[] = 'loginType = ?';
        $this->_db->bind_param_push($arrBind, 's', 'admin');

        $this->_db->strField = ' COUNT(loginFailIp) AS cnt ';
        $this->_db->strWhere = implode(' AND ', $arrWhere);
        $query = $this->_db->query_complete();
        $strSQL = ' SELECT '. array_shift($query) .' FROM ' . DB_LOG_IPLOGINTRY . implode(' ', $query);

        return $this->_db->query_fetch($strSQL, $arrBind, false)['cnt'];
    }

    /**
     * 개인정보접속기록 조회 엑셀 리스트
     *
     * @param array $request 검색파라미터
     *
     * @return array
     */
    public function getAdminLogListExcel($request) {
        $arrBind = [];

        if ($request['managerId']) {
            $where[] = "a.managerId LIKE concat('%',?,'%') ";
            $this->_db->bind_param_push($arrBind, 's', $request['managerId']);
        }

        if ($request['searchDate']) {
            $request['searchDate'][0] = $request['searchDate'][0] ? $request['searchDate'][0] : date('Y-m-d');
            $request['searchDate'][1] = $request['searchDate'][1] ? $request['searchDate'][1] : date('Y-m-d');
            // 최대 검색 일자 (6개월)
            $maximumSearchDays = 179;
            $diffDate = DateTimeUtils::intervalDay($request['searchDate'][0], $request['searchDate'][1]);
            if ($diffDate < 0) {
                $request['searchDate'][0] = $request['searchDate'][1];
            } elseif ($diffDate > $maximumSearchDays) {
                $request['searchDate'][0] = date('Y-m-d', strtotime($request['searchDate'][1] . ' -' . $maximumSearchDays . ' day'));
            }
            // 최대 검색기간
            if ($request['searchDate'][0] < date('Y-m-d', strtotime('-2 year'))){
                $request['searchDate'][0] = date('Y-m-d', strtotime('-2 year'));
            }
            $where[] = '(a.regDt BETWEEN ? AND ?)';
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][0]);
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][1] . ' 23:59:59');
        } else {
            $request['searchDate'][0] = date('Y-m-d', strtotime('-7 day'));
            $request['searchDate'][1] = date('Y-m-d');
            $where[] = '(a.regDt BETWEEN ? AND ?) ';
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][0]);
            $this->_db->bind_param_push($arrBind, 's', $request['searchDate'][1] . ' 23:59:59');
        }

        $where[] = 'a.page NOT IN (?,?) ';
        $this->_db->bind_param_push($arrBind, 's', 'adminInfo');
        $this->_db->bind_param_push($arrBind, 's', 'adminLogExcel');

        // 기존 : 운영자정보 접속기록 탭에 관리자 노출, 공급사는 회원정보 접속기록에 노출
        // 변경 : 관리자, 공급사 두개 다 운영자정보 접속기록에 노출되도록 작업
        // 관리자에 추가된다면, 공급사도 기획측에 확인해서 추가 필요!
        $adminListPage = ['/policy/manage_list.php', '/policy/manage_register.php', '/policy/manage_ps.php', '/policy/manage_permission.php', '/share/layer_manage.php'];
        $providerListPage = ['/provider/policy/manage_list.php', '/provider/policy/manage_register.php', '/provider/policy/manage_ps.php', '/provider/policy/manage_permission.php', '/provider/share/layer_manage.php'];

        $adminListPage = array_merge($adminListPage, $providerListPage);

        $where[] = 'a.baseUri NOT IN (?,?,?,?,?,?,?,?,?,?)';

        foreach($adminListPage as $adminListPageVal) {
            $this->_db->bind_param_push($arrBind, 's', $adminListPageVal);
        }

        $strSQL = " SELECT a.*,m.isDelete  FROM " . DB_ADMIN_LOG . " as a LEFT OUTER JOIN " .DB_MANAGER. " as m ON a.managerNo = m.sno WHERE ";
        if ($where) {
            $strSQL .= implode(' AND ', $where);
        }
        $strSQL .= " ORDER BY a.regDt DESC";
        $result = $this->_db->query_fetch($strSQL, $arrBind);
        $result = $this->setDisplayAdminLogList($result);
        Manager::displayListData($result);

        return $result;
    }
}
