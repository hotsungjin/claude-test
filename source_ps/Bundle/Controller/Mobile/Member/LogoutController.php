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

namespace Bundle\Controller\Mobile\Member;

use Bundle\Component\Member\Util\MemberUtil;
use Origin\Service\MyApp\MyappBridge;

/**
 * Class 회원 로그아웃
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class LogoutController extends \Controller\Mobile\Controller
{
    /**
     * @inheritdoc
     */
    public function index()
    {
        $request = \App::getInstance('request');
        if ($request->isFromMyapp()) {
            MemberUtil::logoutWithCookie();
            $myappBridge = \App::load(MyappBridge::class);
            $this->js($myappBridge->logout());
            exit;
        }

        /** @var \Bundle\Controller\Front\Member\LogoutController $front */
        $front = \App::load('\\Controller\\Front\\Member\\LogoutController');
        $front->index();
        $this->setData($front->getData());
    }
}
