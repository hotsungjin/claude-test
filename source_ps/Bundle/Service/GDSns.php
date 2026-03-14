<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link http://www.godo.co.kr
 */

namespace Bundle\Service;

use Bundle\Component\Member\Member;
use Bundle\Component\Member\MemberSleep;
use Bundle\Component\Member\Util\MemberUtil;
use Framework\Debug\Exception\AlertCloseException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\PlusShop\PlusShopWrapper;
use Framework\StaticProxy\Proxy\Request;

class GDSns
{
    /**
     * 소셜 로그인
     *
     * @param $eventName
     * @param $uuid
     * @param $sessionKey
     * @return bool|void
     */
    public function login($eventName, $uuid, $sessionKey)
    {
        $response = PlusShopWrapper::event($eventName, ['uuid' => $uuid, 'sessionKey' => $sessionKey]);

        if (!empty($response)) {
            $memNo = $response[0]->getContent();

            if ($memNo === null) {
                return false;
            }

            $this->setLoginSession($memNo);

            return true;
        }

        return false;
    }

    /**
     * 로그인처리
     *
     * @param $memNo
     * @throws AlertCloseException
     * @throws AlertRedirectException
     * @throws \Exception
     */
    public function setLoginSession($memNo)
    {
        $logger = \App::getInstance('logger');

        $db = \App::load('DB');
        $db->strField = 'm.memNo, m.memId, m.memPw, m.groupSno, m.memNm, m.appFl, m.sleepFl, m.maillingFl, m.loginLimit';
        $db->strField .= ', m.email, m.adultConfirmDt, m.adultFl, m.loginCnt, m.changePasswordDt, m.guidePasswordDt';
        $db->strField .= ', m.modDt AS mModDt, m.regDt AS mRegDt';
        $db->strField .= ', mg.groupNm, mg.groupSort, mh.sno AS hackOutSno';
        $db->strJoin .= ' LEFT JOIN ' . DB_MEMBER_GROUP . ' AS mg ON mg.sno = m.groupSno';
        $db->strJoin .= ' LEFT JOIN ' . DB_MEMBER_HACKOUT . ' AS mh ON mh.memNo = m.memNo';
        $db->strWhere = 'm.memNo=?';
        $db->strLimit = '1';
        $bind = null;
        $db->bind_param_push($bind, 'i', $memNo);
        $query = $db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_MEMBER . ' AS m ';
        $strSQL .= implode(' ', $query);
        $member = $db->query_fetch($strSQL, $bind, false);
        $loginLimit = json_decode($member['loginLimit'], true);
        $member['loginLimit'] = $loginLimit;

        if ($member['hackOutSno'] != null) {
            throw new \Exception(__('회원 탈퇴를 신청하였거나, 탈퇴한 회원이십니다.<br/>로그인이 제한됩니다.'), 500);
        }

        if ($member['sleepFl'] == 'y') {
            $logger->info(sprintf('Dormant membership restoration is required. memNo[%s], memId[%s], sleepFl[%s]', $member['memNo'], $member['memId'], $member['sleepFl']));
            $session = \App::getInstance('session');
            //@formatter:off
            $session->set(MemberSleep::SESSION_WAKE_INFO, ['memId' => $member['memId'], 'memPw' => $member['memPw']]);
            //@formatter:on
            throw new \Exception(__('휴면회원 해제가 필요합니다.'), 401);
        }

        if ($member['appFl'] != 'y') {
            $logger->info(sprintf('Your login is restricted because you are not authorized on this site. memNo[%s], memId[%s], appFl[%s]', $member['memNo'], $member['memId'], $member['appFl']));
            throw new \Exception(__('고객님은 본 사이트에서 승인되지 않아 로그인이 제한됩니다.'), 500);
        }

        if ($member['loginLimit']['limitFlag'] == 'y') {
            $logger->info('로그인이 제한된 회원입니다.', ['loginLimit' => $member['loginLimit']]);
            throw new \Component\Member\Exception\LoginLimitException('로그인이 제한되었습니다. 10분 후에 시도해 주세요.', 500);
        }

        if ($member['adultFl'] == 'y' && (strtotime($member['adultConfirmDt']) < strtotime("-1 year", time()))) {
            $member['adultFl'] = "n";
        }
        $session = \App::getInstance('session');
        $encrypt = MemberUtil::encryptMember($member);
        $session->set(Member::SESSION_MEMBER_LOGIN, $encrypt);
        $session->set('expireTime', time());
        if ($session->has(Member::SESSION_MEMBER_LOGIN)) {
            \Logger::channel('userLogin')->info('로그인 성공', [$this->getRequestData()]);
        } elseif (!$session->has(SESSION_GLOBAL_MALL) && $session->has('notDefaultStoreMember')) {
            \Logger::channel('userLogin')->warning('기준몰 회원만 로그인 가능', [$this->getRequestData()]);
            // 기준몰에서 해외몰 로그인 시에만 발생하기 때문에 이동 페이지를 루트로 설정하였음
            if (GDRequest::isMobile()) {
                throw new AlertRedirectException('기준몰 회원만 로그인 가능합니다', null, null, '/');
            }
            throw new AlertCloseException('기준몰 회원만 로그인 가능합니다');
        }
    }

    /**
     * 기술지원지 필요한 정보들
     *
     * @param array $data
     * @return string
     * @internal param bool $isJson
     *
     */
    protected function getRequestData($data = [])
    {
        $data['PAGE_URL'] = Request::getDomainUrl() . Request::getRequestUri();
        $data['POST'] = Request::post()->toArray();
        unset($data['POST']['loginPwd']);
        $data['GET'] = Request::get()->toArray();
        $data['USER_AGENT'] = Request::getUserAgent();
        $data['SESSION'] = \Session::get('member');
        unset($data['SESSION']['memPw'], $data['SESSION']['memNm'], $data['SESSION']['nickNm'], $data['SESSION']['cellPhone'], $data['SESSION']['email']);
        $data['COOKIE'] = \Cookie::all();
        $data['REFERER'] = Request::getReferer();
        $data['REMOTE_ADDR'] = Request::getRemoteAddress();
        if (empty($data) === false) {
            $data['DATA'] = $data;
        }

        return $data;
    }
}
