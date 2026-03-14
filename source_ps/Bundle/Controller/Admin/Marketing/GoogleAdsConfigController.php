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

namespace Bundle\Controller\Admin\Marketing;

use Origin\Service\Marketing\Google\GoogleAdsInflowConfigService;
use Request;

/**
 * 구글 쇼핑&애즈
 * @author  Sunny <bluesunh@godo.co.kr>
 */
class GoogleAdsConfigController extends \Controller\Admin\Controller
{
    const GOOGLE_ADS_MARKETING_GUIDE = 'https://marketing-help.nhn-commerce.com/insight/google/center';

    public function index()
    {
        // 메뉴 설정
        $this->callMenu('marketing','googleAds','googleAdsConfig');

        try {
            $dbUrl = \App::load('\\Component\\Marketing\\DBUrl');
            $data = $dbUrl->getConfig('google', 'config');

            // 구글 피드 사용여부 기본값 설정
            gd_isset($data['feedUseFl'], 'n');

            // 스크립트 사용여부 기본값 설정
            gd_isset($data['scriptUseFl'], 'n');

            $checked['feedUseFl'][$data['feedUseFl']] = 'checked="checked"';
            $checked['scriptUseFl'][$data['scriptUseFl']] = 'checked="checked"';

            $googleAdsConfigService = \App::getInstance(GoogleAdsInflowConfigService::class);
            // 카테고리별 체크박스 상태와 전환 라벨 데이터 처리
            $conversionCategoryData = $googleAdsConfigService->processCategoryData($data);

            $data = array_merge(gd_isset($data, []),  $conversionCategoryData['data']);
            $checked = array_merge(gd_isset($checked, []), $conversionCategoryData['checked']);

            if ($data['feedUseFl'] === 'n') {
                $disabled = 'disabled';
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $mallDomain = gd_policy('basic.info')['mallDomain'] ? "http://" . gd_policy('basic.info')['mallDomain'] . "/" : URI_HOME;

        // 관리자 디자인 템플릿
        $this->setData('data', gd_isset($data, []));
        $this->setData('checked', gd_isset($checked, []));
        $this->setData('disabled', $disabled);
        $this->setData('googleDbUrl', sprintf('%s%s', $mallDomain, 'partner/google_all.php'));
        $this->setData('guideUrl', self::GOOGLE_ADS_MARKETING_GUIDE);
        $this->setData('ref', Request::get()->get('ref'));
    }
}
