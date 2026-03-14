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

/**
 * Class BaseFileStorageController
 *
 * @package Controller\Admin\Policy
 * @author  minji Lee <mj2s@godo.co.kr>
 */
class BaseFileStorageSettingController extends \Controller\Admin\Controller
{
    /**
     * 화일 저장소 경로 일괄 관리 페이지
     * [관리자 모드] 화일 저장소 관리 페이지
     *
     */
    public function index()
    {
        // --- 메뉴 설정
        $this->callMenu('policy', 'basic', 'storageSetting');

        $storage = gd_policy('basic.storage');

        $storageList = [
            'goodsStorage' => '상품 이미지 저장소',
            'addGoodsStorage' => '추가상품 이미지 저장소',
            'giftStorage' => '사은품 이미지 저장소',
            'timeSaleStorage' => '타임세일 이미지 저장소',
            'scmStorage' => '공급사 이미지 저장소',
            'boardStorage' => '게시판 파일 저장소',
        ];
        $urlList = [
            'goodsUrl' => '상품 이미지 경로',
            'goodsDescUrl' => '상품상세 설명 이미지 경로',
            'commonHtmlUrl' => '상품상세 공통정보 내용 이미지 경로',
            'addGoodsUrl' => '추가상품 설명 이미지 경로',
            'giftUrl' => '사은품 설명 이미지 경로',
            'boardFileUrl' => '게시글 첨부파일 경로',
            'boardImageUrl' => '게시글 이미지 경로',
            'boardTitleUrl' => '게시판 상단/하단 디자인 이미지 경로',
            'eventUrl' => '기획전 이벤트 내용 이미지 경로',
            'brandUrl' => '브랜드 상단 영역 꾸미기 이미지 경로',
            'categoryUrl' => '카테고리 상단 영역 꾸미기 이미지 경로',
        ];
        // 파일 저장소 관리 -> 상품 등록 시 기본 설정값 없으면 - config값 수정까지 필요없음. 설정안하면
        // default가 기본경로.
        if (empty($storage['storageDefault']) === true) {
            $storage['storageDefault'] = array('imageStorage0' => array('goods'));
        }
        $policy = gd_policy('log.storageSetting');

        $this->setData('storage', $storage);
        $this->setData('data', $policy);
        $this->setData('storageList', $storageList);
        $this->setData('urlList', $urlList);
    }
}
