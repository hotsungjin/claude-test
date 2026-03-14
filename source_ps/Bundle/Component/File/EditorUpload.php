<?php
/**
 * 에디터 이미지 업로드 Class
 *
 * @author sj
 * @version 1.0
 * @since 1.0
 * @copyright ⓒ 2016, NHN godo: Corp.
 */
namespace Bundle\Component\File;

use Framework\Debug\Exception\Except;
use Framework\ObjectStorage\Service\ImageUploadService;
use Framework\Utility\ArrayUtils;
use Component\Database\DBTableField;

class EditorUpload
{
    const ECT_UPLOAD_ONLYIMAGE = '%s.ECT_UPLOAD_ONLYIMAGE';
    const ECT_UPLOAD_FAILURE = '%s.ECT_UPLOAD_FAILURE';

    const TEXT_UPLOAD_ONLYIMAGE = '이미지 파일만 업로드가 가능합니다.';
    const TEXT_UPLOAD_FAILURE = '업로드가 실패하였습니다.';

    private $file;
    private $target;
    private $extType;
    private $chkType;
    private $dataFile = null;

    /**
     * 생성자
     */
    public function  __construct()
    {
    }

    /**
     * 에디터에서 이미지 업로드 (mini_editor)
     * @return string src 이미지 주소
     */
    public function uploadEditorImage($req, $files)
    {
        $src = '';
        $req['idx'] += 0;
        $req['mini_url'] = trim($req['mini_url']);

        if ($req['mini_url'] != '' && $req['mini_url'] != 'http://') {
            $src = $req['mini_url'];
        } else {
            if (!preg_match("/^image/", $files['mini_file']['type'])) {
                throw new Except(sprintf(parent::ECT_UPLOAD_ONLYIMAGE, 'EditorUpload'), __('이미지 파일만 업로드가 가능합니다.'));
                return;
            }

            if (is_uploaded_file($files['mini_file']['tmp_name'])) {
                $this->dataFile = \App::load('\\Component\\File\\DataFile');
                $this->file = &$files['mini_file'];
                $splitUploadNm = explode(".", $this->file['name']);
                $saveFileNm = time() . "." . $splitUploadNm[count($splitUploadNm) - 1];
                $this->target = $saveFileNm;

                $this->upload_set('image');
                if (!$this->upload()) {
                    throw new Except(sprintf(self::ECT_UPLOAD_ONLYIMAGE, 'EditorUpload'), __('이미지 파일만 업로드가 가능합니다.'));
                    exit;
                }

                $arrSrc = $this->dataFile->getUrl('editor', $this->target);
                //$src = $arrSrc['fullPath'];
                $src = $arrSrc['path'];
            }
        }
        if (!$src) {
            throw new Except(sprintf(self::ECT_UPLOAD_FAILURE, 'EditorUpload'), self::ECT_UPLOAD_FAILURE);
        }
        return $src;
    }

    /**
     * 에디터에서 이미지 업로드 (tiny_mce)
     * @param array $files $_FILES
     * @param string $mode 저장타입
     * @return string src 이미지 주소
     */
    public function uploadEditorImageByTinymce($files, $mode = '')
    {
        if (!preg_match("/^image/", $files['mini_file']['type'])) {
            throw new Except(sprintf(parent::ECT_UPLOAD_ONLYIMAGE, 'EditorUpload'), __('이미지 파일만 업로드가 가능합니다.'));
            return;
        }

        $msg = '';
        if (is_uploaded_file($files['mini_file']['tmp_name'])) {
            $this->dataFile = \App::load('\\Component\\File\\DataFile');
            $this->file = &$files['mini_file'];

            if ($mode == '')
                $mode = 'etc';
            $mode .= '/';
            $this->file['name'] = str_replace(' ', '', $this->file['name']);
            $this->target = $mode . $this->file['name'];

            $this->dataFile->setImageStorage('local', 'editor', 'editor');
            if ($this->dataFile->fileExists('editor', $this->target)) {
                $splitUploadNm = explode('.', $this->file['name']);
                $ext = $splitUploadNm[count($splitUploadNm) - 1];
                unset($splitUploadNm[count($splitUploadNm) - 1]);

                $fname = implode('.', $splitUploadNm);
                unset($splitUploadNm);

                $imageStorage = $this->dataFile->getImageStorage('editor');
                $fileList = glob($imageStorage['rootPath'] . '/' . $mode . $fname . '([0-9]*).' . $ext);
                if (ArrayUtils::isEmpty($fileList) === false) {
                    sort($fileList);
                    $arrLastFname = explode($imageStorage['rootPath'] . '/' . $mode . $fname, $fileList[count($fileList) - 1]);
                    $splitFname = explode('.', $arrLastFname[1]);
                    $newNum = preg_replace('/[^0-9]*/', '', $splitFname[0]) + 1;
                    $this->target = $mode . $fname . '(' . $newNum . ').' . $ext;
                    $msg = sprintf(__('같은 이름의 파일이 존재하여, 파일이름이 %1$s(%2$d)%3$s 로 변경되었습니다.'), $fname, $newNum, $ext);
                } else {
                    $newNum = 1;
                    $this->target = $mode . $fname . '(' . $newNum . ').' . $ext;
                    $msg = '같은 이름의 파일이 존재하여, 파일이름이 ' . $fname . '(' . $newNum . ').' . $ext . ' 로 변경되었습니다.';
                    $msg = sprintf(__('같은 이름의 파일이 존재하여, 파일이름이 %1$s(%2$d)%3$s 로 변경되었습니다.'), $fname, $newNum, $ext);
                }
            }

            $this->upload_set('image');
            if (!$this->upload()) {
                throw new Except(sprintf(self::ECT_UPLOAD_ONLYIMAGE, 'EditorUpload'), __('이미지 파일만 업로드가 가능합니다.'));
                exit;
            }

            $arrSrc = $this->dataFile->getUrl('editor', $this->target);
            //$src = $arrSrc['fullPath'];
            $src = $arrSrc['path'];
        }


        if (!$src) {
            throw new Except(sprintf(self::ECT_UPLOAD_FAILURE, 'EditorUpload'), self::ECT_UPLOAD_FAILURE);
        }
        return array('msg' => $msg, 'src' => $src);
    }

