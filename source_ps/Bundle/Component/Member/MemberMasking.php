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

namespace Bundle\Component\Member;

/**
 * Class 회원 마스킹 처리
 * @package Bundle\Component\Member
 * @author  cjb3333
 */
class MemberMasking
{
    /**
     * @var string 주문 개인정보조회 제한 사용 여부
     */
    private $orderMaskingUseFl;

    /**
     * @var string 회원 개인정보조회 제한 사용 여부
     */
    private $memberMaskingUseFl;

    /**
     * @var string 게시판 개인정보조회 제한 사용 여부
     */
    private $boardMaskingUseFl;


    public function __construct()
    {
        $session = \App::getInstance('session');
        if ($session->get('manager.functionAuthState') == 'check'){
            $this->orderMaskingUseFl = $session->get('manager.functionAuth.orderMaskingUseFl');
            $this->memberMaskingUseFl = $session->get('manager.functionAuth.memberMaskingUseFl');
            $this->boardMaskingUseFl = $session->get('manager.functionAuth.boardMaskingUseFl');
        }
    }

    public function getOrderMaskingUseFl()
    {
        return $this->orderMaskingUseFl;
    }

    /**
     * 어드민 권한에 따른 마스킹 처리
     *
     * @param  string $menu 마스킹 도메인
     * @param  string $type 마스킹 대상
     * @param  string $string 출력데이터
     *
     * @return string 원본데이터 or 마스킹데이터
     */
    public function masking($menu, $type, $string){

        if ($menu == 'order') {
            if ($this->orderMaskingUseFl != 'y') {
                return $string;
            }
        } else if($menu == 'member') {
            if ($this->memberMaskingUseFl != 'y') {
                return $string;
            }
        } else if($menu == 'board') {
            if ($this->boardMaskingUseFl != 'y') {
                return $string;
            }
        }

        return $this->maskingRaw($type, $string);
    }

    /**
     * 마스킹 처리
     *
     * @param  string $type 마스킹 대상
     * @param  string $string 출력데이터
     *
     * @return string 마스킹데이터
     */
    public function maskingRaw($type, $string)
    {
        switch ($type) {
            case 'name':
                $result = $this->nameMasking($string);
                break;
            case 'id':
                $result = $this->idMasking($string);
                break;
            case 'tel':
                $result = $this->telMasking($string);
                break;
            case 'email':
                $result = $this->emailMasking($string);
                break;
            case 'ip':
                $result = $this->ipMasking($string);
                break;
            case 'account':
                $result = $this->accountMasking($string);
                break;
            case 'address':
                $result = $this->addressMasking($string);
                break;
            case 'hackOutId': // 회원 탈퇴 아이디
                $result = $this->hackOutIdMasking($string);
                break;
            default:
                $result = '';
                break;
        }
        return $result;
    }

    /**
     * 이름 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     *
     * @note 비회원의 경우 작성자명 1글자 가능, 보안파트 피드백 결과 1글자는 닉네임으로 간주하여 마스킹 스킵 허용
     */
    private function nameMasking($string){
        $strlen = mb_strlen($string);
        if ($strlen == 1) {
            $maskingValue = $string;
        } else if ($strlen == 2) {
            $maskingValue = mb_substr($string, 0, 1 ).'*';
        } else {
            if($strlen > 2){
                for($i=0; $i<($strlen-2); $i++){
                    $hideString .= '*';
                }
                $maskingValue = mb_substr($string, 0, 1 ) . $hideString . mb_substr($string, -1, 1);

            }
        }
        return $maskingValue;
    }

    /**
     * 아이디 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    public function idMasking($string) {
        $strlen = mb_strlen($string);
        if ($strlen == 4) {
            $maskingValue = mb_substr($string, 0, 1) . '***';

        } else if ($strlen == 5){
            $maskingValue = mb_substr($string, 0, 2 ). '***';

        } else if ($strlen == 6){
            $maskingValue = mb_substr($string, 0, 2 ). '****';

        } else {
            $hideString = '';
            for($i=0; $i<($strlen-3); $i++){
                $hideString .= '*';
            }
            $maskingValue = mb_substr($string, 0, 3 ). $hideString;
        }
        return $maskingValue;
    }

    /**
     * 전화번호 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function telMasking($string){
        $tel = explode('-',$string);
        $maskingValue = '';
        if(!empty($tel[0])){
            $maskingValue .= $tel[0];
        }
        if(!empty($tel[1])){
            $strlen = mb_strlen($tel[1]);
            if ($strlen == 3) {
                $maskingValue .= '-' . mb_substr($tel[1], 0, 1) . '**';
            }
            if ($strlen == 4) {
                $maskingValue .= '-' .mb_substr($tel[1], 0, 2) . '**';
            }
        }
        if(!empty($tel[2])){
            $strlen = mb_strlen($tel[2]);
            if ($strlen == 3) {
                $maskingValue .= '-' .mb_substr($tel[2], 0, 1) . '**';
            }
            if ($strlen == 4) {
                $maskingValue .= '-' .mb_substr($tel[2], 0, 2) . '**';
            }
        }
        return $maskingValue;
    }

    /**
     * 이메일 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function emailMasking($string){
        $email = explode('@',$string);
        if(!empty($email[0])){
            $strlen = mb_strlen($email[0]);
            if ($strlen == 1) {
                $maskingValue = '*';
            } else if ($strlen == 2) {
                $maskingValue = mb_substr($email[0], 0, 1) . '*';
            } else {
                $hideString = '';
                for($i=0; $i<($strlen-2); $i++){
                    $hideString .= '*';
                }
                $maskingValue = mb_substr($email[0], 0, 2 ). $hideString;
            }
        }
        if(!empty($email[1])){
            $maskingValue .= '@'.$email[1];
        }

        return $maskingValue;
    }

    /**
     * ip 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function ipMasking($string){
        $email = explode('.',$string);
        if(count($email) > 0 ){
            $strlen = mb_strlen($email[count($email)-1]);
            for($i=0; $i<$strlen; $i++){
                $hideString .= '*';
            }
            $email[count($email)-1] = $hideString;
            $maskingValue = implode(".",$email);
        }

        return $maskingValue;
    }

    /**
     * 계좌번호 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function accountMasking($string){
        $strlen = mb_strlen($string);
        for($i=0; $i<($strlen-4); $i++){
            $maskingValue .= '*';
        }
        $maskingValue .= mb_substr($string, -4);
        return $maskingValue;
    }

    /**
     * 주소 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function addressMasking($string){
        $maskingValue = preg_replace("/[0-9]/", "*", $string);
        return $maskingValue;
    }

    /**
     * 회원 탈퇴한 아이디 마스킹 처리
     * @param  string $string 출력데이터
     *
     * @return string $maskingValue 마스킹데이터
     */
    private function hackOutIdMasking($string) {
        $strlen = mb_strlen($string);
        if ($strlen == 4) {
            $maskingValue = mb_substr($string, 0, 1) . '***';

        } else if ($strlen == 5 || $strlen == 6) {
            $maskingValue = mb_substr($string, 0, 2 ). '***';

        } else {
            for ($i=0; $i<($strlen-3); $i++) {
                $hideString .= '*';
            }
            $maskingValue = mb_substr($string, 0, 3 ). $hideString;
        }
        return $maskingValue;
    }
}

