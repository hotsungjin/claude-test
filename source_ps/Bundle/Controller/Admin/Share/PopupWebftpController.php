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
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Controller\Admin\Share;

use Bundle\Component\File\Webftp;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\File\FileHandler;
use Framework\File\WebFtpSetting;
use Framework\Utility\FileUtils;
use Framework\Security\Token;
use Request;
use Exception;
use Framework\Debug\Exception\AlertReloadException;

/**
 * WebFTP
 *
 * @package Bundle\Controller\Admin\Share
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class PopupWebftpController extends \Controller\Admin\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
        try {
            $this->callMenu('design', 'ftp', 'imageBrowser');

            // 모듈 호출
            if(getenv('setting') == 'y'){
                $webftp = new WebFtpSetting();
            }
            else  {
                $webftp = new Webftp();
            }

            // Get file hash array and JSON encode it
            if (Request::get()->has('hash')) {
                $hashes = $webftp->getFileHash(Request::get()->get('hash'));
                $data = json_encode($hashes);

                // Return the data
                die($data);
            }

            if (Request::get()->has('zip')) {
                $dirArray = $webftp->zipDirectory(Request::get()->get('zip'));
            } else {
                // Initialize the directory array
                if (Request::get()->has('dir')) {
                    // 보안이슈 경로 파라미터에 폴더명이 아닌 상위 디렉토리 접근시
                    if (strpos(Request::get()->get('dir'), '../') !== false) {
                        $msg = 'alert("잘못된 경로로 접근하셨습니다.");';
                        $this->js($msg . 'location.replace("../share/popup_webftp.php"); ');
                    }

                    $dirArray = $webftp->listDirectory(Request::get()->get('dir'));
                    $currentDirectory = Request::get()->get('dir');
                } else {
                    $dirArray = $webftp->listDirectory('data');
                    $currentDirectory = 'data';
                }
            }
            $this->setData('currentDirectory', $currentDirectory);
            $this->setData('dirArray', $dirArray);
            $this->setData('webftp', $webftp);

            // 서버설정에 따른 최대 업로드 사이즈
            $this->setData('maxUploadSize', intval(FileUtils::getMaxUploadSize(true)));

            // CSRF 토큰 생성
            $this->setData('webFtpToken', Token::generate('webFtpToken'));

            // 템플릿 설정
            $this->getView()->setDefine('layout', 'layout_blank.php');
            $this->getView()->setDefine('layoutContent', Request::getDirectoryUri() . '/' . Request::getFileUri());

            // 스크립트 설정
            $this->addScript(
                [
                    'jquery/filer/jquery.filer.js',
                    'jquery/lightbox/ekko-lightbox.min.js',
                ]
            );

            // CSS 호출
            $this->addCss(
                [
                    'jquery/lightbox/ekko-lightbox.min.css',
                    'jquery/filer/jquery.filer.css',
                    'jquery/filer/themes/jquery.filer-dragdropbox-theme.css',
                ]
            );

        } catch (Exception $e) {
            throw new AlertOnlyException($e->getMessage());
        }
    }
}
