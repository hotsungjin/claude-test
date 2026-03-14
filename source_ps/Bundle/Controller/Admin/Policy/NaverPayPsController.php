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

namespace Bundle\Controller\Admin\Policy;

use Component\Godo\NaverPayAPI;
use Component\Naver\NaverPay;
use Component\Policy\Policy;
use Framework\Debug\Exception\LayerException;
use Framework\Utility\HttpUtils;
use Message;

class NaverPayPsController extends \Controller\Admin\Controller
{
    public function index()
    {
        $post = \Request::post()->toArray();
        $mode = \Request::request()->get('mode');
        try {
            switch ($mode) {
                case 'applyApi' :
                    $naverPay = new NaverPay();
                    $naverPayConfig = $naverPay->getConfig();
                    if ($naverPay->checkUse() === false && empty($naverPayConfig['naverId'])) {
                        throw new \Exception(__('네이버페이가 신청되어야 주문 API연동을 신청 할 수 있습니다.'));
                    }

                    $url = NaverPayAPI::RELAY_URL;
                    $apiUrl = URI_API . 'godo/set_naverpay.php';
                    $cryptKey = substr(md5(microtime() . rand(1, 1000)), 0, 10);

                    $requestPost = array(
                        'mode' => 'register',
                        'shopNo' => \Globals::get('gLicense.godosno'),
                        'naverId' => $naverPayConfig['naverId'],
                        'shopButtonKey' => $naverPayConfig['imageId'],
                        'apiUrl' => $apiUrl,
                        'cryptKey' => $cryptKey,
                    );
                    $result = HttpUtils::remotePost($url, $requestPost);
                    if ($result != 'DONE') {
                        throw new \Exception(__('네이버체크아웃 주문 API연동 신청에 실패했습니다.').'(' . $result . ')');
                    }

                    $policy = new Policy();
                    $naverPayConfig = $policy->getNaverPaySetting();
                    $naverPayConfig['cryptkey'] = $cryptKey;
                    $naverPayConfig['linkStock'] = 'y';
                    $policy->saveNaverPaySetting($naverPayConfig);
                    $this->layer(__('네이버체크아웃 주문 API연동 설정이 되었습니다'));

                    break;
                case 'getPresentExcept':
                    $config = ($post['pgMode'] == 'order') ? gd_policy('naverPay.config') : gd_policy('naverEasyPay.config');
                    if (in_array('goods', $post['presentExcept'])) {
                        $arrGoods =  empty($post['goods']) ? $config['exceptGoods'] : array_diff($config['exceptGoods'], $post['goods']);
                        $goods = \App::load('\\Component\\Goods\\Goods');
                        $goodsData = $goods->getGoodsDataDisplay(implode(INT_DIVISION, $arrGoods));
                        foreach($goodsData as $key => $val) {
                            $goodsData[$key]['goodsImage'] = gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 50, $val['goodsNm'], '_blank');
                        }
                        $result['goods'] = $goodsData;
                    }

                    if (in_array('category', $post['presentExcept'])) {
                        $arrCategory = empty($post['category']) ? $config['exceptCategory'] : array_diff($config['exceptCategory'], $post['category']);
                        $cate = \App::load('\\Component\\Category\\Category');
                        $categoryData = [];
                        foreach ($arrCategory as $val) {
                            $cateNm = gd_htmlspecialchars_decode($cate->getCategoryPosition($val));
                            $categoryData[] = ['cateCd' => $val, 'cateNm'=> $cateNm];
                        }
                        $result['category'] = $categoryData;
                    }
                    $this->json($result);
                    break;
                case 'config' :
                    $policy = new Policy();
                    if($post['pgMode'] == 'order') { // 네이버페이 주문형 설정 저장
                        $naverPayConfig = $policy->getNaverPaySetting();
                        if ($naverPayConfig['cryptkey']) {
                            $post['cryptkey'] = $naverPayConfig['cryptkey'];
                        }
                        $post['deliveryData'] = $naverPayConfig['deliveryData'];
                        $policy->saveNaverPaySetting($post);
                    } else { // 네이버페이 결제형 설정 저장
                        $policy->saveNaverEasyPaySetting($post);
                    }
                    $this->layer(__('저장이 완료되었습니다.'));
                default :

            }

        } catch (\Exception $e) {
            throw new LayerException($e->getMessage());
        }

    }
}
