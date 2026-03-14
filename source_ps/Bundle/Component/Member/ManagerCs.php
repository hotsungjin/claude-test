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

namespace Bundle\Component\Member;

use DateTime;
use Component\Database\DBTableField;
use Component\Validator\Validator;
use Framework\Debug\Exception\DatabaseException;
use Framework\Security\Digester;
use Framework\Security\Otp;
use Framework\Utility\ComponentUtils;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\GodoUtils;
/**
 * Class ManagerCs
 * @package Bundle\Component\Member
 * @author  yjwee <yeongjong.wee@godo.co.kr>
 */
class ManagerCs extends \Component\Member\Manager
{
    /** @var string CS 용 계정 아이디 프리픽스 */
    const PREFIX_CS_ID = 'CS';

    /** @var string CS계정 수동생성 용 아이디 프리픽스 */
    const PREFIX_M_CS_ID = 'CS';

    /** @var int 생성 시 유효일 당일포함 15일 */
    protected $createValidDay = 14;

    /** @var int 인증 시 유효일 당일포함 3일 */
    protected $authenticationValidDay = 2;

    /**
     * CS 계정 생성 (계정생성 API용)
     *
     * @param array $permissions CS 계정 권한 설정 정보
     *
     * @return int
     */
    public function createManagerAccounts(array $permissions): int
    {
        try {
            $managerNm = '고도몰5고객지원';
            if ($permissions['permissionFl'] === 'all') {
                $permissions['permissionMenu'] = $permissions['functionAuth'] = [];
                $permissionFl = 's';
                $managerNm .= '(망분리)';
            } else {
                $permissionFl = 'l';
                $managerNm .= '(로컬)';
            }

            $csId = self::PREFIX_CS_ID . Otp::getOtp(6, Otp::OTP_TYPE_INTEGER);
            $csPw = $this->generatePassword();

            $managerInfo = [
                'scmNo'             => DEFAULT_CODE_SCMNO,
                'managerId'         => $csId,
                'managerPw'         => Digester::digest($csPw),
                'managerNm'         => $managerNm,
                'permissionFl'      => $permissionFl,
                'isSuper'           => 'cs',
                'workPermissionFl'  => 'y',
                'debugPermissionFl' => 'y',
            ];
            $this->convertPermissions($permissions, $managerInfo);
            $managerSno = $this->insertManagerProc($managerInfo);

            if ($managerSno < 1) {
                throw new DatabaseException('Manager 계정 생성 실패');
            }

            $encryptor = \App::getInstance('encryptor');
            $csManagerInfo = [
                'scmNo'          => DEFAULT_CODE_SCMNO,
                'managerSno'     => $managerSno,
                'csId'           => $encryptor->mysqlAesEncrypt($csId),
                'csPw'           => $encryptor->mysqlAesEncrypt($csPw),
                'permissionFl'   => $permissions['permissionFl'] === 'all' ? 's' : 'l',
                'permissionMenu' => json_encode(['menu' => $permissions['permissionMenu']], JSON_UNESCAPED_UNICODE),
                'functionAuth'   => null,
                'expireDate'     => DateTimeUtils::dateFormat('Y-m-d', '+ ' . $this->createValidDay . ' days'),
            ];

            if (\is_array($permissions['functionAuth']) && \count($permissions['functionAuth']) > 0) {
                $csManagerInfo['functionAuth'] = json_encode($permissions['functionAuth'], JSON_UNESCAPED_UNICODE);
            }
            $csSno = $this->insertManagerCsProc($csManagerInfo);

            if ($csSno < 1) {
                throw new DatabaseException('ManagerCs 계정 생성 실패');
            }
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $csSno;
    }

    /**
     * DB_MANAGER 테이블에 계정 정보 추가
     *
     * @param array $managerInfo    생성 대상 계정 정보
     * @return int                  생성된 DB_MANAGER의 sno
     */
    private function insertManagerProc(array $managerInfo): int
    {
        $tableManager = DBTableField::tableManager();
        $arrInclude = array_keys($managerInfo);

        $this->db->query_reset();
        $arrBind = $this->db->get_binding($tableManager, $managerInfo, 'insert', $arrInclude);
        $this->db->set_insert_db(DB_MANAGER, $arrBind['param'], $arrBind['bind'], 'y');
        return $this->db->insert_id();
    }

    /**
     * DB_MANAGER_CUSTOMER_SERVICE 테이블에 계정 정보 추가
     *
     * @param array $csManagerInfo  생성 대상 계정 정보
     * @return int                  생성된 DB_MANAGER_CUSTOMER_SERVICE의 sno
     */
    private function insertManagerCsProc(array $csManagerInfo): int
    {
        $tableManagerCustomerService = DBTableField::tableManagerCustomerService();
        $arrInclude = array_keys($csManagerInfo);

        $this->db->query_reset();
        $arrBind = $this->db->get_binding($tableManagerCustomerService, $csManagerInfo, 'insert', $arrInclude);
        $this->db->set_insert_db(DB_MANAGER_CUSTOMER_SERVICE, $arrBind['param'], $arrBind['bind'], 'y');
        return $this->db->insert_id();
    }

    /**
     * CS 계정 비번 생성
     *
     * @return string
     */
    public function generatePassword(): string
    {
        $password = Otp::getOtp(12, Otp::OTP_TYPE_MIX);
        if (!Validator::password($password, true)) {
            $inValid = true;
            while ($inValid) {
                $password = Otp::getOtp(12, Otp::OTP_TYPE_MIX);
                if (Validator::password($password, true)) {
                    $inValid = false;
                }
            }
        }

        return $password;
    }

    /**
     * CS 테이블의 권한 정보 변환 후 관리자 권한 정보에 설정
     *
     * @param array $permissions CS 권한정보 permissionMenu, functionAuth, permissionFl, writeEnabledMenu
     * @param array $managerInfo 관리자 권한정보
     */
    protected function convertPermissions(array $permissions, array &$managerInfo)
    {
        if ($permissions['permissionFl'] !== 'all') {
            $permission3 = [];

            foreach ($permissions['permissionMenu'] as $permission) {
                if (array_key_exists($permission[1], $permission3)) {
                    $permission3[$permission[1]][] = $permission[2];
                } else {
                    $permission3[$permission[1]] = [$permission[2]];
                }
            }

            $permissionMenu = [
                'permission_1' => null,
                'permission_2' => null,
                'permission_3' => $permission3,
            ];
            $managerInfo['permissionMenu'] = json_encode($permissionMenu, JSON_UNESCAPED_UNICODE);

            $functionAuth = ['functionAuth' => null];
            if (\is_array($permissions['functionAuth']) && \count($permissions['functionAuth']) > 0) {
                $functionAuth = ['functionAuth' => $permissions['functionAuth']];
            }
            $managerInfo['functionAuth'] = json_encode($functionAuth, JSON_UNESCAPED_UNICODE);

            $managerInfo['writeEnabledMenu'] = json_encode($permissions['writeEnabledMenu'], JSON_UNESCAPED_UNICODE);
        } else {
            // 전체권한인 경우 관리자 권한 정보에 상품 재고 수정 권한 저장
            $functionAuth['functionAuth'] = $this->getFunctionAuth(true);
            $managerInfo['functionAuth'] = json_encode($functionAuth, JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * CS관리자계정(로컬)을 위한 권한 리스트
     * 관리자 메뉴 테이블의 parent 구조를 조합하여 3단 구조로 반환
     * (계정 생성 시 whitelist로 기입되는 정보에서 특정 메뉴를 선택하여 제외하기 위함)
     *
     * @return array
     */
    public function getNonPrivacyPermissions(): array
    {
        $arrBind = [];
        $this->db->query_reset();
        $this->db->strField = ' lv1.adminMenuNo AS lv1, lv2.adminMenuNo AS lv2, lv3.adminMenuNo AS lv3 ';
        $this->db->strOrder = ' lv1.adminMenuNo, lv2.adminMenuNo, lv3.adminMenuNo ';
        $this->db->strJoin  = ' INNER JOIN '.DB_ADMIN_MENU.' AS lv2 ON lv1.adminMenuNo=lv2.adminMenuParentNo ';
        $this->db->strJoin .= ' INNER JOIN '.DB_ADMIN_MENU.' AS lv3 ON lv2.adminMenuNo=lv3.adminMenuParentNo ';

        // 운영자 수정, 권한설정 제외
        $this->db->strWhere = 'NOT (lv1.adminMenuName LIKE "%기본%" AND 
                                    lv2.adminMenuName LIKE "%관리%" AND 
                                    lv3.adminMenuName LIKE "%수정%" AND lv3.adminMenuUrl="manage_register.php") ';
        // 회원 수정, 권한설정 제외
        $this->db->strWhere .= 'AND NOT (lv1.adminMenuName LIKE "%회원%" AND 
                                         lv2.adminMenuName LIKE "%회원%" AND 
                                         lv3.adminMenuName LIKE "%수정%" AND lv3.adminMenuUrl="member_modify.php") ';
        // 공급사 등록/수정, 권한설정 제외
        $this->db->strWhere .= 'AND NOT (lv1.adminMenuName LIKE "%공급사%" AND 
                                         lv2.adminMenuName LIKE "%관리%" AND 
                                         (lv3.adminMenuName LIKE "%등록%" AND lv3.adminMenuUrl="scm_regist.php") OR 
                                         (lv3.adminMenuName like "%수정%" AND lv3.adminMenuUrl="scm_regist.php")) ';

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ADMIN_MENU . ' AS lv1 ' .  implode(' ', $query);
        $adminMenuList = $this->db->query_fetch($strSQL, $arrBind);

        $result = [];
        foreach($adminMenuList as $menu) {
            $result[] = array_values($menu);
        }

        return $result;
    }

    /**
     * CS관리자계정을 위한 기능 리스트
     *
     * @param bool $isSuper     최고 관리 권한 여부 true / false
     *
     * @return array
     */
    public function getFunctionAuth(bool $isSuper=false): array
    {
        $adminMenu = \App::load('Component\\Admin\\AdminMenu');

        foreach($adminMenu->getMenuFunction('d') as $funcs) {
            foreach ($funcs as $name => $val) {
                $result[$name] = 'y';
            }
            unset($name, $val);
        }
        unset($funcs);

        if (!$isSuper) {
            unset($result['goodsStockExceptView']); // 상품 상세 재고 수정 제외
            unset($result['orderExcelDown']);       // 주문/배송 엑셀 다운로드
            unset($result['memberExcelDown']);      // 회원 엑셀 다운로드
        } else {
            unset($result['withdrawnMembersOrderLimitViewFl']); // 탈퇴회원거래내역조회 제한 제외
        }

        return $result;
    }

    /**
     * CS관리자계정(로컬)을 위한 읽기전용 메뉴 리스트
     * @return array
     */
    public function getNonPrivacyWriteEnabledMenu(): array
    {
        $arrBind = [];
        $this->db->query_reset();
        $this->db->strField = ' lv2.adminMenuNo AS lv2, lv3.adminMenuNo AS lv3 ';
        $this->db->strOrder = ' lv2.adminMenuNo, lv3.adminMenuNo ';
        $this->db->strJoin  = ' INNER JOIN '.DB_ADMIN_MENU.' AS lv2 ON lv1.adminMenuNo=lv2.adminMenuParentNo ';
        $this->db->strJoin .= ' INNER JOIN '.DB_ADMIN_MENU.' AS lv3 ON lv2.adminMenuNo=lv3.adminMenuParentNo ';

        // 기본설정 > 기본정책 > 기본 정보 설정
        $this->db->strWhere = 'NOT (lv1.adminMenuName LIKE "%기본%" AND 
                                    lv2.adminMenuName LIKE "%정책%" AND 
                                    lv3.adminMenuName LIKE "%설정%" AND lv3.adminMenuUrl="base_info.php") ';
        // 기본설정 > 관리 정책 > 운영자 관리, 등록, 수정, 권한설정
        $this->db->strWhere .= 'AND NOT (lv1.adminMenuName LIKE "%기본%" AND 
                                         lv2.adminMenuName LIKE "%관리%" AND 
                                         (lv3.adminMenuName LIKE "%관리%" AND lv3.adminMenuUrl="manage_list.php") OR 
                                         (lv3.adminMenuName like "%등록%" AND lv3.adminMenuUrl="manage_register.php") OR
                                         (lv3.adminMenuName like "%수정%" AND lv3.adminMenuUrl="manage_register.php") OR
                                         (lv3.adminMenuName like "%권한%" AND lv3.adminMenuUrl="manage_permission.php") OR
                                         (lv3.adminMenuName like "%개인정보접속기록%" AND lv3.adminMenuUrl="admin_log_list.php")) ';
        // 회원 > 회원 관리 > 회원 수정
        $this->db->strWhere .= 'AND NOT (lv1.adminMenuName LIKE "%회원%" AND 
                                         lv2.adminMenuName LIKE "%관리%" AND 
                                         lv3.adminMenuName LIKE "%수정%" AND lv3.adminMenuUrl="member_modify.php") ';
        // 공급사 > 공급사 관리 > 공급사 등록, 수정
        $this->db->strWhere .= 'AND NOT (lv1.adminMenuName LIKE "%공급사%" AND 
                                         lv2.adminMenuName LIKE "%관리%" AND 
                                         (lv3.adminMenuName LIKE "%등록%" AND lv3.adminMenuUrl="scm_regist.php") OR
                                         (lv3.adminMenuName LIKE "%수정%" AND lv3.adminMenuUrl="scm_regist.php"))';

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ADMIN_MENU . ' AS lv1 ' .  implode(' ', $query);
        $adminMenuList = $this->db->query_fetch($strSQL, $arrBind);

        $result = [];
        foreach($adminMenuList as $menu) {
            $result[$menu['lv2']][] = $menu['lv3'];
        }
        unset($menu);

        return $result;
    }

    /**
     * CS 계정 테이블 조회 (전체 리스트)
     * ID, PW 복호화
     *
     * @return array
     * @throws DatabaseException
     */
    public function getDecryptListAll(): array
    {
        $arrList = $this->selectListAll();
        $encryptor = \App::getInstance('encryptor');
        foreach ($arrList as $index => &$item) {
            $item['csId'] = $encryptor->mysqlAesDecryptByCS($item['csId']);
            $item['csPw'] = $encryptor->mysqlAesDecryptByCS($item['csPw']);
        }

        return $arrList;
    }

    /**
     * CS 계정 테이블 조회 (특정 계정)
     * ID, PW 복호화
     *
     * @param array  $csData 삭제 대상 CS계정 정보 // e.g.) ['csSno'=>'', 'csid=>'']
     *
     * @return array
     */
    public function getDecryptCsAccount(array $csData = []): array
    {
        if (empty($csData['csSno']) && empty($csData['csId'])) {
            return [];
        }

        $encryptor = \App::getInstance('encryptor');
        $accountData = [];
        $arrBind = [];

        $this->db->query_reset();
        $this->db->strField = 'sno, managerSno, scmNo, csId, csPw, expireDate, permissionFl';
        $this->db->strOrder = 'regDt ASC';
        $this->db->strWhere = 'scmNo = ' . DEFAULT_CODE_SCMNO;

        if (!empty($csData['csSno'])) {
            $this->db->strWhere .= ' AND sno = ? ';
            $this->db->bind_param_push($arrBind, 'i', $csData['csSno']);
        }
        if (!empty($csData['csId'])) {
            $this->db->strWhere .= ' AND csId = ? ';
            $this->db->bind_param_push($arrBind, 's', $encryptor->mysqlAesEncrypt($csData['csId']));
        }

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_MANAGER_CUSTOMER_SERVICE . implode(' ', $query);
        $dbData = $this->db->query_fetch($strSQL, $arrBind, false);
        unset($arrBind);

        if (!empty($dbData)) {
            $accountData = $dbData;
            $accountData['csId'] = $encryptor->mysqlAesDecrypt($dbData['csId']);
            $accountData['csPw'] = $encryptor->mysqlAesDecrypt($dbData['csPw']);
        }

        return $accountData;
    }

    /**
     * CS 계정 테이블 조회
     *
     * @param bool   $isExpired    만료된 계정을 조회할 때에만 true, 기본값은 만료되지 않은 계정 false
     * @param string $expireDate   만료된 계정을 조회하는 경우에만 사용하는 Y-m-d 형식의 만료일자 정보
     *
     * @return array               조회된 CS 계정 정보
     */
    public function selectListAll(bool $isExpired=false, string $expireDate=''): array
    {
        $fields = DBTableField::getFieldTypes(DBTableField::getFuncName(DB_MANAGER_CUSTOMER_SERVICE));
        $arrBind = [];

        $this->db->query_reset();
        $this->db->strField = 'sno, scmNo, permissionFl, permissionMenu, functionAuth, csId, csPw, expireDate';
        $this->db->strOrder = 'regDt ASC';
        $targetDate = DateTimeUtils::dateFormat('Y-m-d', 'now');
        if ($isExpired) {
            $this->db->strWhere = ' expireDate < ?';
            if (DateTime::createFromFormat('Y-m-d', $expireDate)->format('Y-m-d') === $expireDate) {
                $targetDate = $expireDate;
            }
        } else {
            $this->db->strWhere = ' expireDate >= ?';
        }
        $this->db->bind_param_push($arrBind, $fields['expireDate'], $targetDate);

        if (!parent::useProvider()) {
            $this->db->strWhere .= ' AND scmNo = ' . DEFAULT_CODE_SCMNO;
        }

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_MANAGER_CUSTOMER_SERVICE . implode(' ', $query);

        return $this->db->query_fetch($strSQL, $arrBind);
    }

    /**
     * CS 계정 로그인 검증 정보 조회
     *
     * @param array $loginData
     *
     * @return array
     * @throws DatabaseException
     */
    public function getManagerByLogin(array $loginData): array
    {
        $fields = DBTableField::getFieldTypes(DBTableField::getFuncName(DB_MANAGER_CUSTOMER_SERVICE));
        $arrBind = [];

        $this->db->query_reset();
        $this->db->strField = '*';
        $this->db->strWhere = 'csId=? AND csPw=? AND expireDate>=?';
        $encryptor = \App::getInstance('encryptor');
        $this->db->bind_param_push($arrBind, $fields['csId'], $encryptor->mysqlAesEncrypt($loginData['managerId']));
        $this->db->bind_param_push($arrBind, $fields['csPw'], $encryptor->mysqlAesEncrypt($loginData['managerPw']));
        $this->db->bind_param_push($arrBind, $fields['expireDate'], DateTimeUtils::dateFormat('Y-m-d', 'now'));

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_MANAGER_CUSTOMER_SERVICE . implode(' ', $query);
        $result = $this->db->query_fetch($strSQL, $arrBind, false);
        unset($query, $fields, $arrBind);

        if (array_key_exists('managerSno', $result) && array_key_exists('sno', $result)
            && $result['managerSno'] > 0 && $result['sno'] > 0) {
            $this->db->query_reset();
            //@formatter:off
            $arrInclude = [
                'managerId', 'managerNickNm', 'managerNm', 'managerPw', 'cellPhone', 'isSmsAuth', 'employeeFl',
                'workPermissionFl', 'debugPermissionFl', 'permissionFl', 'isSuper', 'changePasswordDt',
                'guidePasswordDt', 'permissionMenu', 'loginLimit',
            ];
            //@formatter:on
            $arrField = DBTableField::setTableField('tableManager', $arrInclude);
            $arrBind = [];
            $arrJoin[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON sm.scmNo = m.scmNo ';
            $this->db->strField = implode(', ', $arrField);
            $this->db->strJoin = implode('', $arrJoin);
            $this->db->strWhere = "m.sno = ? AND m.isDelete = 'n' AND sm.scmType = 'y' "; // 공급사 운영상태 일 경우만 로그인 가능
            $this->db->bind_param_push($arrBind, 'i', $result['managerSno']);
            $query = $this->db->query_complete();
            $strSQL = 'SELECT sno,m.modDt AS mModDt, m.managerId, m.managerNickNm, m.regDt AS mRegDt, m.isEmailAuth,';
            $strSQL .= ' m.email, sm.scmNo,sm.scmKind,sm.scmPermissionInsert,sm.scmPermissionModify,';
            $strSQL .= ' sm.scmPermissionDelete, sm.companyNm,sm.scmType, ' . array_shift($query);
            $strSQL .= ' FROM ' . DB_MANAGER . ' as m ' . implode(' ', $query);

            $data = $this->db->query_fetch($strSQL, $arrBind, false);
            unset($arrBind);
            $loginLimit = json_decode($data['loginLimit'], true);
            $data['loginLimit'] = $loginLimit;
            $data['csSno'] = $result['sno'];

            return $data;
        }

        return parent::getManagerByLogin($loginData);
    }

    /**
     * CS 계정 유효성 검사 및 인증 처리 후 세션 생성
     *
     * @param array $data
     *
     * @throws DatabaseException
     */
    public function afterManagerLogin($data)
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $logger = \App::getInstance('logger');

        $this->db->query_reset();
        $data['lastLoginIp'] = $request->getRemoteAddress();
        // --- 관리자 테이블 갱신
        $arrBind = [];
        $arrUpdate[] = 'lastLoginDt = now()';
        $arrUpdate[] = 'lastLoginIp = \'' . $data['lastLoginIp'] . '\'';
        $arrUpdate[] = 'loginCnt = loginCnt + 1';
        $this->db->bind_param_push($arrBind, 'i', $data['sno']);
        $this->db->set_update_db(DB_MANAGER, $arrUpdate, 'sno = ?', $arrBind);
        $logger->info(__METHOD__ . ', update columns => ', $arrUpdate);
        unset($arrUpdate, $arrBind);

        $this->db->query_reset();
        $tableManagerCustomerService = DBTableField::tableManagerCustomerService();
        $params = ['expireDate' => DateTimeUtils::dateFormat('Y-m-d', '+ ' . $this->authenticationValidDay . ' days'),];
        $arrInclude = array_keys($params);
        $arrBind = $this->db->get_binding($tableManagerCustomerService, $params, 'update', $arrInclude);
        $this->db->bind_param_push($arrBind['bind'], 'i', $data['csSno']);
        $this->db->set_update_db(DB_MANAGER_CUSTOMER_SERVICE, $arrBind['param'], 'sno = ?', $arrBind['bind']);

        // 관리자 세션 허용 시간 제한 (관리자 처리 없이 아래 시간까지 지난 경우 세션 아웃처리됨)
        $managerSecurity = ComponentUtils::getPolicy('manage.security');
        if ($managerSecurity['sessionLimitUseFl'] === 'y') {
            $sessionLimitTime = time() + $managerSecurity['sessionLimitTime'];
        } else {
            // 관리자 자동 로그아웃 사용 안해도 값을 저장하고 있어야 설정 사용 저장 시 로그아웃이 안됨
            $sessionLimitTime = time() + $this->getDefaultSessionLimitTime();
        }
        $data['sessionLimitTime'] = $sessionLimitTime;
        $session->del(self::SESSION_TEMP_MANAGER);
        $session->set(self::SESSION_MANAGER_LOGIN, $data);
    }

    /**
     * 관리자 보안로그인 인증 유효기간 설정
     * CS 계정은 유효기간이 없다
     *
     * @param array $tmpManager   CS 계정 정보
     */
    public function setLoginAuthCookie(array $tmpManager)
    {
        $logger = \App::getInstance('logger');
        $logger->info('disable customer service account authentication save function. csSno=' . $tmpManager['csSno']);
    }

    /**
     * 특정 CS 계정 정보 삭제
     *
     * @param array  $csData 삭제 대상 CS계정 정보 // e.g.) ['csSno'=>'', 'csid=>'']
     *
     * @return int   delete 쿼리의 affectedRows, 두 테이블에서 삭제하기 때문에 하나의 계정 당 2로 반환됨
     */
    public function deleteManagerCs(array $csData=[]): int
    {
        if (empty($csData['csSno']) && empty($csData['csId'])) {
            return 0;
        }

        $arrBind = [];
        $encryptor = \App::getInstance('encryptor');

        $this->db->query_reset();
        $query = 'DELETE mcs, m FROM '. DB_MANAGER_CUSTOMER_SERVICE .' AS mcs ';
        $query .= ' INNER JOIN ' . DB_MANAGER . ' AS m ON mcs.managerSno = m.sno ';
        $query .= ' WHERE m.isSuper = ? ';
        $this->db->bind_param_push($arrBind, 's', 'cs');

        if (!empty($csData['csSno'])) {
            $query .= ' AND mcs.sno = ? ';
            $this->db->bind_param_push($arrBind, 'i', $csData['csSno']);
        }
        if (!empty($csData['csId'])) {
            $query .= ' AND mcs.csId = ? ';
            $this->db->bind_param_push($arrBind, 's', $encryptor->mysqlAesEncrypt($csData['csId']));
        }

        return $this->db->bind_query($query, $arrBind);
    }

    /**
     * 만료일이 지난 CS 계정정보 삭제
     *
     * @param string $expireDate    만료일 Y-m-d (e.g. '2024-07-22')
     *
     * @return int
     * @throws DatabaseException
     */
    public function expireManagerCs(string $expireDate): int
    {
        $arrBind = [];

        $this->db->query_reset();
        $query = 'DELETE mcs, m FROM '. DB_MANAGER_CUSTOMER_SERVICE .' AS mcs ';
        $query .= ' INNER JOIN ' . DB_MANAGER . ' AS m ON mcs.managerSno = m.sno ';
        $query .= ' WHERE m.isSuper = ? AND mcs.expireDate < ?';
        $this->db->bind_param_push($arrBind, 's', 'cs');
        $this->db->bind_param_push($arrBind, 's', $expireDate);

        return $this->db->bind_query($query, $arrBind);
    }

    /**
     * CS 계정 확인 함수
     *
     * @param int $managerSno    CS 계정의 sno
     *
     * @return bool
     * @throws DatabaseException
     */
    public function isCustomerService(int $managerSno): bool
    {
        $arrBind = [];
        $fields = DBTableField::getFieldTypes(DBTableField::getFuncName(DB_MANAGER_CUSTOMER_SERVICE));
        $this->db->query_reset();
        $this->db->strWhere = 'managerSno = ?';
        $this->db->bind_param_push($arrBind, $fields['managerSno'], $managerSno);
        $this->db->strField = 'COUNT(*) AS cnt';
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_MANAGER_CUSTOMER_SERVICE . implode(' ', $query);
        $cnt = $this->db->query_fetch($strSQL, $arrBind, false)['cnt'];
        StringUtils::strIsSet($cnt, 0);

        return $cnt > 0;
    }

    /**
     * 관리자 로그인 시 IP 체크 하는 함수
     * CS 계정은 별도의 IP 체크를 하기 때문에 검증하지 않는다.
     *
     * @param string $remoteIp         접속 IP 주소
     * @param array $manageSecurity    관리자 등록 IP 주소정보
     */
    protected function validateIpByLogin(string $remoteIp, array $manageSecurity)
    {
        $logger = \App::getInstance('logger');
        $logger->info(__METHOD__ . ', cs account pass ip check');
    }
}
