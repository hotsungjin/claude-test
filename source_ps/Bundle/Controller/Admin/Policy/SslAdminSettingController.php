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

namespace Bundle\Controller\Admin\Policy;

use Exception;
use Framework\Debug\Exception\LayerNotReloadException;
use Origin\Service\Member\ManagerLoginService;

/**
 * Class SslPcSettingController
 * @package Bundle\Controller\Admin\Policy
 * @author  Seung-gak Kim <surlira@godo.co.kr>
 */
class SslAdminSettingController extends \Controller\Admin\Controller
{
    public function index()
    {
        // --- 모듈 호출
        $sslAdmin = \App::load('\\Component\\SiteLink\\SecureSocketLayer');
        $requestParam = http_build_query([
            'accessPage' => 'sslIntro',
            'shopNo' => \Globals::get('gLicense.godosno'),
        ]);

        $webSiteSslRequestUrl = $this->getNhnCommerceMainUrl("/ssl-server-install-popup?$requestParam");
        // --- 페이지 데이터
        try {
            $sslData = $sslAdmin->getSslSetting('admin');

            $this->setData('position', 'admin');
            $this->setData('sslData', $sslData);
            $infoMsg[0]['notice-info'] = '관리자 보안서버는 <a href="javascript:gotoGodomall(\'domain\')">마이페이지 > 도메인 연결</a>에서 연결한 대표도메인에만 설정이 가능합니다.';
            $infoMsg[1]['notice-info'] = '보안서버 사용 시 외부 서비스에 저장된 도메인 정보를 https://~ 로 변경해야 하며, 수정하지 않을 경우 오류가 발생할 수 있습니다.<br>
ex) PG사의 관리자 페이지 內 가상 계좌 입금 통보 URL 정보, 간편 로그인 인증업체의 개발자 센터 內 callback URL 등
';
            $this->setData('webSiteSslRequestUrl', $webSiteSslRequestUrl);
            $this->setData('infoMsg', $infoMsg);
            /** @var ManagerLoginService $managerLoginService */
            $managerLoginService = \App::getInstance(ManagerLoginService::class);
            $isOauthSuperManagerLoggedIn = $managerLoginService->isOauthSuperManagerLoggedIn();
            $this->setData('isOauthSuperManagerLoggedIn', $isOauthSuperManagerLoggedIn);
            $this->setData('domainManagementUrl', $this->getNhnCommerceMyGodoUrl('/ssl-management.gd'));
        } catch (Exception $e) {
            throw new LayerNotReloadException($e->getMessage());
        }

        // --- 관리자 디자인 템플릿
        $this->callMenu('policy', 'ssl', 'adminSetting');
        $this->getView()->setPageName('policy/ssl_setting');
    }

    private function getNhnCommerceEchostUrl(string $suffix = null): string
    {
        $baseUrl = \App::isProduction() ? 'https://www.nhn-commerce.com/echost' : 'https://alpha-www.nhn-commerce.com/echost';
        return $baseUrl . $suffix;
    }

    private function getNhnCommerceMyGodoUrl(string $suffix = null): string
    {
        $baseUrl = \App::isProduction() ? 'https://www.nhn-commerce.com/mygodo' : 'https://alpha-www.nhn-commerce.com/mygodo';
        return $baseUrl . $suffix;
    }

    private function getNhnCommerceMainUrl(string $suffix = null): string
    {
        $baseUrl = \App::isProduction() ? 'https://www.nhn-commerce.com/main' : 'https://alpha-www.nhn-commerce.com/main';
        return $baseUrl . $suffix;
    }
}

