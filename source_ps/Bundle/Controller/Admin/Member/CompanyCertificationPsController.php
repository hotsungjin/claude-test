<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Member;

use Bundle\Controller\Admin\Controller;
use Framework\Debug\Exception\AlertOnlyException;

class CompanyCertificationPsController extends Controller
{
    public function index()
    {
        $request = \App::getInstance('request');
        $postData = $request->post()->toArray();

        $service = \App::getInstance(\Component\Member\Company\CompanyCertification::class);
        try {
            switch ($postData['mode']) {
                case 'setting':
                    $result = $service->setCompanyCertificationFileSize($postData['fileSize']);
                    $this->json(['result' => $result]);
                    break;
                default:
                    break;
            }

        } catch (\Throwable $e) {
            $this->json(['result' => $result, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
