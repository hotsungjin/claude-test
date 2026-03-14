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

namespace Bundle\Controller\Mobile\Member;

/**
 * Class 회원가입 방법 선택
 * @package Bundle\Controller\Mobile\Member
 * @author  yjwee
 */
class JoinMethodController extends \Controller\Front\Member\JoinMethodController
{
    public function index()
    {
        parent::index();

        $this->setData(parent::getData());
        $scripts = ['gd_payco.js'];
        if (parent::getData('useFacebookLogin') === true) {
            $scripts[] = 'gd_sns.js';
        }
        if (parent::getData('useNaverLogin') === true) {
            $scripts[] = 'gd_naver.js';
        }
        if (parent::getData('useKakaoLogin') === true) {
            $scripts[] = 'gd_kakao.js';
        }
        if (parent::getData('useWonderLogin') === true) {
            $scripts[] = 'gd_wonder.js';
        }
        if (parent::getData('useGoogleLogin') === true) {
            $scripts[] = 'gd_google.js';
        }
        $this->addScript($scripts);
    }
}
