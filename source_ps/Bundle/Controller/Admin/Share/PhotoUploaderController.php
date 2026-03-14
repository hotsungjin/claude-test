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
namespace Bundle\Controller\Admin\Share;

/**
 * Class 관리자 스마트 에디터 첨부파일 업로드
 * @package Bundle\Controller\Admin\Share
 */
class PhotoUploaderController extends \Controller\Admin\Controller
{
    /**
     * index
     *
     */
    public function index()
    {
        $this->getView()->setDefine('layout', 'layout_blank_noiframe.php');
    }
}