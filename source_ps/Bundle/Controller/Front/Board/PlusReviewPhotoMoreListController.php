<?php
/*
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2023, NHN COMMERCE Corp.
 */

namespace Bundle\Controller\Front\Board;


use Bundle\Component\PlusShop\PlusReview\PlusReviewArticleFront;

class PlusReviewPhotoMoreListController extends \Controller\Front\Controller
{
    public function index()
    {

        $req = \Request::get()->all();
        gd_isset($req['page'], 1);
        $plusReviewArticle = new PlusReviewArticleFront();
        $data = $plusReviewArticle->getListPhotoNoPagingByGoodsNo(null, $req['page'], null, null, $req);
        $this->setData('data',$data);
        $this->setData('isSkinDivison',gd_is_skin_division());
        $this->setData('req',$req);
    }
}
