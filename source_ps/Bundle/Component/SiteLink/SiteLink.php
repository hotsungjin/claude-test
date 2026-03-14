<?php
/**
 *
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 *
 */

namespace Bundle\Component\SiteLink;

use App;
use Framework\Http\Response;
use Framework\Utility\UrlUtils;
use Framework\Utility\DateTimeUtils;
use Logger;
use Request;
use Session;

/**
 * 사이트경로 ssl
 * @package Bundle\Component\SiteLink
 * @author  su
 */
class SiteLink
{
    // 현재 접속경로에 따른 admin,front,mobile SSL 정보
    public $sslCfg;

    // 적용 범위 ssl apply limit
    public $_sslApplyLimit;
    // 적용 무료 ssl domain
    public $_freeSslDomain;
    // 적용 ssl domain
    public $_sslDomain;
    // 적용 ssl port
    public $_sslPort;
    // 적용 무료 SSL 페이지
    public $_freeSslRule;
    // 적용 SSL 페이지
    public $_sslRule;
    // 제외 SSL 페이지
    public $_sslExceptRule;
    // 기본 도메인
    public $_regularDomain;

    public $_prefixDir;

    // 디비 접속
    /** @var \Framework\Database\DBTool $db */
    protected $db;

    // 무료 SSL
    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }

    }

    public function setSslConfig()
    {
        // 보안서버 기본 적용 페이지
        $baseSslRule = App::getConfig('host.ssl');
        $baseSslRule = $baseSslRule->toArray();

        // 보안서버 설정
        $ssl = \App::load('\\Component\\SiteLink\\SecureSocketLayer');

        // admin|front|mobile 체크
        $controller = App::getController();
        $rootDirectory = \App::getInstance('ControllerNameResolver')->getControllerRootDirectory();

        $basicDomain = Request::getHost();
        $mallFl = SESSION::get(SESSION_GLOBAL_MALL.'.domainFl');
        gd_isset($mallFl, 'kr');
        if ($rootDirectory === 'admin') {
            $position = 'admin';
            $searchSslArrData = [
                'sslConfigDomain' => $basicDomain,
                'sslConfigPosition' => $position,
                'sslConfigMallFl' => $mallFl,
            ];
            $this->sslCfg = $ssl->getRedirectSsl($searchSslArrData);
            $this->_prefixDir = rtrim(URI_ADMIN, DS);
            $this->_sslApplyLimit = 'y';
            $this->_sslExceptRule = $baseSslRule['exceptSslAdmin'];
        } else if ($rootDirectory === 'api') {
            $position = 'api';
            $searchSslArrData = [
                'sslConfigDomain' => $basicDomain,
                'sslConfigPosition' => $position,
                'sslConfigMallFl' => $mallFl,
            ];
            $this->sslCfg = $ssl->getRedirectSsl($searchSslArrData);
            $this->_prefixDir = rtrim(URI_API, DS);
            $this->_sslApplyLimit = 'y';
        } else if ($rootDirectory === 'front') {
            $position = 'pc';
            $searchSslArrData = [
                'sslConfigDomain' => $basicDomain,
                'sslConfigPosition' => $position,
                'sslConfigMallFl' => $mallFl,
            ];
            $this->sslCfg = $ssl->getRedirectSsl($searchSslArrData);
            $this->_sslRule = $baseSslRule['pcPay'];
            $this->_freeSslRule = $baseSslRule['pcFree'];
            $this->_prefixDir = rtrim(URI_HOME, DS);
            $this->_sslApplyLimit = $this->sslCfg['sslConfigApplyLimit'];
            $this->_sslExceptRule = array_merge($baseSslRule['exceptSsl'], $baseSslRule['exceptSslPayment']);
        } else if ($rootDirectory === 'mobile') {
            $position = 'mobile';
            $searchSslArrData = [
                'sslConfigDomain' => $basicDomain,
                'sslConfigPosition' => $position,
                'sslConfigMallFl' => $mallFl,
            ];
            $this->sslCfg = $ssl->getRedirectSsl($searchSslArrData);
            $this->_sslRule = $baseSslRule['mobilePay'];
            $this->_prefixDir = rtrim(URI_MOBILE, DS);
            $this->_sslApplyLimit = $this->sslCfg['sslConfigApplyLimit'];
            $this->_sslExceptRule = array_merge($baseSslRule['exceptSsl'], $baseSslRule['exceptSslPayment']);
        }

        // 보안서버 적용 페이지 추가
        if (empty($this->sslCfg['sslConfigUserRule']) === false && $this->sslCfg['sslConfigApplyLimit'] === 'n') {
            $tmp = explode(',', $this->sslCfg['sslConfigUserRule']);
            foreach ($tmp as $v) {
                array_push($this->_sslRule, '~^' . $v . '~');
            }
        }

        $nDate = date('Y-m-d H:i:s');
        $sDateCheck = DateTimeUtils::intervalDay($this->sslCfg['sslConfigStartDate'], $nDate);
        $eDateCheck = DateTimeUtils::intervalDay($nDate, $this->sslCfg['sslConfigEndDate']);
        if (($this->sslCfg['sslConfigType'] == 'godo' || $this->sslCfg['sslConfigType'] == 'free_multi') && $sDateCheck >= 0 && $eDateCheck >= 0 && empty($this->sslCfg['sslConfigDomain']) === false) {
            // 유료보안서버
            $this->_sslDomain = $this->sslCfg['sslConfigDomain'];
            $this->_sslPort = $this->sslCfg['sslConfigPort'];
            $this->_regularDomain = $this->sslCfg['sslConfigDomain'];
        } else if ($this->sslCfg['sslConfigType'] == 'free' && empty($this->sslCfg['sslConfigDomain']) === false) {
            // 무료보안서버
            $this->_freeSslDomain = $this->sslCfg['sslConfigDomain'];
            $this->_sslDomain = $this->sslCfg['sslConfigSuperDomain']; // 대표 도메인으로 리다이렉트 용
            if (empty(Request::get()->get('rd')) === false) {
                $this->_regularDomain = Request::get()->get('rd');
            } else {
                $this->_regularDomain = Request::getServerName();
            }
        } else {
            $this->_regularDomain = Request::getServerName();
        }

        gd_isset($this->_regularDomain, Request::getServerName());
    }

    /**
     * 사이트경로보정
     * 현재 페이지를 기준으로 인자로받은 URL의 경로를 알려 줍니다
     *
     * @param  string $semanticAbsoluteUrl URL
     *
     * @return string URL
     */
    public function link($semanticAbsoluteUrl)
    {
        $this->setSslConfig();
        $parseUrl = parse_url($semanticAbsoluteUrl);
        $url = strstr($parseUrl['path'], '/');
        $query = $parseUrl['query'];
        if ($query) {
            $semanticAbsoluteUrl = $url . '?' . $query;
        } else {
            $semanticAbsoluteUrl = $url;
        }
        $semanticAbsoluteUrl = substr($semanticAbsoluteUrl, 1);

        $request = \App::getInstance('request');
        if ($request->hasGlobalMallSubDomain()) {
            $mallName = $request->getMallNameByUri();
            if (empty($mallName)) {
                $mallName = $request->getMallNameByReferer();
            }
            $sslUrl = $this->sslUrlBuilder($semanticAbsoluteUrl);
            if (UrlUtils::hasSubDirectory($sslUrl, $mallName) === false) {
                $sslUrl = UrlUtils::appendSubDirectory($sslUrl, $mallName);
            }

            return $sslUrl;
        } else {
            return $this->sslUrlBuilder($semanticAbsoluteUrl);
        }
    }

    /**
     * 인자로 받은 URL을 SSL 정책에 따라 변경된 URL 로 반환한다.
     *
     * @param $semanticAbsoluteUrl
     *
     * @return string
     */
    protected function sslUrlBuilder($semanticAbsoluteUrl)
    {
        $this->setSslConfig();
        if (empty($this->_freeSslDomain) === false) {
            // 무료보안서버
            if (empty(Request::server()->get('HTTPS')) === false) {
                if ($this->isFreeSslUrl($semanticAbsoluteUrl) === true) {
                    return $this->_prefixDir . DS . $semanticAbsoluteUrl;
                } else {
                    return $this->getFullUrl('http', $this->_regularDomain, 21, DS . $semanticAbsoluteUrl);
                }
            } else {
                if ($this->isFreeSslUrl($semanticAbsoluteUrl) === true) {
                    $ampersand = (preg_match('~\?$~', $semanticAbsoluteUrl) == 1 ? '&' : '');
                    $semanticAbsoluteUrl = preg_replace('~\?$~', '', $semanticAbsoluteUrl);
                    $ar_parsed = [];
                    // 정식도메인과 무료도메인의 세션공유를 위한 전달
                    $ar_parsed['sess_id'] = session_id();
                    // 정식도메인(접속도메인)으로 돌리기 위한 전달
                    $ar_parsed['rd'] = $this->_regularDomain;

                    // 무료보안서버 포트 는 항상 443임
                    return $this->getFullUrl('https', $this->_freeSslDomain, 443, DS . $semanticAbsoluteUrl . '?' . http_build_query($ar_parsed) . $ampersand);
                } else {
                    return $this->_prefixDir . DS . $semanticAbsoluteUrl;
                }
            }
        } else {
            // 일반페이지
            if (Request::isSecure()) {
                return $this->getFullUrl('https', $this->_regularDomain, 443, DS . $semanticAbsoluteUrl);
            } else {
                return $this->getFullUrl('http', $this->_regularDomain, 80, DS . $semanticAbsoluteUrl);
            }
        }
    }

    /**
     * 리다이렉트(Start Method)
     * SSL도메인 외 접속시 Redirect, 보안적용 Reload
     */
    public function readyRedirect()
    {
        $this->setSslConfig();
        $semanticAbsoluteUrl = Request::getPhpSelf();
        $semanticAbsoluteUrl = substr($semanticAbsoluteUrl, 1);
        if (empty($this->_sslDomain) === false) {
            if (empty($this->_freeSslDomain) === false && $this->isFreeSslUrl($semanticAbsoluteUrl) === true) {
            } else {
                // 유료보안 SSL도메인 외 도메인 접속시 Redirect (다른 도메인으로 접근했을 경우 처리)
                // exceptSsl 경로(PG 입금통보 등)는 리다이렉트 제외 - POST 데이터 손실 방지
                if ((Request::getDefaultHost() != $this->getDefaultSslDomain($this->_sslDomain)) && !Request::isAjax()) {
                    if ($this->isExceptSslUrl($semanticAbsoluteUrl)) {
                        $this->logExceptSslSkip($semanticAbsoluteUrl, 'domainRedirect');
                    } else {
                        $refreshUrl = $this->getFullUrl('http', $this->_sslDomain, 80, Request::getRequestUri());
                        http_response_code(Response::HTTP_TEMPORARY_REDIRECT);
                        header('Location: ' . $refreshUrl);
                        exit();
                    }
                }
            }

            if (empty($this->_freeSslDomain) === true) {
                if ($this->_sslApplyLimit == 'y') {
                    // ssl 제외 경로 처리 - exceptSsl 페이지는 프로토콜 무관하게 통과
                    if ($this->isExceptSslUrl($semanticAbsoluteUrl)) {
                        $this->logExceptSslSkip($semanticAbsoluteUrl, 'applyLimit=y');
                    } else {
                        if (!Request::isSecure()) {
                            $useSameSite = \Session::getHandler()->isUseChrome80();
                            if ($useSameSite === true) {
                                \Cookie::del('GD5SESSID');
                            }
                            $refreshUrl = $this->getFullUrl('https', $this->_sslDomain, $this->_sslPort, Request::getRequestUri());
                            http_response_code(Response::HTTP_TEMPORARY_REDIRECT);
                            header('Location: ' . $refreshUrl);
                            exit();
                        } else {
                            // 보안서버 적용되는 페이지가 보안서버 url 로 온 경우는 통과
                        }
                    }
                } else if ($this->_sslApplyLimit != 'y') {
                    // ssl 제외 경로 처리 - exceptSsl 페이지는 프로토콜 무관하게 통과
                    if ($this->isExceptSslUrl($semanticAbsoluteUrl)) {
                        $this->logExceptSslSkip($semanticAbsoluteUrl, 'applyLimit!=y');
                    } else { //보안서버 적용url 접근
                        //refrere url에 www가 없으면 제거.
                        $sslDomain = $this->_sslDomain;
                        if (strpos(Request::getReferer(), 'www.') === false) {
                            $sslDomain = str_replace('www.', '', $sslDomain);
                        }
                        // 유료보안적용 Reload (현재 호출된 페이지가 HTTPS프로토콜을 타야하는데 그렇지 않은 경우 처리)
                        if ($this->isSslUrl($semanticAbsoluteUrl) && !Request::isSecure() && !Request::isAjax()) {
                            $refreshUrl = $this->getFullUrl('https', $sslDomain, $this->_sslPort, Request::getRequestUri());
                            http_response_code(Response::HTTP_TEMPORARY_REDIRECT);
                            header('Location: ' . $refreshUrl);
                            exit();
                        } // 보안 서버 적용이 아닌경우 http로 Redirect
                        else if (!$this->isSslUrl($semanticAbsoluteUrl) && Request::isSecure() && !Request::isAjax()) {
                            $refreshUrl = $this->getFullUrl('http', $sslDomain, $this->_sslPort, Request::getRequestUri());
                            http_response_code(Response::HTTP_TEMPORARY_REDIRECT);
                            header('Location: ' . $refreshUrl);
                            exit();
                        }
                    }
                }
            }
        } elseif (Request::isSecure() && !($semanticAbsoluteUrl === 'check_ssl_free.php' || !empty($this->_freeSslDomain) && $this->isFreeSslUrl($semanticAbsoluteUrl) || Request::isHyphenSubdomain()) && Request::getSubdomainDirectory() !== 'front') {
            $refreshUrl = $this->getFullUrl('http', $this->_regularDomain, 80, Request::getRequestUri());
            http_response_code(Response::HTTP_TEMPORARY_REDIRECT);
            header('Location: ' . $refreshUrl);
            exit();
        }
    }

    /**
     * URL 조합
     *
     * @param  string $protocol 프로토콜
     * @param  string $domain   도메인
     * @param  string $port     포트
     * @param  string $uri      URI
     *
     * @return string URL
     */
    protected function getFullUrl($protocol, $domain, $port, $uri)
    {
        if ($protocol == 'https') {
            if ($port == '' || $port == '443') {
                return 'https://' . $domain . $uri;
            } else {
                return 'https://' . $domain . ':' . $port . $uri;
            }
        } else {
            return 'http://' . $domain . $uri;
        }
    }

    /**
     * 유료보안적용 URL 체크
     * 인자로 받은 url이 보안서버를 사용해야하는지 체크합니다
     *
     * @param  string $semanticAbsoluteUrl URL
     *
     * @return bool
     */
    protected function isSslUrl($semanticAbsoluteUrl)
    {
        foreach ($this->_sslRule as $each_rule) {
            if (preg_match($each_rule, $semanticAbsoluteUrl) == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * 무료보안적용 URL 체크
     * 인자로 받은 url이 보안서버를 사용해야하는지 체크합니다
     *
     * @param  string $semanticAbsoluteUrl URL
     *
     * @return bool
     */
    protected function isFreeSslUrl($semanticAbsoluteUrl)
    {
        foreach ($this->_freeSslRule as $each_rule) {
            if (preg_match($each_rule, $semanticAbsoluteUrl) == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * 보안적용 제외 URL 체크
     * 인자로 받은 url이 보안서버를 제외해야하는지 체크합니다
     *
     * @param  string $semanticAbsoluteUrl URL
     *
     * @return bool
     */
    protected function isExceptSslUrl($semanticAbsoluteUrl)
    {
        foreach ($this->_sslExceptRule as $each_rule) {
            if (preg_match($each_rule, $semanticAbsoluteUrl) == 1) {
                return true;
            }
        }

        return false;
    }

    public function getSslImage()
    {
        $this->setSslConfig();
        if (Request::isMobile()) {
            $mobileFl = "_mobile";
        }
        if ($this->sslCfg['sslConfigType'] == 'free') {
            if ($this->sslCfg['sslConfigImageUse'] == 'y') {
                $returnSslImage['use'] = $this->sslCfg['sslConfigImageUse'];
                $returnSslImage['sslImage'] = '<img src="/data/commonimg/logo_kisia'.$mobileFl.'.png" alt="보안서버 적용 확인">';
            }
        } else if ($this->sslCfg['sslConfigType'] == 'godo' || $this->sslCfg['sslConfigType'] == 'free_multi') {
            if ($this->sslCfg['sslConfigImageUse'] == 'y') {
                $returnSslImage['use'] = $this->sslCfg['sslConfigImageUse'];
                if ($this->sslCfg['sslConfigImageType'] == 'globalSignAlpha') {
                    $returnSslImage['sslImage'] = '<img src="/data/commonimg/logo_alpha'.$mobileFl.'.png" alt="보안서버 적용 확인">';
                } else if ($this->sslCfg['sslConfigImageType'] == 'globalSignQuick') {
                    $returnSslImage['sslImage'] = '<img src="/data/commonimg/logo_quick'.$mobileFl.'.png" alt="보안서버 적용 확인">';
                } else if ($this->sslCfg['sslConfigImageType'] == 'comodo') {
                    $returnSslImage['sslImage'] = '<img src="/data/commonimg/logo_comodo'.$mobileFl.'.png" alt="보안서버 적용 확인">';
                }
            }
        }

        return $returnSslImage;
    }

    /**
     * 서브도메인을 제외한 기본 Domain명
     *
     * @return mixed|string
     * @author su <su@godo.co.kr>
     */
    public function getDefaultSslDomain($sslDomain)
    {
        // extract subdomain with www etc.
        $subdomain = Request::getSubdomain($sslDomain);

        // when subdomain is 'www', change 'www' to subdomain.
        if ($subdomain == 'www' || $subdomain == 'gdadmin' || $subdomain == 'api' || $subdomain == 'm') {
            return substr($sslDomain, strlen($subdomain) + 1);
        } else {
            return $sslDomain;
        }
    }

    /**
     * SSL 제외 경로 스킵 시 로그 기록
     *
     * @param string $semanticAbsoluteUrl URL
     * @param string $applyLimitLabel 로그 메시지에 표시할 applyLimit 조건 라벨
     */
    protected function logExceptSslSkip(string $semanticAbsoluteUrl, string $applyLimitLabel)
    {
        $logData = [
            'url' => $semanticAbsoluteUrl,
            'requestUri' => Request::getRequestUri(),
            'sslDomain' => $this->_sslDomain,
            'host' => Request::getHost(),
            'isSecure' => Request::isSecure(),
        ];

        // exceptSsl 경로(PG 입금통보 등)인 경우 POST/GET 데이터 전체를 로그에 기록
        $logData['postData'] = Request::post()->toArray();
        $logData['getData'] = Request::get()->toArray();

        Logger::channel('sslRedirect')->info("ExceptSsl skip redirect - allow https ({$applyLimitLabel})", $logData);
    }
}
