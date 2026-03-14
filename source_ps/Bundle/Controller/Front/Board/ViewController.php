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

use Component\Goods\GoodsCate;
use Component\Page\Page;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\RedirectLoginException;
use Framework\Debug\Exception\RequiredLoginException;
use Request;
use View\Template;
use Component\Validator\Validator;
use Globals;
use Component\Board\BoardView;
use Component\Board\BoardList;

class ViewController extends \Controller\Front\Controller
{
    public function index()
    {
        try {

            $this->addScript([
                'gd_board_common.js',
            ]);

            // 웹 치약점 개선사항 추가
            if (Request::get()->has('noheader') && !preg_match('/^[a-zA-Z0-9]*$/', Request::get()->get('noheader'))) {
                Request::get()->set('noheader', 'n');
            }

            $req = array_merge((array)Request::get()->toArray(), (array)Request::post()->toArray());
            $req = gd_htmlspecialchars($req);
            $boardView = new BoardView($req);
            $boardView->checkUsePc();
            $getData = $boardView->getView();
            $relationList = $boardView->getRelation($getData);
            $bdView['cfg'] = gd_isset($boardView->cfg);
            $bdView['data'] = gd_isset($getData);
            $bdView['member'] = gd_isset($boardView->member);
            if (gd_is_login() === false) {
                // 개인 정보 수집 동의 - 이용자 동의 사항
                $tmp = gd_buyer_inform('001009');
                $private = $tmp['content'];
                if (gd_is_html($private) === false) {
                    $bdView['private'] = $private;
                }
            }

            if($bdView['data']['isShow'] == 'n' && ($bdView['data']['memNo'] != \Session::get('member.memNo'))) {
                throw new AlertBackException("해당 게시글은 신고된 상태로 확인이 불가합니다.");
            }

            $boardView->canReadSecretReply($bdView['data']);
            $boardSecretReplyCheck = $boardView->setSecretReplyView($bdView['cfg']);

            // 웹취약점 개선사항 상단 에디터 업로드 이미지 alt 추가
            if ($bdView['cfg']['bdHeader']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $bdView['cfg']['bdHeader'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $bdView['cfg']['bdHeader'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $bdView['cfg']['bdHeader']);
                }
            }

            // 웹취약점 개선사항 하단 에디터 업로드 이미지 alt 추가
            if ($bdView['cfg']['bdFooter']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $bdView['cfg']['bdFooter'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $bdView['cfg']['bdFooter'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $bdView['cfg']['bdFooter']);
                }
            }

            // 웹취약점 개선사항 공지내용 에디터 업로드 이미지 alt 추가
            if ($bdView['data']['workedContents']) {
                $tag = "title";
                preg_match_all( '@'.$tag.'="([^"]+)"@' , $bdView['data']['workedContents'], $match );
                $titleArr = array_pop($match);

                foreach ($titleArr as $title) {
                    $bdView['data']['workedContents'] = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $bdView['data']['workedContents']);
                }
            }

            $this->setData('secretReplyCheck', $boardSecretReplyCheck);
            $this->setData('req', gd_isset($req));
            $this->setData('bdView', $bdView);
            if ($relationList) {
                $this->setData('relationList', $relationList);
                $this->setData('bdListCfg' , $boardView->cfg);
            }

            if (gd_isset($req['noheader'], 'n') != 'n') {
                $this->getView()->setDefine('header', 'outline/_share_header.html');
                $this->getView()->setDefine('footer', 'outline/_share_footer.html');
            }

            $path = 'board/skin/' . $bdView['cfg']['themeId'] . '/view.html';
            $this->getView()->setDefine('view', $path);
            if ($bdView['cfg']['bdListInView'] == 'y') {
                gd_isset($req['page'], 1);
                $boardList = new BoardList($req);
                $getData = $boardList->getList();
                $bdList['cfg'] = $boardList->cfg;
                $bdList['list'] = $getData['data'];
                $bdList['cnt'] = $getData['cnt'];
                $bdList['noticeList'] = $getData['noticeData'];
                $bdList['categoryBox'] = $boardList->getCategoryBox($req['category'], ' onChange="this.form.submit();" ');
                $bdList['pagination'] = $getData['pagination']->getPage();
                $this->setData('logoutReturnUrl', '../board/list.php?bdId=' . $req['bdId']);
                $this->setData('bdList', $bdList);
                $this->setData('inList',true);
                $path = 'board/skin/' . $bdList['cfg']['themeId'] . '/list.html';
                $this->getView()->setDefine('list', $path);
            }
        } catch (RequiredLoginException $e) {
            throw new RedirectLoginException($e->getMessage());
        } catch (\Exception $e) {
            throw new AlertRedirectException($e->getMessage(), null, null, '../board/list.php?' . Request::getQueryString());
        }

    }


}
