<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Controller\Admin\Member;

use Request;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;

class GoogleLoginConfigPsController extends \Controller\Admin\Controller
{
    public function index()
    {
        try {
            $policy = \App::load('Component\\Policy\\GoogleLoginPolicy');

            $mallSno        = Request::post()->get('mallSno');
            $useFl          = Request::post()->get('useFl');
            $simpleLoginFl  = Request::post()->get('simpleLoginFl');

            if ($policy->save(Request::post()->all())) {
                if ($policy->useGoogleLogin()) {
                    $joinitem = \App::load('Component\\Policy\\JoinItemPolicy');
                    $joinitem->useGoogleLogin($mallSno);
                }
                $redirectURL = '../member/google_login_config.php';
                if ($mallSno > 1 && $useFl === 'y' && $simpleLoginFl === 'n') {
                    $redirectURL .= '?mallSno=' . $mallSno;
                }
                $redirectTarget = 'parent';
                throw new AlertRedirectException(__('구글 아이디 로그인 설정이 저장되었습니다.'), 200, null, $redirectURL, $redirectTarget);
            } else {
                throw new AlertOnlyException(__('처리중에 오류가 발생하여 실패되었습니다.'));
            }
        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Logger::channel('googleLogin')->warning('[FAIL] google login policy save', [Request::post()->all()]);
            throw $e;
        }
    }
}
