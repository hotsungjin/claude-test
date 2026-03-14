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
 * 방문자분석 v2 > 방문자 분석 > 페이지뷰 방문현황
 * @author sueun-choi
 */
class VisitPageviewV2Controller extends \Controller\Admin\Controller
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
            $searchData['type'] = 'pageview';
            $searchData['country'] = $searchMall;

            $getDataArr = $visitAnalysis->getVisitTotalData($searchData);

            /* top board */
            $pvTopTotalData = [];
            $pvTopTotalData['all']['pvTotal'] = $getDataArr['top']['total']['pvTotal'];
            $pvTopTotalData['all']['pvNew'] = $getDataArr['top']['total']['pvNew'];
            $pvTopTotalData['all']['pvRe'] = $getDataArr['top']['total']['pvRe'];
            $pvTopTotalData['all']['visitTotal'] = $getDataArr['top']['total']['visitTotal'];
            $pvTopTotalData['all']['visitPv'] = $getDataArr['top']['total']['visitPv'];

            $pvTopTotalData['pc']['pvTotal'] = $getDataArr['top']['pc']['pvTotal'];
            $pvTopTotalData['pc']['pvNew'] = $getDataArr['top']['pc']['pvNew'];
            $pvTopTotalData['pc']['pvRe'] = $getDataArr['top']['pc']['pvRe'];
            $pvTopTotalData['pc']['visitTotal'] = $getDataArr['top']['pc']['visitTotal'];
            $pvTopTotalData['pc']['visitPv'] = $getDataArr['top']['pc']['visitPv'];

            $pvTopTotalData['mobile']['pvTotal'] = $getDataArr['top']['mobile']['pvTotal'];
            $pvTopTotalData['mobile']['pvNew'] = $getDataArr['top']['mobile']['pvNew'];
            $pvTopTotalData['mobile']['pvRe'] = $getDataArr['top']['mobile']['pvRe'];
            $pvTopTotalData['mobile']['visitTotal'] = $getDataArr['top']['mobile']['visitTotal'];
            $pvTopTotalData['mobile']['visitPv'] = $getDataArr['top']['mobile']['visitPv'];

            $this->setData('pvTopTotalData', $pvTopTotalData);

            /* chart */
            $pvDate = array_keys($getDataArr['down']);
            $pvCnt = array_column($getDataArr['down'], 'pvTotal');
            $pvNewCnt = array_column($getDataArr['down'], 'pvNew');
            $pvReCnt = array_column($getDataArr['down'], 'pvRe');
            $pvVisitTotal = array_column($getDataArr['down'], 'visitTotal');
            $pvVisit = array_column($getDataArr['down'], 'visitPv');
            foreach ($pvDate as $key => $val) {
                $pvDate[$key] = "'" . $val . "'";
            }
            $pvChart['Date'] = $pvDate;
            $pvChart['pvCnt'] = $pvCnt;
            $pvChart['pvNewCnt'] = $pvNewCnt;
            $pvChart['pvReCnt'] = $pvReCnt;
            $pvChart['pvVisitTotal'] = $pvVisitTotal;
            $pvChart['pvVisit'] = $pvVisit;
            if (count($pvChart['Date']) < 2) {
                $emptyDateArr = ['0' => "'0'"];
                $emptyArr = ['0' => 0];
                $pvChart['Date'] = array_merge($emptyDateArr, $pvChart['Date']);
                $pvChart['pvCnt'] = array_merge($emptyArr, $pvChart['pvCnt']);
                $pvChart['pvNewCnt'] = array_merge($emptyArr, $pvChart['pvNewCnt']);
                $pvChart['pvReCnt'] = array_merge($emptyArr, $pvChart['pvReCnt']);
                $pvChart['pvVisitTotal'] = array_merge($emptyArr, $pvChart['pvVisitTotal']);
                $pvChart['pvVisit'] = array_merge($emptyArr, $pvChart['pvVisit']);
            }
            $this->setData('pvChart', $pvChart);

            /* table */
            $i = 0;
            foreach ($getDataArr['down'] as $key => $val) {
                $returnPvStatistics[$i]['visitDate'] = substr($key,0,4) . '-' . substr($key,4,2) . '-' . substr($key,-2);
                $returnPvStatistics[$i]['pvCnt'] = $val['pvTotal'];
                $returnPvStatistics[$i]['pvNewCnt'] = $val['pvNew'];
                $returnPvStatistics[$i]['pvReCnt'] = $val['pvRe'];
                $returnPvStatistics[$i]['pvVisitTotal'] = $val['visitTotal'];
                $returnPvStatistics[$i]['pvVisit'] = $val['visitPv'];
                $i++;
            }

            $pvCount = count($returnPvStatistics);
            if ($pvCount > 20) {
                $rowDisplay = 20;
            } else if ($pvCount == 0) {
                $rowDisplay = 5;
            } else {
                $rowDisplay = $pvCount;
            }
        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }

        $this->setData('rowList', json_encode($returnPvStatistics));
        $this->setData('pvCount', $pvCount);
        $this->setData('rowDisplay', $rowDisplay);

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