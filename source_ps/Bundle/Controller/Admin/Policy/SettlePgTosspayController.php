<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Policy;


use Origin\Enum\Payment\Tosspay\SettingMode;
use Origin\Enum\Payment\Tosspay\SettingScope;

class SettlePgTosspayController extends \Controller\Admin\Controller
{
    /**
     * 토스페이 정책 설정 페이지를 표시
     */
    public function index()
    {
        $this->callMenu('policy', 'settle', 'tosspay');

        $policyInfo = gd_policy('pg.tosspay');

        try {
            $viewData = $this->prepareViewData($policyInfo);

            $this->addScript(['jquery/jquery.multi_select_box.js']);
            $this->setViewData($viewData);
        } catch (\Exception $e) {
            \Logger::warning(__METHOD__ . ' [' . $e->getLine() . '], ' . $e->getMessage());
        }
    }

    /**
     * 뷰데이터 준비
     *
     * @param array $data
     * @return array
     */
    private function prepareViewData(array $data): array
    {
        $goodsCommonContent = \App::load('\\Component\\Goods\\CommonContent');

        $radioDisabled = (empty($data) || empty($data['mId'])) ? 'disabled' : '';

        $data['useMode'] = SettingMode::isValidMode($data['useMode']) ? $data['useMode'] : SettingMode::UNUSE;
        $data['useScope'] = SettingScope::isValidScope($data['useScope']) ? $data['useScope'] : SettingScope::ALL;

        $data['useOptions'] = [
            [
                'value' => SettingMode::UNUSE,
                'label' => '사용하지 않음',
                'checked' => $data['useMode'] === SettingMode::UNUSE,
            ],
            [
                'value' => SettingMode::TEST,
                'label' => '테스트하기',
                'checked' => $data['useMode'] === SettingMode::TEST,
            ],
            [
                'value' => SettingMode::USE,
                'label' => '실제 사용하기',
                'checked' => $data['useMode'] === SettingMode::USE,
            ],
        ];

        $data['scopeOptions'] = [
            [
                'value' => SettingScope::ALL,
                'label' => 'PC+모바일',
                'checked' => $data['useScope'] === SettingScope::ALL,
            ],
            [
                'value' => SettingScope::PC,
                'label' => 'PC 쇼핑몰',
                'checked' => $data['useScope'] === SettingScope::PC,
            ],
            [
                'value' => SettingScope::MOBILE,
                'label' => '모바일 쇼핑몰',
                'checked' => $data['useScope'] === SettingScope::MOBILE,
            ],
        ];

        if (!empty($data['exceptGoods'])) {
            $data['exceptGoodsNo'] = $goodsCommonContent->viewGoodsData(
                implode(INT_DIVISION, $data['exceptGoods'])
            );
        }

        if (!empty($data['exceptCategory'])) {
            $data['exceptCateCd'] = $goodsCommonContent->viewCategoryData(
                implode(INT_DIVISION, $data['exceptCategory'])
            );
        }

        if (!empty($data['exceptBrand'])) {
            $data['exceptBrandCd'] = $goodsCommonContent->viewCategoryData(
                implode(INT_DIVISION, $data['exceptBrand']),
                'brand'
            );
        }

        return [
            'data' => $data,
            'radioDisabled' => $radioDisabled,
        ];
    }

    /**
     * 뷰데이터 설정
     *
     * @param array $viewData
     */
    private function setViewData(array $viewData)
    {
        foreach ($viewData as $key => $value) {
            $this->setData($key, $value);
        }
    }
}
