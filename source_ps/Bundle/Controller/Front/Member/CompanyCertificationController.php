<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */


namespace Bundle\Controller\Front\Member;

use Framework\Debug\Exception\AlertOnlyException;

class CompanyCertificationController extends \Controller\Front\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');
        $service = \App::getInstance(\Component\Member\Company\CompanyCertification::class);

        $getData = $request->get()->toArray();

        try {
            switch ($getData['mode']) {
                case 'download':
                    $service->download($getData['sno']);
                    exit;
                default:
                    exit;
            }

        } catch (\Throwable $e) {
            throw new AlertOnlyException($e->getMessage());
        }
        exit;
    }
}
