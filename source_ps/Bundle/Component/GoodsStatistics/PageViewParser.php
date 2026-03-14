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

namespace Bundle\Component\GoodsStatistics;

use DateTime;
use Exception;

/**
 * Class PageViewParser
 * @package Bundle\Component\GoodsStatistics
 * @author  yjwee
 */
class PageViewParser
{
    private $logDate;
    private $logFile;
    private $siteKeys;
    private $startPageViews;
    private $endPageViews;
    private $pageViews;

    const NO_LOG_FILE = -1;
    /**
     * 생성자
     *
     * @param null $callDate
     */
    public function __construct($callDate = null)
    {
        if ($callDate == null) {
            // 전달받은 파라미터가 없는 경우 오늘을 기준으로 -1일된 파일을 대상으로 지정
            $date = new \DateTime();
            $date->modify('-1days');
            $this->logDate = $date->format('Y-m-d');
        } else {
            // 전달받은 파라미터가 있는 경우 파라미터 기준으로 -1일된 파일을 대상으로 지정
            $date = new \DateTime(explode(' ', $callDate)[0]);
            $date->modify('-1days');
            $this->logDate = $date->format('Y-m-d');
        }
    }


    /**
     * loadFile
     *
     * @return $this
     * @throws Exception
     */
    public function loadFile(): PageViewParser
    {
        $basicDomain = str_replace('_', '.', USER_FOLDER_NAME);
        $basicPath = sprintf("%s%s%s-%s", SYSTEM_LOG_PATH, DS, $basicDomain, $this->logDate);

        $this->logFile = $this->getLogContents($basicPath);
        if ($this->logFile === null) {
            throw new Exception(__('로그파일이 존재하지 않습니다.') . ' filePath=' . $basicPath . '.{log|gz}');
        }

        return $this;
    }

    /**
     * @param string $basicPath 로그 파일 기본 path
     *
     */
    public function getLogContents(string $basicPath)
    {
        $logFilePath = sprintf("%s.log", $basicPath);
        if (file_exists($logFilePath)) {
            return $this->loadFileByExtension($logFilePath);
        }

        $logFilePath = sprintf("%s.gz", $basicPath);
        if (file_exists($logFilePath)) {
            return $this->loadFileByExtension($logFilePath);
        }

        // @Deprecated 2024-06-26 이전 데이터
        $request = \App::getInstance('request');
        $serverIp = $request->getServerAddress();
        $logFilePath = SYSTEM_LOG_PATH . DS . 'pageView' . DS . $serverIp . '-pageView-' . $this->logDate . '.log';
        if (file_exists($logFilePath)) {
            return file_get_contents($logFilePath);
        }

        return null;
    }

    /**
     * @param string $logFilePath 파일 full path
     *
     * @return string 페이지뷰 분석 포맷에 맞게 가공 완료된 내용
     */
    public function loadFileByExtension(string $logFilePath): string
    {
        $fileExtension = pathinfo($logFilePath, PATHINFO_EXTENSION);
        switch ($fileExtension) {
            case 'gz':
                return $this->getPageViewContentsWithArchiveGz($logFilePath);
                break;
            case 'log':
                return $this->getPageViewContents($logFilePath);
                break;
            default:
                return "";
        }
    }

    /**
     * @param string $logMessage 파싱 전 내용
     *
     * @return string 파싱 후 내용
     */
    public function extractPageViewLogs(string $logMessage): string
    {
        $regexPattern = '/\[pageView] \[INFO\]/';
        if (! preg_match($regexPattern, $logMessage)) {
            return "";
        }

        // 정규 표현식 패턴에 맞는 라인을 찾았을 때, 필요한 데이터 추출 및 포맷 변경
        $formattedData = '';
        preg_match('/^\[(.*?)\] \[(.*?)\] \: (.*?)\|\/(.*?)\|(.*?) \[\] (.*)$/', $logMessage, $matches);
        if (count($matches) == 7) {
            // 새로운 포맷으로 데이터 조합
            $pageViewContents = [
                $matches[1],
                $matches[3],
                $matches[4],
                $matches[5],
            ];
            // timpestamp|uuid|path|count
            $formattedData = implode("|", $pageViewContents) . "\n";
        }

        return $formattedData;
    }

