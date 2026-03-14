<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2022, NHN COMMERCE Corp.
 */


namespace Bundle\Controller\Admin\Marketing;

class PopupFacebookAdsManageController extends \Controller\Admin\Controller
{
    public function index()
    {
        $dbUrl = \App::load('\\Component\\Marketing\\DBUrl');
        $fbe2Data = $dbUrl->getConfig('facebookExtensionV2', 'config');

        $this->setData('shopName', $fbe2Data['solutionSettings']['shopName'] ?: 'NHNcommerce');
        $this->setData('shopNo', \Globals::get('gLicense.godosno'));
        $this->setData('mode', \Request::get()->get('mode'));

        $this->getView()->setDefine('layout', 'layout_blank.php');
    }
}
