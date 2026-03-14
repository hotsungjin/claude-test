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
namespace Bundle\Controller\Admin\Goods;


use Component\Storage\Storage;
use Framework\Debug\Exception\LayerException;
use Framework\Debug\Exception\LayerNotReloadException;
use Exception;
use Message;
use Request;
use Session;

class GoodsPsController extends \Controller\Admin\Controller
{

    /**
     * 상품 관련 처리 페이지
     * [관리자 모드] 상품 관련 처리 페이지
     *
     * @author artherot
     * @version 1.0
     * @since 1.0
     * @copyright ⓒ 2016, NHN godo: Corp.
     * @throws Except
     * @throws LayerException
     * @param array $get
     * @param array $post
     * @param array $files
     */
    public function index()
    {

        // --- 각 배열을 trim 처리
        $postValue = Request::post()->toArray();

        // --- 상품 class
        $goods = \App::load('\\Component\\Goods\\GoodsAdmin');

        try {

            switch ($postValue['mode']) {
                // 상품 등록 / 수정
                case 'register':
                case 'modify':
                    $applyFl = $goods->saveInfoGoods($postValue);

                    if($applyFl =='a') {
                        $this->layer(__("승인을 요청하였습니다."));
                    } else {
                        $this->layer(__('저장이 완료되었습니다.'));
                    }

                    break;

                // 상품 복사
                case 'copy':
                    if (empty($postValue['goodsNo']) === false) {
                        $goodsObsConvertStatus = \App::load('\\Component\\Goods\\GoodsObsConvertStatus');

                        if (!is_array($postValue['goodsNo'])) {
                            $postValue['goodsNo'] = explode("||", $postValue['goodsNo']);
                        }
                        sort($postValue['goodsNo']);
                        $totalNum = count($postValue['goodsNo']);
                        foreach ($postValue['goodsNo'] as $key => $goodsNo) {
                            // goods_list 에서 프로그래스바 표시를 위해 추가
                            if ($postValue['loadingModalYn'] == 'y' && (($key % 20 == 0 && $key != '0') || $totalNum - 1 == $key)) {
                                $progressPercent = ($totalNum - 1) > 0 ? round((100 / ($totalNum - 1)) * $key) : 100;
                                echo "<script> parent.progressCopy('" . $progressPercent . "'); </script>";
                                ob_flush();
                                flush();
                            }

                            $checkObsData = $goodsObsConvertStatus->getGoodsObsConvertStatusCount($goodsNo);
                            if ($checkObsData['obsConvertStatusCnt'] < 1) {
                                $goods->setCopyGoods($goodsNo, $postValue);
                            }
                        }
                    }

                    unset($postArray);

                    if (Session::get('manager.isProvider') && Session::get('manager.scmPermissionInsert') == 'c') {
                        $this->layer(__("승인을 요청하였습니다."));
                    }  else {
                        $this->layer(__('복사가 완료 되었습니다.'));
                    }
                    break;

                // 상품삭제상태 변경
                case 'delete_state':

                    if (empty($postValue['goodsNo']) === false) {
                        $applyFl = $goods->setDelStateGoods($postValue['goodsNo']);

                        unset($postArray);

                        if($applyFl =='a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('삭제 되었습니다.'));
                        }

                    }

                    break;

                // 상품 삭제
                case 'delete':

                    if (empty($postValue['goodsNo']) === false) {
                        foreach ($postValue['goodsNo']as $goodsNo) {
                            $goods->setDeleteGoods($goodsNo);
                        }
                    }

                    unset($postArray);

                    $this->layer(__('삭제 되었습니다.'));

                    break;
                case 'option_select':

                    $result = $goods->getGoodsOptionSelect($postValue['goodsNo'], $postValue['optionVal'], $postValue['optionKey'], $postValue['mileageFl']);

                    echo json_encode($result);
                    exit;

                    break;
                // 상품 일괄 품절처리
                case 'soldout':

                    $applyFl  = $goods->setSoldOutGoods($postValue['goodsNo'], $postValue['modDtUse']);

                    unset($postArray);

                    if($applyFl =='a') {
                        $this->layer(__("승인을 요청하였습니다."));
                    } else {
                        $this->layer(__('품절처리 되었습니다.'));
                    }


                    break;

                // 상품승인
                case 'apply':

                    if (empty($postValue['goodsNo']) === false) {

                        foreach ($postValue['goodsNo']as $goodsNo) {
                            $goods->setApplyGoods($goodsNo,$postValue['applyType'][$goodsNo], $postValue['modDtUse']);
                        }

                    }

                    unset($postArray);
                    $this->layer(__('승인처리 되었습니다.'));

                    break;

                // 상품반려
                case 'applyReject':

                    if (empty($postValue['goodsNo']) === false) {

                        $goods->setApplyRejectGoods($postValue['goodsNo'],$postValue['applyMsg']);

                    }

                    unset($postArray);

                    $this->layer(__('반려처리 되었습니다.'));

                    break;

                // 자주쓰는 옵션 등록 / 수정
                case 'option_register':
                case 'option_modify':

                    $goods->saveInfoManageOption($postValue);

                    $this->layer(__('저장이 완료되었습니다.'));

                    break;

                // 자주쓰는 옵션 등록 (상품 상세에서 바로등록)
                case 'option_direct_register':

                    $goods->saveInfoManageOption($postValue);

                    exit();
                    break;

                // 자주쓰는 옵션 복사
                case 'option_copy':

                    if (empty($postValue['sno']) === false) {
                        foreach ($postValue['sno']as $sno) {
                            $goods->setCopyManageOption($sno);
                        }
                    }


                    $this->layer(__('복사가 완료 되었습니다.'));

                    break;

                // 자주쓰는 옵션 삭제
                case 'option_delete':

                    if (empty($postValue['sno']) === false) {
                        foreach ($postValue['sno']as $sno) {
                            $goods->setDeleteManageOption($sno);
                        }
                    }


                    $this->layer(__('삭제 되었습니다.'));


                    exit;
                    break;

                // 상품 아이콘 등록 / 수정
                case 'icon_register':
                case 'icon_modify':

                    $goods->saveInfoManageGoodsIcon($postValue);

                    $this->layer(__('저장이 완료되었습니다.'));

                    break;

                // 상품 아이콘 수정
                case 'icon_etc':

                    $goods->saveInfoManageEtcIcon($postValue);

                    $this->layer(__('저장이 완료되었습니다.'));

                    break;

                // 상품 아이콘 삭제
                case 'icon_delete':

                    if (empty($postValue['sno']) === false) {
                        foreach ($postValue['sno']as $sno) {
                            $goods->setDeleteManageGoodsIcon($sno);
                        }
                    }


                    $this->layer(__('삭제 되었습니다.'));

                    exit;

                    break;

                // 상품 가격 관리 > 일괄 적용
                case 'batch_price':
                    try {
                        \DB::begin_tran();

                        if ($postValue['isPrice'] == 'y') {
                            $data = $goods->setBatchPrice($postValue);
                            echo json_encode(gd_htmlspecialchars_stripslashes(array(
                                'info' => $data,
                                'cnt' => count($data)
                            )), JSON_FORCE_OBJECT);

                            \DB::commit();
                        } else {
                            $applyFl = $goods->setBatchPrice($postValue);
                            \DB::commit();

                            if ($applyFl == 'a') {
                                $this->layer(__("승인을 요청하였습니다."));
                            } else {
                                $this->layer(__('가격 수정이 완료 되었습니다.'));
                            }
                        }
                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 마일리지/혜택 관리 > 일괄 적용
                case 'batch_mileage':
                    try {
                        \DB::begin_tran();
                        $goods->setBatchMileage($postValue);
                        \DB::commit();

                        if ($postValue['type'] == 'discount') {
                            $this->layer(__('상품할인 수정이 완료 되었습니다.'));
                        } else {
                            $this->layer(__('마일리지 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 품절/노출/재고 관리 > 일괄 적용
                case 'batch_stock':
                    try {
                        \DB::begin_tran();
                        $applyFl = $goods->setBatchStock($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('품절/노출/재고 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 카테고리 연결 일괄 적용
                case 'batch_link_category':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 카테고리 연결 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 카테고리 연결 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchLinkCategory($arrGoodsNo, $postValue['categoryCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 카테고리 연결이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 카테고리 이동 일괄 적용
                case 'batch_move_category':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 카테고리 이동 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 카테고리 이동 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchMoveCategory($arrGoodsNo, $postValue['categoryCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if($applyFl =='a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 카테고리 이동이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 카테고리 복사 일괄 적용
                case 'batch_copy_category':
                    try {
                        \DB::begin_tran();

                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 카테고리 복사 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 카테고리 복사 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchCopyCategory($arrGoodsNo, $postValue['categoryCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if($applyFl == 'a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 카테고리 복사가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 브랜드 교체 일괄 적용
                case 'batch_link_brand':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 브랜드 교체 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 브랜드 교체 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchLinkBrand($arrGoodsNo, $postValue['brandCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 브랜드 교체가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 카테고리 전체 해제 일괄 적용
                case 'batch_unlink_category':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 카테고리 전체 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 카테고리 전체 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchUnlinkCategory($arrGoodsNo, null, $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('선택한 상품에 대한 카테고리 해제가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 카테고리 부분 해제 일괄 적용
                case 'batch_unlink_category_part':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 카테고리 부분 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 카테고리 부분 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchUnlinkCategory($arrGoodsNo, $postValue['categoryPartCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 카테고리 해제가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 브랜드 전체 해제 일괄 적용
                case 'batch_unlink_brand':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 브랜드 전체 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 브랜드 전체 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchUnlinkBrand($arrGoodsNo, null, $postValue['modDtUse']);
                        \DB::commit();

                        if($applyFl =='a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('선택한 상품에 대한 브랜드 해제가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 브랜드 부분 해제 일괄 적용
                case 'batch_unlink_brand_part':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 브랜드 부분 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 브랜드 부분 해제 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setBatchUnlinkBrand($arrGoodsNo, $postValue['brandPartCode'], $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layerNotReload(__("승인을 요청하였습니다."));
                        } else {
                            $this->layerNotReload(__('선택한 상품에 대한 브랜드 해제가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 이동/복사/삭제 관리 > 상품 삭제 일괄 적용
                case 'batch_delete_goods':
                    try {
                        \DB::begin_tran();
                        if ($postValue['batchAll'] == 'y') {
                            \Logger::channel('goods')->info(sprintf("검색된 상품 전체 삭제 (%s)", $postValue['mode']), [__METHOD__, $postValue['totalSearchGoodsNoList']]);
                            $arrGoodsNo = explode(',', $postValue['totalSearchGoodsNoList']);
                        } else {
                            \Logger::channel('goods')->info(sprintf("선택한 상품 삭제 (%s)", $postValue['mode']), [__METHOD__, $postValue['arrGoodsNo']]);
                            $arrGoodsNo = $postValue['arrGoodsNo'];
                        }

                        $applyFl = $goods->setDelStateGoods($arrGoodsNo, $postValue['modDtUse']);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('선택한 상품에 대한 삭제가 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 아이콘/대표색상 관리 > 일괄 적용
                case 'batch_icon':
                    try {
                        \DB::begin_tran();

                        $applyFl = $goods->setBatchIcon($postValue);
                        \DB::commit();

                        if($applyFl =='a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('아이콘/대표색상 변경이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 상품 배송 관리 > 일괄 적용
                case 'batch_delivery':
                    try {
                        \DB::begin_tran();

                        $applyFl = $goods->setBatchDelivery($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            if ($postValue['type'] == 'delivery') {
                                $this->layer(__('배송비 수정이 완료 되었습니다.'));
                            } else {
                                $this->layer(__('배송일정 수정이 완료 되었습니다.'));
                            }
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 네이버 쇼핑 상품 설정 > 일괄 적용
                case 'batch_naver_config':
                    try {
                        \DB::begin_tran();

                        $applyFl = $goods->setBatchNaverConfig($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('네이버쇼핑 노출여부 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }

                    break;

                // 페이코 쇼핑 상품 설정 > 일괄 적용
                case 'batch_payco_config':
                    try {
                        \DB::begin_tran();

                        $applyFl = $goods->setBatchPaycoConfig($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('페이코쇼핑 노출여부 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    break;

                // 다음 쇼핑하우 상품 설정 > 일괄 적용
                case 'batch_daum_config':
                    try {
                        \DB::begin_tran();

                        $applyFl = $goods->setBatchDaumConfig($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('쇼핑하우 노출여부 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    break;

                // 메타 FBE 설정 > 일괄 적용
                case 'batch_fbe_config':
                    try {
                        \DB::begin_tran();
                        $applyFl = $goods->setBatchFbeConfig($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('메타 FBE 피드 설정 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    break;

                // 구글 상품 설정 > 일괄 적용
                case 'batch_google_config':
                    try {
                        \DB::begin_tran();
                        $applyFl = $goods->setBatchGoogleConfig($postValue);
                        \DB::commit();

                        if ($applyFl == 'a') {
                            $this->layer(__("승인을 요청하였습니다."));
                        } else {
                            $this->layer(__('구글 쇼핑 노출여부 수정이 완료 되었습니다.'));
                        }

                    } catch (\Exception $e) {
                        \DB::rollback();
                        throw $e;
                    }
                    break;

                // 상품 재입고 상태 일괄 변경
                case 'batch_restock':

                    $applyFl = $goods->setBatchRestockStatus($postValue);
                    $this->json(['applyFl' => $applyFl]);
                    break;

                // 상품 순서 변경
                case 'goods_sort_change':

                    $goods->setGoodsSortChange($postValue);
                    $this->layer(__('상품 순서 변경이 완료 되었습니다.'));
                    break;

                // 삭제상품 복구
                case 'goods_restore':

                    $goods->setGoodsReStore($postValue);
                    $this->layer(__('정상적으로 복구 되었습니다.'));
                    break;

                case 'getStorage' :
                    $storageName = Request::post()->get('storage');
                    $type = Request::post()->get('type');
                    if($type=='add_goods') $pathCode = Storage::PATH_CODE_ADD_GOODS;
                    else $pathCode = Storage::PATH_CODE_GOODS;

                    if ($storageName != 'local' && $storageName != 'obs') {
                        $savePath = Storage::disk($pathCode,$storageName)->getRealPath('');
                        $savePath = str_replace('//', '/', $savePath);
                        if ($storageName != 'url') $savePath = $storageName . $savePath;
                        echo $savePath;
                    }
                    exit;
                    break;

                case 'apply_goods_option':

                    Request::get()->set("sno",$postValue['sno']);
                    $data = $goods->getAdminListOption('layer');

                    $displayFl = [];
                    foreach($data['data'] as $k => $v) {
                        $tmpOptionName[] = $v['optionName'];
                        $displayFl[$v['optionDisplayFl']] = $v['optionDisplayFl'];
                        for($i = 1; $i < 6; $i++) {
                            if($v['optionValue'.$i]) $tmpOptionValue[] = explode(STR_DIVISION,$v['optionValue'.$i]);
                        }
                    }

                    $setData['optionName'] = explode(STR_DIVISION,implode(STR_DIVISION,$tmpOptionName));
                    $setData['optionValue'] = $tmpOptionValue;
                    if(count($displayFl) > 1) $setData['displayFl'] = "";
                    else $setData['displayFl'] = $data['data'][0]['optionDisplayFl'];

                    echo json_encode($setData);

                    exit;

                    break;
                case 'get_category_flag' :
                    $cate = \App::load('\\Component\\Category\\CategoryAdmin');
                    $result =  $cate->getCategoryFlag($postValue['cateCd']);
                    echo json_encode($result);
                    exit;
                case 'get_naver_stats':

                    $setData = $goods->getNaverStats();
                    echo json_encode($setData);

                    exit;
                case 'get_daum_stats':
                    try {
                        $setData = $goods->getDaumStats();
                        echo json_encode($setData);
                    } catch (Exception $e) {
                        echo json_encode(__('새로고침 중 오류가 발생하였습니다. 다시 시도해 주세요.'));
                    }
                    exit;
                case 'get_fbe_stats':
                    try {
                        $setData = $goods->getFbeStats();
                        echo json_encode($setData);
                    } catch (Exception $e) {
                        echo json_encode(__('새로고침 중 오류가 발생하였습니다. 다시 시도해 주세요.'));
                    }
                    exit;
                case 'goods_sale':
                    $applyFl = $goods->setGoodsSale($postValue);
                    if($applyFl =='a') {
                        $this->layer(__("상품 일괄수정승인을 요청하였습니다."));
                    } else {
                        $this->layer(__('상품 일괄수정이 완료 되었습니다.'));
                    }
                    exit;
                    break;

                case 'delete_goodsRestock' :
                    $goods->deleteGoodsRestock($postValue);

                    $this->layer(__('정상적으로 삭제 되었습니다.'));
                    break;

                case 'populate_register':
                    unset($postValue['mode']);
                    if (empty($postValue['same']) === true) {
                        $postValue['same'] = 'n';
                    }
                    // --- 인기상품 class
                    $populate = \App::load('\\Component\\Goods\\Populate');
                    $errReturn = $populate->insertPopulateSettings($postValue); //등록
                    if(!empty($errReturn)){
                        $this->layer(__($errReturn));
                    }else{
                        $this->layer(__('정상적으로 저장 되었습니다.'), 'parent.window.location=\'populate_list.php\'');
                    }

                    break;
                case 'populate_update':
                    unset($postValue['mode']);
                    if (empty($postValue['same']) === true) {
                        $postValue['same'] = 'n';
                    }
                    // --- 인기상품 class
                    $populate = \App::load('\\Component\\Goods\\Populate');
                    $populate->updatePopulateSettings($postValue); //수정

                    $this->layer(__('정상적으로 수정 되었습니다.'));
                    break;
                case 'populate_delete':
                    unset($postValue['mode']);
                    // --- 인기상품 class
                    $populate = \App::load('\\Component\\Goods\\Populate');
                    $populate->deletePopulateSettings($postValue); //삭제
                    $this->layer(__('삭제 되었습니다.'));
                    break;
                case 'populate_goods':
                    $populate = \App::load('\\Component\\Goods\\Populate');
                    $populate->populateGoods($postValue);
                    break;
                case 'bandwagon_push':
                    unset($postValue['mode']);
                    $bandwagon = \App::load('\\Component\\Goods\\BandwagonPush');
                    $bandwagon->save($postValue, \Request::files()->get('iconFile'));

                    $this->layer(__('정상적으로 저장 되었습니다.'));
                    break;

                // 카테고리 분류 변경/추가(팝업)
                case 'popup_category_change' :
                case 'popup_category_add' :
                    $goods->saveInfoPopupCategory($postValue);
                    break;

                // 카테고리 분류 삭제(팝업)
                case 'popup_category_delete' :
                    $goods->modifyPopupCategory($postValue);
                    break;

                // 상품리스트 상품수정일변경
                case 'goods_moddt' :
                    $goods->saveGoodsModdt($postValue);
                    break;

                // 상품리스트 조회항목 가져오기(그리드)
                case 'get_goods_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsGridConfigList($postValue['goodsGridMode']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 옵션리스트 조회항목 가져오기(그리드)
                case 'get_goods_option_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsOptionGridConfigList($postValue['goodsOptionGridMode']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 상품 품절/노출/재고관리 조회항목 가져오기(그리드)
                case 'get_goods_batch_stock_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsBatchStockGridConfigList($postValue['goodsBatchStockGridMode']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 상품리스트 조회항목 저장하기(그리드)
                case 'save_goods_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $goodsAdminGrid->setGoodsGridConfigList($postValue);

                    throw new LayerException(__('설정값이 저장 되었습니다.'), null, null, null, 1000, true);
                    break;

                // 상품 옵션 조회항목 저장하기(그리드)
                case 'save_goods_option_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $goodsAdminGrid->setGoodsOptionGridConfigList($postValue);

                    throw new LayerException(__('설정값이 저장 되었습니다.'), null, null, null, 1000, true);
                    break;

                // 상품 옵션 조회항목 저장하기(그리드)
                case 'save_goods_batch_stock_admin_grid_list' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $goodsAdminGrid->setGoodsBatchStockGridConfigList($postValue);

                    throw new LayerException(__('설정값이 저장 되었습니다.'), null, null, null, 1000, true);
                    break;

                // 상품리스트 전체 조회항목 정렬순서에 따라 가져오기(그리드)
                case 'get_grid_all_list_sort' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsGridConfigAllSortList($postValue['goodsGridMode'], $postValue['gridSort']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 상품 품절/노출/재고관리 조회항목 정렬순서에 따라 가져오기(그리드)
                case 'get_grid_batch_stock_list_sort' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsGridConfigBatchStockSortList($postValue['goodsBatchStockGridMode'], $postValue['gridSort']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 상품 품절/노출/재고관리 조회항목 정렬순서에 따라 가져오기(그리드)
                case 'get_grid_option_list_sort' :
                    $goodsAdminGrid = \App::load('\\Component\\Goods\\GoodsAdminGrid');
                    $listGridConfig = $goodsAdminGrid->getGoodsGridConfigOptionSortList($postValue['goodsOptionGridMode'], $postValue['gridSort']);

                    echo json_encode($listGridConfig, JSON_UNESCAPED_UNICODE);
                    break;

                // 상품리스트 메모 저장하기(그리드)
                case 'goods_list_memo' :
                    try {
                        if ($goods->getGoodsListAdminMemo($postValue['goodsNo'], $postValue['adminMemo'], 'update')) {
                            $this->layer(__('정상적으로 저장 되었습니다.'));
                        }
                    } catch (Exception $e) {
                        throw new LayerNotReloadException($e->getMessage());
                    }
                    break;

                // 상품리스트 선택 조회항목 삭제하기
                case 'goods_list_add_grid_del' :
                    try {
                        // 조회항목
                        if ($goods->goodsListDisplayEachDelete($postValue)) {
                            echo true;
                        }
                    } catch (Exception $e) {
                        throw new LayerNotReloadException($e->getMessage());
                    }
                    break;

                case 'goods_option_temp_reigster':
                    try{
                        $return = $goods->goodsOptionTempRegister($postValue);
                        ?>
                        <script type="text/javascript">
                            parent.opener.$('#optionTmp').html('');

                            //IE가 아닌 경우에만 아래 코드
                            var agent = navigator.userAgent.toLowerCase();
                            if ( (navigator.appName == 'Netscape' && navigator.userAgent.search('Trident') != -1) || (agent.indexOf("msie") != -1) || (agent.indexOf("edge") != -1) ) {
                                parent.opener.$('#optionTmp')[0].innerHTML = parent.$('#depth-toggle-layer-stockOption-popup')[0].outerHTML;
                                for(i=0;i<parent.$('#depth-toggle-layer-stockOption-popup *> input').length;i++){
                                    parent.opener.$('#optionTmp *> input:eq('+i+')').val(parent.$('#depth-toggle-layer-stockOption-popup *> input:eq('+i+')').val());
                                }
                                for(i=0;i<parent.$('#depth-toggle-layer-stockOption-popup *> input[type="checkbox"]').length;i++){
                                    parent.opener.$('#optionTmp *> input[type="checkbox"]:eq('+i+')').prop('checked', parent.$('#depth-toggle-layer-stockOption-popup *> input[type="checkbox"]:eq('+i+')').prop('checked'));
                                }
                                for(i=0;i<parent.$('#depth-toggle-layer-stockOption-popup *> input[type="radio"]').length;i++){
                                    parent.opener.$('#optionTmp *> input[type="radio"]:eq('+i+')').prop('checked', parent.$('#depth-toggle-layer-stockOption-popup *> input[type="radio"]:eq('+i+')').prop('checked'));
                                }
                            }else{
                                parent.$('#depth-toggle-layer-stockOption-popup').clone().appendTo(parent.opener.$('#optionTmp'));
                                parent.opener.$('#optionTmp *> .bootstrap-filestyle').remove();

                            }

                            for(i=0;i<parent.$('#optionGridTable *> select').length;i++){
                                parent.opener.$('#optionGridTable *> select')[i].value = parent.$('#optionGridTable *> select')[i].value;
                            }

                            parent.opener.$('[name="optionFl"]:first').click();
                            parent.opener.$('#optionRegisterBtn').text('옵션 수정');
                            parent.opener.$('#optionTempDisplay').html('<strong style="color:red">[상품 적용 전]</strong>');
                            parent.opener.$('[name="optionTempSession"]').val("<?=$return['sessionString']?>");
                            parent.opener.$('[name="optionTempStocked"]').val("<?=$return['stocked']?>");
                            parent.opener.$('[name="optionReged"]').val("y");
                            parent.opener.$('[name="stockCnt"]').val("<?=$return['stock']?>");
                            
                            if(parent.opener.$('#optionTmp *> input[name="optionY[optionNo][]"]').length == 0){
                                parent.opener.$('#optionTempDisplay').html('');
                                parent.opener.$('input[name="optionReged"]').val('');
                                parent.opener.$('input[name="optionTempStocked"]').val('');
                                parent.opener.$('#optionTmp').html('');
                            }

                            parent.self.close();
                        </script>
                        <?php
                        exit;
                    } catch (Exception $e){
                        throw new LayerNotReloadException($e->getMessage());
                    }
            }

        } catch (Exception $e) {
            throw new LayerException($e->getMessage());
        }
    }
}
