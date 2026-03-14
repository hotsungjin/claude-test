<?php

namespace Bundle\Component\Storage;

use Framework\Http\Response;
use Encryptor;
use Framework\StaticProxy\Proxy\UserFilePath;

class AwsStorage extends \Framework\AwsS3\AwsS3
{
    CONST GIF_IMAGE = 1;
    CONST JPEG_IMAGE = 2;
    CONST PNG_IMAGE = 3;

    CONST ROTATE_LEFT_180 = 3;
    CONST ROTATE_RIGHT_90 = 6;
    CONST ROTATE_LEFT_90 = 8;

    function __construct($pathCode, array $configData)
    {
        $basePath = $this->getDiskPath($pathCode);
        $savePath = $basePath == '' ? trim($configData['savePath']) : trim($configData['savePath']) . "/$basePath";
        $this->basePath = trim($configData['savePath']) . DS . $basePath;
        parent::__construct($configData['ftpId'], Encryptor::decrypt($configData['ftpPw']), $configData['ftpHost'], trim($configData['ftpPath'], '/'), $savePath);
    }

    public function getPath($pathCodeDirName)
    {
        return $pathCodeDirName;
    }

    public function isFile(): bool
    {
        return false;
    }

    public function getHttpPath($filePath): string
    {
        $path = trim($filePath, '/');
        return "$this->baseUrl/$path";
    }

    public function getFilename()
    {
        return null;
    }

    public function getMountPath($fliePath)
    {
        return $this->basePath . DS . trim($fliePath, '/');
    }

    /**
     * getRealPath
     *
     * @param $filePath
     * @return string
     */
    public function getRealPath($filePath): string
    {
        return DS . $this->getMountPath($filePath);
    }

    /**
     * getDownloadPath
     *
     * @param $filePath
     * @return string
     */
    public function getDownloadPath($filePath): string
    {
        $tmpfname = tempnam("/tmp", "storage");
        $local = fopen($tmpfname, 'w');
        $realPath = $this->getHttpPath($filePath);
        $stream = file_get_contents($realPath);
        fwrite($local, $stream);
        return $tmpfname;
    }

    /**
     * download
     *
     * @param $filePath
     * @param $downloadFilename
     * @return string
     * @throws \Exception
     */
    final public function download($filePath, $downloadFilename)
    {
        try {
            // cdn url일 수가 있기 때문에 Obs getObjectSize 사용 안함
            $file_size = $this->getFileSize($this->getHttpPath($filePath));
            /** 아래 내용은 try-catch RuntimeException의 AlertBackException에 걸리지가 않아서 file_size 체크 후 throw 해주는 방법으로..*/
            if ($file_size > 0) {
                $response = new Response();
                $response->prepare(\App::getInstance('request'));
                $response->getHeaders()->set('Content-Disposition', sprintf('attachment; filename="%s"', $downloadFilename));
                $response->getHeaders()->set('Content-Length', $file_size);
                $response->sendHeader();
                $response->setContent(file_get_contents($this->getHttpPath($filePath)));
                $response->send();
            } else {
                throw new \Exception(__('다운로드 받을 파일이 존재하지 않습니다.'));
            }
        } catch (\RuntimeException $re) {
            throw new \Exception(__('다운로드 받을 파일이 존재하지 않습니다.'));
        } catch (\Exception $e) {
            throw new \Exception(__('다운로드 받을 파일이 존재하지 않습니다.'));
        }
    }

    public static function getFileSize(string $fileUrl): int
    {
        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_exec($ch);
        return curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    }


    public function createDir()
    {
        return false;
    }

    public function deleteDir()
    {
        return false;
    }

