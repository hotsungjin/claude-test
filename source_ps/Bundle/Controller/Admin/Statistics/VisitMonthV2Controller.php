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
namespace Bundle\Controller\Admin\Statistics;

use Component\VisitStatistics\VisitAnalysis;
use Component\Mall\Mall;
use DateTime;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\ArrayUtils;
use Request;

/**
 * 방문자분석 v2 > 방문자 분석 > 월별 방문현황
 * @author Seung-gak Kim <surlira@godo.co.kr>
 */
class VisitMonthV2Controller extends \Controller\Admin\Controller
{
    /**
     * index
     *
     * @throws \Exception
     */
    public function index()
    {
        $this->callMenu('statistics', 'visitV2', 'visitDayV2');

        $visitAnalysis = new VisitAnalysis();

        try {
            $mall = new Mall();
            $searchMallList = $mall->getStatisticsMallList();
            $this->setData('searchMallList', $searchMallList);

            $searchMall = Request::get()->get('mallFl');
            if (!$searchMall) {
                $searchMall = 'all';
            }
            $searchDevice = Request::get()->get('searchDevice');
            if (!$searchDevice) {
                $searchDevice = 'all';
            }
            $searchPeriod = Request::get()->get('searchPeriod');
            $searchDate = Request::get()->get('searchDate');

            $sDate = new DateTime();
            $eDate = new DateTime();

            $startDate = new DateTime($searchDate[0]);
            $endDate = new DateTime($searchDate[1]);

            if (!$searchDate[0]) {
                $date = $sDate->format('d');
                $searchDate[0] = ($date === '01') ? $sDate->sub(new \DateInterval('P1M'))->format('Ymd') : $sDate->modify('-' . ($date - 1) . ' days')->format('Ymd');
            } else {
                if ($sDate->format('Ymd') <= $startDate->format('Ymd')) {   // 기간검색 앞 날짜가 오늘날짜보다 뒤일 때
                    $date = $sDate->format('d');
                    $searchDate[0] = $sDate->modify('-' . ($date - 1) . ' days')->format('Ymd');
                } else {
                    $date = $startDate->format('d');
                    $searchDate[0] = $startDate->modify('-' . ($date - 1) . ' days')->format('Ymd');
                }
            }
            if (!$searchDate[1]) {
                $searchDate[1] = $eDate->modify('-1 days')->format('Ymd');
            } else {
                if ($eDate->format('Ymd') <= $endDate->format('Ymd') || $endDate->format('Ym') == $eDate->format('Ym')) {
                    $searchDate[1] = $eDate->modify('-1 days')->format('Ymd');
                } else {
                    $date = $endDate->format('d');
                    $searchDate[1] = $endDate->add(new \DateInterval('P1M'))->modify('-' . $date . ' days')->format('Ymd');
                }
            }

            $sDate = new DateTime($searchDate[0]);
            $eDate = new DateTime($searchDate[1]);
            $dateDiff = date_diff($sDate, $eDate);
            if ($dateDiff->days > 360) {
                $date = $eDate->format('d');
                $searchDate[0] = $eDate->modify('-' . ($date - 1) . ' days')->format('Ymd');
                $searchPeriod = 0;
            }

            $checked['searchMall'][$searchMall] = 'checked="checked"';
            $checked['searchDevice'][$searchDevice] = 'selected="selected"';
            $checked['searchPeriod'][$searchPeriod] = 'checked="checked"';
            $active['searchPeriod'][$searchPeriod] = 'active';
            $this->setData('searchDate', $searchDate);
            $this->setData('checked', $checked);
            $this->setData('active', $active);

            $searchData['date'] = $searchDate;
            $searchData['device'] = $searchDevice;
            $searchData['type'] = 'monthly';
            $searchData['country'] = $searchMall;

            $getDataArr = $visitAnalysis->getVisitTotalData($searchData);

            /* top board */
            $visitTopTotalData = [];
            $visitTopTotalData['all']['visitMax'] = $getDataArr['top']['total']['visitMax'];
            $visitTopTotalData['all']['visitMaxDate'] = substr($getDataArr['top']['total']['visitMaxDate'],0,4) . '-' . substr($getDataArr['top']['total']['visitMaxDate'],4,2);
            $visitTopTotalData['pc']['visitMax'] = $getDataArr['top']['pc']['visitMax'];
            $visitTopTotalData['mobile']['visitMax'] = $getDataArr['top']['mobile']['visitMax'];

            $visitTopTotalData['all']['visitMin'] = $getDataArr['top']['total']['visitMin'];
            $visitTopTotalData['all']['visitMinDate'] = substr($getDataArr['top']['total']['visitMinDate'],0,4) . '-' . substr($getDataArr['top']['total']['visitMinDate'],4,2);
            $visitTopTotalData['pc']['visitMin'] = $getDataArr['top']['pc']['visitMin'];
            $visitTopTotalData['mobile']['visitMin'] = $getDataArr['top']['mobile']['visitMin'];

            $visitTopTotalData['all']['pvMax'] = $getDataArr['top']['total']['pvMax'];
            $visitTopTotalData['all']['pvMaxDate'] = substr($getDataArr['top']['total']['pvMaxDate'],0,4) . '-' . substr($getDataArr['top']['total']['pvMaxDate'],4,2);
            $visitTopTotalData['pc']['pvMax'] = $getDataArr['top']['pc']['pvMax'];
            $visitTopTotalData['mobile']['pvMax'] = $getDataArr['top']['mobile']['pvMax'];

            $visitTopTotalData['all']['pvMin'] = $getDataArr['top']['total']['pvMin'];
            $visitTopTotalData['all']['pvMinDate'] = substr($getDataArr['top']['total']['pvMinDate'],0,4) . '-' . substr($getDataArr['top']['total']['pvMinDate'],4,2);
            $visitTopTotalData['pc']['pvMin'] = $getDataArr['top']['pc']['pvMin'];
            $visitTopTotalData['mobile']['pvMin'] = $getDataArr['top']['mobile']['pvMin'];
            $this->setData('visitTopTotalData', $visitTopTotalData);

            /* chart */
            $visitDate = array_keys($getDataArr['down']);
            $visitCnt = array_column($getDataArr['down'], 'visitCnt');
            $visitNumberCnt = array_column($getDataArr['down'], 'visitTotal');
            $visitNewCnt = array_column($getDataArr['down'], 'visitNew');
            $visitReCnt = array_column($getDataArr['down'], 'visitRe');
            foreach ($visitDate as $key => $val) {
                $visitDate[$key] = "'" . $val . "'";
            }
            $visitChart['Date'] = $visitDate;
            $visitChart['Count'] = $visitCnt;
            $visitChart['Number'] = $visitNumberCnt;
            $visitChart['NewCnt'] = $visitNewCnt;
            $visitChart['ReCnt'] = $visitReCnt;
            if (count($visitChart['Date']) < 2) {
                $emptyDateArr = ['0' => "'0'"];
                $emptyArr = ['0' => 0];
                $visitChart['Date'] = array_merge($emptyDateArr, $visitChart['Date']);
                $visitChart['Count'] = array_merge($emptyArr, $visitChart['Count']);
                $visitChart['Number'] = array_merge($emptyArr, $visitChart['Number']);
                $visitChart['NewCnt'] = array_merge($emptyArr, $visitChart['NewCnt']);
                $visitChart['ReCnt'] = array_merge($emptyArr, $visitChart['ReCnt']);
            }
            $this->setData('visitChart', $visitChart);

            /* table */
            $i = 0;
            foreach ($getDataArr['down'] as $key => $val) {
                $returnVisitStatistics[$i]['visitDate'] = substr($key,0,4) . '-' . substr($key,4,2);
                $returnVisitStatistics[$i]['visitNumberCnt'] = $val['visitTotal'];
                $returnVisitStatistics[$i]['visitCnt'] = $val['visitCnt'];
                $returnVisitStatistics[$i]['visitNewCnt'] = $val['visitNew'];
                $returnVisitStatistics[$i]['visitReCnt'] = $val['visitRe'];
                $i++;
            }
            $visitCount = count($returnVisitStatistics);

            if ($visitCount > 20) {
                $rowDisplay = 20;
            } else if ($visitCount == 0) {
                $rowDisplay = 5;
            } else {
                $rowDisplay = $visitCount;
            }
        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }

        $this->setData('rowList', json_encode($returnVisitStatistics));
        $this->setData('visitCount', $visitCount);
        $this->setData('rowDisplay', $rowDisplay);
        $this->setData('visitChart', $visitChart);
        $this->setData('getDataArr', $getDataArr);

        $this->addScript(
            [
                'backbone/backbone-min.js',
                'tui/code-snippet.min.js',
                'raphael/effects.min.js',
                'raphael/raphael-min.js',
                'tui.chart-master/chart.min.js',
                'tui.grid/grid.min.js',
            ]
        );
        $this->addCss(
            [
                'chart.css',
                'tui.grid/grid.css',
            ]
        );
    }
}
