<div class="table-title gd-help-manual">네이버페이 결제형 인증설정</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>사용여부</th>
        <td>
            <label class="radio-inline"><input type="radio" class="form-control" name="useYn" value="y" <?= gd_isset($checked['useYn']['y']) ?>/>사용함</label>
            <label class="radio-inline"><input type="radio" class="form-control" name="useYn" value="n" <?= gd_isset($checked['useYn']['n']) ?>/>사용안함</label>
            <div class="notice-info">네이버페이 결제형을 아직 신청하지 않은 경우 먼저 신청을 진행해주시기 바랍니다. <a href="https://admin.pay.naver.com/" target="_blank">네이버페이센터 바로가기></a></div>
        </td>
    </tr>
    <tr>
        <th class="require">파트너 ID</th>
        <td><input type="text" class="form-control" name="partnerId" value="<?= $naverEasyPayData['partnerId'] ?>"></td>
    </tr>
    <tr>
        <th class="require">Client ID</th>
        <td><input type="text" class="form-control" name="clientId" value="<?= $naverEasyPayData['clientId'] ?>"></td>
    </tr>
    <tr>
        <th class="require">Client SECRET</th>
        <td><input type="text" class="form-control" name="clientSecret" value="<?= $naverEasyPayData['clientSecret'] ?>"></td>
    </tr>
</table>
<div class="table-title gd-help-manual">
    네이버페이 결제형 예외상품설정
    <span class="flo-right">
        <button type="button" class="btn btn-white" onclick="getPersentExceptLayer('order')">네이버페이 주문형 예외조건 불러오기</button>
    </span>
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>예외 조건</th>
        <td>
            <span id="presentFlExcept_goods"><label class="checkbox-inline"><input type="checkbox" name="presentExceptFl[]" value="goods" onclick="presentExcept_conf(this.value)">예외
                    상품</label></span>
            <span id="presentFlExcept_category"><label class="checkbox-inline"><input type="checkbox" name="presentExceptFl[]" value="category"
                                                                                      onclick="presentExcept_conf(this.value)">예외 카테고리</label></span>
        </td>
    </tr>
    <tr id="presentFlExcept_goods_tbl" style="display:none">
        <th>예외 상품
            <div><input type="button" class="btn btn-sm btn-gray" value="상품 선택" onclick="layer_register('goods','except');"/></div>
        </th>
        <td>

            <table id="exceptGoodsTable" class="table table-cols" style="width:80%">
                <thead <?php if (is_array($naverEasyPayData['exceptGoodsNo']) == false) {
                    echo "style='display:none'";
                } ?>>
                <tr>
                    <th class="width5p">번호</th>
                    <th class="width10p">이미지</th>
                    <th>상품명</th>
                    <th class="width8p">삭제</th>
                </tr>
                </thead>
                <tbody id="exceptGoods">
                <?php
                if (is_array($naverEasyPayData['exceptGoodsNo'])) {
                    foreach ($naverEasyPayData['exceptGoodsNo'] as $key => $val) {
                        echo '<tr id="idExceptGoods_' . $val['goodsNo'] . '">' . chr(10);
                        echo '<td>' . ($key + 1) . '<input type="hidden" name="exceptGoods[]" value="' . $val['goodsNo'] . '" /></td>' . chr(10);
                        echo '<td>' . gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 50, $val['goodsNm'], '_blank') . '</td>' . chr(10);
                        echo '<td><a href="../goods/goods_register.php?goodsNo=' . $val['goodsNo'] . '" target="_blank">' . $val['goodsNm'] . '</a></td>' . chr(10);
                        echo '<td><input type="button" class="btn btn-gray btn-sm" onclick="field_remove(\'idExceptGoods_' . $val['goodsNo'] . '\');" value="삭제" /></td>' . chr(10);
                        echo '</tr>' . chr(10);
                    }
                }
                ?>
                </tbody>
                <tfoot <?php if (is_array($naverEasyPayData['exceptGoodsNo']) == false) {
                    echo "style='display:none'";
                } ?>>
                <tr>
                    <td colspan="4"><input type="button" class="btn btn-sm btn-gray" value="전체삭제" onclick="$('#exceptGoods').html('');"></td>
                </tr>
                </tfoot>
            </table>

        </td>
    </tr>

    <tr id="presentFlExcept_category_tbl" style="display:none">
        <th>예외 카테고리
            <div><input type="button" class="btn btn-sm btn-gray" value="카테고리 선택" onclick="layer_register('category','except');"/></div>
        </th>
        <td>
            <table id="exceptCategoryTable" class="table table-cols" style="width:80%">
                <thead <?php if (is_array($naverEasyPayData['exceptCateCd']) == false) {
                    echo "style='display:none'";
                } ?>>
                <tr>
                    <th class="width5p">번호</th>
                    <th>카테고리</th>
                    <th class="width8p">삭제</th>
                </tr>
                </thead>
                <tbody id="exceptCategory">
                <?php
                if (is_array($naverEasyPayData['exceptCateCd'])) {
                    foreach ($naverEasyPayData['exceptCateCd']['code'] as $key => $val) {
                        echo '<tr id="idExceptCategory_' . $val . '">' . chr(10);
                        echo '<td>' . ($key + 1) . '<input type="hidden" name="exceptCategory[]" value="' . $val . '" /></td>' . chr(10);
                        echo '<td>' . $naverEasyPayData['exceptCateCd']['name'][$key] . '</td>' . chr(10);
                        echo '<td><input type="button" class="btn btn-sm btn-gray" onclick="field_remove(\'idExceptCategory_' . $val . '\');" value="삭제" /></td>' . chr(10);
                        echo '</tr>' . chr(10);
                    }
                }
                ?>
                </tbody>
                <tfoot <?php if (is_array($naverEasyPayData['exceptCateCd']) == false) {
                    echo "style='display:none'";
                } ?>>
                <tr>
                    <td colspan="4"><input type="button" class="btn btn-sm btn-gray" value="전체삭제" onclick="$('#exceptCategory').html('');"></td>
                </tr>
                </tfoot>
            </table>

        </td>

    </tr>
