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
namespace Bundle\Controller\Admin\Share;

class PopupMustinfoController   extends \Controller\Admin\Controller
{

    public function index()
    {
        $goods = \App::load('\\Component\\Goods\\GoodsAdmin');
        // KC인증 정보 처리
        $data['data']['kcmarkInfo'] = json_decode($data['data']['kcmarkInfo'], true);
        // 2023-01-01 법률 개정으로 여러개의 KC 인증정보 입력 가능하도록 변경됨. 기존 데이터는 {} json 이며 이후 [{}] 으로 저장되게 됨에 따라 분기 처리
        if (!isset($data['data']['kcmarkInfo'][0])) {
            //한개만 지정되어 있다면 array로 변환
            $tmpKcMarkInfo = $data['data']['kcmarkInfo'];
            unset($data['data']['kcmarkInfo']);
            $data['data']['kcmarkInfo'][0] = $tmpKcMarkInfo;
        }
        foreach($data['data']['kcmarkInfo'] as $key => $value) {
            gd_isset($data['data']['kcmarkInfo'][$key]['kcmarkFl'], 'n');
            $data['checked']['kcmarkFl'][$data['data']['kcmarkInfo'][$key]['kcmarkFl']] = 'checked="checked"';
        }

        $this->setData('data', gd_htmlspecialchars($data['data']));
        $this->getView()->setDefine('layout', 'layout_blank.php');
        $this->setData('kcmarkDivFl', $goods->getKcmarkcode());
        // 공급사와 동일한 페이지 사용
        $this->getView()->setPageName('share/popup_mustinfo.php');
    }
}
