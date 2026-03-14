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

use Bundle\Component\Nhn\ACounterScript;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Globals;

class AcounterManagerController extends \Controller\Admin\Controller
{
    public function Index() {
        // --- 메뉴 설정
        $this->callMenu('statistics', 'acounter', 'manager');

        try {
            $aCounterScript = new ACounterScript();
            $aCounterUse = $aCounterScript->getAcounterUseCheckByNull();
            if($aCounterUse == 'y') {
                // 상점 번호
                $sno = Globals::get('gLicense.godosno');
                $godoSno = $sno;
                echo '<script> window.open("./popupAcounterManager.php", "_blank");</script>';
            }else {
                throw new AlertRedirectException(__('에이스카운터 서비스를 신청하셔야 사용가능합니다.'), null, null, '../statistics/acounter_info.php');
            }
            $this->setData('godoSno', $godoSno);

        } catch(AlertOnlyException $e) {
            throw new AlertOnlyException($e->getMessage());
        }
    }
}
