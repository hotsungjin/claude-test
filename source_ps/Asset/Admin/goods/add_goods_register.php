<script type="text/javascript">
    <!--
    $(document).ready(function () {

        //custom validation rule - text only
        $.validator.addMethod(
            'optionCheck', function (value, element) {
                var result = true;

                var type = $('input[name="optionType"]:checked').val();

                if (type == '0') {
                    return true;
                } else {
                    $('#tbl_multi_option  input[name*="optionNm["]').each(function () {
                        if ($(this).val() == '')  result = false;
                        ;
                    });
                }

                return result;

            }, '옵션명을 입력해주세요.'
        );

        $("#frmGoods").validate({
            submitHandler: function (form) {
                oEditors.getById["editor"].exec("UPDATE_CONTENTS_FIELD", []);	// 에디터의 내용이 textarea에 적용됩니다.
                form.target = 'ifrmProcess';
                form.submit();
            },
            // onclick: false, // <-- add this option
            rules: {
                goodsNm: {
                    required: true
                },
                mode: {
                    optionCheck: true
                }
            },
            messages: {
                goodsNm: {
                    required: "상품명을 입력하세요."
                }
            }
        });


        <?php

            if ($data['taxFreeFl'] == 'f') {
                echo '	disabled_switch(\'taxPercent\',true);'.chr(10);
            }

         ?>

        <?php if(($data['mode'] =='register' &&  Request::get()->get('scmFl')) ||  $data['mode'] =='modify') { ?>
        $('input:radio[name=scmFl]').prop("disabled", true);
        $('button.scmBtn').attr("disabled", true);
        <?php }?>

        $('#imageStorage').trigger('change');

        <?php if ($gGlobal['isUse'] === true) { ?>
        $(".js-global-name  input:checkbox").click(function () {
            var globalName = $(this).closest("tr").find("input[type='text']");
            if($(this).is(":checked")) {
                var gloablNameText = $(globalName).val();
                if(gloablNameText) $(globalName).data('global-name',gloablNameText);
                $(globalName).val('');
                $(globalName).prop('disabled',true);
            } else {
                var gloablNameOriText = $(globalName).data('global-name');
                if(gloablNameOriText) $(globalName).val(gloablNameOriText);
                $(globalName).prop('disabled',false);
            }
        });
        <?php } ?>
        image_storage_selector('<?=$data['imageStorage'];?>');
    });


    /**
     * disabled 여부
     *
     * @param string  inputName 해당 input Box의 name
     * @param boolean modeBool 출력 여부 (true or false)
     */
    function disabled_switch(inputName, modeBool) {
        $('input[name=\'' + inputName + '\']').prop('disabled', modeBool);
    }


    /**
     * 카테고리 연결하기 Ajax layer
     */
    function layer_register(typeStr, mode, isDisabled) {

        var addParam = {
            "mode": mode,
        };

        if (typeStr == 'must_info') {
            addParam['dataInputNm'] = 'mustInFoSno';
            addParam['scmFl'] = $('input[name="scmFl"]:checked').val();
            addParam['scmNo'] = $('input[name="scmNo"]').val();
            addParam['callFunc'] = 'set_add_must_info';
        }

        if (typeStr == 'scm') {
            addParam['callFunc'] = 'setScmSelect';
            $('input:radio[name=scmFl]:input[value=y]').prop("checked", true);
        }

        if (!_.isUndefined(isDisabled) && isDisabled == true) {
            addParam.disabled = 'disabled';
        }

        layer_add_info(typeStr,addParam);
    }

    /**
     * 공급사 선택 후 세팅
     *
     * @param object data 공급사 내용
     */
    function setScmSelect(data) {
        //공급사 값 세팅
        displayTemplate(data);
        //수수료 세팅
        $('input[name="commission"]').val(data.info[0].scmCommission);

    }


    /**
     * 이미지 저장소에 따른 상품 이미지 종류
     *
     * @param string modeType 이미지 저장소 종류
     */
    function image_storage_selector(storageName) {
        /*
        $('span[id*=\'imageStorageMode_\']').removeClass();
        <?php  //if ($data['mode'] == "register") {?>
        var addPath = '코드1/코드2/코드3/상품코드/';
        <?php //} else {?>
        var addPath = '<?//=$data['imagePath'];?>';
        <?php //}?>
        $('.imageStorageText').html($('#imageStorage option:selected').text());
        $.post("goods_ps.php", {mode: "getStorage",type: "add_goods", storage: storageName })
            .done(function (data) {
                $("#imageStorageModeNm").html(data+addPath);
            });
            */

        $('span[id*=\'imageStorageMode_\']').removeClass();
        $('span[id*=\'imageStorageMode_\']').addClass('display-none');
        if (storageName == 'url') {
            $('#imageStorageMode_url').removeClass();
            $('#imageStorageMode_url').addClass('display-block');
        } else if (storageName == 'local') {
            $('#imageStorageMode_local').removeClass();
            $('#imageStorageMode_local').addClass('display-block');
        } else if (storageName == '') {
            $('#imageStorageMode_local').removeClass();
            $('#imageStorageMode_local').addClass('display-block');
            $('#imageStorageMode_none').removeClass();
            $('#imageStorageMode_none').addClass('display-block');
        } else {
            $('#imageStorageMode_etc').removeClass();
            $('#imageStorageMode_etc').addClass('display-block');
            $('#imageStorageModeNm').html(storageName);
        }
        image_storage_selector_option();
    }

    /**
     * 이미지 저장소에 따른 상품 옵션 추가노출 이미지 종류
     *
     * @param string modeType 이미지 저장소 종류
     */
    function image_storage_selector_option() {
        $(".cla_image_filed").attr("type", "file");
    }

    function change_option(showOption, hideOption) {
        $("#" + showOption).show();
        $("#" + hideOption).hide();
    }


    function set_opotion() {
        $('#tbl_multi_option .cla_option_info').remove();

        var optionStr = $('input[name=\'optionStr\']').val();

        var option_arr = optionStr.split(',');

        if(option_arr.length > 50 ) {
            alert('옵션 복수 등록은 50개 상품까지 가능합니다.');
            return false;
        }

        for (var i = 0; i < option_arr.length; i++) {
            if (option_arr[i]) {

                var addHtml = '<tr class="cla_option_info" id="tblAddOptionTr' + i + '"><td><input type="checkbox" name="chkOption" value="' + i + '"></td>';
                addHtml += '<td id="tblAddOptionNumber' + i + '">' + (i + 1) + '</td>';
                addHtml += '<td><input type="text" name="optionNm[' + i + ']"  class="form-control " value="' + option_arr[i] + '"/></td>';
                addHtml += '<td><div class="form-inline"> <?=gd_currency_symbol(); ?><input type="text" name="goodsPrice[' + i + ']"  class="width80p form-control" value="<?=gd_money_format($data['goodsPrice'], false); ?>"/><?=gd_currency_string(); ?></div></td>';
                addHtml += '<td><div class="form-inline"> <?=gd_currency_symbol(); ?><input type="text" name="costPrice[' + i + ']"  class="width80p form-control" value="<?=gd_money_format($data['costPrice'], false); ?>"/><?=gd_currency_string(); ?></div></td>';
                addHtml += '<td style="white-space:nowrap"><div class="form-inline"> <label class="radio-inline"><input type="radio" name="stockUseFl[' + i + ']"  value="0" checked onclick="disabled_switch(\'stockCnt[' + i + ']\',true)"/>제한없음</label>';
                addHtml += '<label class="radio-inline"><input type="radio" name="stockUseFl[' + i + ']" value="1"  onclick="disabled_switch(\'stockCnt[' + i + ']\',false)"/><input type="text" name="stockCnt[' + i + ']"  class="form-control width-2xs"  id="stockCnt' + i + '"  disabled="true"/></label></div></td>';
                addHtml += '<td><input type="text" name="goodsCd[' + i + ']"  class="form-control width60p js-maxlength" maxlength="30" /></td>';
                addHtml += '<td><div class="form-inline"><input type="file" name="imageNm[' + i + ']"  class="form-control  cla_image_filed"/></div></td>';
                addHtml += '<td class="center"><select class="form-control"  name="viewFl[' + i + ']"><option value="y">노출함</optiton><option value="n" >노출안함</optiton></select></td>';
                addHtml += '<td class="center"><select class="form-control"  name="soldOutFl[' + i + ']"><option value="n" >정상</optiton><option value="y" >품절</optiton></select></td></tr>';


                $('#tbl_multi_option > tbody:last-child').append(addHtml);
            }
        }

        $("input.js-type-normal").bind('keyup', function () {
            $(this).val($(this).val().replace(/[^a-z0-9_]*/gi, ''));
        });

        $('#lay_multi_option input[maxlength]').maxlength({
            showOnReady: true,
            alwaysShow: true
        });

        $('#lay_multi_option textarea[maxlength]').maxlength({
            placement: 'top-right-inside',
            showOnReady: true,
            alwaysShow: true
        });

        init_file_style();

    }

    function delete_option() {

        var chkCnt = $('input[name=chkOption]:checked').length;
        if (chkCnt == 0) {
            alert('선택된 추가상품이 없습니다.');
            return;
        }

        dialog_confirm('선택한 ' + chkCnt + '개 추가상품을 삭제하시겠습니까?', function (result) {
            if (result) {
                $("input[name=chkOption]:checked").each(function () {
                    field_remove('tblAddOptionTr' + $(this).val());
                });

                $('td[id*=\'tblAddOptionNumber\']').each(function (key) {
                    $(this).html(key + 1);
                });
            }
        });

    }

    function setAddGoods(addGoodsList) {

        var addGoodsHtml = addGoodsList.replace(/display-none/g, "");

        $("#tbl_add_goods_tr_none", opener.document).remove();
        $("#tbl_add_goods_set", opener.document).append(addGoodsHtml);

        $("td[id^='addGoodsNumber_']", opener.document).each(function (i) {
            $(this).html(i + 1);
        });
    }

    /**
     * 팝업등록인경우
     *
     * @param string modeStr 사은품 모드
     */
    function popup_submit() {

        $('input[name=\'mode\']').val('<?=$data['mode']; ?>_ajax');

        var data = new FormData($("#frmGoods")[0]);

        $.ajax({
            type: 'POST',
            data: data,
            url: 'add_goods_ps.php',
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {

                var addGoods = $.parseJSON(data);

                opener.parent.setAddGoods(addGoods);

                BootstrapDialog.show({
                    type: BootstrapDialog.TYPE_PRIMARY,
                    message: addGoods.msg,
                    onshown: function (dialogRef) {
                        setTimeout(function () {
                            dialogRef.close();
                            self.close();
                        }, 1000);
                    }
                });
            }
        });

    }

    function reset_scm() {
        $('#scmLayer').html('');
        $('input[name="commission"]').val('0');
    }

    /**
     * 필수 정보 세팅
     *
     * @param string val
     */
    function set_add_must_info(mustInfo) {
        $.each(mustInfo.info, function (k, v) {
            if(v.mustInfoSno) {
                //필수정보 세팅
                $.post('./goods_must_info_ps.php', {'mode': 'select_json', 'sno': v.mustInfoSno}, function (data) {
                    var mustinfo = $.parseJSON(data);
                    var addMustInfo = 'addMustInfo';
                    // 해당 필수정보 선택시 필드가 없는 경우 에러 발생해서 추가
                    if (_.isObject(mustinfo)) {
                        $.each(mustinfo, function (key, val) {
                            add_must_info(val.count);
                            var fieldNoChk = $('#' + addMustInfo).find('tr:last').get(0).id.replace(addMustInfo, '');
                            if (fieldNoChk == '') {
                                var fieldNoChk = 0;
                            }

                            var tdCnt = 0;
                            $.each(val.info, function (index, array) {
                                $.each(array, function (key1, val1) {
                                    $('input[name="addMustInfo[infoTitle][' + fieldNoChk + '][' + tdCnt + ']"]').val(key1);
                                    $('input[name="addMustInfo[infoValue][' + fieldNoChk + '][' + tdCnt + ']"]').val(val1);
                                    tdCnt++;
                                });
                            });
                        });
                    }
                });
            }
        });
    }

    /**
     * 필수 정보 세팅
     *
     * @param string val
     */
    function set_add_detail_info(detailInfo) {

        $.each(detailInfo.info, function (k, v) {
            if(v.detailInfoInformCd) {
                //필수정보 세팅
                $.post('../policy/goods_ps.php', {'mode': 'search_detail_info', 'informCd': v.detailInfoInformCd}, function (data) {
                    var detailInfo = $.parseJSON(data);

                    if(detailInfo.groupCd == '002') var detailInfoId = 'detailInfoDelivery';
                    if(detailInfo.groupCd == '003') var detailInfoId = 'detailInfoAS';
                    if(detailInfo.groupCd == '004') var detailInfoId = 'detailInfoRefund';
                    if(detailInfo.groupCd == '005') var detailInfoId = 'detailInfoExchange';

                    $.each(detailInfo, function (key, val) {

                        if(key == 'informCd'){
                            $('input[name='+detailInfoId+']').val(val);
                        }else if(key == 'informNm'){
                            $('#'+detailInfoId+'InformNm').html(val);
                        }else if(key == 'content'){
                            oEditors.getById[detailInfoId+'SelectionInput'].setIR(val);
                        }

                    });

                });
            }
        });
    }

    /**
     * 상품 필수 정보 추가
     */
    function add_must_info(infoCnt) {
        var fieldID = 'addMustInfo';
        $('#' + fieldID).show();
        var fieldNoChk = $('#' + fieldID).find('tr:last').get(0).id.replace(fieldID, '');
        if (fieldNoChk == '') {
            var fieldNoChk = 0;
        }
        var fieldNo = parseInt(fieldNoChk) + 1;

        var colspanStr = '';
        if (infoCnt == 2) {
            colspanStr = ' colspan="3"';
        }

        var addHtml = '';
        addHtml += '<tr id="' + fieldID + fieldNo + '">';
        addHtml += '<td class="center"><input type="text" name="addMustInfo[infoTitle][' + fieldNo + '][0]" value="" class="form-control" maxlength="60" /></td>';
        addHtml += '<td class="center"' + colspanStr + '><input type="text" name="addMustInfo[infoValue][' + fieldNo + '][0]" value="" class="form-control" maxlength="500" /></td>';
        if (infoCnt == 4) {
            addHtml += '<td class="center"><input type="text" name="addMustInfo[infoTitle][' + fieldNo + '][1]" value="" class="form-control" maxlength="60" /></td>';
            addHtml += '<td class="center"><input type="text" name="addMustInfo[infoValue][' + fieldNo + '][1]" value="" class="form-control" maxlength="500" /></td>';
        }
        addHtml += '<td class="center"><input type="button" class="btn btn-sm btn-white btn-icon-minus" onclick="field_remove(\'' + fieldID + fieldNo + '\');" value="삭제" /></span></td>';
        addHtml += '</tr>';
        $('#' + fieldID).append(addHtml);
    }

    $(document).ready(function () {
        $('.btn-kc').on('click', function(){
            var imgSrc = '../admin/gd_share/img/kcmark.jpg';
            <?php if (gd_is_provider()) { ?>
            imgSrc = '../' + imgSrc;
            <?php } ?>
            var message = '<div class="notice-info mgl15">KC인증 표시 기본 예시 입니다. (인증구분과 인증번호를 모두 입력한 경우)</div><div class="kcmark-info mgl15 mgr15 mgb15"><img src="' + imgSrc + '" class="mgt10 mgr10 mgl10 kcmark-img">[어린이제품] 안전확인 대상 품목으로 아래의 국가 통합인증 필함</br>인증번호 : <label>CB113F002-6006</label></br>(해당 인증 검사 정보는 판매자가 직접 등록한 것으로 등록 정보에 대한 책임은 판매자에게 있습니다.)</div>' +
                '<div class="table-kcmark mgt10"><div class="notice-danger mgl15">공급자적합성확인 대상 중 인증번호가 없는 경우, 인증번호를 별도로 입력하지 않아도 됩니다.</br>인증번호 미 입력 시, 아래와 같이 노출됩니다.</div><div class="kcmark-info1 mgl15 mgr15"><img src="' + imgSrc + '" class="mgr10 mgl10 kcmark-img">[어린이제품] 공급자적합성확인 대상 품목으로 아래의 국가 통합인증 필함</br>(해당 인증 검사 정보는 판매자가 직접 등록한 것으로 등록 정보에 대한 책임은 판매자에게 있습니다.)</div></div>';
            BootstrapDialog.show({
                title: 'KC인증 표시 예시',
                size: BootstrapDialog.SIZE_WIDE,
                message: message,
                closable: true
            });
        })

        $('input[name="kcmarkInfo[kcmarkFl]"]').on('click', function(){
            if ($(this).val() == 'y') {
                $('.select-kcmark').show();
            } else {
                $('.select-kcmark').hide();
            }
        })

        $('input[name="kcmarkInfo[kcmarkNo]"]').on('keyup focusout', function(){
            var oldText = $(this).val();
            var newText = oldText.replace(/[^0-9a-zA-Z-]+/g,"");
            $(this).val(newText);
        })

        // 방송통신지자재_kc인증일자 노출여부
        function kcDateDisplay(show) {
            if (show != true) {
                $('.kcmarkDivFl').off("change")
            }
            if (show != false) {
                $('.kcmarkDivFl').change(function() {
                    if($(this).val() == 'kcCd04' || $(this).val() == 'kcCd05' || $(this).val() == 'kcCd06'){
                        $(this).parent().next().next().show();
                    }else{
                        $(this).parent().next().next().hide();
                    }
                });
            }
        }
        kcDateDisplay(true);

        // 방송통신지자재_kc인증일자 노출 여부(상품수정)
        if ($('input[name="mode"]').val() == 'modify'){
            if($('input[name="kcmarkInfo[kcmarkFl]"]:checked').val() == 'y') {
                $('.kcmarkDivFl').each(function (index, kcMark) {
                    if (kcMark.value == 'kcCd04' || kcMark.value == 'kcCd05' || kcMark.value == 'kcCd06') {
                        $('.kcmarkDivFl:eq('+index+')').parent().next().next().show();
                    } else {
                        $('.kcmarkDivFl:eq('+index+')').parent().next().next().hide();
                    }
                });
            }else{
                $('.select-kcmark-dt').hide();
            }
        }else{
            $('.kcmarkDivFl').each(function (index, kcMark) {
                if (kcMark.value == 'kcCd04' || kcMark.value == 'kcCd05' || kcMark.value == 'kcCd06') {
                    $('.kcmarkDivFl:eq('+index+')').parent().next().next().show();
                } else {
                    $('.kcmarkDivFl:eq('+index+')').parent().next().next().hide();
                }
            });
        }

        // KC인증정보 추가
        $('.js-add-kcmark').click(function () {
            var html = "";
            html += "<li class=\"mgb5\" style=\"position: relative;\">";
            html += "    <label class=\"select-kcmark\">";
            html += "        <?= addslashes(gd_select_box('kcmarkDivFl', 'kcmarkInfo[kcmarkDivFl][]', $kcmarkDivFl, null, null, '선택', null, 'form-control kcmarkDivFl')); ?>";
            html += "    </label>";
            html += "    <label class=\"select-kcmark\">";
            html += "        <input type=\"text\" name=\"kcmarkInfo[kcmarkNo][]\" class=\"form-control width-xl\" value=\"\" placeholder=\"인증번호 입력 시, - 포함하여 입력하세요.\" maxlength=\"30\">";
            html += "    </label>";
            html += "    <div class=\"input-group js-datepicker select-kcmark-dt display-none\" style=\"display: none;\">";
            html += "        <input type=\"text\" class=\"form-control width-md\" name=\"kcmarkDt[]\" value=\"\" placeholder=\"인증일자를 입력하세요\"/>";
            html += "        <span class=\"input-group-addon\"><span class=\"btn-icon-calendar\"></span></span>";
            html += "    </div>";
            html += "    <input type=\"button\" value=\"삭제\" class=\"btn btn-sm btn-white btn-icon-minus select-kcmark js-del-kcmark\">";
            html += "</li>";

            $("#kcmark-list").append(html);
            init_datetimepicker();
            kcDateDisplay();
        });

        // KC인증정보 삭제
        $("#kcmark-list > li").parent().on("click", '.js-del-kcmark', function() {
            this.parentNode.remove();
        })
    });
    //-->
