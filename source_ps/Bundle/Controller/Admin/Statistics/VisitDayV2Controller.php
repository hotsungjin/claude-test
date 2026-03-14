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
use Request;

/**
 * 방문자분석 v2 > 방문자 분석 > 일별 방문현황
 * @author sueun-choi
 */
class VisitDayV2Controller extends \Controller\Admin\Controller
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
            if (!$searchDate[0]) {
                $searchDate[0] = $sDate->modify('-7 days')->format('Ymd');
            } else {
                $startDate = new DateTime($searchDate[0]);
                if ($sDate->format('Ymd') <= $startDate->format('Ymd')) {
                    $searchDate[0] = $sDate->modify('-1 days')->format('Ymd');
                } else {
                    $searchDate[0] = $startDate->format('Ymd');
                }
            }
            if (!$searchDate[1]) {
                $searchDate[1] = $eDate->modify('-1 days')->format('Ymd');
            } else {
                $endDate = new DateTime($searchDate[1]);
                if ($eDate->format('Ymd') <= $endDate->format('Ymd') || (strlen($searchDate[1]) < 10) && $endDate->format('Ym') == $eDate->format('Ym')) {
                    $searchDate[1] = $eDate->modify('-1 days')->format('Ymd');
                } else {
                    if (strlen($searchDate[1]) == 10) {
                        $searchDate[1] = $endDate->format('Ymd');
                    } else {
                        $date = $endDate->format('d');
                        $searchDate[1] = $endDate->add(new \DateInterval('P1M'))->modify('-' . $date . ' days')->format('Ymd');
                    }
                }
            }

            $sDate = new DateTime($searchDate[0]);
            $eDate = new DateTime($searchDate[1]);
            $dateDiff = date_diff($sDate, $eDate);
            if ($dateDiff->days > 90) {
                $sDate = $eDate->modify('-7 day');
                $searchDate[0] = $sDate->format('Ymd');
                $searchPeriod = 7;
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
            $searchData['type'] = 'daily';
            $searchData['country'] = $searchMall;
            $getDataArr = $visitAnalysis->getVisitTotalData($searchData);
            $topDataArr = $getDataArr['top'];
            $downDataArr = $getDataArr['down'];

            /* top board */
            //$visitTopTotalData = [];
            $visitTopTotalData['visitTotalCnt'] = $topDataArr['total']['visitTotal'];
            $visitTopTotalData['visitCnt'] = $topDataArr['total']['visitCnt'];
            $visitTopTotalData['visitNewCnt'] = $topDataArr['total']['visitNew'];
            $visitTopTotalData['visitReCnt'] = $topDataArr['total']['visitRe'];
            $visitTopPcData['visitTotalCnt'] = $topDataArr['pc']['visitTotal'];
            $visitTopPcData['visitCnt'] = $topDataArr['pc']['visitCnt'];
            $visitTopPcData['visitNewCnt'] = $topDataArr['pc']['visitNew'];
            $visitTopPcData['visitReCnt'] = $topDataArr['pc']['visitRe'];
            $visitTopMobileData['visitTotalCnt'] = $topDataArr['mobile']['visitTotal'];
            $visitTopMobileData['visitCnt'] = $topDataArr['mobile']['visitCnt'];
            $visitTopMobileData['visitNewCnt'] = $topDataArr['mobile']['visitNew'];
            $visitTopMobileData['visitReCnt'] = $topDataArr['mobile']['visitRe'];
            // �Ϻ� �湮��Ȳ ��
            $this->setData('visitTopTotalData', $visitTopTotalData);
            $this->setData('visitTopPcData', $visitTopPcData);
            $this->setData('visitTopMobileData', $visitTopMobileData);

            /* chart */
            $visitDate = array_keys($downDataArr);
            $visitCnt = array_column($downDataArr, 'visitCnt');
            $visitNumberCnt = array_column($downDataArr, 'visitTotal');
            $visitNewCnt = array_column($downDataArr, 'visitNew');
            $visitReCnt = array_column($downDataArr, 'visitRe');
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
                $visitChart['NewCount'] = array_merge($emptyArr, $visitChart['NewCnt']);
                $visitChart['ReCount'] = array_merge($emptyArr, $visitChart['ReCnt']);
            }
            $this->setData('visitChart', $visitChart);

            /* table */
            $i = 0;
            foreach ($downDataArr as $key => $val) {
                $returnVisitStatistics[$i]['visitDate'] = substr($key,0,4) . '-' . substr($key,4,2) . '-' . substr($key,-2);
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

        /* script */
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
