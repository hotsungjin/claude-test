<form id="frmNaver" name="frmNaver" action="naver_pay_ps.php" method="post" >
    <input type="hidden" name="mode" value="config"/>
    <input type="hidden" name="pgMode" value="<?= $pgMode; ?>"/>
    <input type="hidden" name="testYn" value="n"/>
    <div class="page-header js-affix">
        <h3><?= end($naviMenu->location); ?>
            <small></small>
        </h3>
        <?php ?>
        <div class="btn-group">
            <input type="submit" class="btn btn-red" value="저장">
        </div>
    </div>

    <table class="table table-cols paycoInfo">
        <tr>
            <td class="payco_BgColorWhite">
                <strong>네이버페이 결제란?</strong><br/> 네이버에서 제공하는 간편결제 서비스입니다.<br/>
                <table cellpadding="5" cellspacing="0" border="0" width="100%" class="mgt10">
                    <tr>
                        <td>
                            <table class="table table-cols sub_paycoInfo">
                                <colgroup>
                                    <col class="width-sm"/>
                                    <col/>
                                </colgroup>
                                <tr>
                                    <td width="70" class="payco_BgColorGray2 text-center">네이버페이<br/>주문형</td>
                                    <td class="lastTd">
                                        <div>- 네이버 ID로 상품상세 페이지에서 주문(비회원 구매)</div>
                                        <div>- 네이버의 결제수단으로 주문</div>
                                        <div>- 네이버에서 제공하는 모든 결제수단 지원</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="table table-cols sub_paycoInfo">
                                <colgroup>
                                    <col class="width-sm"/>
                                    <col/>
                                </colgroup>
                                <tr>
                                    <td width="70" class="payco_BgColorGray2 text-center">네이버페이<br/>결제형</td>
                                    <td class="lastTd">
                                        <div>- 쇼핑몰 ID나 비회원으로 쇼핑몰 주문서 페이지에서 주문</div>
                                        <div>- 기존의 쇼핑몰 결제수단과 함께 사용 가능</div>
                                        <div>- 카드 간편결제, 계좌 간편결제, 포인트 결제 지원</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <p class="msg">자세한 내용은 서비스 안내를 참고하여 주세요.&nbsp;
                    <button type="button" class="btn btn-black btn-xs" onclick="window.open('/service/service_info.php?menu=pg_naverPay_info','_blank')">바로가기</button>
                </p>
            </td>
        </tr>
    </table>

    <ul class="nav nav-tabs mgb30" role="tablist">
        <li role="presentation" class="<?= $pgMode == 'order' ? 'active' : ''; ?>">
            <a href="naver_pay_config.php?pgMode=order" role="tab">네이버페이 주문형 설정</a>
        </li>
        <li role="presentation" class="<?= $pgMode == 'pay' ? 'active' : ''; ?>">
            <a href="naver_pay_config.php?pgMode=pay" role="tab">네이버페이 결제형 설정</a>
        </li>
    </ul>

    <?php include($layoutPgContent); ?>
