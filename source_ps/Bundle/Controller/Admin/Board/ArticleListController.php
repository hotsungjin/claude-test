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
namespace Bundle\Controller\Admin\Board;

use Component\Board\BoardUtil;
use Component\Board\Board;
use Component\Board\ArticleListAdmin;
use Component\Board\BoardAdmin;
use Component\Goods\GoodsCate;
use Component\Page\Page;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertOnlyException;
use Request;
use Session;

class ArticleListController extends \Controller\Admin\Controller
{
    /**
     * Description
     */
    public function index()
    {
        // --- 메뉴 설정
        $this->addScript(['jquery/jquery.dataOverlapChk.js', 'gd_board_common.js']);

        // --- 페이지 데이터
        try {
            $boardAdmin = new BoardAdmin();
            $boardList = $boardAdmin->selectList();
            $req = Request::get()->toArray();
            gd_isset($req['isShow'], 'y');
            gd_isset($req['listType'], 'board');
            $this->setData('isShow', $req['isShow']);
            $this->setData('listType', $req['listType']);

            if (count($boardList) > 0) {
                if (Request::get()->has('bdId') === false) {
                    $req["bdId"] =  $boardList[0]['bdId'];
                }
            }

            if ($req['searchPeriod'] != -1 && (!$req['rangDate'][0] && !$req['rangDate'][1])) {
                $req['rangDate'][0] = date('Y-m-d', strtotime('-6 day'));
                $req['rangDate'][1] = date('Y-m-d');
            }
            $arrInclude = null;

            if($req['isShow'] == 'n') {
                gd_isset($req['searchDateFl'], 'reportDt');
            }
            if($req['searchDateFl'] == 'reportDt') {
                $arrInclude = ['br.regDt'];
            }

            $articleListAdmin = new ArticleListAdmin($req);
            gd_isset($req['pageNum'],10);
            if(gd_is_provider()) {
                if($req['listType'] == 'board') {
                    $getData = $articleListAdmin->getList(true, $req['pageNum'], null, ['g.scmNo = ' . \Session::get('manager.scmNo')], $arrInclude);
                } else {
                    $getData = $articleListAdmin->getReportMemoList();
                }
                if(Request::get()->get('bdId') == Board::BASIC_GOODS_QA_ID) {
                    $pageId = 'goodsQaList';
                }
                else {
                    $pageId = 'goodsReviewList';
                }
                $this->callMenu('board', 'board', $pageId);
            } else {
                $this->callMenu('board', 'board', 'boardList');
                if($req['listType'] == 'board') {
                    $getData = $articleListAdmin->getList(true, $req['pageNum']);
                } else {
                    $getData = $articleListAdmin->getReportMemoList();
                }
            }

            // --- 페이지 설정
            $bdList['cfg'] = $articleListAdmin->cfg;
            $bdList['list'] = $getData['data'];
            $bdList['sort'] = $getData['sort'];
            if (Request::get()->has('category')) {
                $requestCategory = Request::get()->get('category');
            }
            $bdList['pagination'] = $getData['pagination']->getPage();
            $bdList['cnt'] = $getData['cnt'];
            $bdList['categoryBox'] = $articleListAdmin->getCategoryBox($requestCategory, ' onChange="this.form.submit();" ');
        } catch (\Exception $e) {
            throw new AlertOnlyException($e->getMessage());
        }

        // 보안이슈 : 게시글 관리 정렬 값 체크
        $boardListSort = implode(STR_DIVISION, array_keys($bdList['sort']));

        // --- 관리자 디자인 템플릿
        $this->setData('page', $getData['pagination']);
        $this->setData('bdList', $bdList);
        $this->setData('board', $articleListAdmin);
        $this->setData('req', gd_htmlspecialchars($articleListAdmin->req));
        $this->setData('searchKindASelectBox', \Component\Member\Member::getSearchKindASelectBox());
        $this->setData('boards', $boardList);
        $this->setData('boardListSort', $boardListSort);
        $this->getView()->setPageName('board/article_list.php');
    }
}
