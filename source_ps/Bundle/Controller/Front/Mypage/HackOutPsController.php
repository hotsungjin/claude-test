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

namespace Bundle\Controller\Front\Mypage;

use Component\Godo\GodoPaycoServerApi;
use Component\Member\HackOut\HackOutService;
use Component\Member\Member;
use Component\Member\MemberSnsService;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Origin\Service\MyApp\MyappAuth;
use Request;

/**
 * Class HackOutPsController
 * @package Bundle\Controller\Front\Mypage
 * @author  yjwee
 */
class HackOutPsController extends \Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');
        $request = \App::getInstance('request');
        try {
            if ($session->has(Member::SESSION_MEMBER_LOGIN) == false) {
                throw new Exception(__('로그인이 필요합니다.'));
            }
            $hackOutService = new HackOutService();
            $memberSession = $session->get(Member::SESSION_MEMBER_LOGIN);

            $regIp = $request->getRemoteAddress();
            $hackOutService->userHackOutByParams($request->post()->all(), $memberSession['memNo'], $memberSession['memId'], $regIp);

            // IFDO 탈퇴 스크립트
            $ifdoScript = \App::Load('\\Component\\Service\\IfdoScript');
            $ifdoConfig = $ifdoScript->getIfdoConfig();
            $ifdoScriptCommon = $ifdoScript->getIfdoCommonScript();
            $ifdoScriptHackOut =  $ifdoScript->getIfdoHackOutScript($memberSession['memNo']);

            // 에이스카운터 환경변수 스크립트(회원탈퇴)
            $aCounterScript = \App::load('\\Component\\Nhn\\ACounterScript');
            $aCounterUseFl = $aCounterScript->getACounterUseCheck();
            if($aCounterUseFl == 'y') {
                $controller = \App::getController();
                $dir = $controller->getRootDirecotory();

                if($dir == 'front'){
                    $aCounterHackOut =  $aCounterScript->getHackOutScript();
                    $aCounterCommonScript = $aCounterScript->getCommonScript();
                    $returnScript = $aCounterHackOut . $aCounterCommonScript;
                }else if($dir == 'mobile'){
                    $aCounterHackMobileOut =  $aCounterScript->getHackOutMobileScript();
                    $aCounterCommonMobileScript = $aCounterScript->getCommonMobileScript();
                    $returnScript = $aCounterHackMobileOut . $aCounterCommonMobileScript;
                }

            }

            if (Request::isAjax()) {
                $this->json(__('탈퇴가 정상적으로 처리되었습니다.'));
            } else {
                $returnStr = '';

                if($ifdoConfig) {
                    if(empty($returnStr) == false){
                        $returnStr .= $ifdoScriptHackOut . $ifdoScriptCommon;
                    }else{
                        $returnStr = $ifdoScriptHackOut . $ifdoScriptCommon;
                    }
                }

                // 에이스카운터 환경변수 스크립트(회원탈퇴)
                if($aCounterUseFl){
                    if(empty($returnStr) === false){
                        $returnStr .= $returnScript;
                    }else{
                        $returnStr = $returnScript;
                    }
                }

                $returnStr .= '<script>alert("' . __("탈퇴가 정상적으로 처리되었습니다.") . '"); parent.location.href="/"</script>';
                echo $returnStr;
                exit;
            }
        } catch (Exception $e) {
            if ($request->isAjax()) {
                $this->json($this->exceptionToArray($e));
            } else {
                throw new AlertOnlyException($e->getMessage());
            }
        }
    }
}
