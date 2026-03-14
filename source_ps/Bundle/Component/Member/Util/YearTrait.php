<?php
/*
 * Copyright (C) 2024 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Component\Member\Util;

trait YearTrait
{
    /**
     * 6자리 생년월일을 다시 8자리로 만들기 위한 기능
     *
     * @param string $strBirthYear  생년월일 정보 (기대값 6자리) e.g.) 900506
     * @param string $genderCode    주민등록번호 7번째자리의 성별코드값 (기대값 0~9)
     *
     * @return string               8자리로 재조합된 생년월일 (실패 시 $strBirthYear 를 그대로 반환)
     */
    public static function fixBirthYear(string $strBirthYear, string $genderCode): string
    {
        if ( empty($genderCode) || strlen($strBirthYear)!==6 ) {
            return $strBirthYear;
        }

        switch($genderCode) {
            // 18xx 년대 출생자
            case '9': // 남성
            case '0': // 여성
                $yearPrefix = '18';
                break;
            // 20xx 년대 출생자
            case '3': // 남성
            case '4': // 여성
            case '7': // 남성(외국인)
            case '8': // 여성(외국인)
                $yearPrefix = '20';
                break;
            // 19xx 년대 출생자
            case '1': // 남성
            case '2': // 여성
            case '5': // 남성(외국인)
            case '6': // 여성(외국인)
                $yearPrefix = '19';
                break;
            default:
                // $strGender 값이 올바르지 않은 경우를 대비한 예외처리 로직
                $birthYear  = intval(substr($strBirthYear, 0, 2));
                $nowYear    = intval(date('y'));
                if ($birthYear > $nowYear) {
                    $yearPrefix = '19';
                } else {
                    $yearPrefix = '20';
                }
                break;
        }

        return $yearPrefix.$strBirthYear;
    }
}
