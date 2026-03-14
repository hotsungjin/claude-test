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

namespace Bundle\Controller\Admin\Member;

use Component\Member\Member;
use Framework\Utility\SkinUtils;
use Framework\Utility\DateTimeUtils;

/**
 * Class 회원리스트
 * @package Bundle\Controller\Admin\Member
 * @author  yjwee
 */
class MemberListController extends \Controller\Admin\Controller
{
    /**
     * @inheritdoc
     */
    public function index()
    {
        $this->callMenu('member', 'member', 'list');
        $request = \App::getInstance('request');

        // 회원 아이디 검증 (영문, 숫자, 특수문자(-),(_),(.),(@)만 가능함)
        if ($request->get()->get('key') === 'memId' && !preg_match('/^[a-zA-Z0-9\.\-\_\@]*$/', $request->get()->get('keyword'))) {
            $request->get()->set('keyword', preg_replace("/([^a-zA-Z0-9\.\-\_\@])/", "", $request->get()->get('keyword')));
        } else {
            $request->get()->set('keyword', preg_replace("!<script(.*?)<\/script>!is", "", $request->get()->get('keyword')));
        }

        if (!$request->get()->has('mallSno')) {
            $request->get()->set('mallSno', '');
        }
        if (!$request->get()->has('page')) {
            $request->get()->set('page', 1);
        }
        if (!$request->get()->has('pageNum')) {
            $request->get()->set('pageNum', 10);
        }

        // ISMS 인증관련 추가
        if (array_search($request->get()->get('pageNum'), SkinUtils::getPageViewCount()) === false) {
            $request->get()->set('pageNum', 10);
        }

        $memberService = \App::load(Member::class);
        $funcSkipOverTime = function () use ($memberService, $request) {
            $getAll = $request->get()->all();
            $page = $request->get()->get('page');
            $pageNum = $request->get()->get('pageNum');

            return $memberService->listsWithCoupon($getAll, $page, $pageNum);
        };
        $funcCondition = function () use ($request) {
            return \count($request->get()->all()) === 3
                && $request->get()->get('mallSno') === ''
                && $request->get()->get('page') === 1
                && $request->get()->get('pageNum') === 10;
        };

        //검색시 리스트 출력
        if ($request->get()->get('searchFl') == 'y'){
            $getData = $this->skipOverTime($funcSkipOverTime, $funcCondition, [], $isSkip);
        } else {
            if ($request->get()->has('entryDt') === false) {
                $request->get()->set('entryDt', DateTimeUtils::getBetweenDateString('-6 days'));
            }
            $getData = [];
        }

        $pageObject = new \Component\Page\Page($request->get()->get('page'), 0, 0, $request->get()->get('pageNum'));
        $pageTotal = \count($getData);
        $pageObject->setTotal($pageTotal);
        $pageObject->setCache(true);
        if ($pageTotal > 0 && $pageObject->hasRecodeCache('total') === false) {
            $total = $memberService->foundRowsByListsWithCoupon($request->get()->all());
            $pageObject->setTotal($total);
        }
        if ($pageObject->hasRecodeCache('amount') === false) {
            $amount = $memberService->getCount(DB_MEMBER, 'memNo', 'WHERE sleepFl=\'n\'');
            $pageObject->setAmount($amount);
        }

        $pageObject->setUrl($request->getQueryString());
        $pageObject->setPage();
        $checked = \Component\Member\Util\MemberUtil::checkedByMemberListSearch($request->get()->all());
        $selected = \Component\Member\Util\MemberUtil::selectedByMemberListSearch($request->get()->all());

        // 개인정보수집 동의상태 변경 카운트 및 버튼 노출
        $policy = \App::load('\\Component\\Policy\\Policy');
        $servicePrivacy = $policy->getValue('member.servicePrivacy');
        $history = \App::load('\\Component\\Member\\History');
        gd_isset($servicePrivacy['period'], 7); // 변경기간 시작일자 초기값 설정
        $servicePrivacyHistoryTotal = $history->getServicePrivacyHistoryCount($servicePrivacy['period']);
        $servicePrivacyHistoryClassName = ($servicePrivacyHistoryTotal > 0) ? 'icon_notice_red' : 'icon_notice_gray';

        $this->setData('isSkip', $isSkip);
        $this->setData('page', $pageObject);
        $this->setData('data', $getData);
        $this->setData('search', $request->get()->all());
        $this->setData('groups', \Component\Member\Group\Util::getGroupName());
        $this->setData('combineSearch', \Component\Member\Member::getCombineSearchSelectBox());
        $this->setData('searchKindASelectBox', \Component\Member\Member::getSearchKindASelectBox());
        $this->setData('checked', $checked);
        $this->setData('selected', $selected);
        $this->setData('isPeriodBtn', true);
        $this->setData('servicePrivacyHistoryTotal', $servicePrivacyHistoryTotal);
        $this->setData('servicePrivacyHistoryClassName', $servicePrivacyHistoryClassName);
        $this->addScript(
            [
                'member.js',
                'sms.js',
            ]
        );
    }
}