    /**
     * 변수 할당
     * @void
     */
    function upload_set($chkType = '')
    {
        switch ($this->chkType) {
            case "design":
                $this->extType = array('html', 'php');
                $this->chkType = "text";
                break;
            default:
                $this->extType = array('html', 'htm', 'php');
                $this->chkType = $chkType;
                break;
        }
    }

    /**
     * 일반 업로드 파일 확장자 검증
     * @return bool
     */
    function file_extension_check()
    {
        if ($this->file['name']) {
            $tmp = explode('.', $this->file['name']);
            $extension = strtolower($tmp[count($tmp) - 1]);
            if (in_array($extension, $this->extType))
                return false;
        }
        return true;
    }

    /**
     * 일반 업로드 파일 검증
     * @return bool
     */
    function file_type_check()
    {
        if (!function_exists('mime_content_type'))
            return true;
        if ($this->file['tmp_name']) {
            $mime = mime_content_type($this->file['tmp_name']);
            if ($this->chkType && !preg_match('/' . $this->chkType . '/', $mime))
                return false;
        }
        return true;
    }

    /**
     * 파일업로드
     * @return bool
     */
    function upload()
    {
        if ($this->file['tmp_name']) {
            if (!$this->file_extension_check()) {
                return false;
            }
            if (!$this->file_type_check()) {
                return false;
            }

            $this->dataFile->setImageStorage('local', 'editor', 'editor');

            // 이미지 저장
            $this->dataFile->setSrcLocalFile($this->file['tmp_name']);
            $this->dataFile->setDestFile('editor', $this->target);
            $this->dataFile->move(true);
        }
        return true;
    }

    /**
     * obs 이미지 업로드 및 컨텐츠 대체
     *
     * @param array $localImageSources 로컬 이미지 경로 배열
     * @param string $uploadPath obs 업로드 경로(디렉토리)
     * @return array 이미지를 obs로 대체한 에디터 컨텐츠
     */
    public function obsUploadByImageSources(array $localImageSources, string $uploadPath): array
    {
        $obsUploadResults = [];
        foreach ($localImageSources as $src) {
            $filePath = $this->removeQuotAndDomain($src);
            $result = $this->obsImageUpload($filePath, $uploadPath);
            $obsUploadResults[$src] = $result;
        }

        return $obsUploadResults;
    }

    /**
     * obs 이미지 업로드
     *
     * @param string $filePath 로컬이미지 경로
     * @return array
     */
    public function obsImageUpload(string $filePath, string $uploadPath): array
    {
        $result['result'] = false;
        try {
            if (file_exists($filePath)) {
                $saveFileName = substr(md5(microtime()), 0, 16) . rand(100, 999);
                $file = fopen($filePath, 'rb');
                $size = filesize($filePath);
                $binary_data = fread($file, $size);
                $result = (new ImageUploadService())->uploadBinaryImageToRealPath($binary_data, $uploadPath, $saveFileName, false);
            }
        } catch (\Throwable $e) {
            \Logger::channel('obs')->error(__METHOD__ . ' 에디터 obs 이미지 업로드 실패 ' . $e);
        }
        return $result;
    }

