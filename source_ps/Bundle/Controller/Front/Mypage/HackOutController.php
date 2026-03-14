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

namespace Bundle\Controller\Front\Mypage;

use Bundle\Component\Apple\AppleLogin;
use Component\Agreement\BuyerInform;
use Component\Agreement\BuyerInformCode;
use Component\Coupon\Coupon;
use Component\Design\ReplaceCode;
use Component\Member\Member;
use Component\Godo\GodoNaverServerApi;
use Component\Godo\GodoKakaoServerApi;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\NumberUtils;
use Framework\Utility\SkinUtils;
use Origin\Service\Oauth\Google\GoogleLoginService;
use Session;
use Request;

/**
 * Class HackOutController
 * @package Bundle\Controller\Front\Mypage
 * @author  yjwee
 */
class HackOutController extends \Controller\Front\Controller
{
    public function index()
    {
        $memberSession = Session::get(Member::SESSION_MEMBER_LOGIN);

        $isPasswordRequiredSns = $memberSession['snsJoinFl'] == 'y' &&
                                 $memberSession['snsTypeFl'] != 'naver' &&
                                 $memberSession['snsTypeFl'] != 'kakao' &&
                                 $memberSession['snsTypeFl'] != 'google';
        if (isset($memberSession['memPw']) == false && $isPasswordRequiredSns) {
            $this->js('alert(\'' . __('쇼핑몰 비밀번호를 먼저 등록하셔야 회원탈퇴가 가능합니다.') . '\');location.href=\'' . \Request::getReferer('/mypage/index.php') . '\';');
        }

        $isHackoutNaver  = ($memberSession['snsTypeFl'] == 'naver'  && Session::has(GodoNaverServerApi::SESSION_NAVER_HACK) === false);
        $isHackoutKakao  = ($memberSession['snsTypeFl'] == 'kakao'  && Session::has(GodoKakaoServerApi::SESSION_KAKAO_HACK) === false);
        $isHackoutGoogle = ($memberSession['snsTypeFl'] == 'google' && Session::has(GoogleLoginService::SESSION_GOOGLE_HACK) === false);
        $isHackoutApple  = ($memberSession['snsTypeFl'] == 'apple'  && Session::has(AppleLogin::SESSION_APPLE_HACK) === false);

        if ($isHackoutNaver || $isHackoutKakao || $isHackoutApple || $isHackoutGoogle) {
            $this->js('location.href="../mypage/my_page_password.php?type=hack_out";');
        }

        $memberService = new Member();
        $memberData = $memberService->getMember($memberSession['memNo'], 'memNo', 'deposit, mileage');
        if ($memberData['deposit'] > 0) {
            throw new AlertRedirectException(sprintf(__('현재 예치금을 %s 보유중입니다. 보유중인 예치금이 있는 회원은 탈퇴하실 수 없습니다.'), NumberUtils::currencyDisplay($memberData['deposit'])), 200, null, '../mypage/index.php');
        }
        $memberData['mileage'] = NumberUtils::currencyDisplay($memberData['mileage']);


        $coupon = new Coupon();
        $memberCouponCount = $coupon->getMemberCouponUsableCount(null, $memberSession['memNo']);

        $buyerInform = new BuyerInform();
        $guide = $buyerInform->getInformData(BuyerInformCode::HACK_OUT_GUIDE);

        $replaceCode = new ReplaceCode();
        $replaceCode->initWithUnsetDiff(['{rc_mallNm}']);
        $content = $replaceCode->replace($guide['content'], $replaceCode->getDefinedCode());
        $mallSno = \SESSION::get(SESSION_GLOBAL_MALL)['sno'] ? \SESSION::get(SESSION_GLOBAL_MALL)['sno'] : DEFAULT_MALL_NUMBER;
        $this->setData('memberData', $memberData);
        $this->setData('memberCouponCount', $memberCouponCount);
        $this->setData('hackOutGuideContent', gd_isset($content, ''));
        if(Request::isMobile()) {
            $this->setData('reasonCodeRadio', SkinUtils::makeSelectByHackOut($mallSno));
            $this->setData('gPageName', __("회원 탈퇴"));
        } else {
            $this->setData('reasonCodeRadio', SkinUtils::makeCheckboxByHackOut($mallSno));

        }
        $this->setData('snsType', $memberSession['snsTypeFl'] ?? '');
    }
}
