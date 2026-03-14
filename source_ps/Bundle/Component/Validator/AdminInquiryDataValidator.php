<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2023, NHN COMMERCE Corp.
 * @link https://www.nhn-commerce.com/
 */

namespace Bundle\Component\Validator;

use Component\Storage\Storage;
use Component\Validator\Validator;
use Framework\Debug\Exception\AlertOnlyException;
use Request;

/**
 * Class 관리자 페이지 1:1 문의 데이터 검증
 * @package Bundle\Component\Validator
 * @author  sooyeon
 */
class AdminInquiryDataValidator
{
    /**
     * 1:1문의 검증 함수
     *
     * @param array $requestData
     */
    public static function validateInquiry(array $requestData)
    {
        // 1:1문의 유효성 검사
        self::validateInquiryCategoryNo($requestData['categoryNo']);
        self::validateInquiryTitle(trim($requestData['title']));
        self::validateInquiryContents(trim($requestData['contents']));
        self::validateInquiryFile($requestData['qnaFile']);
        self::validateInquiryAdminId(trim($requestData['adminId']));
        self::validateInquiryFtpId(trim($requestData['ftpId']));
        self::validateInquiryAdminPassword(trim($requestData['adminPassword']));
        self::validateInquiryFtpPassword(trim($requestData['ftpPassword']));
        self::validateInquiryFtpAddress($requestData['ftpAddress']);
        self::validateInquiryAccountHolder($requestData['accountHolder']);
        self::validateInquiryBankName($requestData['bankName']);
        self::validateInquiryAccountNumber($requestData['accountNumber']);
        self::validateInquiryEmail($requestData['email']);
        self::validateInquiryHp($requestData['hp']);
    }

    /**
     * 1:1문의 분류 검증
     *
     * @param string|int $categoryNo
     * @throws AlertOnlyException
     *
     * 카테고리 미 선택시 empty string으로 넘어옴
     */
    private static function validateInquiryCategoryNo($categoryNo)
    {
        if (Validator::required($categoryNo) === false) {
            throw new AlertOnlyException('문의 분류를 선택해 주세요.');
        }
    }

    /**
     * 1:1문의 제목 검증
     *
     * @param string $title
     * @throws AlertOnlyException
     */
    private static function validateInquiryTitle(string $title)
    {
        if (Validator::required($title) === false) {
            throw new AlertOnlyException('문의 제목을 입력해 주세요.');
        }
        if (Validator::maxlen(50, $title, false, 'UTF-8') === false) {
            throw new AlertOnlyException('문의 제목은 50자를 초과할 수 없습니다.');
        }
        if (Validator::pattern('/[\&\"\'\<\>]/', $title) === true) {
            throw new AlertOnlyException('문의 제목에 & " \' < > 를 입력할 수 없습니다.');
        }
    }

    /**
     * 1:1문의 내용 검증
     *
     * @param string $contents
     * @throws AlertOnlyException
     */
    private static function validateInquiryContents(string $contents)
    {
        if (Validator::required($contents) === false) {
            throw new AlertOnlyException('문의 내용을 입력해 주세요.');
        }
    }

    /**
     * 1:1문의 파일 검증
     *
     * @param string $file
     * @throws AlertOnlyException
     */
    private static function validateInquiryFile(string $file)
    {
        $qnaFile = Request::files()->get('qnaFile');
        if (!$qnaFile['name']) return;
        $arrExtType = ['gif', 'png', 'bmp', 'jpg', 'jpeg', 'xlsx', 'xls', 'doc', 'hwp', 'zip', 'rar', 'alz'];
        if (Validator::checkFileExtension($file, $arrExtType) === false) {
            throw new AlertOnlyException('지원하지 않는 형식의 파일입니다.');
        }
        if (Validator::checkMaxFileSize($qnaFile, 5242880) === false) {
            throw new AlertOnlyException('지원하지 않는 크기의 파일입니다.');
        }
    }

    /**
     * 1:1문의 관리자 아이디 검증
     *
     * @param string $adminId
     * @throws AlertOnlyException
     */
    private static function validateInquiryAdminId(string $adminId)
    {
        if (Validator::maxlen(60, $adminId) === false) {
            throw new AlertOnlyException('관리자 아이디는 60자 초과 입력 불가합니다.');
        }
    }

    /**
     * 1:1문의 관리자 비밀번호 검증
     *
     * @param string $adminPassword
     * @throws AlertOnlyException
     */
    private static function validateInquiryAdminPassword(string $adminPassword)
    {
        if (Validator::maxlen(60, $adminPassword) === false) {
            throw new AlertOnlyException('관리자 비밀번호는 60자 초과 입력 불가합니다.');
        }
    }

