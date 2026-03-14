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
namespace Bundle\Controller\Admin\Statistics;

use Bundle\Component\Godo\GodoACounterServerApi;
use Logger;
/**
 * 에이스카운터1 관리자 팝업 페이지
 */
class PopupAcounterManagerController extends \Controller\Admin\Controller
{
    /**
     * {@inheritDoc}
     */
    public function index()
    {
        $this->getView()->setDefine ('layout', 'layout_blank.php');
        $aCounterApi = new GodoACounterServerApi();
        $loginURI = $aCounterApi->connectACounterAPI('aCounterLogin');
        echo $loginURI;
        Logger::channel('acecounter')->info(__METHOD__ . ' ACOUNTER MODE: aCounterLogin, Manager Login :', [$loginURI]);
        exit;
    }
}
