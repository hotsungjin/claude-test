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

use App;
use Component\Godo\GodoCenterServerApi;
use Exception;
use UserFilePath;
use Request;
use Framework\Utility\StringUtils;

/**
 * 도로명 주소 검색
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class PostcodeSearchController extends \Controller\Admin\Controller
{

    /**
     * index
     *
     */
    public function index()
    {
        try {
            $request = StringUtils::xssArrayCleanAndEscape(Request::get()->toArray());
            $idCodeStr = $request['zoneCodeID'] . STR_DIVISION . $request['addrID'] . STR_DIVISION . $request['zipCodeID'];

            $gdShare = UserFilePath::adminSkin('gd_share', 'img', 'share')->www();

            $bootHtmlPath = App::getBasePath() . '/postcode/postcode_search.html';
            if (is_file($bootHtmlPath)) {
                $top = $request['top'];
                gd_isset($top, 0);
                ob_start();
                require_once $bootHtmlPath;
                $content = ob_get_contents();
                $content = str_replace('{gubun}', $idCodeStr, $content);
                $content = str_replace('{gd_share}', $gdShare, $content);
                $content = str_replace('{top}', $top, $content);
                if (Request::isMobileDevice() === true) {
                    $windowTopScript = 'self.close(); window.top.scrollTo({top:'.$top.'});';
                    $content = str_replace('self.close();', $windowTopScript, $content);
                }

                ob_end_clean();
                echo $content;

                if (Request::isMobileDevice() === true) {
                    echo '<script type="text/javascript">' . PHP_EOL;
                    echo '$(document).on("click","#close",function(){
                            window.top.layerSearch.removeChild(window.top.layerSearch.firstChild);
                            window.scrollTo({top:'.$top.'});
                          });';
                    echo '</script>' . PHP_EOL;
                }
            }
            exit;
        }
        catch (Exception $e) {
            throw $e;
        }
    }
}
