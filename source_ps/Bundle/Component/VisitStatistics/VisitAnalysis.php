<?php

namespace Bundle\Component\VisitStatistics;

use Bundle\Component\Page\Page;
use Component\Mall\Mall;
use DateTime;
use Framework\Http\GuzzleHttpClient;
use Framework\Utility\DateTimeUtils;
use Framework\StaticProxy\Proxy\Session;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Origin\Service\ApiClient\NHNCommerceApi\AdminApi\Statistics\AnalyticsApiClient;
use Request;

class VisitAnalysis
{
    protected $db;

    /**
     * VisitSAnalysis constructor.
     *
     * @param null $date YYYY-mm-dd
     */
    public function __construct($date = null)
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }

        $globals = \App::getInstance('globals');

        $this->license = $globals->get('gLicense');
        $this->shopSno = $this->license['godosno'];
    }

    /**
     * 방문자 분석 - 전체 및 검색 상점 검색 일별 방문현황 데이터
     *
     * @param array $searchDate [0]시작일,[1]종료일
     *
     * @return array $getDataArr 일별 방문현황 정보
     *
     * @throws \Exception
     * @author sueun-choi
     */
    public function getVisitTotalData($searchData)
    {
        // search 날짜 세팅 YYYY-MM-DD 00:00:00.000000
        $sDate = new DateTime($searchData['date'][0]);
        $eDate = new DateTime($searchData['date'][1]);
        $dateDiff = date_diff($sDate, $eDate);

        if ($searchData['date'][0] > $searchData['date'][1]) {
            throw new \Exception(__('시작일이 종료일보다 클 수 없습니다.'));
        }

        if ($searchData['device'] != 'all') {
            if ($searchData['device'] == 'pc') {
                $searchData['device'] = 'P';
            } else {
                $searchData['device'] = 'M';
            }
        } else {
            unset($searchData['device']);
        }

        if ($searchData['country'] == 1) {
            $searchData['country'] = 'kr';
        } elseif ($searchData['country'] == 2) {
            $searchData['country'] = 'en';
        } elseif ($searchData['country'] == 3) {
            $searchData['country'] = 'cn';
        } elseif ($searchData['country'] == 4) {
            $searchData['country'] = 'jp';
        } else {
            unset($searchData['country']);
        }

        if (empty($searchData['inflow']) === false && $searchData['inflow'] != 'all') {
            $searchData['engine'] = $searchData['inflow'];
        }
        unset($searchData['inflow']);

        // 상단 요약_일별 방문 현황
        $getDataJson['top'] = $this->getVisitAnalysisDataByApi('top', $searchData);

        // 차트, 하단 table
        $getDataJson['down'] = $this->getVisitAnalysisDataByApi('down', $searchData);

        return $getDataJson;
    }

    /**
     * 메인 대시보드용 방문자 일별 통계 데이터 조회
     *
     * @param array $searchData ['param' => [...], 'menuCode' => ''] 형식
     *
     * @return array API 응답 데이터
     */
    public function getVisitTotalDataByMain($searchData)
    {
        $analyticsApiClient = \App::getInstance(AnalyticsApiClient::class);

        // 상단 요약_일별 방문 현황
        return $analyticsApiClient->getVisitAnalysisStatistics('/visitors/day', $searchData);
    }

    /**
     * 방문현황 상단요약, 하단 정보 출력(일별, 시간대별, 요일별, 월별, 페이지뷰, 유입, 유입검색어)
     *
     * @param Mixed $searchData
     *
     * @return array 방문통계 정보
     *
     * @author sueun-choi
     */
    public function getVisitAnalysisDataByApi($section, $searchData)
    {
        $searchData['startDate'] = DateTimeUtils::dateFormat('Y-m-d', $searchData['date'][0]);
        $searchData['endDate'] = DateTimeUtils::dateFormat('Y-m-d', $searchData['date'][1]);
        unset($searchData['date']);

        if ($section == 'top') {
            // 개발
            // 방문자 분석(일, 시간대, 요일, 월, 페이지뷰 별 현황)
            if($searchData['type'] != 'keyword') {
                $returnData = $this->_connectCurl(self::getGodoStatisticApiUrl('/summary/'), $searchData);
            }
        } elseif ($section == 'down') {
            // 개발
            if ($searchData['type'] == 'inflow' || $searchData['type'] == 'keyword') {
                $returnData = $this->_connectCurl(self::getGodoStatisticApiUrl('/keyword/'), $searchData);
            } else {
                $returnData = $this->_connectCurl(self::getGodoStatisticApiUrl('/term/'), $searchData);
            }
        }

        return $returnData;

    }

    /**
     * Curl Connect
     *
     * @param string       $curlUrl            url
     * @param string|array $curlData           CURLOPT_POSTFIELDS DATA
     *
     * @return array
     */
    private function _connectCurl($curlUrl, $curlData)
    {
        $curlConnection = curl_init();
        curl_setopt($curlConnection, CURLOPT_URL, $curlUrl . $this->shopSno . '?' . http_build_query($curlData));
        curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlConnection, CURLOPT_SSL_VERIFYPEER, false);
        $responseData = curl_exec($curlConnection);
        curl_close($curlConnection);

        $result = json_decode($responseData, true);
        if ($result['msg'] == 'complete') {
            return $result['data'];
        } else {
            //로그 예시
            \Logger::channel('http')->info('GODO_VISIT_ANALYSIS_SERVER_ERROR', $result['code']);
        }
    }

    /**
     * 방문자통계 - 메인 테이블 / 차트
     * getDayMainTableChartVisit
     *
     * @param $searchDate visitYMD
     *
     * @return array
     * @throws \Exception
     */
    public function getDayMainTableChartVisit($searchDate)
    {
        $searchDate['type'] = 'daily';
        $dayData = $this->getVisitTotalData($searchDate);
        $searchDate['type'] = 'pageview';
        $pvData = $this->getVisitTotalData($searchDate);

        $returnData['visit']['total'] = $dayData['top']['total']['visitTotal'];
        $returnData['visit']['pc'] = $dayData['top']['pc']['visitTotal'];
        $returnData['visit']['mobile'] = $dayData['top']['mobile']['visitTotal'];
        $returnData['visit']['data'] = $dayData['down'];

        $returnData['pv']['total'] = $pvData['top']['total']['pvTotal'];
        $returnData['pv']['pc'] = $pvData['top']['pc']['pvTotal'];
        $returnData['pv']['mobile'] = $pvData['top']['mobile']['pvTotal'];
        $returnData['pv']['data'] = $pvData['down'];

        return $returnData;
    }

    /**
     * 메인 탭용 일별 방문자 통계 조회
     * 지정된 기간의 전체 방문자 수를 조회하여 반환
     *
     * @param array $searchData 검색 조건

     * @return int 해당 기간의 전체 방문자 수
     * @throws \Exception
     */
    public function getDayMainTabVisit(array $searchData): int
    {
        // api 요청할 데이터 가공
        $request = $this->prepareVisitAnalyticsRequestData($searchData['mallSno']); //$searchData['mallSno']에 device 정보가 들어옴
        $request['startDate'] = (new DateTime($searchData['orderYMD'][0]))->format('Y-m-d');
        $request['endDate'] = (new DateTime($searchData['orderYMD'][1]))->format('Y-m-d');

        // 요약 전체 방문자 통계 api 요청
        return $this->getVisitTotalDataByMain($request)['summary']['visit_total'];
    }

    /**
     * Analytics API 요청 데이터 준비
     * 디바이스 필터 및 헤더 정보를 설정하여 API 요청에 필요한 데이터를 구성
     *
     * @param string $device 디바이스 타입 ('all', 'pc', 'mobile', 'myApp')
     * @return array 요청 파라미터
     */
    public function prepareVisitAnalyticsRequestData(string $device): array
    {
        $requestData = [];

        // device 세팅
        $deviceMap = ['pc' => 'P', 'mobile' => 'M', 'myApp' => 'A'];
        if ($device !== 'all' && array_key_exists($device, $deviceMap)) {
            $requestData['device'] = $deviceMap[$device];
        }

        return $requestData;
    }

    /**
     * 특정 기간의 방문자 애널리틱스 데이터 조회
     * 오늘부터 지정된 일수 전까지의 방문자 통계 데이터를 API로부터 조회
     *
     * @param array $requestData 요청 데이터
     * @param int   $daysBefore 조회할 기간 일수
     * @return array API 응답 데이터
     */
    public function getVisitAnalyticsDataByPeriod(array $requestData, int $daysBefore): array
    {
        $analyticsApiClient = \App::getInstance(AnalyticsApiClient::class);

        // 오늘부터 count이므로 하루 제외
        $daysBefore = $daysBefore - 1;

        // $daysBefore 부터 오늘까지 조회
        $requestData['startDate'] = (new DateTime())->modify('-' . $daysBefore . ' days')->format('Y-m-d');
        $requestData['endDate'] = (new DateTime())->format('Y-m-d');

        return $analyticsApiClient->getVisitAnalysisStatistics('/visitors/day', $requestData);
    }

    /**
     * 메인 대시보드용 방문자 애널리틱스 데이터 처리
     * - 7일 데이터: 일별 상세 데이터(날짜별 방문자수, 페이지뷰) 및 합계
     * - 15일, 30일 데이터: 합계만 추출
     *
     * @param array $visitAnalysis API 응답 데이터
     *
     * @return array 포맷팅된 데이터
     */
    public function processMainAnalyticsVisitData(array $visitAnalysis): array
    {
        // 7일 동안의 데이터에 대한 노출될 표 데이터 가공
        $processedVisitAnalysis = [];
        if (isset($visitAnalysis['7Days']['details']) && is_array($visitAnalysis['7Days']['details'])) {
            foreach ($visitAnalysis['7Days']['details'] as $visitAnalysisInfo) {
                $displayDate = DateTimeUtils::dateFormat('m/d', $visitAnalysisInfo['period_value']);
                $processedVisitAnalysis['7DaysVisit'][$displayDate]['visitCount'] = $visitAnalysisInfo['visit_total'] ?? 0;
                $processedVisitAnalysis['7DaysPv'][$displayDate]['pv'] = $visitAnalysisInfo['pv_total'] ?? 0;
            }
        }

        // 7/15/30일 동안의 데이터에 대한 합계
        $periodsToProcess = ['7Days', '15Days', '30Days'];
        foreach ($periodsToProcess as $periodKey) {
            if (isset($visitAnalysis[$periodKey]['summary'])) {
                $summaryData = $visitAnalysis[$periodKey]['summary'];
                $processedVisitAnalysis["{$periodKey}Total"]['visitCount'] = $summaryData['visit_total'] ?? 0;
                $processedVisitAnalysis["{$periodKey}Total"]['pv'] = $summaryData['pv_total'] ?? 0;
            }
        }

        return $processedVisitAnalysis;
    }

    /**
     * 통계 수집 서버 API URL
     * 
     * @param string|null $suffix URL에 추가할 접미사
     * 
     * @return string
     */
    public static function getCollectorStatisticsApiUrl(string $suffix = null): string
    {
        $baseUrl = \App::isProduction()
            ? 'https://collector-statistics.nhn-commerce.com'
            : 'https://alpha-collector-statistics.nhn-commerce.com';
        return $baseUrl . $suffix;
    }

    /**
     * 방문자 분석 API URL
     * 
     * @param string|null $suffix URL에 추가할 접미사
     * 
     * @return string
     */
    public static function getGodoStatisticApiUrl(string $suffix = null): string
    {
        $baseUrl = \App::isProduction()
            ? 'https://godo-api.godo.co.kr/godomall5/statistic'
            : 'https://alpha-godo-api.godo.co.kr/godomall5/statistic';
        return $baseUrl . $suffix;
    }

}
