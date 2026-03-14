<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Member;

use Bundle\Controller\Admin\Controller;

/**
 * 사업자 등록증 설정 관련 컨트롤러
 */
class LayerCompanyCertificationSettingController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function index()
    {
        try {
            $companyCertification = \App::getInstance(\Component\Member\Company\CompanyCertification::class);
            $fileSize = $companyCertification->getCompanyCertificationFileSize();

            $this->setData('fileSize', $fileSize);

            $this->getView()->setDefine('layout', 'layout_layer.php');
            $this->getView()->setPageName('member/layer_company_certification_setting.php');

        } catch (\Throwable $e) {
            \Logger::warning('사업자 등록증 설정 페이지 로딩 중 오류 발생: ' . $e->getMessage());
            throw $e;
        }
    }
}
