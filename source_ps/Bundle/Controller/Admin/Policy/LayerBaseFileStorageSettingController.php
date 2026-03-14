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

use Request;
use Exception;

/**
 * 저장소 경로 변경하기 레이어 팝업
 *
 */
class LayerBaseFileStorageSettingController extends \Controller\Admin\Controller
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function index()
    {
        try {
            $getValue = Request::get()->all();

            $tmpStorageConf = gd_policy('basic.storage'); // 저장소 설정
            foreach ($tmpStorageConf['httpUrl'] as $key => $val) {
                $conf['storage'][$val] = $tmpStorageConf['storageName'][$key];
            }
            $conf['storage']['url'] = '직접입력';

            $this->setData('conf', $conf);
            $this->setData('target', $getValue['target']);
            $this->setData('title', $getValue['title']);
            $this->setData('local', URI_HOME);
            $this->getView()->setDefine('layout', 'layout_layer.php');
        } catch (Exception $e) {
            throw $e;
        }
    }
}

