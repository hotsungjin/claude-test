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

namespace Bundle\Controller\Front\Test;

use Core\View\TemplateCacheConfig;

/**
 * 위젯 호출 방법
 *
 * @package Bundle\Controller\Front\Test
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class SubviewController extends \Controller\Front\Controller
{
    public function index()
    {
        $view = $this->getWidget(\Widget\Front\Mypage\MemberSummaryWidget::class);
        echo $view->render(new TemplateCacheConfig());
    }
}
