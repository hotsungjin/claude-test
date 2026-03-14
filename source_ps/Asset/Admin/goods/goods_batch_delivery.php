<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
    <div class="btn-group">
        <input type="button" value="저장" class="btn btn-red" id="batchSubmit"/>
    </div>
</div>

<?php include($goodsSearchFrm); ?>

<form id="frmBatchDelivery" name="frmBatchDelivery" action="./goods_ps.php"    target="ifrmProcess" method="post">
    <input type="hidden" name="mode" value="batch_delivery" />
    <input type="hidden" name="modDtUse" value="" />
    <?php
    echo '<input type="hidden" name="totalSearchGoodsNoList" value="' . $totalSearchGoodsNoList . '" />';
    ?>
    <div class="table-responsive">
        <table class="table table-rows table-fixed">
            <thead>
            <tr>
                <th class="width-2xs center"><input type="checkbox" class="js-checkall" data-target-name="arrGoodsNo[]"></th>
                <th class="width-2xs center">번호</th>
                <th class="width-xs center">상품코드</th>
                <th class="width-xs">이미지</th>
                <th class="width-lg center">상품명</th>
                <th class="width-xs center">공급사</th>
                <th class="width-xs center">노출상태</th>
                <th class="width-xs center">판매상태</th>
                <th class="width-md center">판매가</th>
                <th class="width-lg center">배송비</th>
                <th class="width-lg center">배송일정</th>

            </tr>
            </thead>
            <tbody>
            <?php
            if (gd_isset($data) && count($data) > 0 ) {
                $arrGoodsDisplay = ['y' => '노출함', 'n' => '노출안함'];
                $arrGoodsSell = ['y' => '판매함', 'n' => '판매안함'];
                $goodsConfig = (gd_policy('goods.display')); //상품  설정 config 불러오기
                $goodsConfig['goodsModDtTypeAll'] = gd_isset($goodsConfig['goodsModDtTypeAll'], 'y');
                $goodsConfig['goodsModDtFl'] = gd_isset($goodsConfig['goodsModDtFl'], 'n');
                foreach ($data as $key => $val) {
                    if ($val['goodsDiscountFl'] == 'y') {
                        if ($val['goodsDiscountUnit'] == 'price') $goodsDiscount = gd_currency_symbol() . $val['goodsDiscount'] . gd_currency_string();
                        else $goodsDiscount = $val['goodsDiscount'] . '%';
                    } else $goodsDiscount = '사용안함';

                    if ($val['mileageFl'] == 'g') {
                        if ($val['mileageGoodsUnit'] == 'mileage') $mileageGoods = $val['mileageGoods'] . Globals::get('_siteConf.member.mileageBasic.unit');
                        else $mileageGoods = $val['mileageGoods'] . '%';
                    } else $mileageGoods = $conf['mileage']['goods'] . '%';

                    if ($val['deliveryScheduleFl'] == 'y') {
                        if ($val['deliveryScheduleType'] == 'send') {
                            $deliverySchedule = '발송 소요일<br>(' . $val['deliveryScheduleDay'] . '일 이내 발송)';
                        } else {
                            $deliveryScheduleTime = explode(":",$val['deliveryScheduleTime']);
                            $deliverySchedule = '당일발송 기준시간<br>(' . $deliveryScheduleTime[0] . '시 '. $deliveryScheduleTime[1] . '분)';
                        }
                    } else {
                        $deliverySchedule = '';
                    }
                    ?>
                    <tr>
                        <td class="center number">
                            <input type="checkbox" name="arrGoodsNo[]" value="<?=$val['goodsNo']; ?>"/>
                        </td>
                        <td class="center"><?=number_format($page->idx--); ?></td>
                        <td class="center number"><?=$val['goodsNo']; ?></td>
                        <td class="center">

                                <?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>

                        </td>
                        <td>
                            <a href="./goods_register.php?goodsNo=<?=$val['goodsNo']; ?>" target="_blank"><span class="emphasis_text"><?=$val['goodsNm']; ?></span></a>

                        </td>
                        <td class="center"><?= $val['scmNm'] ?></td>
                        <td class="center lmenu"><?=$arrGoodsDisplay[$val['goodsDisplayFl']]; ?></td>
                        <td class="center lmenu"><?=$arrGoodsSell[$val['goodsSellFl']]; ?></td>
                        <td class="center number">
                            <div class="form-inline"><?=gd_currency_symbol(); ?><?=gd_money_format($val['goodsPrice']); ?><?=gd_currency_string(); ?></div>
                        </td>
                        <td class="center"><?=$val['deliveryNm']?></td>
                        <td class="center"><?=$deliverySchedule?></td>
                    </tr>
                    <?php
                }
            }  else {

                ?>
                <tr><td class="no-data" colspan="10">검색된 정보가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="center"><?=$page->getPage();?></div>
    <div class="mgt10"></div>
    <div>


        <table class="table table-cols">
            <colgroup><col class="width-md" /><col /></colgroup>
            <tr>
                <th class="center">
                    <select name="type" onchange="view_terms(this.value)" class="form-control">
                        <option value="delivery" selected>배송비</option>
                        <option value="delivery_schedule">배송일정</option>
                    </select><br/>
                    조건설정
                </th>
                <td id="display_set">
                    <label class="checkbox-inline"><input type="checkbox" id="batchAll" name="batchAll" value="y" />검색된 상품 전체(<?=number_format($page->recode['total']);?>개 상품)를 수정합니다.</label>
                    <p class="notice-danger mgt5">상품수가 많은 경우 비권장합니다. 가능하면 한 페이지씩 선택하여 수정하세요.</p>

                    <table class="table table-cols mgt5" id="tbl_set_delivery">
                        <colgroup><col class="width-md" /><col/></colgroup>
                        <tr>
                            <th class="input_title r_space ">배송비 선택</th>
                            <td>
                                <label> <button type="button" class="btn btn-sm btn-gray" onclick="layer_register('delivery', 'radio')">배송비 선택</button></label>
                                <span id="deliveryLayer" class="width100p">
                                </span>
                                <p class="notice-info">
                                    배송비는 <a href="<?php if (gd_is_provider() === true) { ?>/provider<?php } ?>/policy/delivery_config.php" target="_blank" class="btn-link">[기본설정&gt;배송 정책&gt;배송비조건 관리]</a>에서 추가할 수 있습니다.
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table class="table table-cols mgt5 display-none" id="tbl_set_delivery_schedule">
                        <colgroup><col class="width-md" /><col/></colgroup>
                        <tr>
                            <th>
                                <label class="radio-inline"><input type="radio" name="deliveryScheduleType" value="send" checked />발송 소요일</label>
                            </th>
                            <td>
                                <div class="form-inline">
                                    <?php echo gd_select_box(null, "deliveryScheduleDay", $deliveryScheduleDayList); ?>일 이내 발송 예정
                                    <p class="notice-info">
                                        선택한 일정을 기준으로 쇼핑몰에 배송일정이 노출되므로 주말을 제외한 영업일 기준으로 입력하시기 바랍니다.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="radio-inline"><input type="radio" name="deliveryScheduleType" value="time" />당일발송 기준시간</label>
                            </th>
                            <td>
                                <div class="form-inline">
                                    <img src="<?=PATH_ADMIN_GD_SHARE?>img/icon_calendar.png">
                                    <input type="text" name="deliveryScheduleTime" class="form-control width-xs js-timepicker" value="00:00">
                                    까지 결제 시 오늘 발송
                                </div>
                                <div class="form-inline pdt10">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="deliveryScheduleGuideTextFl" value="y" />
                                        체크 시 당일발송 마감안내 문구가 추가됩니다.
                                    </label>
                                </div>
                                <div class="form-inline pdt3"><input type="text" name="deliveryScheduleGuideText" class="form-control input-width js-maxlength" maxlength="250" placeholder="금일 당일발송이 마감 되었습니다." />
                                    <p class="notice-info">
                                        당일발송 기준시간 이후 해당 상품의 상세페이지 접근 시 당일발송 마감안내 문구가 대체되어 보여집니다.<br>
                                        당일발송 마감안내 문구 추가를 체크 하고 내용 미입력 시, 기본 당일발송 마감안내 문구가 노출됩니다.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>


    </div>
</form>

<script type="text/javascript">
    <!--

    $(document).ready(function(){
        $(".scm_all").hide();

        if($('input[name=detailSearch]').val() !='y')
        {
            $('.js-search-detail').show();
            $('.js-search-detail tr').hide();
            $('.js-search-detail .js-search-delivery').show();
        } else {
            $('.js-search-detail tr').show();
        }


        $( ".js-search-toggle" ).click(function() {

            var detailSearch = $('input[name=detailSearch]').val();

            if(detailSearch == 'y')
            {
                $('.js-search-detail tr').show();
            }
            else
            {
                $('.js-search-detail').show();
                $('.js-search-detail tr').hide();
                $('.js-search-detail .js-search-delivery').show();
            }

        });



        $( "#batchSubmit" ).click(function() {

            var msg = '';

            var type =  $('select[name="type"]').val();

            if ($('#batchAll:checked').length == 0) {
                if ($('input[name="arrGoodsNo[]"]:checked').length == 0) {
                    $.warnUI('항목 체크', '선택된 항목이 없습니다.');
                    return false;
                }

                msg += '선택된 상품의 ';
            } else {
                msg += '검색된 전체 상품의 ';
            }

            if (type =='delivery') {
                if ($('#display_set input[name="deliverySno"]').length == 0) {
                    $.warnUI('항목 체크', '배송비를 선택해주세요.');
                    return false;
                } else {
                    msg += '배송비를 ' + $('#display_set input[name="deliverySnoNm"]').val() + '로 \n';
                }
            } else {
                msg += '배송일정을 ';
            }

            msg += '일괄 수정하시겠습니까?\n\n';
            msg += '[주의] 일괄적용 후에는 이전상태로 복원이 안되므로 신중하게 변경하시기 바랍니다.';


            dialog_confirm(msg, function (result) {
                if (result) {
                    //상품수정일 변경 확인 팝업
                    <?php if ($goodsConfig['goodsModDtTypeAll'] == 'y' && $goodsConfig['goodsModDtFl'] == 'y') { ?>
                    dialog_confirm("상품수정일을 현재시간으로 변경하시겠습니까?", function (result2) {
                        if (result2) {
                            $('input[name="modDtUse"]').val('y');
                        } else {
                            $('input[name="modDtUse"]').val('n');
                        }
                        $( "#frmBatchDelivery").submit();
                    }, '상품수정일 변경', {cancelLabel:'유지', 'confirmLabel':'변경'});
                    <?php } else { ?>
                        //상품 수정일 변경 범위설정 체크
                        <?php if ($goodsConfig['goodsModDtTypeAll'] == 'y') { ?>
                            $('input[name="modDtUse"]').val('y');
                        <?php } else { ?>
                            $('input[name="modDtUse"]').val('n');
                        <?php } ?>
                        $( "#frmBatchDelivery").submit();
                    <?php } ?>
                }
            });



        });


        $('select[name=\'pageNum\']').change(function () {
            $('#frmSearchGoods').submit();
        });

        $('select[name=\'sort\']').change(function () {
            $('#frmSearchGoods').submit();
        });


    });

    /**
     * 카테고리 연결하기 Ajax layer
     */
    function layer_register(typeStr, mode, isDisabled) {

        var addParam = {
            "mode": mode
        };

        // 레이어 창

        if (typeStr == 'scm') {
            addParam['mode'] = 'radio';
            $('input:radio[name=scmFl]:input[value=y]').prop("checked", true);
        }

        if (typeStr == 'delivery') {
            addParam['dataInputNm']		= 'deliverySno';
            var scmFl = $('input[name="scmFl"]:checked').val();
            if(scmFl !='all')
            {
                addParam['scmFl'] =scmFl;

                if($('input[name="scmNo[]"]').val()) addParam['scmNo'] =$('input[name="scmNo[]"]').val();
                else addParam['scmNo'] = $('input[name="scmNo"]').val();
            }

        }

        if (!_.isUndefined(isDisabled) && isDisabled == true) {
            addParam.disabled = 'disabled';
        }

        layer_add_info(typeStr,addParam);
    }

    function view_terms(id)
    {
        $("#display_set .table-cols").hide();
        $("#tbl_set_"+id).show();
        $('.js-maxlength').trigger('maxlength.reposition');
    }
    //-->
</script>
