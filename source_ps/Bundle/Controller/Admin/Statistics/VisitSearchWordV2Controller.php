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
 * 방문자분석 v2 > 방문자 경로분석 > 유입검색어 현황
 * @author sueun-choi
 */
class VisitSearchWordV2Controller extends \Controller\Admin\Controller
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
            $mallSno = gd_isset($mallSno, DEFAULT_MALL_NUMBER);

            $searchDevice = Request::get()->get('searchDevice');
            if (!$searchDevice) {
                $searchDevice = 'all';
            }
            $searchInflow = Request::get()->get('searchInflow');
            if (!$searchInflow) {
                $searchInflow = 'all';
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
            $checked['searchInflow'][$searchInflow] = 'selected="selected"';
            $active['searchPeriod'][$searchPeriod] = 'active';
            $this->setData('searchDate', $searchDate);
            $this->setData('checked', $checked);
            $this->setData('active', $active);

            $searchData['date'] = $searchDate;
            $searchData['device'] = $searchDevice;
            $searchData['type'] = 'keyword';
            $searchData['inflow'] = $searchInflow;
            $getDataArr = $visitAnalysis->getVisitTotalData($searchData)['down'];

            $i = 0;
            $totalSearchWord = count($getDataArr);
            foreach ($getDataArr as $key => $val) {
                $returnSearchStatistics[$i]['searchWord'] = $val['keyword'];
                $returnSearchStatistics[$i]['searchCount'] = ($val['count'] == null) ? '0' : $val['count'];
                $returnSearchStatistics[$i]['searchPercent'] = $val['ratio'];
                $i++;
            }

            if ($totalSearchWord > 20) {
                $rowDisplay = 20;
            } else if ($totalSearchWord == 0) {
                $rowDisplay = 5;
            } else {
                $rowDisplay = $totalSearchWord;
            }
        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }

        $this->setData('rowList', json_encode($returnSearchStatistics));
        $this->setData('visitCount', $totalSearchWord);
        $this->setData('rowDisplay', $rowDisplay);

        $this->addScript(
            [
                'backbone/backbone-min.js',
                'tui/code-snippet.min.js',
                'tui.grid/grid.min.js',
            ]
        );
        $this->addCss(
            [
                'tui.grid/grid.css',
            ]
        );
    }
}
