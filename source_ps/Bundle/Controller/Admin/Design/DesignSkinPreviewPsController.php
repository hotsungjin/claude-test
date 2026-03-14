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

namespace Bundle\Controller\Admin\Design;

use Component\Design\SkinSave;
use Component\Policy\DesignSkinPolicy;
use Framework\Debug\Exception\AlertBackException;
use Framework\Utility\StringUtils;

/**
 * 디자인 스킨 미리보기 처리
 *
 * @author  shindonggyu
 */
class DesignSkinPreviewPsController extends \Controller\Admin\Controller
{
    /**
     * index
     *
     * @throws AlertBackException
     */
    public function index()
    {
        $request = \App::getInstance('request');

        try {
            // skinPreviewCode 확인
            if ($request->get()->has('skinPreviewCode') === false) {
                throw new AlertBackException('스킨 미리보기에 오류가 발생하였습니다.(1)');
            }

            // 로그 처리
            $logger = \App::getInstance('logger')->channel('design');
            $logger->info('Skin Preview Log - admin');

            // skinPreviewCode 처리
            $tmpCode = explode(STR_DIVISION, StringUtils::xssClean($request->get()->get('skinPreviewCode')));
            $tmpData['mallSno'] = $tmpCode[0];  // 몰번호
            $tmpData['device'] = $tmpCode[1];   // 디바이스 체크
            $tmpData['skinCode'] = $tmpCode[2]; // 스킨코드
            $tmpData['pageUri'] = $tmpCode[3];  // 페이지 uri (디자인 페이지에서 화면 보기시)

            // 로그 저장
            $logger->info('info - referer', [\Request::getReferer()]);
            $logger->info('info - get param', $tmpCode);

            // 각 코드 체크
            if (empty($tmpData['mallSno']) === true) { // mallSno 가 없는 경우 기준몰인 1로 처리
                $tmpData['mallSno'] = DEFAULT_MALL_NUMBER;
            }
            if (empty($tmpData['device']) === true) { // device 즉 front or mobile 정보가 없는경우 exception
                throw new AlertBackException('스킨 미리보기에 오류가 발생하였습니다.(2)');
            }
            if (empty($tmpData['skinCode']) === true) { // skinCode 정보가 없는경우 exception
                throw new AlertBackException('스킨 미리보기에 오류가 발생하였습니다.(3)');
            }

            // gadmin에 설정된 도메인 정보를 몰별로 가져움
            $mallClass = \App::load('Component\\Mall\\Mall');
            $tmpDomainList = $mallClass->getShopDomainAllList();

            // 도메인 정보가 없는 경우 (ex. 샘플샵)
            if (empty($tmpDomainList)) {
                $tmpDomainList = [];
                $tmpDomainList['kr'][] = \Request::getDefaultHost(); // 기본 도메인으로 처리
            }

            // 로그 저장
            $logger->info('info - domain list', $tmpDomainList);

            // 몰별 체크
            $arrMallSno = [
                1 => 'kr',
                2 => 'us',
                3 => 'cn',
                4 => 'jp',
            ];

            // 디바이스 체크
            $arrDevice = [
                'front' => 'pc',
                'mobile' => 'mobile',
            ];

            // 각 몰별 사용하는 도메인으로 추출
            $domainList = $tmpDomainList[$arrMallSno[$tmpData['mallSno']]];

            // 보안서버 사용여부
            $sslClass = \App::load('\\Component\\SiteLink\\SecureSocketLayer');
            $searchArr = [
                'sslConfigUse' => 'y',
                'sslConfigPosition' => $arrDevice[$tmpData['device']],
                'sslConfigMallFl' => $arrMallSno[$tmpData['mallSno']],
            ];
            $sslUse = $sslClass->getSsl($searchArr);

            // 보안서버 사용한다면 보안서버 사용하는 주소로 처리함
            $previewDomain = '';
            if (empty($sslUse) === false) {
                foreach ($sslUse as $sVal) {
                    $sslStatus = $sslClass->getSslDomainStatus($sVal);
                    if ($sslStatus['status'] == 'used') {
                        $previewDomain = 'https://' . $sVal['sslConfigDomain'] . ($sVal['sslConfigPort'] == '443' ? '' : ':' . $sVal['sslConfigPort']) . '/';
                        break;
                    }
                }
            }

            // 보안서버를 사용하지 않는다면 사용하는 도메인중 하나를 추출
            if (empty($previewDomain)) {
                // 기본 도메인 처리
                $previewDomain = $tmpData['device'] === 'mobile' ? URI_MOBILE : URI_HOME;

                // 해외몰인 경우 해외몰 주소로 기본 도메인 처리
                if ($tmpData['mallSno'] != DEFAULT_MALL_NUMBER) {
                    if (empty($domainList) === false) {
                        $previewDomain = $domainList[0];
                    } else{
                        $previewDomain = $previewDomain . $arrMallSno[$tmpData['mallSno']];
                    }
                    $previewDomain .= '/';
                }
            }

            // 미리보기 코드 암호화 (godosno , IP , device , skinCode , time)
            // __gd5_skin_preview
            $skinCode = \Globals::get('gLicense.godosno') . STR_DIVISION . \Request::getRemoteAddress() . STR_DIVISION . $tmpData['device'] . STR_DIVISION . $tmpData['skinCode'] . STR_DIVISION . time();
            $logger->info('info - preview code', [$skinCode]);
            $skinCode = base64_encode($skinCode);

            // 이동할 페이지가 있는지 체크
            $checkQuery = '?';
            if (empty($tmpData['pageUri']) === false) {
                $check = parse_url($tmpData['pageUri']);
                if (isset($check['query']) === true) {
                    $checkQuery = '&';
                }
            }

            // 페이지 세팅
            $previewDomain = $previewDomain . $tmpData['pageUri'] . $checkQuery . '__gd5_skin_preview=' . $skinCode;

            // 로그 저장
            $logger->info('info - url', [$previewDomain]);

            // 페이지 이동
            $this->redirect($previewDomain);

        } catch (\Exception $e) {
            throw new AlertBackException($e->getMessage());
        }
    }
}
