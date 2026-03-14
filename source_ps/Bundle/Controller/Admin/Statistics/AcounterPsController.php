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

use Bundle\Component\Godo\GodoACounterServerApi;
use Bundle\Component\Nhn\ACounterScript;
use Request;
use App;
use Component\Godo\GodoNhnServerApi;
use Exception;
use Globals;
use Logger;
use Bundle\Component\Mall\Mall;

/**
 * Class 통계-에이스카운터-에이스카운터 신청/관리
 * @package Bundle\Controller\Admin\Statistics
 * @author  choisueun
 */
class AcounterPsController extends \Controller\Admin\Controller {

    public function Index() {

        try {
            $requestPostParams = Request::post()->all();
            $mode = $requestPostParams['mode'];

            $aCounterApi = new GodoACounterServerApi();
            switch($mode) {
                // 에이스카운터1 계정발급(서비스신청)
                case 'aCounterRegist':
                    if(empty($requestPostParams['customerNm']) === true){
                        $this->js('alert("가입자명을 입력해주세요."); location.replace("../statistics/acounter_info.php"); ');
                        return false;
                    }
                    if(empty($requestPostParams['customerPhone']) === true){
                        $this->js('alert("휴대폰번호를 입력해주세요."); location.replace("../statistics/acounter_info.php"); ');
                        return false;
                    }
                    if(empty($requestPostParams['customerEmail']) === true){
                        $this->js('alert("E-Mail을 입력해주세요."); location.replace("../statistics/acounter_info.php"); ');
                        return false;
                    }

                    // 도메인
                    $mall = new Mall();
                    $this->currentDomain = $mall->currentMallByPc();
                    $requestPostParams['defaultDomain'] = $this->currentDomain;

                    // 중계서버 통신
                    $response= $aCounterApi->connectACounterAPI('aCounterRegist', $requestPostParams);

                    if($response) {
                        if($response['result'] == 'fail'){
                            $msg = 'alert("가입이 실패되었습니다. 관리자에게 문의해주세요.(Error Code:' . $response['code']  . ')");';
                            $this->js($msg . 'location.replace("../statistics/acounter_info.php"); ');
                        }else{
                            $msg = 'alert("에이스카운터 신청이 완료되었습니다.");';
                            $this->js($msg . 'location.replace("../statistics/acounter_info.php"); ');
                        }
                    } else {
                        $this->js('alert("가입이 실패되었습니다. 관리자에게 문의해주세요.(Error Code:' . $response['code']  . ')"); location.replace("../statistics/acounter_info.php"); ');
                    }
                    break;

                // 에이스카운터1 신청/관리 수정 (에이스카운터1 사용여부 중계서버 동기화)
                case 'aCounterModify':
                    // aCounterDomainFl 값이 없는 경우 기본인 kr 로 설정
                    if (empty($requestPostParams['aCounterDomainFl'])) $requestPostParams['aCounterDomainFl'] = 'kr';

                    // 에이스카운터1 정보
                    $policy = \App::load('\\Component\\Policy\\Policy');
                    if ($requestPostParams['domainFl'] == 'kr') {
                        $aCounterConf = $policy->getACounterSettingByDefault();
                    } else {
                        $aCounterConf = $policy->getACounterServiceListByGlobals();
                    }

                    if (empty($aCounterConf) === false) {
                        // 설정 도메인
                        $aCounterDomain = gd_isset($requestPostParams['aCounterDomain'], $requestPostParams['aCounterServiceAdd']);

                        // 동기화할 데이터가 있는지 체크
                        $checkConf = false;
                        foreach($aCounterConf as $domain => $val){
                            // aCounterDomainFl 값이 없는 경우 기본인 kr 로 설정
                            if (empty($val['aCounterDomainFl'])) $val['aCounterDomainFl'] = 'kr';

                            // 체크
                            if ($domain == $aCounterDomain && $val['aCounterDomainFl'] == $requestPostParams['domainFl']) {
                                // 사용 여부가 변경이 되었는지 체크
                                /*if ($val['aCounterUseFl'] != $requestPostParams['aCounterFl']) {
                                    $checkConf = true;
                                }*/

                                // 사용 여부가 변경이 되었는지 체크없이 동기화를 위해 무조건 처리함
                                $checkConf = true;
                            }
                        }

                        if ($checkConf === true) {
                            // 통신 정보 세팅
                            $requestData['aCounterGCode'] = $requestPostParams['aCounterGCode'];
                            $requestData['aCounterUseFl'] =$requestPostParams['aCounterFl'];

                            // 설정값 변경 시, 사용여부 중계서버 동기화(PUT메소드 확인 해봐야함)
                            $res = $aCounterApi->connectACounterAPI('aCounterUseStatus', $requestData);

                            if($res['result'] == 'success'){
                                // 사용여부 처리
                                $requestPostParams['aCounterUseFl'] = $requestPostParams['aCounterFl'];

                                // 저장 데이터 처리
                                $saveKey = ['aCounterUseFl', 'aCounterMemIdAnalyticsFl'];
                                foreach ($saveKey as $sVal) {
                                    $saveData[$sVal] = $requestPostParams[$sVal];
                                }

                                // 설정값 저장
                                $result = $policy->saveACounterModify($aCounterDomain, $saveData, $requestPostParams['domainFl']);
                                if ($result === true){
                                    $msg = 'alert("상태가 변경되었습니다.");';
                                } else {
                                    $msg = 'alert("오류로 변경되지 않았습니다.");';
                                }
                            }else{
                                $msg = 'alert("통신오류로 변경되지 않았습니다.");';
                            }
                        } else {
                            $msg = 'alert("변경 할 정보가 없습니다.");';
                        }
                    } else {
                        $msg = 'alert("설정된 정보가 없습니다.");';
                    }

                    $this->js($msg . 'location.replace("../statistics/acounter_info.php"); ');
                    break;

                // 에이스카운터1 분석 사이트 관리
                case 'aCounterLogin':
                    $result = $aCounterApi->connectACounterAPI($mode);
                    echo str_replace("'", '"', $result);
                    break;

                // 에이스카운터1 신청/관리 서비스 신청 도메인 셀렉트 박스
                case 'aCounterSelect':
                    $policy = \App::load('\\Component\\Policy\\Policy');
                    $aCounterConfDefault = $policy->getACounterSettingByDefault();
                    $aCounterConfGlobals = $policy->getACounterServiceListByGlobals();
                    if(empty($aCounterConfGlobals)){
                        $aCounterConf = $aCounterConfDefault;
                    }else{
                        $aCounterConf = array_merge($aCounterConfDefault, $aCounterConfGlobals);
                    }
                    foreach($aCounterConf as $confDomain => $val){
                        if($confDomain == $requestPostParams['domain']){
                            $returnData['data']['kind'] = $val['aCounterKind'];
                            $returnData['data']['gCode'] = $val['aCounterGCode'];
                            $returnData['data']['expDt'] = $val['aCounterPeriod'];
                            $returnData['data']['domain'] = $confDomain;
                            $returnData['data']['useFl'] = $val['aCounterUseFl'];
                            $returnData['data']['memIdAnalyticsFl'] = gd_isset($val['aCounterMemIdAnalyticsFl'], '0');
                        }
                    }
                    echo json_encode($returnData);
                    break;

                // 에이스카운터1 서비스 추가
                case 'aCounterServiceAdd':
                    $policy = \App::load('\\Component\\Policy\\Policy');
                    $aCounterConfDefault = $policy->getACounterSettingByDefault();
                    $aCounterConfGlobals = $policy->getACounterServiceListByGlobals();
                    if(empty($aCounterConfGlobals)){
                        $aCounterConf = $aCounterConfDefault;
                    }else{
                        $aCounterConf = array_merge($aCounterConfDefault, $aCounterConfGlobals);
                    }
                    $requestData['cnt'] = count($aCounterConf);
                    $requestData['domainFl'] = $requestPostParams['domainFl'];

                    if($requestPostParams['aCounterKind'] == 'e'){
                        $requestData['aCounterKind'] = 'ecom';
                        $requestData['aCounterServiceAddDomain'] = $requestPostParams['aCounterServiceAddE'];
                    }else{
                        $requestData['aCounterKind'] = 'mweb';
                        $requestData['aCounterServiceAddDomain'] = $requestPostParams['aCounterServiceAddM'];
                    }
                    $res = $aCounterApi->connectACounterAPI('aCounterServiceAdd', $requestData);
                    if($res) {
                        if($res['result'] == 'fail'){
                            $msg = '통신오류로 추가되지 않았습니다.(Error Code:' . $res['code']  . ')';
                        }else{
                            $msg = '서비스 추가되었습니다.';
                        }
                    } else {
                        $msg = '통신오류로 추가되지 않았습니다.(Error Code:' . $res['code']  . ')';
                    }
                    $this->layer(__($msg), "parent.opener.location.reload();parent.window.close();");
                    break;

                // 에이스카운터1 분석 대상 도메인 설정
                case 'aCounterAddDomain':
                    $policy = \App::load('\\Component\\Policy\\Policy');
                    $aCounterConfDefault = $policy->getACounterSettingByDefault();
                    $aCounterConfGlobals = $policy->getACounterServiceListByGlobals();
                    if(empty($aCounterConfGlobals)){
                        $aCounterConf = $aCounterConfDefault;
                    }else{
                        $aCounterConf = array_merge($aCounterConfDefault, $aCounterConfGlobals);
                    }

                    $requestData['aCounterConf'] = $aCounterConf[$requestPostParams['acRequestDomain']];
                    $requestData['acRequestDomain'] = $requestPostParams['acRequestDomain'];
                    $requestData['acAddDomain'] = implode('|', $requestPostParams['acAddDomain']);

                    $res = $aCounterApi->connectACounterSend('aCounterAddDomain', $requestData);

                    if ($res['result'] == 'fail') {
                        $msg = $res['msg'];
                    } else if ($res['result'] == 'success') {
                        // 저장할값 설정 ( 분석 대상 도메인 추가, 구분자는 쉼표로 )
                        $saveData = [];
                        $saveData['aCounterAddDomain'] = implode(',', $requestPostParams['acAddDomain']);

                        // 설정값 저장
                        $policy = \App::load('\\Component\\Policy\\Policy');
                        $result = $policy->saveACounterInfo($mode, $requestData['acRequestDomain'], $saveData, null, $requestPostParams['acDomainFl']);
                        if($result){
                            $msg = $res['msg'];
                        } else {
                            $msg = '도메인 추가에 실패하였습니다. 1:1로 문의하여주시기 바랍니다. (에러코드 : ACEDOMAINGD)'; // 저장에 실패 한경우
                        }
                    } else {
                        $msg = '도메인 추가에 실패하였습니다. 1:1로 문의하여주시기 바랍니다. (에러코드 : ACEDOMAINXX)'; // 리턴 코드가 없거나 설정된 코드와 상이한경우
                    }

                    // 결과 전송
                    $this->json(
                        [
                            'result'  => 'ok',
                            'message' => __($msg),
                        ]
                    );
                    break;
            }

        } catch(Exception $e) {

        }
    }
}
