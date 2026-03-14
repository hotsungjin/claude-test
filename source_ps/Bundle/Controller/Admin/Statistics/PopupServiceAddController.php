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
use Framework\Utility\GodoUtils;
use Component\Mall\Mall;
use Component\Godo\GodoNhnServerApi;
use Component\Nhn\AcounterCommonScript;
use Framework\Debug\Exception\AlertOnlyException;

/**
 * Class 통계-에이스카운터-에이스카운터 서비스 추가 팝업
 * @package Bundle\Controller\Admin\Statistics
 * @author  choisueun
 */
class PopupServiceAddController extends \Controller\Admin\Controller
{
    private $acecounterConfig;
    private $isEnabled;

    public function Index()
    {
        // --- 메뉴 설정

        try {
            $mall = \APP::load('\\Component\\Mall\\Mall');
            $arrGlobalsE = $mall->mallSecurityByPc();
            $arrGlobalsM = $mall->mallSecurityByMobile();
            $domainList = array_merge_recursive($arrGlobalsE, $arrGlobalsM);

            // 에이스카운터1 서비스 신청 여부 체크
            $policy = \App::load('\\Component\\Policy\\Policy');
            $aCounterConfDefault = $policy->getACounterSettingByDefault();
            $aCounterConfGlobals = $policy->getACounterServiceListByGlobals();
            if(empty($aCounterConfGlobals)){
                $aCounterConfig = $aCounterConfDefault;
            }else {
                $aCounterConfig = array_merge($aCounterConfDefault, $aCounterConfGlobals);
            }

            foreach ($aCounterConfig as $domain => $val) {
                $arrConfigDomainData[] = $domain;
            }
            if (empty($domainList) === false) {
                foreach ($domainList as $mallNm => $value) {
                    foreach ($value as $domainFl => $val) {
                        foreach ($val as $url) {
                            if (!in_array($url, $arrConfigDomainData)) {
                                if (strpos($url, 'm.') === false) {
                                    $arrAddDomainE[$domainFl] = '[' . $mallNm . '] ' . $url;
                                } else {
                                    $arrAddDomainM[$domainFl] = '[' . $mallNm . '] ' . $url;
                                }
                            }
                        }
                    }
                }
            }

            $this->setData('aCounterServiceAddDomainE', $arrAddDomainE);
            $this->setData('aCounterServiceAddDomainM', $arrAddDomainM);
            $this->getView()->setDefine('layout', 'layout_blank.php');


        } catch (AlertOnlyException $e) {
            throw new AlertOnlyException($e->getMessage());
        }
    }
}