</form>
<script>
    function getPersentExceptLayer(pgMode) {
        var mine = (pgMode == 'order' ? '결제형' : '주문형');
        var other = (pgMode == 'order' ? '주문형' : '결제형');
        var title = '네이버페이 ' + other + ' 예외조건 불러오기';
        var message = '';
        message += '<div class="notice-info">네이버페이 ' + other + ' 예외상품 설정의 예외조건을 불러옵니다.</div>';
        message += '<div class="notice-info">네이버페이 ' + mine + ' 예외조건에 기 설정된 조건은 제외하고 적용됩니다.</div>';
        message += '<table class="table table-cols">';
        message += '<colgroup><col class="width-md"/><col/></colgroup>';
        message += '<tr>';
        message += '<th>예외 조건</th>';
        message += '<td>';
        message += '<label class="checkbox-inline"><input type="checkbox" name="presentExceptType[]" value="goods">예외 상품</label>';
        message += '<label class="checkbox-inline"><input type="checkbox" name="presentExceptType[]" value="category">예외 카테고리</label>';
        message += '</td>';
        message += '</tr>';
        message += '</table>';
        var btnText = {};
        btnText.confirmLabel = '적용';
        btnText.cancelLabel = '닫기';
        dialog_confirm(message, function(result) {
            if (result) {
                var presentExceptType = [];
                $("input[name='presentExceptType[]']:checked").each(function(){
                    presentExceptType.push($(this).val());
                });
                if(presentExceptType.length > 0) {
                    getPersentExcept(pgMode, presentExceptType);
                }
            }
        }, title, btnText);
    }

    function getPersentExcept(pgMode, presentExcept) {
        var data = {
            'mode': 'getPresentExcept',
            'pgMode': pgMode,
            'presentExcept': presentExcept,
            'goods': [],
            'category': []
        };

        if($.inArray('goods', presentExcept) > -1) {
            $("input[name='exceptGoods[]'").each(function(){
                data.goods.push($(this).val());
            });
        }
        if($.inArray('category', presentExcept) > -1) {
            $("input[name='exceptCategory[]'").each(function(){
                data.category.push($(this).val());
            });
        }

        $.ajax({
            method: 'POST',
            url: './naver_pay_ps.php',
            data: data,
        }).success(function (result) {
            if (result.goods && result.goods.length > 0) {
                add_goods_list(result.goods);
            }
            if (result.category && result.category.length > 0) {
                add_category_list(result.category);
            }
        });
    }

    function add_goods_list(goods) {
        var addHtml='';
        var key = $("tr[id^=idExceptGoods]").length;
        $.each(goods, function (index, val) {
            var complied = _.template($('#goodsTemplate').html());
            addHtml += complied({
                key: key++,
                goodsNo: val.goodsNo,
                goodsNm: val.goodsNm,
                goodsImage: val.goodsImage
            });
        });
        $("#exceptGoods").append(addHtml);
        if(!$('input[name="presentExceptFl[]"][value=goods]').is(':checked')) {
            $('input[name="presentExceptFl[]"][value=goods]').attr('checked', true);
            $("#presentFlExcept_goods_tbl, #presentFlExcept_goods_tbl thead, #presentFlExcept_goods_tbl tbody, #presentFlExcept_goods_tbl tfoot").show();
        }
    }

    function add_category_list(category) {
        var addHtml='';
        var key = $("tr[id^=idExceptCategory]").length;
        $.each(category, function (index, val) {
            var complied = _.template($('#categoryTemplate').html());
            addHtml += complied({
                key: key++,
                cateCd: val.cateCd,
                cateNm: val.cateNm
            });
        });
        $("#exceptCategory").append(addHtml);
        if(!$('input[name="presentExceptFl[]"][value=category]').is(':checked')) {
            $('input[name="presentExceptFl[]"][value=category]').attr('checked', true);
            $("#presentFlExcept_category_tbl, #presentFlExcept_category_tbl thead, #presentFlExcept_category_tbl tbody, #presentFlExcept_category_tbl tfoot").show();
        }
    }

    function presentExcept_conf(thisValue) {
        if ($('#presentFlExcept_' + thisValue + "_tbl").is(':hidden')) $('#presentFlExcept_' + thisValue + "_tbl").show();
        else  $('#presentFlExcept_' + thisValue + "_tbl").hide();
    }

    /**
     * 구매 상품 범위 등록 / 예외 등록 Ajax layer
     *
     * @param string typeStr 타입
     * @param string modeStr 예외 여부
     */
    function layer_register(typeStr, modeStr, isDisabled) {
        var layerFormID = 'addPresentForm';

        typeStrId = typeStr.substr(0, 1).toUpperCase() + typeStr.substr(1);

        if (typeof modeStr == 'undefined') {
            var parentFormID = 'present' + typeStrId;
            var dataFormID = 'id' + typeStrId;
            var dataInputNm = 'present' + typeStrId;
            var layerTitle = '조건 - ';
        } else {
            var parentFormID = 'except' + typeStrId;
            var dataFormID = 'idExcept' + typeStrId;
            var dataInputNm = 'except' + typeStrId;
            var layerTitle = '예외 조건 - ';
        }

        // 레이어 창
        if (typeStr == 'goods') {
            var layerTitle = layerTitle + '상품';
            var mode = 'simple';

            $("#" + parentFormID + "Table thead").show();
            $("#" + parentFormID + "Table tfoot").show();
        }
        if (typeStr == 'category') {
            var layerTitle = layerTitle + '카테고리';
            var mode = 'simple';

            $("#" + parentFormID + "Table thead").show();
            $("#" + parentFormID + "Table tfoot").show();
        }

        var addParam = {
            "mode": mode,
            "layerFormID": layerFormID,
            "parentFormID": parentFormID,
            "dataFormID": dataFormID,
            "dataInputNm": dataInputNm,
            "layerTitle": layerTitle,
        };
        //console.log(addParam);

        if (typeStr == 'goods') {
            addParam['scmFl'] = $('input[name="scmFl"]:checked').val();
            addParam['scmNo'] = $('input[name="scmNo"]').val();
        }


        if (!_.isUndefined(isDisabled) && isDisabled == true) {
            addParam.disabled = 'disabled';
        }

        layer_add_info(typeStr, addParam);
    }
</script>
<script type="text/html" id="goodsTemplate">
    <tr id="idExceptGoods_<%=goodsNo%>">
        <td><%=(key + 1)%><input type="hidden" name="exceptGoods[]" value="<%=goodsNo%>"></td>
        <td><%=goodsImage%></td>
        <td><a href="../goods/goods_register.php?goodsNo=<%=goodsNo%>" target="_blank"><%=goodsNm%></a></td>
        <td><input type="button" class="btn btn-sm btn-gray" onclick="$(this).parents('tr')[0].remove();" value="삭제"></td>
    </tr>
</script>
<script type="text/html" id="categoryTemplate">
    <tr id="idExceptGoods_<%=cateCd%>">
        <td><%=(key + 1)%><input type="hidden" name="exceptCategory[]" value="<%=cateCd%>"/></td>
        <td><%=cateNm%></td>
        <td><input type="button" class="btn btn-sm btn-gray" onclick="$(this).parents('tr')[0].remove();" value="삭제"></td>
    </tr>
</script>