    public function delete($filePath): bool
    {
        try {
            $this->delObject($this->getMountPath($filePath));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * getSize
     *
     * @param $filePath
     * @return int
     */
    public function getSize($filePath): int
    {
        return parent::getObjectSize(trim($filePath, '/'));
    }

    public function isFileExists($filePath): bool
    {
        try {
            return $this->getSize($filePath) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listContents(): array
    {
        return [];
    }

    public function rename($oldFilePath, $newFilePath): bool
    {
        try {
            $this->copyObject(trim($newFilePath, '/'), trim($oldFilePath, '/'));
            $this->delObject(trim($oldFilePath, '/'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function upload($filePath, $savePath, $option = ['width' => 0, 'height' => 0, 'quality' => 'high', 'overWrite' => true])
    {
        \Logger::debug(__METHOD__, func_get_args());

        $option['overWrite'] = (!$option['overWrite']) ? true : $option['overWrite'];
        $option['quality'] = (!$option['quality']) ? 'high' : $option['quality'];

        if ($option['quality'] == 'high') {
            $quality = 100;
        } else if ($option['quality'] == 'low') {
            $quality = 80;
        } else {
            $quality = 80;
        }
        if ($option['width'] > 0) {
            $tmpfname = tempnam("/tmp", "board");
            copy($filePath, $tmpfname);
            $filePath = $tmpfname;
            $thumbBinaryData = $this->_convertThumbnail($filePath, $option['width'], $quality, $option['height']);
            $this->putObject($thumbBinaryData, trim($filePath, '/'));
        }

        $stream = fopen($filePath, 'r+');
        $fileSize = filesize($filePath);
        $binaryData = fread($stream, $fileSize);
        if ($this->isFileExists($savePath)) {
            if ($option['overWrite'] == false) {
                throw new \Exception(__('중복된 파일이 존재합니다.'));
            }

        }

        try {
            $this->putObject($binaryData, trim($savePath, '/'));
            $return = true;
        } catch (\Exception $e) {
            $return = false;
        }

        if ($tmpfname) {
            $this->delete($tmpfname);
        }
        fclose($stream);
        if ($return) {
            return $this->getHttpPath($savePath);
        }

        return false;
    }

    public function getBasePath(): string
    {
        return $this->baseUrl;
    }

    protected function _convertThumbnail($path, $width, $quality = 100, $height = null)
    {
        try {
            list($imageWidth, $imageHeight, $imageType) = @getimagesize($path);

            switch ($imageType) {
                case self::GIF_IMAGE:
                    $image = imagecreatefromgif($path);
                    break;
                case self::JPEG_IMAGE:
                    $image = imagecreatefromjpeg($path);
                    break;
                case self::PNG_IMAGE:
                    $image = imagecreatefrompng($path);
                    break;
                default:
                    return false;
            }

            if ($width) {
                $ratio = $imageWidth > $width ? $imageWidth / $width : 1;
                $saveImageSize['width'] = $width > 0 && $height > 0 ? $width : round($imageWidth / $ratio);
                $saveImageSize['height'] = $width > 0 && $height > 0 ? $height : round($imageHeight / $ratio);

                $dest = imagecreatetruecolor($saveImageSize['width'], $saveImageSize['height']);

                //png인 경우 백그라운드를 하얀색으로
                if ($imageType == 3) {
                    imagealphablending($dest, false);
                    imagesavealpha($dest, true);
                    $transparentindex = imagecolorallocatealpha($dest, 255, 255, 255, 127);
                    imagefill($dest, 0, 0, $transparentindex);
                }
                imagecopyresampled($dest, $image, 0, 0, 0, 0, $saveImageSize['width'], $saveImageSize['height'], $imageWidth, $imageHeight);
                $dest = $this->getRotateImage($path, $dest);

                // 이미지 생성
                ob_start();
                switch ($imageType) {
                    case self::GIF_IMAGE:
                        imagegif($dest);
                        break;
                    case self::JPEG_IMAGE:
                        imagejpeg($dest);
                        break;
                    case self::PNG_IMAGE:
                        imagepng($dest);
                        break;
                    default:
                        ob_end_clean();
                        return false;
                }
                $binary_data = ob_get_contents();
                ob_end_clean();

                return $binary_data;
            }
            return false;
        } catch (\Throwable $e) {
            \Logger::channel('imageResize')->error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    public function getRotate($imageFilePath): int
    {
        $exif = @exif_read_data($imageFilePath);

        switch ($exif['Orientation']){
            case self::ROTATE_LEFT_180 :
                return 180;
            case self::ROTATE_RIGHT_90 :
                return -90;
            case self::ROTATE_LEFT_90 :
                return 90;
        }
        return 0;
    }

    public function getRotateImage($imageFilePath, $dest){
        $rotate = $this->getRotate($imageFilePath);
        if($rotate != 0){
            return imagerotate($dest, $rotate, 0);
        }
        return $dest;
    }

    public function getDiskPath($pathCode): string
    {
        switch ($pathCode) {
            case Storage::PATH_CODE_GOODS:
                $path = $this->getPath('goods');
                break;
            case Storage::PATH_CODE_ADD_GOODS:
                $path = $this->getPath('add_goods');
                break;
            case Storage::PATH_CODE_GIFT:
                $path = $this->getPath('gift');
                break;
            case Storage::PATH_CODE_BOARD:
                $path = $this->getPath('board');
                break;
            case Storage::PATH_CODE_EDITOR:
                $path = $this->getPath('editor');
                break;
            case Storage::PATH_CODE_ETC:
                $path = $this->getPath('etc');
                break;
            case Storage::PATH_CODE_CATEGORY:
                $path = $this->getPath('category');
                break;
            case Storage::PATH_CODE_BRAND:
                $path = $this->getPath('brand');
                break;
            case Storage::PATH_CODE_DISPLAY:
                $path = $this->getPath('display');
                break;
            case Storage::PATH_CODE_MOBILE:
                $path = $this->getPath('mobile');
                break;
            case Storage::PATH_CODE_COUPON_IMAGE:
                $path = $this->getPath('coupon_image');
                break;
            case Storage::PATH_CODE_GOODS_ICON:
                $path = UserFilePath::icon('goods_icon');
                break;
            case Storage::PATH_CODE_COUPON_BG:
                $path = UserFilePath::icon('coupon_bg');
                break;
            case Storage::PATH_CODE_GROUP_ICON:
                $path = UserFilePath::icon('group_icon');
                break;
            case Storage::PATH_CODE_GROUP_IMAGE:
                $path = UserFilePath::icon('group_image');
                break;
            case Storage::PATH_CODE_FRONT_SKIN_CODI:
                // 배경이미지가 저장되는 경로
                $path = UserFilePath::frontSkin(\Globals::get('gSkin.frontSkinWork'), 'img', 'codi');
                break;
            case Storage::PATH_CODE_MOBILE_SKIN_CODI:
                // 배경이미지가 저장되는 경로
                $path = UserFilePath::mobileSkin(\Globals::get('gSkin.mobileSkinWork'), 'img', 'codi');
                break;
            case Storage::PATH_CODE_SCM:
                // 공급사 관리 - 공급사 대표이미지, 공급사 사업자 등록증 이미지
                $path = UserFilePath::data('scm');
                break;
            case Storage::PATH_CODE_CHECK:
                // 공급사 관리 - 공급사 대표이미지, 공급사 사업자 등록증 이미지
                $path = UserFilePath::data('check');
                break;
            case Storage::PATH_CODE_COMMON:
                // 프론트 - 공통 파일
                $path = UserFilePath::data('common');
                break;
            case Storage::PATH_CODE_ATTENDANCE_ICON_USER:
                $path = UserFilePath::data('attendance', 'upload');
                break;
            case Storage::PATH_CODE_GHOST_DEPOSITOR_BANNER:
                $path = UserFilePath::data('ghost_depositor', 'banner');
                break;
            case Storage::PATH_CODE_MULTI_POPUP:
                $path = UserFilePath::data('multi_popup');
                break;
            case Storage::PATH_CODE_POLL:
                $path = UserFilePath::data('poll');
                break;
            case Storage::PATH_CODE_COMMONIMG:
                $path = UserFilePath::data('commonimg');
                break;
            case Storage::PATH_CODE_PLUS_REIVEW :
                $path = UserFilePath::data('plus_review');
                break;
            case Storage::PATH_CODE_EVENT_GROUP :
                $path = UserFilePath::data('event_group');
                break;
            case Storage::PATH_CODE_EVENT_GROUP_TMP :
                $path = UserFilePath::data('event_group_tmp');
                break;
            case Storage::PATH_CODE_DEFAULT:
                $path = UserFilePath::data();
                break;
            case Storage::PATH_CODE_JOIN_EVENT:
                $path = UserFilePath::data('join_event');
                break;
            case '' :
                $path = '';
                break;
            default :
                throw new \Exception('NOT EXISTS PATH_CODE');
        }
        return $path;
    }
}
