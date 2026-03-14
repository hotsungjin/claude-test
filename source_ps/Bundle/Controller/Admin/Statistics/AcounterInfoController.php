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
use Bundle\Component\Godo\GodoACounterServerApi;
use Globals;
use Framework\Utility\GodoUtils;
use Component\Godo\GodoNhnServerApi;
use Component\Nhn\ACounterScript;
use Framework\Debug\Exception\AlertOnlyException;
use Component\Mall\Mall;
use Framework\Utility\HttpUtils;

/**
 * Class 통계-에이스카운터-에이스카운터 신청/관리
 * @package Bundle\Controller\Admin\Statistics
 * @author  choisueun
 */
class AcounterInfoController extends \Controller\Admin\Controller
{
    public function Index() {
        // --- 메뉴 설정
        $this->callMenu('statistics', 'acounter', 'info');

        try {
            $policy = \App::load('\\Component\\Policy\\Policy');
            $aCounterConfDefault = $policy->getACounterSettingByDefault();
            $aCounterConfGlobals = $policy->getACounterServiceListByGlobals();
            if(empty($aCounterConfGlobals)){
                $aCounterConf = $aCounterConfDefault;
            }else{
                $aCounterConf = array_merge($aCounterConfGlobals, $aCounterConfDefault);
            }

            if(empty($aCounterConf) === false){
                $mode = 'aCounterModify';

                // 상점 번호
                $sno = Globals::get('gLicense.godosno');
                $godoSno = $sno;

                // 서비스 추가된 도메인 리스트
                foreach($aCounterConf as $url => $val) {
                    if (empty($val['aCounterSort'])) {
                        $aCounterConf[$url]['aCounterSort'] = 1;
                    }
                    $sortData[$url] = $aCounterConf[$url]['aCounterSort'];
                }
                array_multisort($sortData, SORT_DESC, $aCounterConf);

                $defaultData = array_slice($aCounterConf, 0, 1);
                foreach($defaultData as $defaultUrl => $defaultVal){
                    // 서비스명
                    if($defaultVal['aCounterKind'] == 'ecom'){    // 이커머스
                        $defaultKind = '이커머스';
                    }else if($defaultVal['aCounterKind'] == 'mweb'){  // 모바일웹
                        $defaultKind = '모바일웹';
                    }

                    $gCode = $defaultVal['aCounterGCode'];
                    $expDt = $defaultVal['aCounterPeriod'];
                    $checked['aCounterUseFl'][$defaultVal['aCounterUseFl']] =
                    $checked['aCounterMemIdAnalyticsFl'][gd_isset($defaultVal['aCounterMemIdAnalyticsFl'], 0)] = 'checked="checked"';
                }

                foreach($aCounterConf as $reConfUrl => $reConfVal){
                    // 서비스명
                    if($reConfVal['aCounterKind'] == 'ecom'){    // 이커머스
                        $kind = '이커머스';
                    } else if($reConfVal['aCounterKind'] == 'mweb'){  // 모바일웹
                        $kind = '모바일웹';
                    }

                    if($reConfVal['aCounterDomainFl'] == 'us'){
                        $mallNm = '영문몰';
                    } else if($reConfVal['aCounterDomainFl'] == 'cn'){
                        $mallNm = '중문몰';
                    } else if($reConfVal['aCounterDomainFl'] == 'jp'){
                        $mallNm = '일문몰';
                    } else if($reConfVal['aCounterDomainFl'] == 'kr' || empty($reConfVal['aCounterDomainFl'])) {
                        $mallNm = '기준몰';
                    }
                    $serviceList[$reConfUrl] = '[' . $kind . '] [' . $mallNm . '] ' . $reConfVal['aCounterUrl'];
                }
            }else{
                $mode = 'aCounterRegist';
                $aCounterApi = new GodoACounterServerApi();
                $terms = $aCounterApi->getAgree('aCounterTermUrl');
                $privateTerms = $aCounterApi->getAgree('aCounterPrivateUrl');

                $this->setData('terms', $terms);
                $this->setData('privateTerms', $privateTerms);
            }

            // 메일도메인
            $emailDomain = gd_array_change_key_value(gd_code('01004'));
            $emailDomain = array_merge(['self' => __('직접입력')], $emailDomain);

            $this->setData('godoSno', $godoSno);

            $this->setData('emailDomain', $emailDomain);
            $this->setData('checked',gd_isset($checked));
            $this->setData('mode', $mode);
            $this->setData('kind',$defaultKind);
            $this->setData('gCode',$gCode);
            $this->setData('expDt',$expDt);
            $this->setData('aCounterServiceDomain', $serviceList);

        } catch(AlertOnlyException $e) {
            throw new AlertOnlyException($e->getMessage());
        }
    }
}
