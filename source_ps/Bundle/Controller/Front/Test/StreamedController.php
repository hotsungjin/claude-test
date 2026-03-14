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
 * 출력 버퍼를 즉시 비워 화면에 즉시 출력하도록 하는 예제
 *
 * @package Bundle\Controller\Front\Test
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class StreamedController extends \Core\Base\Controller\StreamedController
{

    /**
     * @inheritdoc
     */
    public function index()
    {
        for ($i=1; $i<=20; $i++) {
            echo ($i . ' 출력 <br />');

            // 2가지 flush를 모두 사용해야 출력버퍼가 비워진다.
            ob_flush();
            flush();
            sleep(1);
        }
    }
}
