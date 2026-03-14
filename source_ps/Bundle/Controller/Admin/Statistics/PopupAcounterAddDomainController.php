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

namespace Bundle\Controller\Admin\Statistics;

use App;
use Globals;
use Request;
use Framework\Debug\Exception\AlertCloseException;

/**
 * Class 통계-에이스카운터-분석 대상 도메인 설정의 추가 도메인 설정 팝업
 * @package Bundle\Controller\Admin\Statistics
 * @author  shindonggyu
 */
class PopupAcounterAddDomainController extends \Controller\Admin\Controller
{
    public function Index()
    {
        try {
            // Get 파라메터
            $getValue = Request::get()->toArray();

            // 추가분석 도메인 리스트
            $getDomain = $this->_getAddDomainData($getValue['acRequestDomain']);

            // 추가 분석 도메인이 없는 경우
            if (empty($getDomain['domainList'])) {
                throw new AlertCloseException(__('추가 도메인 설정을 할 수 없습니다.'));
            }

            // 이미 설정된 추가 분석 도메인
            $checked = [];
            foreach ($getDomain['addDomain'] as $addDomain) {
                $checked['acAddDomain'][$addDomain] = 'checked="checked"';
            }

            $this->setData('acRequestDomain', $getValue['acRequestDomain']);
            $this->setData('acDomainFl', $getDomain['domainFl']);
            $this->setData('acAddDomain', $getDomain['domainList']);
            $this->setData('checked', $checked);
            $this->getView()->setDefine('layout', 'layout_blank.php');

        } catch (AlertCloseException $e) {
            throw new AlertCloseException($e->getMessage());
        }
    }

    /**
     * 분석 대상 도메인 정보
     *
     * @param string $checkDomain 신청도메인
     * @return string
     * @author  shindonggyu
     */
    protected function _getAddDomainData($checkDomain)
    {
        // 몰별 도메인 추출
        $mall = \APP::load('\\Component\\Mall\\Mall');
        $_getDomainList = $mall->getShopDomainAllList();

        // 에이스 카운터 설정
        $policy = \App::load('\\Component\\Policy\\Policy');
        $_aDefault = $policy->getACounterSettingByDefault();
        $_aGlobals = $policy->getACounterServiceListByGlobals();
        if(empty($_aGlobals)){
            $_aConf = $_aDefault;
        }else{
            $_aConf = array_merge($_aGlobals, $_aDefault);
        }

        // 현재 도메인의 에이스 카운터 설정 중 국가 코드
        $_domainFl = $_aConf[$checkDomain]['aCounterDomainFl'];

        // 현재 도메인의 에이스 카운터 설정 중 서비스명
        $_kindFl = $_aConf[$checkDomain]['aCounterKind'];

        // 모바일 여부
        $_prefixFl = '';
        if ($_kindFl == 'mweb') {
            $_prefixFl = DOMAIN_USEABLE_LIST['mobile'] . '.';
        }

        // 해당몰 도메인 제외하고 리스트에서 제거
        foreach ($_getDomainList as $dKey => $dVal) {
            if ($_domainFl != $dKey) {
                unset($_getDomainList[$dKey]);
            }
        }

        // 데이터 추출
        $returnDomainList = [];

        // 도메인 종류
        $returnDomainList['domainFl'] = $_domainFl;

        // 이미 설정된 추가 분석 도메인
        $returnDomainList['addDomain'] = [];
        if (empty($_aConf[$checkDomain]['aCounterAddDomain']) === false) {
            $returnDomainList['addDomain'] = explode(',', $_aConf[$checkDomain]['aCounterAddDomain']);
        }

        // 추가분석 도메인 리스트 추출
        foreach ($_getDomainList as $domainVal) {
            foreach ($domainVal as $dVal) {
                $domainNm = $_prefixFl . $dVal;
                if ($domainNm != $checkDomain) {
                    $returnDomainList['domainList'][] = $domainNm;
                }
            }
        }

        return $returnDomainList;
    }
}