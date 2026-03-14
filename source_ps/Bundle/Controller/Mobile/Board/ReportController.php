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

namespace Bundle\Controller\Mobile\Board;

use Framework\Debug\Exception\RedirectLoginException;
use Component\Board\BoardView;
use Component\Board\Board;
use Component\Database\DBTableField;
use Component\PlusShop\PlusReview\PlusReviewArticleFront;

class ReportController extends \Controller\Mobile\Controller
{
    public function index()
    {
        $memberReport = \App::load('\\Component\\Member\\MemberReport');
        $this->db = \App::load('DB');
        $memId = \Session::get('member.memId');

        $data = \Request::get()->all();
        if (gd_is_login() === false) {
            throw new RedirectLoginException();
        }
        if ($data['bdId'] == 'plusReview') { // 플러스리뷰 게시글에서 회원을 신고하는 경우
            $plusReviewArticle = new PlusReviewArticleFront();
            $getData = $plusReviewArticle->get($data['bdSno']);
            if ($data['memoSno'] > 0) { // 대상 게시글이 댓글인 경우
                foreach ($getData['memoList'] as $val) {
                    if ($data['memoSno'] == $val['sno']) { // 작성된 댓글데이에서 피신고자의 댓글만 가져옴
                        $memoData = $val;
                    }
                    $reportData = $memberReport->getReportData($memoData['memNo']);
                }
            } else {
                $reportData = $memberReport->getReportData($getData['memNo']);
            }
            $reportedMemNo = $memoData['memNo'] ? $memoData['memNo'] : $getData['memNo'];
        } else {
            $boardView = new boardView($data);
            $getData = $boardView->getView();
            if ($data['memoSno'] > 0) { // 대상 게시글이 댓글인 경우
                foreach ($getData['memoList'] as $val) {
                    if ($data['memoSno'] == $val['sno']) { // 작성된 댓글데이에서 피신고자의 댓글만 가져옴
                        $memoData = $val;
                    }
                    $reportData = $memberReport->getReportData($memoData['memNo']);
                }
            } else {
                $reportData = $memberReport->getReportData($getData['memNo']);
            }
            $reportedMemNo = $memoData['memNo'] ? $memoData['memNo'] : $getData['memNo'];
        }

        $this->setData('req', $data);
        $this->setData('memo', $memoData);
        $this->setData('memId', $memId);
        $this->setData('writerMemNo', $getData['memNo']); // 피신고자 회원번호
        $this->setData('writerId', $getData['writerId']); // 피신고자 아이디
        $this->setData('blockAllBoardFl', $reportData['blockAllBoardFl']); // 회원 차단 여부

        $this->getView()->setDefine('header', 'outline/_share_header.html');
    }
}
