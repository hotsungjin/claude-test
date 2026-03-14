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

namespace Bundle\Controller\Admin\Share;

use Component\Godo\GodoPanelApi;
use Component\Godo\GodoGongjiServerApi;
use Component\Admin\AdminPanel;
use Request;

/**
 * 관리자 페이지내 고도 Panel / API
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class AdminPanelApiController extends \Controller\Admin\Controller
{
    const NOTICE_2DEPTH_CATEGORY_NO = ['36', '42']; // cos 게시판-게시판관리-소식-공지사항- 2차 카테고리 no 값(공통, 고도몰)
    const PATCH_2DEPTH_CATEGORY_NO = '39'; // cos 게시판-게시판관리-소식-업데이트- 2차 카테고리 no 값 (고도몰)

    /**
     * Cos Panel section Code
     *
     * @var string
     */
    private $loginPanelSectionCode;
    private $bannerPanelMainTopSectionCode;
    private $bannerPanelMainMiddleSectionCode;
    private $bannerPanelMainSideSectionCode;
    private $popupPanelLayerSectionCode;
    private $popupPanelModalSectionCode;

    private $adminPanel;

    public function index()
    {
        // cos 서버 모듈 호출
        $cosApi = new GodoPanelApi();

        // cos 섹션 코드 init
        $this->initializePanelSectionCodes();

        // 고도 공지서버 모듈 호출
        $godoApi = new GodoGongjiServerApi();

        // 페이지 코드 (메뉴얼의 메뉴 코드를 이용, get / post로 받아옴)
        $pageCode = Request::request()->toArray();

        // 관리자 패널 클래스 호출
        $this->adminPanel = new AdminPanel();

        // 관리자 로그인 페이지
        if ($this->isLoginPanel($pageCode)) {
            $this->addLoginPanel($cosApi);
        }

        // 관리자 메인 페이지
        if ($this->isAdminMain($pageCode)) {
            // 배너
            $this->addBannerPanels($cosApi);
            // 보드
            $this->addBoardPanels($godoApi, $cosApi);
            // 링크
            $this->addLinkPanels($godoApi);
            // 고객센터
            $this->addCustomerPanel($godoApi);
            // 팝업(cos)
            $this->addPopupPanelByCos($cosApi, 'main');
        }

        // 페이지 패널
        if ($this->isPagePanel($pageCode)) {
            // 팝업(gongji)
            $this->addPopupPanel($godoApi, $pageCode);
        }

        // 카카오 알림톡 설정
        if ($this->isKakaoAlrimPanel($pageCode)) {
            $this->addKakaoAlrimNoticePanel($cosApi, 'kakaoAlrim');
        }

        $panel = $this->adminPanel->getPanel();

        // json data
        $panelData = '{}';
        if (empty($panel) === false) {
            $panelData = json_encode($panel, JSON_UNESCAPED_UNICODE);
        }

        echo $panelData;
        exit();
    }

    /**
     * Initialize cos section code
     * popupPanel은 팝업이 필요한 구좌에 따라서 데이터가 늘어날 수 있으므로, 배열처리 했습니다!
     * @return void
     */
    private function initializePanelSectionCodes()
    {
        $cosSectionCodeList = \App::getConfig('cosSectionCodeList')->toArray();

        $this->loginPanelSectionCode = $cosSectionCodeList['loginBanner'];
        $this->bannerPanelMainTopSectionCode = $cosSectionCodeList['mainTopBanner'];
        $this->bannerPanelMainMiddleSectionCode = $cosSectionCodeList['mainMiddleBanner'];
        $this->bannerPanelMainSideSectionCode = $cosSectionCodeList['mainSideBanner'];
        $this->popupPanelLayerSectionCode = [
            'main' => $cosSectionCodeList['layerPopup']['main']
        ];
        $this->popupPanelModalSectionCode = [
            'main' => $cosSectionCodeList['modalPopup']['main'],
            'kakaoAlrim' => $cosSectionCodeList['kakaoAlrim']
        ];
    }

    /**
     * @param array $pageCode
     * @return bool
     */
    private function isLoginPanel(array $pageCode): bool
    {
        return $pageCode['menuCode'] === 'base' && empty($pageCode['menuKey']) && $pageCode['menuFile'] === 'login';
    }

    /**
     * @param array $pageCode
     * @return bool
     */
    private function isAdminMain(array $pageCode): bool
    {
        return $pageCode['menuCode'] === 'base' && $pageCode['menuKey'] === 'index' && $pageCode['menuFile'] === 'index';
    }

    /**
     * @param array $pageCode
     * @return bool
     */
    private function isPagePanel(array $pageCode): bool
    {
        return empty($pageCode['menuCode']) === false && empty($pageCode['menuFile']) === false;
    }

    /**
     * @param array $pageCode
     * @return bool
     */
    private function isKakaoAlrimPanel(array $pageCode): bool
    {
        return $pageCode['menuCode'] === 'member' && $pageCode['menuFile'] === 'kakao_alrim_setting';
    }

    /**
     * @param GodoPanelApi $cosApi
     * @return void
     */
    private function addLoginPanel(GodoPanelApi $cosApi)
    {
        $setData = $cosApi->getGodoSectionData($this->loginPanelSectionCode);
        if (empty($setData) === false && empty($setData['posts'][0]['postBodyText']) === false) {
            $this->adminPanel->setPanel('banner', 'loginPanel', $setData);
        }
    }

    /**
     * @param GodoPanelApi $cosApi
     * @return void
     */
    private function addBannerPanels(GodoPanelApi $cosApi)
    {
        $indexBannerPanelKey = [
            'mainTop' => $this->bannerPanelMainTopSectionCode,
            'mainMiddle' => $this->bannerPanelMainMiddleSectionCode,
            'mainSide' => $this->bannerPanelMainSideSectionCode
        ];

        foreach ($indexBannerPanelKey as $panelKey => $sectionCode) {
            $setData = $cosApi->getGodoSectionData($sectionCode);
            if (empty($setData) === false && empty($setData['posts'][0]['postBodyText']) === false) {
                $this->adminPanel->setPanel('banner', $panelKey, $setData);
            }
        }
    }

    /**
     * @param GodoGongjiServerApi $godoApi
     * @return void
     */
    private function addBoardPanels(GodoGongjiServerApi $godoApi, GodoPanelApi $cosApi)
    {
        $indexBoardPanelKey = [
            'noticeAPI',
            'patchAPI',
            'betterAPI',
        ];

        // cos 카테고리 값 map
        $cosCategoryMap = [
            'noticeAPI' => self::NOTICE_2DEPTH_CATEGORY_NO,
            'patchAPI' => self::PATCH_2DEPTH_CATEGORY_NO,
        ];

        foreach ($indexBoardPanelKey as $panelKey) {
            if (array_key_exists($panelKey, $cosCategoryMap)) {
                $setData = $cosApi->getBoardData($cosCategoryMap[$panelKey]);
            } else {
                $setData = $godoApi->getGodoServerData($panelKey);
            }

            if (!empty($setData)) {
                $this->adminPanel->setPanel('board', $panelKey, $setData);
            }
        }
    }

    /**
     * @param GodoGongjiServerApi $godoApi
     * @return void
     */
    private function addLinkPanels(GodoGongjiServerApi $godoApi)
    {
        $arrLink = [
            'noticeLink',
            'patchLink',
            'betterLink',
            'customerLink',
        ];
        $setData = $godoApi->getGodoServerBoardUrl();

        foreach ($arrLink as $panelKey) {
            $this->adminPanel->setPanel('link', $panelKey, $setData[$panelKey]);
        }
    }

    /**
     * @param GodoGongjiServerApi $godoApi
     * @return void
     */
    private function addCustomerPanel(GodoGongjiServerApi $godoApi)
    {
        // 고객센터 정보
        $setData = $godoApi->getGodoServerCustomerCenter();
        $this->adminPanel->setPanel('customer', 'customerAPI', $setData);
    }

    /**
     * @param GodoGongjiServerApi $godoApi
     * @param array $pageCode
     * @return void
     */
    private function addPopupPanel(GodoGongjiServerApi $godoApi, array $pageCode)
    {
        // 페이지 카테고리
        $pageCategory = $pageCode['menuCode'] . '/' . $pageCode['menuFile'];
        $setData = $godoApi->getGodoServerData('popupPanel', null, $pageCategory);

        if (empty($setData) === false) {
            $this->adminPanel->setPanel('popup', 'popupPanel', $setData);
        }
    }

    /**
     * @param GodoPanelApi $cosApi
     * @param string $popupAreaCode
     * @return void
     */
    private function addPopupPanelByCos(GodoPanelApi $cosApi, string $popupAreaCode)
    {
        if (empty($this->popupPanelModalSectionCode[$popupAreaCode]) === false) {
            $setData = $cosApi->getGodoSectionData($this->popupPanelModalSectionCode[$popupAreaCode]);
            if (empty($setData) === false && empty($setData['posts'][0]['postBodyText']) === false) {
                $this->adminPanel->setPanel('popupCos', 'modal', $setData);
            }
        }

        if (empty($this->popupPanelLayerSectionCode[$popupAreaCode]) === false) {
            $setData = $cosApi->getGodoSectionData($this->popupPanelLayerSectionCode[$popupAreaCode]);
            if (empty($setData) === false && empty($setData['posts'][0]['postBodyText']) === false) {
                $this->adminPanel->setPanel('popupCos', 'layer', $setData);
            }
        }
    }

    /**
     * @param GodoPanelApi $cosApi
     * @param string $popupAreaCode
     * @return void
     */
    private function addKakaoAlrimNoticePanel(GodoPanelApi $cosApi, string $popupAreaCode)
    {
        $setData = $cosApi->getGodoSectionData($this->popupPanelModalSectionCode[$popupAreaCode]);
        if (empty($setData) === false && empty($setData['posts'][0]['postBodyText']) === false) {
            $this->adminPanel->setPanel('kakaoAlrim', 'modal', $setData);
        }
    }
}
