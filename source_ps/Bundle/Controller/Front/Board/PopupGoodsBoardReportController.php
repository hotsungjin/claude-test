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

namespace Bundle\Controller\Front\Board;

use Framework\Debug\Exception\AlertRedirectCloseException;

class PopupGoodsBoardReportController extends \Controller\Front\Controller
{
    public function index()
    {
        $data = \Request::get()->all();
        if (gd_is_login() === false) {
            throw new AlertRedirectCloseException(__('로그인이 필요한 서비스입니다.'),null, null, '/member/login.php?returnUrl='.urlencode($data['returnUrl']), 'opener');
        }

        $memId = \Session::get('member.memId');

        $this->setData('req', $data);
        $this->setData('memId', $memId);

        $this->getView()->setDefine('header', 'outline/_share_header.html');
    }
}