</script>
<form id="frmGoods" name="frmGoods" action="./add_goods_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="<?=$data['mode']; ?>"/>
    <input type="hidden" name="addGroup" value="<?= $addGroup ?>"/>
    <?php if ($data['mode'] == 'modify') { ?>
        <input type="hidden" name="addGoodsNo" value="<?=gd_isset($data['addGoodsNo']); ?>" />
        <input type="hidden" name="applyFl" value="<?=$data['applyFl'];?>" />
    <?php } ?>

    <div class="page-header js-affix">
        <h3><?=end($naviMenu->location); ?></h3>
        <div class="btn-group">
            <input type="button" value="목록" class="btn btn-white btn-icon-list" onclick="goList('./add_goods_list.php');" />
            <?php if(gd_is_provider() && $data['applyFl'] =='a') { ?>
                <input type="button" value="승인처리 진행 중" class="btn btn-red"  />
            <?php } else { ?>
                <?php if ($addGroup && Request::get()->get('popupMode') == 'yes') { ?>
                    <input type="button" value="저장" class="btn btn-red" onclick="popup_submit()"/>
                <?php } else { ?>
                    <input type="submit" value="저장" class="btn btn-red"/>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <div class="table-title gd-help-manual">
        기본정보
    </div>
        <?php if(gd_is_provider()) { ?>
            <input type="hidden" name="scmNo" value="<?=$data['scmNo']?>">
        <?php } ?>
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col/>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <?php if(gd_use_provider()) { ?>
                <?php if(gd_is_provider()) { ?>
                    <input type="hidden" name="scmNo" value="<?=$data['scmNo']?>">
                <?php }  else { ?>
                    <tr>
                <th>공급사 구분</th>
                <td <?php if(gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === false) { ?>colspan="3"<?php } ?>>
                    <label class="radio-inline"><input type="radio" name="scmFl"
                                  value="n" <?=gd_isset($checked['scmFl']['n']); ?> onclick="reset_scm()" />본사</label>
                    <label class="radio-inline"><input type="radio" name="scmFl" value="y" <?=gd_isset($checked['scmFl']['y']); ?>
                                  onclick="layer_register('scm','radio',true)"/>공급사</label>
                    <label >
                        <button type="button" class="btn btn-sm btn-gray scmBtn" onclick="layer_register('scm','radio',true)">공급사 선택</button>
                    </label>

                        <div id="scmLayer" class="selected-btn-group <?=$data['scmNoNm'] && $data['scmNo'] != DEFAULT_CODE_SCMNO ? 'active' : ''?>">
                        <?php if ($data['scmNo']) { ?>
                            <h5>선택된 공급사 : </h5>
                            <span id="info_scm_<?= $data['scmNo'] ?>" class="btn-group btn-group-xs">
							<input type="hidden" name="scmNo" value="<?= $data['scmNo'] ?>"/>
                                <input type="hidden" name="scmNoNm" value="<?= $data['scmNoNm'] ?>"/>
                                <?php if ($data['scmNo'] != DEFAULT_CODE_SCMNO) { ?>

                                    <span class="btn"><?= $data['scmNoNm'] ?></span>

                                    <?php if ($data['mode'] == 'register' && !Request::get()->get('scmFl')) { ?>
                                        <button type="button" class="btn btn-danger" data-toggle="delete" data-target="#info_scm_<?= $data['scmNo'] ?>">삭제</button> <?php } ?>
                                <?php } ?>
					        </span>
                        <?php } ?>
                    </div>

                </td>
            <?php if(gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true) { ?>

                    <th>매입처</th>
                    <td class="input_area" <?php if(!gd_use_provider() || gd_is_provider()) {?>colspan="3"<?php } ?>>
                        <label><input type="text" name="purchaseNoNm" value="<?=$data['purchaseNoNm']; ?>"
                                      class="form-control"  onclick="layer_register('purchase', 'radio')" readonly/></label>
                        <label>
                            <button type="button" class="btn btn-sm btn-gray" onclick="layer_register('purchase', 'radio')">매입처 선택</button>
                        </label>
                        <?php if (gd_is_provider() === false) { ?>
                            <a href="./purchase_register.php" target="_blank" class="btn btn-sm btn-white btn-icon-plus">매입처 추가</a>
                        <?php } ?>
                        <label id="purchaseNoDel" style="display:<?= $data['purchaseNoNm'] ? '':'none'; ?>"><input type="checkbox" name="purchaseNoDel" value="y"> <span class="text-red">체크시 삭제</span></label>
                        <div id="purchaseLayer" class="width100p">
                            <?php if ($data['purchaseNo']) { ?>
                                <span id="info_parchase_<?= $data['purchaseNo'] ?>" class="pull-left">
                        <input type="hidden" name="purchaseNo" value="<?= $data['purchaseNo'] ?>"/>
                        </span>
                            <?php } ?>
                        </div>
                    </td>
            <?php } ?>
            </tr>
            <?php } ?>
            <?php } ?>
            <tr>
                <th>수수료</th>
                <td>
                    <div class="form-inline"><label title=""><input type="text" name="commission" value="<?=gd_isset($data['commission']); ?>"
                                                                    class="form-control width-width-xs"/></label>%
                    </div>
                </td>
                <th>과세/면세</th>
                <td>
                    <div class="form-inline">
                        <label class="radio-inline" title="과세상품인 경우 &quot;과세&quot;를 선택후 과세율을 입력하세요!"><input type="radio" name="taxFreeFl" value="t" <?=gd_isset($checked['taxFreeFl']['t']);?> onclick="disabled_switch('taxPercent',false);" />과세</label>
                        <label title="과세율을 입력하세요">
                            <select  class="form-control" name="taxPercent" >
                                <?php foreach($conf['tax']['goodsTax'] as $k => $v) { ?>
                                    <?php if($v > 0 ) { ?><option value="<?=$v?>" <?php if($v == $data['taxPercent']) {  echo "selected"; } ?> ><?=$v?></option><?php } ?>
                                <?php } ?>
                            </select> <span class="align">%</span>
                        </label>
                        <label class="radio-inline mgl10" title="면세 상품인경우 &quot;면세&quot;를 선택하세요!"><input type="radio" name="taxFreeFl" value="f" <?=gd_isset($checked['taxFreeFl']['f']);?> onclick="disabled_switch('taxPercent',true);" />면세</label>
                </td>
            </tr>
            <tr>
                <th>상품코드</th>
                <td>
                    <?php if ($data['addGoodsNo']) { ?><?= $data['addGoodsNo'] ?> <label title=""><input type="hidden"
                                                                                                         name="addGoodsNo"
                                                                                                         value="<?=gd_isset($data['addGoodsNo']); ?>"/></label>
                    <?php } else {
                        echo '상품 등록 저장 시 자동 생성됩니다.';
                    } ?>
                </td>
                <th>모델번호</th>
                <td ><label title=""><input type="text" name="goodsModelNo"
                                                       value="<?=gd_isset($data['goodsModelNo']); ?>"
                                                       class="form-control width-lg js-maxlength" maxlength="30"/></label>
                </td>
            </tr>
            <?php if ($gGlobal['isUse'] === true) { ?>
            <tr>
                <th class="input_title r_space require">상품명</th>
                <td class="input_area" colspan="5">
                    <table class="table table-cols">
                        <colgroup>
                            <col class="width-md"/>
                            <col/>
                        </colgroup>
                        <tr>
                            <th>기준몰</th>
                            <td>
                                <label title=""><input type="text" name="goodsNm" value="<?=gd_isset($data['goodsNm']); ?>"
                                                       class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                            </td>
                        </tr>
                        <tbody class="js-global-name">
                        <?php
                        foreach ($gGlobal['useMallList'] as $val) {
                            if ($val['standardFl'] == 'n') {
                                ?>
                                <tr>
                                    <th>
                                        <span class="js-popover flag flag-16 flag-<?= $val['domainFl']?>" data-content="<?=$val['mallName']?>"></span>
                                    </th>
                                    <td>
                                        <input type="text" name="globalData[<?= $val['sno'] ?>][goodsNm]" value="<?= $data['globalData'][ $val['sno'] ]['goodsNm']; ?>" class="form-control  width-2xl js-maxlength" maxlength="250" <?php if(empty($data['globalData'][ $val['sno'] ]['goodsNm'])) { ?>disabled="disabled" <?php } ?> data-global=''/>
                                        <div>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="goodsNmGlobalFl[<?= $val['sno'] ?>]" value="y" <?= gd_isset($checked['goodsNmGlobalFl'][$val['sno']]); ?>> 기준몰 기본 상품명 공통사용
                                            </label>
                                            <a class="btn btn-sm btn-black js-translate-google" data-language="<?= $val['domainFl'] ?>" data-target-name="goodsNm">참고 번역</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        }?>
                        </tbody>
                    </table>

                </td>
            </tr>
            <?php } else { ?>
            <tr>
                <th class="input_title r_space require">상품명</th>
                <td class="input_area" colspan="5">
                    <label title=""><input type="text" name="goodsNm" value="<?=gd_isset($data['goodsNm']); ?>"
                                           class="form-control width-3xl js-maxlength" maxlength="250"/></label>
                </td>
            </tr>
            <?php } ?>
            <tr>
                <th>브랜드</th>
                <td>
                    <label><input type="text" name="brandCdNm" value="<?=$data['brandCdNm']; ?>"
                                  class="form-control"  readonly onclick="layer_register('brand', 'radio')"/> </label>
                    <label><button type="button" class="btn btn-sm btn-gray" onclick="layer_register('brand', 'radio')">브랜드 선택</button></label>
                    <?php if (gd_is_provider() === false) { ?><a href="./category_tree.php?cateType=brand" target="_blank" class="btn btn-sm btn-white btn-icon-plus" >브랜드 추가</a><?php } ?>
                    <label id="brandCdDel" style="display:<?= $data['brandCdNm'] ? '':'none'; ?>"><input type="checkbox" name="brandCdDel" value="y"> <span class="text-red">체크시 삭제</span></label>

                    <div id="brandLayer" class="width100p">
                        <?php if ($data['brandCd']) { ?>
                            <span id="idbrand<?= $data['brandCd'] ?>" class="pull-left">
                        <input type="hidden" name="brandCd" value="<?= $data['brandCd'] ?>"/>
                        </span>
                        <?php } ?>
                    </div>
                </td>
                <th>제조사</th>
                <td>
                    <label title=""><input type="text" name="makerNm" value="<?=gd_isset($data['makerNm']); ?>"
                                           class="form-control width-lg js-maxlength" maxlength="30"/></label>
                </td>
            </tr>
            <tr>
                <th>상품설명</th>
                <td class="input_area" colspan="3">
                    <textarea name="goodsDescription" rows="3" style="width:100%; height:400px;" id="editor"
                              class="form-control"><?=$data['goodsDescription']; ?></textarea>

                </td>
            </tr>
        </table>



    <div class="table-title gd-help-manual">
        이미지 설정
    </div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-sm"/>
        </colgroup>
        <tr>
            <th>저장소 선택</th>
            <td>
                <div class="form-inline">
                    <?=gd_select_box('imageStorage', 'imageStorage', $conf['storage'], null, $data['imageStorage'], '=저장소 선택=', 'onchange="image_storage_selector(this.value);"'); ?></div>
            </td>
        </tr>
        <tr>
            <th>저장 경로</th>
            <td>
                <?php
                if (empty($data['imagePath'])) {
                    echo '<span id="imageStorageMode_url" class="display-none">&quot;URL 직접입력&quot;은 따로 저장 경로가 없이 아래 작성한 URL로 대체 됩니다.</span>';
                    echo '<span id="imageStorageMode_local" class="display-none">'.UserFilePath::data('addGoods')->www().'코드1/코드2/코드3/상품코드/</span>';
                    echo '<span id="imageStorageMode_etc" class="display-none"><span id="imageStorageModeNm">'.$data['imageStorage'].'</span>'.DIR_ADDGOODS_IMAGE_FTP.'코드1/코드2/코드3/상품코드/</span>';
                } else {
                    echo '<span id="imageStorageMode_url" class="display-none">&quot;URL 직접입력&quot;은 따로 저장 경로가 없이 아래 작성한 URL로 대체 됩니다.</span>';
                    echo '<span id="imageStorageMode_local" class="display-none">'.UserFilePath::data('addGoods', $data['imagePath'])->www().'</span>';
                    echo '<span id="imageStorageMode_etc" class="display-none"><span id="imageStorageModeNm">'.$data['imageStorage'].'</span>'.DIR_ADDGOODS_IMAGE_FTP.$data['imagePath'].'</span>';
                }
                ?>
                <input type="hidden" name="imagePath" value="<?=$data['imagePath'];?>" class="form-control" />
            </td>
        </tr>
    </table>


    <div class="table-title gd-help-manual">
        옵션 및 가격/재고 설정
    </div>


        <?php if ($data['mode'] == 'register') { ?>
            <table class="table table-cols">
                <colgroup>
                    <col class="width-sm"/>
                    <col/>
                </colgroup>
                <tr>
                    <th>등록방법 선택</th>
                    <td class="input_area" colspan="5">
                        <label class="radio-inline"><input type="radio" name="optionType" value="0"
                                      onclick="change_option('lay_option','lay_multi_option')" checked/>단일등록</label>
                        <label class="radio-inline"><input type="radio" name="optionType" value="1"
                                      onclick="change_option('lay_multi_option','lay_option')"/>복수등록</label>
                    </td>
                </tr>
            </table>
        <?php } ?>
        <div id="lay_option">
            <table class="table table-cols">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                </colgroup>
                <tr>
                    <th>옵션값</th>
                    <th><img src="<?=PATH_ADMIN_GD_SHARE;?>img/bl_required.png" style="padding-right: 5px">판매가</th>
                    <th>매입가</th>
                    <th>재고</th>
                    <th>자체 상품코드</th>
                    <th>상품 이미지</th>
                    <th>노출상태</th>
                    <th>품절상태</th>
                </tr>
                <tr>
                    <td><input type="text" name="optionNm[]" class="form-control"
                               value="<?=gd_isset($data['optionNm']); ?>"/></td>
                    <td>
                        <div class="form-inline">
                            <?=gd_currency_symbol(); ?>
                            <input type="text" name="goodsPrice[]" value="<?=gd_money_format($data['goodsPrice'], false); ?>" class="form-control width80p" >
                            <?=gd_currency_string(); ?>
                        </div>
                    </td>
                    <td>
                        <div class="form-inline">
                            <?=gd_currency_symbol(); ?>
                            <input type="text" name="costPrice[]" value="<?=gd_money_format($data['costPrice'], false); ?>" class="form-control width80p" >
                            <?=gd_currency_string(); ?>
                        </div>
                    </td>
                    <td style="white-space:nowrap">
                        <div class="form-inline"><label class="radio-inline"><input type="radio" name="stockUseFl[]" value="0"
                                                               onclick="disabled_switch('stockCnt[]',true);" <?=gd_isset($checked['stockUseFl']['0']); ?> />제한없음</label>
                            <label class="radio-inline"><input type="radio" name="stockUseFl[]" value="1"
                                          onclick="disabled_switch('stockCnt[]',false);" <?=gd_isset($checked['stockUseFl']['1']); ?> /><input
                                type="text" name="stockCnt[]" id="stockCnt"
                                class="form-control width-2xs" <?php if ($data['stockUseFl'] == '0') {
                                echo "disabled='true'";
                            } ?> value="<?=gd_isset($data['stockCnt']); ?>"/></label></div>
                    </td>
                    <td><input type="text" name="goodsCd[]" class="form-control width60p js-maxlength"
                               value="<?=gd_isset($data['goodsCd']); ?>" maxlength="30"/></td>
                    <td>
                        <div class="form-inline">
                            <input type="file" name="imageNm[]" class="form-control  cla_image_filed"/>
                            <?php if ($data['imageNm']) { ?>
                                <input type="hidden" name="imageNm[]" value="<?= $data['imageNm'] ?>">
                                <?=gd_html_add_goods_image($data['addGoodsNo'], $data['imageNm'], $data['imagePath'], $data['imageStorage'], 30, $data['goodsNm'], '_blank'); ?>
                                <label class="checkbox-inline"><input type="checkbox" name="imageDelFl" value="y">삭제</label>
                                <?= $data['imageNm'] ?>
                            <?php } ?>
                        </div>
                    </td>
                    <td class="center"><select class="form-control" name="viewFl[]">
                            <option value="y" <?php if ($data['viewFl'] == 'y') {
                                echo "selected";
                            } ?>>노출함</option>
                            <option value="n" <?php if ($data['viewFl'] == 'n') {
                                echo "selected";
                            } ?>>노출안함</option>
                        </select></td>
                    <td class="center"><select class="form-control" name="soldOutFl[]">
                            <option value="y" <?php if ($data['soldOutFl'] == 'y') {
                                echo "selected";
                            } ?>>품절</option>
                            <option value="n" <?php if ($data['soldOutFl'] == 'n') {
                                echo "selected";
                            } ?>>정상</option>
                        </select></td>

                </tr>
            </table>
        </div>
        <div id="lay_multi_option" style="display:none">
            <table class="table table-cols" id="tbl_multi_option">
                <colgroup>
                    <col/>
                    <col/>
                    <col />
                    <col />
                    <col />
                    <col />
                    <col />
                    <col />
                    <col />
                </colgroup>
                <thead>
                <tr>
                    <th colspan="2">옵션값</th>
                    <td class="input_area" colspan="7">
                        <div class="form-inline">
                            <input type="text" name="optionStr" placeholder="콤마(,)로 구분 ex)빨강,파랑" class="form-control width-lg"/><label>&nbsp;&nbsp;<input type="button"
                                                                                                             value="적용"
                                                                                                             onclick="set_opotion()" class="btn btn-sm btn-grey"/></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><input type="checkbox" class="js-checkall" data-target-name="chkOption"></th>
                    <th>번호</th>
                    <th>옵션값</th>
                    <th>판매가</th>
                    <th>매입가</th>
                    <th>재고</th>
                    <th>자체상품코드</th>
                    <th>상품이미지</th>
                    <th>노출상태</th>
                    <th>품절상태</th>
                </tr>
                </thead>
                <tr class="cla_option_info">
                    <td colspan="9" class="no-data">등록된 추가상품이 없습니다.</td>
                </tr>

            </table>
            <div class="table-action">
            <div class="pull-left">
                <button class="checkDelete btn btn-white" type="button" onclick="delete_option()" >선택 삭제
                    </button>
            </div>
            </div>
            <br/>
        </div>
    <?php
    if ($data['mode'] != "register") {
    ?>
    <div class="table-title gd-help-manual">
        상품 필수정보
        <span class="depth-toggle"><button type="button" class="btn btn-sm btn-link bold depth-toggle-button" depth-name="mustInfo"><span>닫힘</span></button></span>
    </div>
    <input type="hidden" id="depth-toggle-hidden-mustInfo" value="<?=$toggle['mustInfo_'.$SessScmNo]?>">
    <div id="depth-toggle-line-mustInfo" class="depth-toggle-line display-none"></div>
    <div id="depth-toggle-layer-mustInfo">
        <div class="notice-danger">
            공정거래위원회에서 공고한 전자상거래법 상품정보제공 고시에 관한 내용을 필독해 주세요!
            <a href="http://www.ftc.go.kr/www/FtcRelLawUList.do?key=290&law_div_cd=07" target="_blank" class="btn-link-underline">내용 확인 ></a>
        </div>
        <div class="notice-info">
            전자상거래법에 의거하여 판매 상품의 필수 (상세) 정보 등록이 필요합니다.<br/>
            <a class="btn-link-underline"  onclick="goods_must_info_popup();">품목별 상품정보고시 내용보기</a>를 참고하여 상품필수 정보를 등록하여 주세요.
        </div>
        <div class="notice-danger">
            전기용품 및 생활용품 판매 시 "전기용품 및 생활용품 안전관리법"에 관한 내용을 필독해 주세요!
            <a href="http://www.law.go.kr/lsInfoP.do?lsiSeq=180398#0000" target="_blank" class="btn-link-underline">내용 확인 ></a>
        </div>
        <table class="table table-cols">
            <colgroup>
                <col class="width-lg">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>필수정보 선택</th>
                <td>
                    <div class="form-inline">
                        <button type="button" class="btn btn-sm btn-gray" onclick="layer_register('must_info', 'radio')">필수정보 선택</button>
                        <a href="./goods_must_info_register.php" target="_blank" class="btn btn-sm btn-white btn-icon-plus">필수정보 추가</a>
                    </div>
                </td>
            </tr>
            <tr>
                <th>KC인증 표시 여부</th>
                <td>
                    <label class="radio-inline">
                        <input type="radio" name="kcmarkInfo[kcmarkFl]" value="y" <?=gd_isset($checked['kcmarkFl']['y']); ?> />사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="kcmarkInfo[kcmarkFl]" value="n" <?=gd_isset($checked['kcmarkFl']['n']); ?> />사용안함
                    </label>
                    <button type="button" class="btn btn-sm btn-gray btn-kc">예시화면</button>
                    <div class="notice-info">
                        안전관리대상 제품은 안전인증 등의 표시(KC 인증마크 및 인증번호)를 소비자가 확인할 수 있도록 상품 상세페이지 내 표시해야 합니다.<br/>
                        <a class="btn-link-underline"  href="http://safetykorea.kr/policy/targetsSafetyCert" target="_blank" >국가기술표준원(KATS) 제품안전정보센터</a>에서 인증대상 품목여부를 확인하여 등록하여 주세요.
                    </div>
                    <div class="mgt15 select-kcmark form-inline">
                        <hr class="select-kcmark">
                        <ul class="pd0" id="kcmark-list">
                            <?php
                            foreach($data['kcmarkInfo'] as $kcMarkKey => $kcMarkValue) {
                                ?>
                                <li class="mgb5" style="position: relative;">
                                    <label class="select-kcmark <?= $display ?>">
                                        <?= gd_select_box('kcmarkDivFl', 'kcmarkInfo[kcmarkDivFl][]', $kcmarkDivFl, null, $data['kcmarkInfo'][$kcMarkKey]['kcmarkDivFl'], '선택', null, "form-control kcmarkDivFl"); ?>
                                    </label>
                                    <label class="select-kcmark <?= $display ?>">
                                        <input type="text" name="kcmarkInfo[kcmarkNo][]" class="form-control width-xl" value="<?=$data['kcmarkInfo'][$kcMarkKey]['kcmarkNo']?>" placeholder="인증번호 입력 시, - 포함하여 입력하세요." maxlength="30">
                                    </label>
                                    <div class="input-group js-datepicker select-kcmark-dt <?= $display ?>">
                                        <input type="text" class="form-control width-md" name="kcmarkDt[]" value="<?= $data['kcmarkInfo'][$kcMarkKey]['kcmarkDt']; ?>" placeholder="인증일자를 입력하세요"/>
                                        <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                                    </div>
                                    <?php
                                    if ($kcMarkKey === 0) {
                                        ?>
                                        <input type="button" value="추가" class="btn btn-sm btn-white btn-icon-plus select-kcmark js-add-kcmark <?= $display ?>">
                                        <?php
                                    } else {
                                        ?>
                                        <input type="button" value="삭제" class="btn btn-sm btn-white btn-icon-minus select-kcmark js-del-kcmark <?= $display ?>">
                                        <?php
                                    }
                                    ?>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <div class="notice-info select-kcmark <?= $display ?>">
                            인증번호가 없는 공급자적합성확인 대상의 경우, 별도로 입력하지 않아도 무관하나 제품명, 모델명, 제조업자명 또는 수입업자명을 소비자가 확인할 수 있도록 상세페이지 내 표시해야 합니다.</br>
                            <a class="btn-link-underline"  href="http://www.kats.go.kr/content.do?cmsid=13&cid=20174&mode=view
" target="_blank" >전기용품 및 생활용품 안전관리법 가이드라인</a>의 내용을 확인해 주세요.
                        </div>
                        <div class="notice-info select-kcmark <?= $display ?>">
                            방송통신기자재의 인증번호 검색 시 인증일자가 필수로 입력되어야 검색이 가능합니다.</br>
                            <div style="color:#fa2828;">인증일자를 입력하지 않은 경우, 구매자가 인증번호를 검색할 수 없으므로 인증일자를 입력하실 것으로 권고 드립니다.</div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>항목추가</th>
                <td>
                    <button type="button" class="btn btn-sm btn-white btn-icon-goods-must-info-02" onclick="add_must_info(4);">4칸 항목 추가</button>
                    <button type="button" class="btn btn-sm btn-white btn-icon-goods-must-info-01" onclick="add_must_info(2);">2칸 항목 추가</button>
                    <span class="notice-danger"> 항목과 내용 란에 아무 내용도 입력하지 않으면 저장되지 않습니다.</span></td>
            </tr>
            </tbody>
        </table>

        <table class="table table-rows <?php if (empty($data['goodsMustInfo'])) { ?>display-none<?php } ?>" id="addMustInfo">
            <colgroup>
                <col class="width15p"/>
                <col class="width30p"/>
                <col class="width15p"/>
                <col class="width30p"/>
                <col class="width10p"/>
            </colgroup>
            <thead>
            <tr>
                <th>항목</th>
                <th>내용</th>
                <th>항목</th>
                <th>내용</th>
                <th>-</th>
            </tr>
            </thead>
            <?php
            if (!empty($data['goodsMustInfo'])) {
                $nextNo = 0;
                foreach ($data['goodsMustInfo'] as $lKey => $lVal) {
                    $colspanStr = '';
                    if (count($lVal) == 1) {
                        $colspanStr = ' colspan="3"';
                    }
                    ?>
                    <tr id="addMustInfo<?=$nextNo; ?>">
                        <?php
                        foreach ($lVal as $sKey => $sVal) {
                            ?>
                            <td class="center">
                                <input type="text" name="addMustInfo[infoTitle][<?=$nextNo; ?>][]" value="<?=$sVal['infoTitle']; ?>" class="form-control"/>
                            </td>
                            <td class="center"<?=$colspanStr; ?>>
                                <input type="text" name="addMustInfo[infoValue][<?=$nextNo; ?>][]" value="<?=$sVal['infoValue']; ?>" class="form-control"/>
                            </td>
                            <?php
                        }
                        ?>
                        <td class="center">
                            <input type="button" class="btn btn-sm btn-white btn-icon-minus" onclick="field_remove('addMustInfo<?=$nextNo; ?>');" value="삭제"/></span>
                        </td>
                    </tr>
                    <?php
                    $nextNo++;
                }
            }
            ?>
        </table>
    </div>
    <?php
    }
    ?>



</form>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/smart/js/service/HuskyEZCreator.js" charset="utf-8"></script>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/smart/js/editorLoad.js" charset="utf-8"></script>