    /**
     * 에디터 로컬 이미지 경로 추출
     *
     * @param string $data
     * @return array 로컬 이미지 경로들 - "/data/editor*" or "{상점 도메인}/data/editor*" array
     */
    public function extractLocalImageSources(string $data): array
    {
        try {
            $pattern = '/<img[^>]*src=("(?:https?:\/\/[^"]*\/data\/editor\/[^"]*|\/data\/editor\/[^"]*)")/i';
            preg_match_all($pattern, $data, $matches);
            return array_unique($matches[1]);
        } catch (\Throwable $e) {
            $logger = \App::getInstance('logger');
            $logger->emergency(__METHOD__ . ' : ' . $e->getMessage(), $e->getTrace());
        }
        return [];
    }

    /**
     * 에디터 로컬 이미지 경로를 파일 상대 경로로 변환
     *
     * @param string $src "/data/editor/*" or "{상점 도메인}/data/editor/*"
     * @return string 파일 상대 경로 data/editor/*
     */
    public function removeQuotAndDomain(string $src): string
    {
        // 앞뒤 ", 앞 /, 상점 도메인,  제거
        $path = trim($src, '"');
        $position = strpos($path, 'data/editor/') ?: 0;
        return substr($path, $position);
    }

    /**
     * string contents의 로컬 이미지 경로를 obs 경로로 대체
     *
     * @param string $contents 에디터 컨텐츠
     * @param array $obsUploadResults [로컬 이미지 경로 배열 => obs 업로드 결과] 구조의 array
     * @return string 이미지 경로가 대체된 contents
     */
    public function replaceImageSource(string $contents, array $obsUploadResults): string
    {
        foreach ($obsUploadResults as $localImageSource => $obsUploadResult) {
            $contents = str_replace($localImageSource, '"' . $obsUploadResult['imageUrl']. '"', $contents);
        }
        return $contents;
    }

    /**
     * es_editor_attachments 테이블 저장
     *
     * @param array $obsUploadResults [로컬 이미지 경로 배열 => obs 업로드 결과] 구조의 array
     */
    public function saveEditorAttachments(array $obsUploadResults, string $uploadPath)
    {
        $db = \App::load('DB');
        foreach ($obsUploadResults as $localImageSource => $obsUploadResult) {
            $filePath = "/" . $this->removeQuotAndDomain($localImageSource);
            $arrData = [
                'contentsInfo' => '{ "contentsType": "mail" }',
                'imageFolder' => $obsUploadResult['imageFolder'],
                'imageUrl' => $obsUploadResult['imageUrl'],
                'obsDelFl' => 'n',
                'localPath' => $filePath,
                'localDelFl' => 'y',
            ];

            $arrBind = $db->get_binding(DBTableField::tableEditorAttachments(), $arrData, 'insert', null);
            $db->set_insert_db(DB_EDITOR_ATTACHMENTS, $arrBind['param'], $arrBind['bind'], 'y');
        }
    }



    /**
     * 에디터 로컬 이미지 삭제
     * 한 화면의 여러, 단일 에디터 내에서 ctrl+c, ctrl+v 하는 경우 -> 상점 도메인을 포함한 img 태그가 복사됨
     * 같은 로컬 파일을 바라보는 여러 img 태그가 있을 수 있어 모든 처리 이후 삭제 해야함
     *
     * @param array $imageSources "/data/editor/*" or "{상점 도메인}/data/editor/*" 리스트
     */
    public function removeLocalFileByImageSources(array $obsUploadResults)
    {
        foreach ($obsUploadResults as $localImageSource => $obsUploadResult) {
            $filePath = $this->removeQuotAndDomain($localImageSource);
            if (file_exists($filePath)) {
                //@unlink($filePath); // 안정화 이후 로컬이미지 삭제필요
            }
        }
    }

    /**
     * 에디터 obs 이미지 삭제
     *
     * @param array $obsUploadResults obs 업로드 결과 array
     */
    public function removeObsFileByUploadResults(array $obsUploadResults)
    {
        $imageService = new ImageUploadService();
        foreach($obsUploadResults as $obsUploadResult){
            $imageService->deleteImage($obsUploadResult['imageUrl']);
        }
    }

    /**
     * 에디터 attachments 테이블에 포함된 contentsType 매핑
     *
     * @param string $tableName 테이블명
     * @return string $contentsType
     */
    public static function getEditorContentsType(string $tableName): string
    {
        if(strpos($tableName, "es_bd_") === 0){
            return 'bd' . ucfirst(substr($tableName, 6));
        } else {
            return substr($tableName, 3);
        }
    }
}