    /**
     * oom 방지를 위해 로그를 한번에 한줄씩 읽어 처리
     * @param string $gzLogFilename 파일 full path
     *
     * @return string 파일 내용
     */
    public function getPageViewContentsWithArchiveGz(string $gzLogFilename): string
    {
        $pageViewLog = '';
        $gzFileHandler = gzopen($gzLogFilename, 'r');
        if ($gzFileHandler) {
            while (($fileContents = gzgets($gzFileHandler)) !== false) {
                $pageViewLog .= $this->extractPageViewLogs($fileContents);
            }
            gzclose($gzFileHandler);
        }
        return $pageViewLog;
    }

    /**
     * oom 방지를 위해 로그를 한번에 한줄씩 읽어 처리
     * @param string $logFilename 파일 full path
     *
     * @return string 파일 내용
     */
    public function getPageViewContents(string $logFilename): string
    {
        $pageViewLog = '';
        $fileHandler = fopen($logFilename, 'r');
        if ($fileHandler) {
            while (($fileContents = fgets($fileHandler)) !== false) {
                $pageViewLog .= $this->extractPageViewLogs($fileContents);
            }
            fclose($fileHandler);
        }
        return $pageViewLog;
    }

    public function parseLog()
    {
        $this->parseRows();
        $this->parseSiteKey();
    }

    public function parseRows()
    {
        if ($this->logFile == null) {
            throw new Exception(__('분석할 로그파일이 없습니다.'));
        }
        $rows = explode("\n", $this->logFile);
        foreach ($rows as $row) {
            $pageView = explode('|', $row);
            $siteKey = $pageView[1];
            if (empty($siteKey)) {
                continue;
            }
            $this->siteKeys[$siteKey][] = $pageView;
        }

        return $this;
    }

    public function parseSiteKey()
    {
        if ($this->siteKeys == null) {
            throw new Exception(__('분석할 정보가 없습니다.'));
        }
        foreach ($this->siteKeys as $siteKey => $pageViews) {
            $this->_parsePageView($pageViews);
        }
    }

    /**
     * @return mixed
     */
    public function getStartPageViews()
    {
        return $this->startPageViews;
    }

    /**
     * @return mixed
     */
    public function getEndPageViews()
    {
        return $this->endPageViews;
    }

    /**
     * @return mixed
     */
    public function getPageViews()
    {
        return $this->pageViews;
    }

    private function _parsePageView($pageViews)
    {
        $this->startPageViews[$pageViews[0][3]][$pageViews[0][2]]++;
        $this->endPageViews[$pageViews[0][3]][end($pageViews)[2]]++;

        $arrayObject = new \ArrayObject($pageViews);
        $it = $arrayObject->getIterator();
        while ($it->valid()) {
            $current = $it->current();
            $it->next();
            $after = $it->current();
            $currentURI = $current[2];
            if ($after == null) {
                $this->pageViews[$currentURI]['viewSeconds']++;
                $this->pageViews[$currentURI]['viewCount']++;
                $this->pageViews[$currentURI]['viewDate'] = $this->logDate;
                $this->pageViews[$currentURI]['mallSno'] = $current[3];
                break;
            }
            $afterURI = $after[2];
            if ($currentURI == $afterURI) {
                continue;
            }
            $currentDateTime = new DateTime($current[0]);
            $afterDateTime = new DateTime($after[0]);
            $second = $afterDateTime->diff($currentDateTime)->s;
            if ($second < 1) {
                $second = 1;
            }
            $this->pageViews[$currentURI]['viewSeconds'] += $second;
            $this->pageViews[$currentURI]['viewCount']++;
            $this->pageViews[$currentURI]['viewDate'] = $this->logDate;
            $this->pageViews[$currentURI]['mallSno'] = $current[3];
        }
    }

    /**
     * @return string
     */
    public function getLogDate()
    {
        return $this->logDate;
    }
}