</table>
<div class="notice-danger pdb20">
    네이버페이 규칙에 의해 매매가 허락되지 않는 ‘취급제한 상품’과 현행 법령상 매매가 금지된 ‘취급불가 상품’은 네이버페이 이용이 제한되는 상품이오니, 예외상품으로 설정하시기 바랍니다.
    <a href="https://admin.pay.naver.com/introduction/restrictedPayment" target="_blank" class="btn-link-underline">네이버페이 결제형 취급 제한/불가 상품 ></a>
</div>
<script>
    $(function () {
        $("#frmNaver").validate({
            submitHandler: function (form) {
                form.target = 'ifrmProcess';
                form.submit();
            },
            rules: {
                partnerId: {
                    required: function() {
                        return $("input[name=useYn][value=y]").is(':checked') ==  true;
                    },
                },
                clientId: {
                    required: function() {
                        return $("input[name=useYn][value=y]").is(':checked') ==  true;
                    },
                },
                clientSecret: {
                    required: function() {
                        return $("input[name=useYn][value=y]").is(':checked') ==  true;
                    },
                },
            },
            messages: {
                partnerId: {
                    required: "네이버페이를 사용하시려면 파트너 ID를 입력해주세요.",
                },
                clientId: {
                    required: "네이버페이를 사용하시려면 Client ID를 입력해주세요.",
                },
                clientSecret: {
                    required: "네이버페이를 사용하시려면 Client SECRET을 입력해주세요.",
                },
            },
        });

        <?php  if (is_array($naverEasyPayData['exceptGoodsNo'])) { ?> $('input[name="presentExceptFl[]"][value=goods]').click();<?php  } ?>
        <?php  if (is_array($naverEasyPayData['exceptCateCd'])) { ?> $('input[name="presentExceptFl[]"][value=category]').click();<?php  } ?>
    });
</script>