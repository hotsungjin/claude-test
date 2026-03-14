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
use DateTime;
use Framework\Debug\Exception\AlertBackException;
use Request;

/**
 * 방문자분석 v2 > 방문자 경로분석 > 검색유입 현황
 * @author sueun-choi
 */
class VisitInflowV2Controller extends \Controller\Admin\Controller
{
    /**
     * index
     *
     * @throws \Exception
     */
    public function index()
    {
        $this->callMenu('statistics', 'visitV2', 'visitInflowV2');

        $visitAnalysis = new VisitAnalysis();

        try {
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

            $checked['searchPeriod'][$searchPeriod] = 'checked="checked"';
            $checked['searchDevice'][$searchDevice] = 'selected="selected"';
            $active['searchPeriod'][$searchPeriod] = 'active';
            $this->setData('searchDate', $searchDate);
            $this->setData('checked', $checked);
            $this->setData('active', $active);

            $searchData['date'] = $searchDate;
            $searchData['device'] = $searchDevice;
            $searchData['type'] = 'inflow';
            $getDataArr = $visitAnalysis->getVisitTotalData($searchData);
            foreach($getDataArr as $tKey => $tVal){
                if ($tVal['top']['total']['searchEngine'] == 'naver') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '네이버';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'naver') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '네이버';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'naver') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '네이버';
                }

