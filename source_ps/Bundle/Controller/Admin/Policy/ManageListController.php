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

namespace Bundle\Controller\Admin\Policy;

use Bundle\Component\Member\Member;
use Component\Member\Manager;
use Component\Member\ManagerCs;
use Component\Page\Page;
use Component\Scm\Scm;
use Framework\Debug\Exception\LayerException;
use Framework\Utility\GodoUtils;
use Framework\Utility\StringUtils;
use Globals;
use Request;

/**
 * 운영자 관리 리스트
 *
 * @author Lee Namju <lnjts@godo.co.kr>
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class ManageListController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu('policy', 'management', 'list');

        $request = \App::getInstance('request');
        try {
            $component = \App::load(Manager::class);
            $getValue = $request->get()->toArray();
            $getData = $component->getManagerList($getValue);
            $page = \App::load(Page::class); // 페이지 재설정
            $department = gd_code('02001'); // 부서
            $position = gd_code('02002'); // 직급
            $duty = gd_code('02003'); // 직책

            // SMS 자동발송 수신여부 관련 체크
            $smsAutoReceiveKind = $component->smsAutoReceiveKind;
            $smsAutoReceiveKind = array_merge(['all' => __('전체')], ['n' => __('SMS 수신안함')], $smsAutoReceiveKind);

            // 장기 미로그인 운영자 안내
            $dataSecurity = gd_policy('manage.security');
            gd_isset($dataSecurity['noVisitAlarmFl'], 'n');
            gd_isset($dataSecurity['noVisitPeriod'], 364);
            $noVisitPeriodText = ['89' => '3개월', '179' => '6개월', '364' => '1년', '729' => '2년'];
            $this->setData('noVisitPeriodText', $noVisitPeriodText[$dataSecurity['noVisitPeriod']]);
            $this->setData('noVisitDate', $component->getNoVisitDate());
            if($dataSecurity['noVisitAlarmFl'] == 'y') {
                $noVisit = $component->getNoVisitAlarm();
                $this->setData('noVisit', $noVisit);
                $this->setData('noVisitAlarmFl', $noVisit['noVisitCnt'] > 0 && empty($getValue));
            }

        } catch (\Exception $e) {
            throw new LayerException($e->getMessage());
        }

        $scm = \App::load(Scm::class);
        $scmList = StringUtils::htmlSpecialCharsStripSlashes($scm->selectOperationScmList());
        $scmList[0]['companyNm'] .= '(본사)';

        $this->setData('useAppCodes', GodoUtils::getUsePlusShopCodes());
        $this->setData('employeeList', $component->getEmployeeList());
        $this->setData('data', $getData['data']);
        $this->setData('search', $getData['search']);
        $this->setData('searchKindArray', Member::getSearchKindASelectBox());
        $this->setData('sort', $getData['sort']);
        $this->setData('checked', $getData['checked']);
        $this->setData('noVisitCnt', $getData['noVisitCnt']);
        $this->setData('page', $page);
        $this->setData('department', $department);
        $this->setData('position', $position);
        $this->setData('duty', $duty);
        $this->setData('smsAutoReceiveKind', $smsAutoReceiveKind);
        $this->setData('scmList', $scmList);
        $this->addCss(['layer.css']);
    }
}
