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

use Component\Page\Page;
use Component\UserPage;
use Logger;
use Framework\Utility\HttpUtils;

class SampleController extends \Controller\Front\Controller
{
    /**
     *
     * Description
     */
    public function index()
    {
        $data = array(
            "data1" => "샘플데이터 [1] 입니다.",
            "data2" => "샘플데이터 [2] 입니다.",
            "data3" => "샘플데이터 [3] 입니다.",
        );
        $this->setData($data);

        $page = new Page(10, 100, 100, 10, 10);
        $userPage = new UserPage();

        $this->setData('page', $page->getPage());
        $this->setData('userPage', $userPage->getPage());

        Logger::debug($page->getPage());
        Logger::debug($userPage->getPage());
    }
}