                if ($tVal['top']['total']['searchEngine'] == 'daum') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '다음';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'daum') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '다음';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'daum') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '다음';
                }

                if ($tVal['top']['total']['searchEngine'] == 'kakao') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '카카오';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'kakao') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '카카오';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'kakao') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '카카오';
                }

                if ($tVal['top']['total']['searchEngine'] == 'google') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '구글';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'google') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '구글';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'google') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '구글';
                }

                if ($tVal['top']['total']['searchEngine'] == 'nate') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '네이트';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'nate') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '네이트';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'nate') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '네이트';
                }

                if ($tVal['top']['total']['searchEngine'] == 'bing') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '빙';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'bing') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '빙';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'bing') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '빙';
                }

                if ($tVal['top']['total']['searchEngine'] == 'etc') {
                    $getDataArr[$tKey]['top']['total']['searchEngine'] = '기타';
                }
                if ($tVal['top']['pc']['searchEngine'] == 'etc') {
                    $getDataArr[$tKey]['top']['pc']['searchEngine'] = '기타';
                }
                if ($tVal['top']['mobile']['searchEngine'] == 'etc') {
                    $getDataArr[$tKey]['top']['mobile']['searchEngine'] = '기타';
                }

                if($tKey == 'down'){
                    foreach($tVal as $dDate => $dVal){
                        $getDataArr[$tKey][$dDate]['네이버'] = $dVal['inflow_naver'];
                        $getDataArr[$tKey][$dDate]['다음'] = $dVal['inflow_daum'];
                        $getDataArr[$tKey][$dDate]['카카오'] = $dVal['inflow_kakao'];
                        $getDataArr[$tKey][$dDate]['구글'] = $dVal['inflow_google'];
                        $getDataArr[$tKey][$dDate]['네이트'] = $dVal['inflow_nate'];
                        $getDataArr[$tKey][$dDate]['빙'] = $dVal['inflow_bing'];
                        $getDataArr[$tKey][$dDate]['기타'] = $dVal['inflow_etc'];
                        unset($getDataArr[$tKey][$dDate]['inflow_naver']);
                        unset($getDataArr[$tKey][$dDate]['inflow_daum']);
                        unset($getDataArr[$tKey][$dDate]['inflow_kakao']);
                        unset($getDataArr[$tKey][$dDate]['inflow_google']);
                        unset($getDataArr[$tKey][$dDate]['inflow_nate']);
                        unset($getDataArr[$tKey][$dDate]['inflow_bing']);
                        unset($getDataArr[$tKey][$dDate]['inflow_etc']);
                    }
                }
            }
            $topDataArr = $getDataArr['top'];
            $downDataArr = $getDataArr['down'];

            $pcTopData['inflow'] = $topDataArr['pc']['inflow'];
            $pcTopData['inflowTotal'] = $topDataArr['pc']['inflowTotal'];
            $pcTopData['inflowRatio'] = $topDataArr['pc']['inflowRatio'];
            $mobileTopData['inflow'] = $topDataArr['mobile']['inflow'];
            $mobileTopData['inflowTotal'] = $topDataArr['mobile']['inflowTotal'];
            $mobileTopData['inflowRatio'] = $topDataArr['mobile']['inflowRatio'];
            $totalTopData['inflow'] = $topDataArr['total']['inflow'];
            $totalTopData['inflowTotal'] = $topDataArr['total']['inflowTotal'];
            $totalTopData['inflowRatio'] = $topDataArr['total']['inflowRatio'];
            $pcTopSearchData['date'] = $topDataArr['top']['pc']['date'];
            $pcTopSearchData['count'] = $topDataArr['top']['pc']['count'];
            $pcTopSearchData['searchEngine'] = $topDataArr['top']['pc']['searchEngine'];
            $mobileTopSearchData['date'] = $topDataArr['top']['mobile']['date'];
            $mobileTopSearchData['count'] = $topDataArr['top']['mobile']['count'];
            $mobileTopSearchData['searchEngine'] = $topDataArr['top']['mobile']['searchEngine'];
            $totalTopSearchData['date'] = $topDataArr['top']['total']['date'];
            $totalTopSearchData['count'] = $topDataArr['top']['total']['count'];
            $totalTopSearchData['searchEngine'] = $topDataArr['top']['total']['searchEngine'];

            $this->setData('pcTopData', $pcTopData);
            $this->setData('mobileTopData', $mobileTopData);
            $this->setData('totalTopData', $totalTopData);
            $this->setData('pcTopSearchData', $pcTopSearchData);
            $this->setData('mobileTopSearchData', $mobileTopSearchData);
            $this->setData('totalTopSearchData', $totalTopSearchData);

            /* chart */
            foreach ($downDataArr as $key => $val) {
                $searchChart['Date'][] = "'" . $key . "'";
                foreach ($val as $inflowKey => $inflowVal) {
                    $searchChart[$inflowKey][] = $inflowVal;
                }
            }
            if (count($searchChart['Date']) < 2) {
                $emptyDateArr = ['0' => "'0'"];
                $emptyArr = ['0' => 0];
                $visitChart['Date'] = array_merge($emptyDateArr, $searchChart['Date']);
                foreach ($searchChart as $key => $val) {
                    if ($key != 'Date') {
                        $searchChart[$key] = array_merge($emptyArr, $searchChart[$key]);
                    }
                }
            }
            foreach ($searchChart as $key => $val) {
                if ($key != 'Date') {
                    $sort[$key] = array_sum($val);
                }
            }
            arsort($sort);
            $deviceTotal = array_sum($sort);
            //$this->setData('searchChart', $searchChart);

            /* table */
            $i = 0;
            foreach ($sort as $key => $val) {
                $inflowTitle[] = $key;
                $returnInflowStatistics[$i]['searchTool'] = $key;
                $returnInflowStatistics[$i]['searchCount'] = $val;
                $returnInflowStatistics[$i]['searchPercent'] = round($val / $deviceTotal * 100, 2);
                $i++;
            }

            $inflowCount = count($returnInflowStatistics);

            if ($inflowCount > 20) {
                $rowDisplay = 20;
            } else if ($inflowCount == 0) {
                $rowDisplay = 5;
            } else {
                $rowDisplay = $inflowCount;
            }
        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }

        $this->setData('rowList', json_encode($returnInflowStatistics));
        $this->setData('inflowCount', $inflowCount);
        $this->setData('rowDisplay', $rowDisplay);
        $this->setData('searchChart', $searchChart);
        $this->setData('inflowTitle', $inflowTitle);

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
