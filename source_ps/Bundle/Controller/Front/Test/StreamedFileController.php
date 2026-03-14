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

/**
 * Class StreamedFileController
 *
 * @package Controller\Front\Test
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class StreamedFileController extends \Core\Base\Controller\Controller
{
    /**
     * @{inheritdoc}
     */
    public function index()
    {
//        echo '인덱스내에 스트링을 출력하면 해당 내용이 결과로 저장되며, 파일 확장자에 따라서 자동으로 Mime-Type이 설정됩니다.';
//        $this->streamedDownload('sample_test.txt');

        echo '<table><tr><th>표제목</th></tr><tr><th>표내용</th></tr></table>';
        $this->streamedDownload('sample_test.xls');
    }
}
