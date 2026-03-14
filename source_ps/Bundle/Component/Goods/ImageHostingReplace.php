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

namespace Bundle\Component\Goods;

use Component\Storage\Storage;
use Component\File\StorageHandler;
use Component\Database\DBTableField;
use Framework\Utility\HttpUtils;
use Framework\Utility\SkinUtils;
use Globals;
use FileHandler;
use Framework\StaticProxy\Proxy\UserFilePath;
use LogHandler;
use Request;
use Session;

/**
 * 이미지 호스팅 일괄전환
 * @author jwno
 */
class ImageHostingReplace
{
    // 디비 접속
    protected $db;

    protected $imageHostingReplaceDir = 'gd5replace';

    /**
     * 생성자
     *
     */
    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }

    /**
     * 넘어온 문자열내의 http로 시작하지않는 이미지경로의 카운트를 리턴
     *
     * @param string $sString 상품의 상세설명
     * @return array 상세설명
     */
    public function getImageReplaceCount($sString)
    {
        $cnt = array('tot' => 0, 'in' => 0);

        if (is_string($sString) === true) {
            $aSplit = $this->_split($sString);
        } else {
            return $cnt;
        }

        $iCnt = count($aSplit);
        for ($i=1; $i < $iCnt; $i += 2) {
            $cnt['tot']++;
            if (preg_match('@^http:\/\/@ix', $aSplit[$i]));
            else if (preg_match('@^https:\/\/@ix', $aSplit[$i]));
            else {
                if (substr($aSplit[$i],0,1) == '/') $imgPath = Request::server()->get('DOCUMENT_ROOT') . $aSplit[$i];
                if (file_exists($imgPath) === true) {
                    $chkimg = getimagesize($imgPath);
                    if ($chkimg[2] != 0){
                        $cnt['in']++;
                    }
                }
            }
        }

        return $cnt;
    }

    private function _split($sString)
    {
        $Ext = '(?<=src\=")(?:[^"])*[^"](?=")'.
            "|(?<=src\=')(?:[^'])*[^'](?=')".
            '|(?<=src\=\\\\")(?:[^"])*[^"](?=\\\\")'.
            "|(?<=src\=\\\\')(?:[^'])*[^'](?=\\\\')";
        $sPattern = '@('. $Ext .')@ix';
        $aSplit = preg_split($sPattern, $sString, -1, PREG_SPLIT_DELIM_CAPTURE);
        return $aSplit;
    }

    /**
     * 단순 ftp 체크
     *
     * @param string $aPost ftp정보
     * @return array 상세설명
     */
    public function checkUseStorage($aPost)
    {
        Storage::checkUseStorage($aPost);
    }

    /**
     * 이미지 호스팅 일괄전환
     *
     * @param $uploadInfo
     * @return int
     * @throws \Exception
     */
    public function uploadImages(array $uploadInfo): int
    {
        // FTP 서버 접속 및 로그인
        $ftpConnection = $this->setFtpConnection($uploadInfo);

        // 상점 기본 도메인 가져오기
        $baseDomain = HttpUtils::remoteGet('http://gongji.godo.co.kr/userinterface/get.basicdomain.php?sno=' . Globals::get('gLicense.godosno'));
        $mallName = explode('.', $baseDomain)[0];
        $goodsAdmin = \App::load('\\Component\\Goods\\GoodsAdmin');

        $uploadedGoodsImageCount = 0;
        $arrDescriptionType = ['goodsDescription', 'goodsDescriptionMobile'];
        $pattern = '/<img src="([^"]+)"/';
        foreach ($uploadInfo['goodsNo'] as $goodsNo) {
            $goodsInfo = $goodsAdmin->getGoodsInfo($goodsNo, 'g.goodsNm, g.goodsDescription, g.goodsDescriptionMobile');

            foreach ($arrDescriptionType as $descriptionType) {
                preg_match_all($pattern, $goodsInfo[$descriptionType], $matches);
                $arrImgSrc = $matches[1]; // img src 값들만 추출하여 배열로 저장

                foreach ($arrImgSrc as $imgSrc) {
                    // FTP 서버에 업로드하고자 하는 이미지 유효성 체크
                    if (!$this->validateImgSrc($imgSrc)) {
                        continue;
                    }

                    // FTP 서버에 업로드할 이미지의 디렉토리 생성
                    $localImageSrc = (substr($imgSrc, 0, 1) != '/') ? DS . $imgSrc : $imgSrc;
                    $localImageDir = dirname($localImageSrc);
                    $originImageDir = DS . $this->imageHostingReplaceDir . DS . $mallName . $localImageDir;
                    $this->mkdirFtp($ftpConnection, $originImageDir);

                    // FTP 서버에 이미지 업로드
                    $originImagePath = DS . $this->imageHostingReplaceDir . DS . $mallName . $localImageSrc;
                    $localImagePath = str_replace("/data/", UserFilePath::data() . DS, $localImageSrc);
                    \Logger::channel('goods')->info("START IMAGE UPLOAD ", [$originImagePath, $localImagePath]);
                    $ftpPutResult = ftp_put($ftpConnection, $originImagePath, $localImagePath, FTP_BINARY);

                    // FTP 업로드된 이미지 주소로 img src 값 변경
                    if ($ftpPutResult) {
                        $originImageSrc = $uploadInfo['protocol'] . '://' . $uploadInfo['httpUrl'] . DS . $this->imageHostingReplaceDir . DS . $mallName . $localImageSrc;
                        $replaceImgSrc = $originImageSrc . '" godoOld="' . $localImageSrc;
                        $goodsInfo[$descriptionType] = str_replace("\"{$imgSrc}\"", "\"{$replaceImgSrc}\"", $goodsInfo[$descriptionType]);
                        $goodsInfo[$descriptionType] = str_replace("'{$imgSrc}'", "'{$replaceImgSrc}'", $goodsInfo[$descriptionType]);
                        \Logger::channel('goods')->info("REPLACE img src ", [$replaceImgSrc]);
                    } else {
                        throw new \Exception('Fail to upload image');
                    }
                }
                \Logger::channel('goods')->info("UPLOADED GOODS DESCRIPTION ", [$goodsNo]);
                $goodsAdmin->setGoodsDescription($goodsInfo[$descriptionType], $goodsNo, $descriptionType);
            }
            $uploadedGoodsImageCount++;
            \Logger::channel('goods')->info("UPLOADED GOODS IMAGE COUNT ", [$uploadedGoodsImageCount]);
        }
        return $uploadedGoodsImageCount;
    }

    /**
     * FTP 접속 및 세팅
     */
    private function setFtpConnection(array $uploadInfo)
    {
        $ftpConnection = ftp_connect($uploadInfo['ftpHost']);
        \Logger::channel('goods')->info('RESULT CONNECT FTP - ', [$uploadInfo['ftpHost']]);
        if ($ftpConnection) {
            $ftpLoginResult = ftp_login($ftpConnection, $uploadInfo['ftpId'], \Encryptor::decrypt($uploadInfo['ftpPw']));
            \Logger::channel('goods')->info('RESULT FTP LOGIN', [$ftpLoginResult]);
        }
        if ($uploadInfo['passive'] == 'y') {
            ftp_pasv($ftpConnection, true); // passive 모드로 전환
        }
        ftp_set_option($ftpConnection, FTP_TIMEOUT_SEC, 15);
        return $ftpConnection;
    }

    /**
     * FTP 서버 내 이미지 업로드할 디렉토리 생성
     * @param $ftpConnection
     * @param string $originImageDir
     * @return void
     */
    private function mkdirFtp($ftpConnection, string $originImageDir)
    {
        $result = 'SKIP';
        if (!ftp_nlist($ftpConnection, $originImageDir)) {
            \Logger::channel('goods')->info('START FTP MKDIR - '. $originImageDir);
            $this->mkdirForRootDir($ftpConnection, $originImageDir);
            $result = 'SUCCESS';
        }
        \Logger::channel('goods')->info('RESULT FTP MKDIR', [$result]);
    }

    /**
     * 이미지 호스팅 루트 디렉토리부터 만들기
     *
     * @param $ftpConnection
     * @param string $originImageDir
     * @return void
     * @throws \Exception
     */
    private function mkdirForRootDir($ftpConnection, string $originImageDir) {
        $directoryList = explode('/', $originImageDir);
        $targetDir = '';

        foreach ($directoryList as $directory) {
            $targetDir .= '/'.$directory;
            if (!ftp_nlist($ftpConnection, $targetDir)) {
                $result = ftp_mkdir($ftpConnection, $targetDir);
                \Logger::channel('goods')->info('TRY FTP MKDIR : '.($result ? 'SUCCESS' : 'FAIL').' - '. $targetDir);

                if (!$result) {
                    throw new \Exception('Fail make Directory');
                }
            }
        }
    }

    /**
     * 로컬 내 이미지 경로 유효성 검사
     */
    private function validateImgSrc(string $imgSrc): bool
    {
        if (preg_match('@^http:\/\/@ix', $imgSrc) || preg_match('@^https:\/\/@ix', $imgSrc)) {
            return false;
        }

        $localRootImgSrc = (substr($imgSrc, 0, 1) == '/') ? Request::server()->get('DOCUMENT_ROOT') . $imgSrc : '';
        if (file_exists($localRootImgSrc) !== true) {
            return false;
        }

        $imageSize = getimagesize($localRootImgSrc)[2];
        if ($imageSize == 0) {
            return false;
        }

        return true;
    }

    /**
     * 단순 ftp 체크
     *
     * @param string $aPost ftp정보
     * @return array 상세설명
     */
    public function doReplace($aPost)
    {
        // 이미지 호스팅에 들어갈 폴더명 생성 $this->imageHostingReplaceDir/domain/time()상품번호
        $sno = Globals::get('gLicense.godosno');
        $freedomain_result = HttpUtils::remoteGet('http://gongji.godo.co.kr/userinterface/get.basicdomain.php?sno=' . $sno);
        $sNewDir1 = explode('.', $freedomain_result);
        $oStorage = Storage::customDisk($aPost, true);
        $goods = \App::load('\\Component\\Goods\\GoodsAdmin');

        // 처리카운트
        $resultCount = 0;

        $arrDesc = ['goodsDescription', 'goodsDescriptionMobile'];
        foreach ($aPost['goodsNo'] as $val) {
            //$oStorage->createDir('/' . $this->imageHostingReplaceDir . DS . $sNewDir1[0]);
            //$oStorage->createDir('/' . $this->imageHostingReplaceDir . DS . $sNewDir1[0] . DS . $sNewDir2 . $val);
            $tmpData = $goods->getGoodsInfo($val, 'g.goodsNm, g.goodsDescription, g.goodsDescriptionMobile'); //  기존 상품 정보
            foreach($arrDesc as $column) {
                $aSplit = $this->_split($tmpData[$column]);

                $iCnt = count($aSplit);
                for ($i = 1; $i < $iCnt; $i += 2) {
                    if (preg_match('@^http:\/\/@ix', $aSplit[$i])) ;
                    else if (preg_match('@^https:\/\/@ix', $aSplit[$i])) ;
                    else {
                        if (substr($aSplit[$i], 0, 1) == '/') $imgPath = Request::server()->get('DOCUMENT_ROOT') . $aSplit[$i];
                        if (file_exists($imgPath) === true) {
                            $chkimg = getimagesize($imgPath);
                            if ($chkimg[2] != 0) {
                                $orgImage = $aSplit[$i];
                                if (substr($orgImage, 0, 1) != '/') {
                                    $orgImage = '/' . $orgImage;
                                }
                                $explodeImage = explode('/', $orgImage);
                                array_pop($explodeImage);
                                $ImageDir = implode('/', $explodeImage);
                                $oStorage->createDir('/' . $this->imageHostingReplaceDir . DS . $sNewDir1[0] . $ImageDir);

                                // 변경된 이미지호스팅 이미지 주소
                                $prevSplit = $aSplit[($i - 1)];
                                $prevSplit = substr($prevSplit, -1, 1);
                                if (in_array($prevSplit, array('"', "'")) === false) $prevSplit = '';
                                $hostingImageUrl = $aPost['protocol'] . '://' . $aPost['httpUrl'] . '/' . $this->imageHostingReplaceDir . DS . $sNewDir1[0] . $orgImage;
                                $hostingImageUrl = $hostingImageUrl . $prevSplit . ' godoOld=' . $prevSplit . $aSplit[$i];
                                // <img src="data/editor/goods/aaa.jpg">
                                // <img src="http://godo.co.kr/data/editor/goods/aaa.jpg">
                                // 두가지가 함께 있을 경우 두번째 src 경로도 함께 변경되기 때문에 ' 와 " 를 포함하여 replace
                                $tmpData[$column] = str_replace('"'.$aSplit[$i].'"', '"'.$hostingImageUrl.'"', $tmpData[$column]);
                                $tmpData[$column] = str_replace("'".$aSplit[$i]."'", "'".$hostingImageUrl."'", $tmpData[$column]);

                                if (substr($orgImage, 0, 6) == '/data/') {
                                    $orgImage = substr($orgImage, 6);
                                }
                                //파일명이 중복일 경우, skip 될 수 있도록 try 문 추가
                                try {
                                    Storage::customCopy(Storage::PATH_CODE_DEFAULT, 'local', $orgImage, $aPost, $this->imageHostingReplaceDir . DS . $sNewDir1[0] . '/data/' . $orgImage);
                                } catch (\Exception $e) {
                                }

                                $loginInfo = "goodsNo : " . $val . chr(10);
                                $loginInfo .= "column : " . $column . chr(10);
                                $loginInfo .= "orgFile : /data/" . $orgImage . chr(10);
                                $loginInfo .= "chgFile : " . $aPost['protocol'] . "://" . $aPost['httpUrl'] . '/' . $this->imageHostingReplaceDir . DS . $sNewDir1[0] . '/data/' . $orgImage . chr(10);
                                \Logger::channel('goods')->info('IMAGE REPLACE RESULT', [$loginInfo, __METHOD__]);
                            }
                        }
                    }
                }

                $goods->setGoodsDescription($tmpData[$column], $val, $column);
            }

            // 상품카운트
            $resultCount++;
        }

        return $resultCount;
    }

    /**
     * 저장소 경로 일괄 수정
     *
     * @param $arrData
     * @return bool
     */
    public function fileStorageSetting($arrData)
    {
        $limit = 5000;
        $storageList = [
            'goodsStorage' => [DB_GOODS => ['imageStorage']],
            'addGoodsStorage' => [DB_ADD_GOODS => ['imageStorage']],
            'giftStorage' => [DB_GIFT => ['imageStorage']],
            'timeSaleStorage' => [DB_GOODS_SALE_STATISTICS => ['imageStorage']],
            'scmStorage' => [DB_SCM_MANAGE => ['imageStorage']],
            'boardStorage' => [DB_BOARD => ['bdUploadStorage']],
            // boardFileUrl 의 경우 관리자 화면에서는 파일 경로 변경에 포함되어야 하여 urlList에 있지만, 처리시에는 storage와 성격이 같아 storageList에 포함.
            'boardFileUrl' => ['es_bd_notice' => ['bdUploadStorage'], 'es_bd_cooperation' => ['bdUploadStorage'], 'es_bd_goodsqa' => ['bdUploadStorage'], 'es_bd_goodsreview' => ['bdUploadStorage'], 'es_bd_qa' => ['bdUploadStorage']],
        ];
        $urlList = [
            'goodsUrl' => [DB_GOODS_IMAGE => ['imageName']],
            'goodsDescUrl' => [DB_GOODS => ['goodsDescription', 'goodsDescriptionMobile']],
            'commonHtmlUrl' => [DB_COMMON_CONTENT => ['commonHtmlContent', 'commonHtmlContentMobile']],
            'addGoodsUrl' => [DB_ADD_GOODS => ['goodsDescription']],
            'giftUrl' => [DB_GIFT => ['giftDescription']],
            'boardImageUrl' => ['es_bd_notice' => ['contents'], 'es_bd_cooperation' => ['contents', 'answerContents'], 'es_bd_goodsqa' => ['contents', 'answerContents'], 'es_bd_goodsreview' => ['contents'], 'es_bd_qa' => ['contents', 'answerContents']],
            'boardTitleUrl' => [DB_BOARD => ['bdHeader', 'bdFooter']],
            'eventUrl' => [DB_DISPLAY_THEME => ['pcContents', 'mobileContents']],
            'brandUrl' => [DB_CATEGORY_BRAND => ['cateHtml1', 'cateHtml1Mobile', 'cateHtml2', 'cateHtml2Mobile', 'cateHtml3', 'cateHtml3Mobile']],
            'categoryUrl' => [DB_CATEGORY_GOODS => ['cateHtml1', 'cateHtml1Mobile', 'cateHtml2', 'cateHtml2Mobile', 'cateHtml3', 'cateHtml3Mobile']],
        ];

        if($storageList[$arrData['target']]) {
            $target = $storageList[$arrData['target']];
            $local = 'local';
        } else if($urlList[$arrData['target']]) {
            $target = $urlList[$arrData['target']];
            $local = '';
        } else {
            return false;
        }
        if($arrData['beforeProtocol'] == 'local') {
            $arrData['beforeDomain'] = $local;
        }
        if($arrData['afterProtocol'] == 'local') {
            $arrData['afterDomain'] = $local;
        }

        // log 저장
        $logData['managerId']   = Session::get('manager.managerId');
        $logData['ip'] = Request::getRemoteAddress();
        $logData['name'] = $arrData['name'];
        $logData['beforeDomain'] = $arrData['beforeDomain'] ? $arrData['beforeDomain'] : '/';
        $logData['afterDomain'] = $arrData['afterDomain'] ? $arrData['afterDomain'] : '/';
        $logBind = $this->db->get_binding(DBTableField::tableLogStorageSetting(), $logData, 'insert');
        $this->db->set_insert_db(DB_LOG_STORAGE_SETTING, $logBind['param'], $logBind['bind'], 'y');

        foreach($target as $table => $val) {
            foreach($val as $column) {
                if($arrData['beforeDomain']) {
                    $strSQL = "select count(*) as cnt from {$table} where {$column} like '%{$arrData['beforeDomain']}%'";
                } else {
                    $strSQL = "select count(*) as cnt from {$table} where {$column} regexp 'src=\\\\\\\\\"/' or {$column} ". ' regexp "src=\\\\\\\\\'/"';
                }

                $total = $this->db->query_fetch($strSQL,null,false)['cnt'];
                $repeat = ceil($total / $limit);
                for($i = 0; $i < $repeat; $i++) {
                    if($arrData['beforeDomain']) {
                        if($arrData['afterDomain'] == '') { // 도메인 -> 기본경로로 변경 시 / 로 끝날 경우 / 제거
                            if(substr($arrData['beforeDomain'], -1) == '/') $arrData['beforeDomain'] = substr($arrData['beforeDomain'], 0, -1);
                        }
                        // UPDATE table SET column = REPLACE(column, beforeDomain, afterDomain) WHERE column LIKE %beforeDomain% LIMIT 5000;

                        $arrBind = [];
                        $this->db->bind_param_push($arrBind, 's', $arrData['beforeDomain']);
                        $this->db->bind_param_push($arrBind, 's', $arrData['afterDomain']);
                        $this->db->bind_param_push($arrBind, 's', $arrData['beforeDomain']);
                        $this->db->set_update_db($table, $column." = REPLACE(". $column .",?,?) ", $column." LIKE concat('%',?,'%') LIMIT ".$limit, $arrBind);

                    } else { // 기본경로 -> 도메인 으로 변경 시 src=\"/ 또는 src=\'/ 태그를 기준으로 변경.
                        // before src=\"/data/
                        // after src=\"http://godohosting.co.kr/data
                        // afterDomain가 '/'로 끝나지 않을 경우 http://godohosting.co.krdata로 변경 됨.
                        if(substr($arrData['afterDomain'], -1) != '/') $arrData['afterDomain'] .= '/';

                        // UPDATE table SET column = REPLACE(column, src=\"/, src=\"afterdomain/) WHERE column regexp 'src=\\\\\\\\"/';
                        // UPDATE table SET column = REPLACE(column, src=\'/, src=\'afterdomain/) WHERE column regexp "src=\\\\\\\\'/";

                        $arrBind = [];
                        $afterDomain1 = 'src="' . $arrData['afterDomain'];
                        $this->db->bind_param_push($arrBind, 's', $afterDomain1);
                        $this->db->set_update_db($table, $column." = REPLACE(". $column .",'src=\\\\\"/',?) ", $column." regexp 'src=\\\\\\\\\"/' LIMIT ".$limit, $arrBind);
                        $arrBind = [];
                        $afterDomain2 = "src='" . $arrData['afterDomain'];
                        $this->db->bind_param_push($arrBind, 's', $afterDomain2);
                        $this->db->set_update_db($table, $column.' = REPLACE('. $column .',"src=\\\\\'/",?) ', $column.' regexp "src=\\\\\\\\\'/" LIMIT '.$limit, $arrBind);
                    }
                }
            }
        }

        // 처리 완료 후 마지막 변경일시 업데이트
        $policy = \App::load('\\Component\\Policy\\Policy');
        $policyData = $policy->getValue('log.storageSetting');
        $policyData[$arrData['target']] = date('Y-m-d H:i:s');
        $policy->setValue('log.storageSetting', $policyData);
    }
}
