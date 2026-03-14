<script type="text/javascript">
<!--
/**
 * 상품 필수정보 Ajax layer
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
 * 필수 정보 세팅
 *
 * @param string val
 */
function set_add_must_info(mustInfo) {
    $.each(mustInfo.info, function (k, v) {
        if(v.mustInfoSno) {
            //필수정보 세팅
            $.post('../goods/goods_must_info_ps.php', {'mode': 'select_json', 'sno': v.mustInfoSno}, function (data) {
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

    // 부모창 상품선택값
    var cntAddGoodsNo = $('input[name*="addGoodsNo"]:checked', opener.document).map(function(){
        return this.value;
    }).get().join(',');
    $("input[name='addGoodsNo']").val(cntAddGoodsNo);

    // 창 닫기
    $('.js-check-close').click(function () {
        close();
    });

    // 설정 저장
    $('.js-check-save').click(function () {
        $("form[name='frmGoods']").submit();
    });
});
//-->
</script>
<form id="frmGoods" name="frmGoods" action="../goods/add_goods_ps.php" method="post" enctype="multipart/form-data" target="ifrmProcess">
    <input type="hidden" name="mode" value="mustinfo_multi">
    <input type="hidden" name="addGoodsNo" value="">
    <div class="popup-page-header js-affix ">
        <h3>상품 필수정보 설정</h3>
    </div>
        <input type="hidden" id="depth-toggle-hidden-mustInfo" value="<?=$toggle['mustInfo_'.$SessScmNo]?>">
        <div id="depth-toggle-line-mustInfo" class="depth-toggle-line display-none"></div>
        <div id="depth-toggle-layer-mustInfo">
            <div class="notice-danger" style="margin-top: 10px;">
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
                                    <a href="../goods/goods_must_info_register.php" target="_blank" class="btn btn-sm btn-white btn-icon-plus">필수정보 추가</a>
                                </div>
                            </td>
                    </tr>
                    <tr>
                        <th>KC인증 표시 여부</th>
                        <td>
                            <label class="radio-inline">
                                <input type="radio" name="kcmarkInfo[kcmarkFl]" value="y" />사용함
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="kcmarkInfo[kcmarkFl]" value="n" checked />사용안함
                            </label>
                            <button type="button" class="btn btn-sm btn-gray btn-kc">예시화면</button>
                            <div class="notice-info">
                                안전관리대상 제품은 안전인증 등의 표시(KC 인증마크 및 인증번호)를 소비자가 확인할 수 있도록 상품 상세페이지 내 표시해야 합니다.<br/>
                                <a class="btn-link-underline"  href="http://safetykorea.kr/policy/targetsSafetyCert" target="_blank" >국가기술표준원(KATS) 제품안전정보센터</a>에서 인증대상 품목여부를 확인하여 등록하여 주세요.
                            </div>
                            <div class="mgt15 select-kcmark form-inline display-none">
                                <hr class="select-kcmark display-none">
                                <ul class="pd0" id="kcmark-list">
                                    <li class="mgb5" style="position: relative;">
                                        <label class="select-kcmark display-none">
                                            <?= gd_select_box('kcmarkDivFl', 'kcmarkInfo[kcmarkDivFl][]', $kcmarkDivFl, null, $data['kcmarkInfo'][$kcMarkKey]['kcmarkDivFl'], '선택', null, "form-control kcmarkDivFl"); ?>
                                        </label>
                                        <label class="select-kcmark display-none">
                                            <input type="text" name="kcmarkInfo[kcmarkNo][]" class="form-control width-xl" value="<?=$data['kcmarkInfo'][$kcMarkKey]['kcmarkNo']?>" placeholder="인증번호 입력 시, - 포함하여 입력하세요." maxlength="30">
                                        </label>
                                        <div class="input-group js-datepicker select-kcmark-dt">
                                            <input type="text" class="form-control width-md" name="kcmarkDt[]" value="" placeholder="인증일자를 입력하세요"/>
                                            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                                        </div>
                                        <input type="button" value="추가" class="btn btn-sm btn-white btn-icon-plus select-kcmark js-add-kcmark display-none">
                                    </li>
                                </ul>
                                <div class="notice-info select-kcmark display-none">
                                    인증번호가 없는 공급자적합성확인 대상의 경우, 별도로 입력하지 않아도 무관하나 제품명, 모델명, 제조업자명 또는 수입업자명을 소비자가 확인할 수 있도록 상세페이지 내 표시해야 합니다.</br>
                                    <a class="btn-link-underline"  href="http://www.kats.go.kr/content.do?cmsid=13&cid=20174&mode=view" target="_blank" >전기용품 및 생활용품 안전관리법 가이드라인</a>의 내용을 확인해 주세요.
                                </div>
                                <div class="notice-info select-kcmark display-none">
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
                            <span class="notice-danger"> 항목과 내용 란에 아무 내용도 입력하지 않으면 저장되지 않습니다.</span>
                        </td>
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
        <div class="text-center">
            <input type="button" class="btn btn-white js-check-close" value="닫기">
        <input type="button" class="btn btn-black js-check-save" value="적용">
    </div>
</form>