    /**
     * 1:1문의 FTP 주소 검증
     *
     * @param string $ftpAddress
     * @throws AlertOnlyException
     */
    private static function validateInquiryFtpAddress(string $ftpAddress)
    {
        if (empty($ftpAddress)) return;
        if (Validator::maxlen(320, $ftpAddress, false, 'UTF-8') === false) {
            throw new AlertOnlyException('FTP 주소는 320자 초과 입력 불가합니다.');
        }
        if (Validator::pattern('/[\s]/', $ftpAddress) === true) {
            throw new AlertOnlyException('FTP 주소에 공백을 입력할 수 없습니다.');
        }
    }

    /**
     * 1:1문의 FTP 아이디 검증
     *
     * @param string $ftpId
     * @throws AlertOnlyException
     */
    private static function validateInquiryFtpId(string $ftpId)
    {
        if (empty($ftpId)) return;
        if (Validator::maxlen(60, $ftpId) === false) {
            throw new AlertOnlyException('FTP 아이디는 60자 초과 입력 불가합니다.');
        }
    }

    /**
     * 1:1문의 FTP 비밀번호 검증
     *
     * @param string $ftpPassword
     * @throws AlertOnlyException
     */
    private static function validateInquiryFtpPassword(string $ftpPassword)
    {
        if (empty($ftpPassword)) return;
        if (Validator::maxlen(60, $ftpPassword) === false) {
            throw new AlertOnlyException('FTP 비밀번호는 60자 초과 입력 불가합니다.');
        }
    }

    /**
     * 1:1문의 예금주 검증
     *
     * @param string $accountHolder
     * @throws AlertOnlyException
     */
    private static function validateInquiryAccountHolder(string $accountHolder)
    {
        if (empty($accountHolder)) return;
        if (Validator::maxlen(50, $accountHolder, false, 'UTF-8') === false) {
            throw new AlertOnlyException('예금주 성함은 50자 초과 입력 불가합니다.');
        }
        if (Validator::pattern('/^[A-Za-z0-9ㄱ-ㅎㅏ-ㅣ가-힣\s.]*$/', $accountHolder) === false) {
            throw new AlertOnlyException('예금주 성함은 숫자, 영문자, 한글, 공백 및 일부 특수문자만 입력할 수 있습니다.');
        }
    }

    /**
     * 1:1문의 환불 은행명 검증
     *
     * @param string $bankName
     * @throws AlertOnlyException
     */
    private static function validateInquiryBankName(string $bankName)
    {
        if (empty($bankName)) return;
        if (Validator::maxlen(50, $bankName, false, 'UTF-8') === false) {
            throw new AlertOnlyException('환불 은행명은 50자 초과 입력 불가합니다.');
        }
        if (Validator::pattern('/^[A-Za-z0-9ㄱ-ㅎㅏ-ㅣ가-힣\s.]*$/', $bankName) === false) {
            throw new AlertOnlyException('환불 은행명은 숫자, 영문자, 한글,공백 및 일부 특수문자만 입력할 수 있습니다.');
        }
    }

    /**
     * 1:1문의 환불 계좌 검증
     *
     * @param string $accountNumber
     * @throws AlertOnlyException
     */
    private static function validateInquiryAccountNumber(string $accountNumber)
    {
        if (empty($accountNumber)) return;
        if (Validator::maxlen(14, $accountNumber) === false) {
            throw new AlertOnlyException('환불 계좌는 14자 초과 입력 불가합니다.');
        }
        if (Validator::number($accountNumber) === false) {
            throw new AlertOnlyException('환불 계좌는 숫자만 입력할 수 있습니다.');
        }
    }

    /**
     * 1:1문의 이메일 검증
     *
     * @param string $email
     * @throws AlertOnlyException
     */
    private static function validateInquiryEmail(string $email)
    {
        if (Validator::required($email) === false) {
            throw new AlertOnlyException('이메일 주소를 입력해 주세요.');
        }
        if (Validator::maxlen(320, $email, false, 'UTF-8') === false) {
            throw new AlertOnlyException('이메일 주소는 320자 초과 입력 불가합니다.');
        }
        if (Validator::email($email) === false) {
            throw new AlertOnlyException('이메일 주소가 올바르지 않습니다.');
        }
    }

    /**
     * 1:1문의 휴대전화 번호 검증
     *
     * @param string $hp
     * @throws AlertOnlyException
     */
    private static function validateInquiryHp(string $hp)
    {
        if (Validator::required($hp) === false) {
            throw new AlertOnlyException('휴대폰 번호를 입력해 주세요.');
        }
        if (Validator::number($hp) === false) {
            throw new AlertOnlyException('휴대폰 번호는 숫자만 입력할 수 있습니다.');
        }
        if (Validator::minlen(8, $hp) === false) {
            throw new AlertOnlyException('휴대폰 번호는 8자 미만 입력 불가합니다.');
        }
        if (Validator::maxlen(11, $hp) === false) {
            throw new AlertOnlyException('휴대폰 번호는 11자 초과 입력 불가합니다.');
        }
        if (Validator::pattern('/^01([0|1|6|7|8|9])/', $hp) === false) {
            throw new AlertOnlyException('휴대폰 번호는 010, 011, 016, 017, 018, 019로 시작할 수 있습니다.');
        }
    }

}
