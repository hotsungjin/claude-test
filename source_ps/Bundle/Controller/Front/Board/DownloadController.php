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

namespace Bundle\Controller\Front\Board;

use Component\Board\Board;
use Component\Board\BoardFront;
use Component\Storage\Storage;
use Framework\Debug\Exception\AlertBackException;
use Framework\ObjectStorage\Service\ImageUploadService;
use Request;
use Session;

class DownloadController extends \Controller\Front\Controller
{
    protected $db;

    public function index()
    {
        try {
            if (!is_object($this->db)) {
                $this->db = \App::load('DB');
            }

            $req = Request::get()->toArray();

            if (!isset($req['bdId']) || !isset($req['fid']) || !isset($req['sno'])) {
                exit();
            }

            $bdId = $req['bdId'];
            $fid = (int)$req['fid'];

            // Board 존재유무 확인
            $this->db->strField = "COUNT(*) as bdCount, bdId as bdName";
            $this->db->strWhere = "bdId = ?";
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 's', $bdId);

            $query = $this->db->query_complete();
            $strSQL = "SELECT " . array_shift($query) . " FROM " . DB_BOARD . " " . implode(' ', $query);
            $queryResult = $this->db->query_fetch($strSQL, $arrBind, false);

            if ((int)$queryResult['bdCount'] <= 0) {
                throw new AlertBackException(__('존재하지 않는 게시판입니다.'));
            }

            // 존재 유무 확인된 경우 실행
            $this->db->strField = "uploadFileNm, saveFileNm, bdUploadStorage, bdUploadPath, isDelete, isSecret, memNo, isSecret, groupThread, parentSno";
            $this->db->strWhere = "sno = ?";
            $arrBind = [];
            $this->db->bind_param_push($arrBind, 'i', $req['sno']);

            $query = $this->db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_BD_ . $queryResult['bdName'] . ' ' . implode(' ', $query);
            $data = $this->db->query_fetch($strSQL, $arrBind, false);

            // 게시글 및 다운로드 파일이 존재하지 않는 경우 (보안이슈)
            if (!$data) {
                throw new AlertBackException(__('다운로드 받을 파일이 존재하지 않습니다.'));
            }

            // 게시판 읽기 권한에 따른 첨부파일 다운로드 (XSS 취약점 개선)
            $boardFront = new BoardFront($req);
            $auth = $boardFront->canRead($data);

            if ($auth === 'n') {
                throw new AlertBackException(__('다운로드 불가한 회원입니다.'));
            } else if ($auth === 'c') {
                if ($data['isSecret'] === 'y') {
                    // 비회원 비밀글 첨부파일 다운로드시 비밀번호 검증여부 체크
                    if (!Session::has('writerPwOk_' . $req['bdId'] . '_' . $req['sno'])) {
                        throw new AlertBackException(__('다운로드 불가한 회원입니다.'));
                    }
                }
            }

            // 첨부파일 테이블에서 가져오기
            $arrWhere = $arrBind = [];
            $dbTable = Board::getBoardTableName($bdId);
            switch ($bdId) {
                case Board::BASIC_GOODS_REIVEW_ID:
                    $arrWhere[] = "reviewNo = ?";
                    $arrWhere[] = "reviewType != 'plusreview'";
                    break;
                case Board::BASIC_GOODS_QA_ID:
                case Board::BASIC_QA_ID:
                    $arrWhere[] = "qaNo = ?";
                    break;
                case Board::BASIC_NOTICE_ID:
                    $arrWhere[] = "noticeNo = ?";
                    break;
                case Board::BASIC_EVENT_ID:
                    $arrWhere[] = "eventNo = ?";
                    break;
                case Board::BASIC_COOPERATION_ID:
                    $arrWhere[] = "cooperationNo = ?";
                    break;
                default:
                    $arrWhere[] = "bdId = ?";
                    $arrWhere[] = "boardSno = ?";
                    $this->db->bind_param_push($arrBind, 's', $bdId);
            }
            $this->db->bind_param_push($arrBind, 'i', $req['sno']);
            $this->db->strField = 'uploadFileNm, imageUrl';
            $this->db->strWhere = implode(' AND ', $arrWhere);
            $query = $this->db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $dbTable . ' ' . implode(' ', $query);
            $result = $this->db->query_fetch($strSQL, $arrBind, true);
            if (!empty($result)) {
                array_walk($result, function ($v, $k) use (&$arrUploadFileNm, &$arrSaveFileNm) {
                    $arrUploadFileNm[$k] = $v['uploadFileNm'];
                    $arrSaveFileNm[$k] = $v['imageUrl'];
                });
                $data['uploadFileNm'] = implode(STR_DIVISION, $arrUploadFileNm);
                $data['saveFileNm'] = implode(STR_DIVISION, $arrSaveFileNm);
            }

            $uploadFileNm = explode(STR_DIVISION, $data['uploadFileNm']);
            $saveFileNm = explode(STR_DIVISION, $data['saveFileNm']);

            $uploadFileNm = $uploadFileNm[$fid];
            $saveFileNm = $saveFileNm[$fid];

            // 기존 데이터베이스에는 ObjectStorage를 사용하지 않는 데이터들도 있을 것이기 때문에 Obs Path인지 확인
            if ($data['bdUploadStorage'] == 'obs') {
                (new ImageUploadService())->download($uploadFileNm, $saveFileNm);
            } else {
                Storage::disk(Storage::PATH_CODE_BOARD, $data['bdUploadStorage'])->download($data['bdUploadPath'] . $saveFileNm, $uploadFileNm);
            }
        } catch (\RuntimeException $re) {
            throw new AlertBackException(__('다운로드 받을 파일이 존재하지 않습니다.'));
        } catch (\Exception $e) {
            if ($e->getMessage()) {
                throw new AlertBackException(__($e->getMessage()));
            } else {
                throw new AlertBackException(__('다운로드 받을 파일이 존재하지 않습니다.'));
            }
        }
    }
}
