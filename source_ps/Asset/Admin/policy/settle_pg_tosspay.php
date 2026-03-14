<form id="frmTosspay" name="frmTosspay" action="settle_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="tosspay_config" />
    <input type="hidden" name="pgName" value="tosspay" />
    <div class="page-header js-affix">
        <h3><?= end($naviMenu->location); ?></h3>

        <div class="btn-group">
            <input type="submit" value="저장" class="btn btn-red" />
        </div>
    </div>

    <div class="table-title gd-help-manual">토스페이 연동 설정</div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-lg" />
            <col />
        </colgroup>
        <tr>
            <th>토스페이 사용 설정</th>
            <td>
                <div>
                <?php foreach ($data['useOptions'] as $option): ?>
                    <label class="radio-inline">
                        <input type="radio" name="useMode" value="<?= $option['value'] ?>" <?= $option['checked'] ? 'checked="checked"' : '' ?>>
                        <?= $option['label'] ?>
                    </label> &nbsp;
                <?php endforeach; ?>
                </div>
                <p class="notice-info">‘테스트하기’를 선택하면 결제버튼이 관리자 로그인 시에만 보여지며, 쇼핑몰에서 결제 시 구매 과정 및 실제 결제는 동일하게 처리됩니다.</p>
            </td>
        </tr>
        <tr>
            <th>토스페이 MID</th>
            <td>
                <input type="hidden" name="mId" value="<?= $data['mId']; ?>" />
                <?php if (!empty($data['mId'])): ?>
                    <span class="text-blue bold"><?= $data['mId']; ?></span> <span class="text-blue">(자동 설정 완료)</span>
                <?php else: ?>
                    <div class="notice-info notice-danger">토스페이 신청 전 또는 승인대기 상태입니다. <a href="https://www.nhn-commerce.com/echost/power/add/payment/easypg-intro.gd" target="_blank" class="btn btn-gray btn-sm">토스페이 신청</a></div>
                    <div class="notice-info">토스페이 신청 전인 경우 먼저 서비스를 신청하세요.</div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>토스페이 이용 영역</th>
            <td>
                <?php foreach ($data['scopeOptions'] as $option): ?>
                    <label class="radio-inline">
                        <input type="radio" name="useScope" value="<?= $option['value'] ?>" <?= $option['checked'] ? 'checked="checked"' : '' ?>>
                        <?= $option['label'] ?>
                    </label> &nbsp;
                <?php endforeach; ?>
                <p class="notice-info">
                    쇼핑몰에서 토스페이 이용 영역을 선택하세요.
                </p>
            </td>
        </tr>
    </table>

    <div class="table-title gd-help-manual">토스페이 예외상품 설정</div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md" />
            <col />
        </colgroup>
        <tr>
            <th>예외 조건</th>
            <td>
                <span id="presentFlExcept_goods"><label class="checkbox-inline"><input type="checkbox" name="presentExceptFl[]" value="goods" onclick="presentExcept_conf(this.value)">예외상품</label></span>
                &nbsp;
                <span id="presentFlExcept_category"><label class="checkbox-inline"><input type="checkbox" name="presentExceptFl[]" value="category" onclick="presentExcept_conf(this.value)">예외 카테고리</label></span>
                &nbsp;
                <span id="presentFlExcept_brand"><label class="checkbox-inline"><input type="checkbox" name="presentExceptFl[]" value="brand" onclick="presentExcept_conf(this.value)">예외 브랜드</label></span>
            </td>
        </tr>
        <tr id="presentFlExcept_goods_tbl" style="display:none">
            <th>예외 상품
                <div>
                    <button type="button" class="btn btn-sm btn-gray" onclick="layer_register('goods');">상품 선택</button>
                </div>
            </th>
            <td>
                <table id="exceptGoodsTable" class="table table-cols" style="width:80%">
                    <thead <?= is_array($data['exceptGoodsNo']) ? '' : "style='display:none'" ?>>
                        <tr>
                            <th class="width5p">번호</th>
                            <th class="width10p">이미지</th>
                            <th>상품명</th>
                            <th class="width8p">삭제</th>
                        </tr>
                    </thead>
                    <tbody id="exceptGoods">
                        <?php
                        if (!empty($data['exceptGoodsNo']) && is_array($data['exceptGoodsNo'])):
                            foreach ($data['exceptGoodsNo'] as $idx => $exceptGoods):
                        ?>
                        <tr>
                            <td><input type="hidden" name="exceptGoods[]" value="<?= $exceptGoods['goodsNo'] ?>" /><?= $idx + 1 ?></td>
                            <td><?= gd_html_goods_image($exceptGoods['goodsNo'], $exceptGoods['imageName'], $exceptGoods['imagePath'], $exceptGoods['imageStorage'], 50, $exceptGoods['goodsNm'], '_blank') ?></td>
                            <td><a href="../goods/goods_register.php?goodsNo=<?= $exceptGoods['goodsNo'] ?>" target="_blank"><?= $exceptGoods['goodsNm'] ?></a></td>
                            <td><button type="button" class="btn btn-gray btn-xs" onclick="removeRow(this);">삭제</button></td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                    <tfoot <?= is_array($data['exceptGoodsNo']) ? '' : "style='display:none'" ?>>
                        <tr>
                            <td colspan="4"><input type="button" class="btn btn-sm btn-gray" value="전체삭제" onclick="$('#exceptGoods').html('');"></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr id="presentFlExcept_category_tbl" style="display:none">
            <th>예외 카테고리
                <div>
                    <button type="button" class="btn btn-sm btn-gray" onclick="layer_register('category');">카테고리 선택</button>
                </div>
            </th>
            <td>
                <table id="exceptCategoryTable" class="table table-cols" style="width:80%">
                    <thead <?= is_array($data['exceptCateCd']) ? '' : "style='display:none'"; ?>>
                        <tr>
                            <th class="width5p">번호</th>
                            <th>카테고리</th>
                            <th class="width8p">삭제</th>
                        </tr>
                    </thead>
                    <tbody id="exceptCategory">
                        <?php
                        if (!empty($data['exceptCateCd']['code']) && is_array($data['exceptCateCd']['code'])):
                            foreach ($data['exceptCateCd']['code'] as $idx => $exceptCateCd):
                        ?>
                        <tr>
                            <td><input type="hidden" name="exceptCategory[]" value="<?= $exceptCateCd ?>" /><?= $idx + 1 ?></td>
                            <td><?= $data['exceptCateCd']['name'][$idx] ?? '' ?></td>
                            <td><button type="button" class="btn btn-gray btn-xs" onclick="removeRow(this);">삭제</button></td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                    <tfoot <?= is_array($data['exceptCateCd']) ? '' : "style='display:none'" ?>>
                        <tr>
                            <td colspan="4"><input type="button" class="btn btn-sm btn-gray" value="전체삭제" onclick="$('#exceptCategory').html('');"></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr id="presentFlExcept_brand_tbl" style="display:none">
            <th>예외 브랜드
                <div>
                    <button type="button" class="btn btn-xs btn-gray" onclick="layer_register('brand');">브랜드 선택</button>
                </div>
            </th>
            <td>
                <table id="exceptBrandTable" class="table table-cols mgt20" style="width:80%">
                    <thead <?= is_array($data['exceptBrandCd']) ? '' : "style='display:none'"; ?>>
                        <tr>
                            <th class="width5p">번호</th>
                            <th>브랜드</th>
                            <th class="width8p">삭제</th>
                        </tr>
                    </thead>
                    <tbody id="exceptBrand">
                        <?php
                        if (!empty($data['exceptBrandCd']['code']) && is_array($data['exceptBrandCd']['code'])):
                            foreach ($data['exceptBrandCd']['code'] as $idx => $exceptBrandCd):
                        ?>
                        <tr>
                            <td><input type="hidden" name="exceptBrand[]" value="<?= $exceptBrandCd ?>" /><?= $idx + 1 ?></td>
                            <td><?= $data['exceptBrandCd']['name'][$idx] ?? '' ?></td>
                            <td><button type="button" class="btn btn-gray btn-xs"  onclick="removeRow(this);">삭제</button></td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                    <tfoot <?= is_array($data['exceptBrandCd']) ? '' : "style='display:none'" ?>>
                        <tr>
                            <td colspan="4"><input type="button" value="전체삭제" class="btn btn-default btn-xs btn-gray" onclick="$('#exceptBrand').html('');"></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </table>

</form>

<script type="text/javascript">
    const exceptGoods = <?= json_encode($data['exceptGoods'] ?? []); ?>;
    const exceptCategory = <?= json_encode($data['exceptCategory'] ?? []); ?>;
    const exceptBrand = <?= json_encode($data['exceptBrand'] ?? []); ?>;

    <!--
    $(document).ready(function() {
        $('#frmTosspay').validate({
            submitHandler: function(form) {
                form.target = 'ifrmProcess';
                form.submit();
            },
            rules: {},
            messages: {}
        });
    });

    if (exceptGoods.length > 0) {
        $('input[name="presentExceptFl[]"][value=goods]').click();
    }
    if (exceptCategory.length > 0) {
        $('input[name="presentExceptFl[]"][value=category]').click();
    }
    if (exceptBrand.length > 0) {
        $('input[name="presentExceptFl[]"][value=brand]').click();
    }

    /**
     * 예외 조건 출력
     * @param string type
     */
    function presentExcept_conf(type) {
        let target = $('#presentFlExcept_' + type + "_tbl");
        if (target.is(':hidden')) {
            target.show();
        } else {
            target.hide();
        }
    }

    /**
     * 구매 상품 범위 등록 / 예외 등록 Ajax layer
     *
     * @param string exceptType 타입
     */
    function layer_register(exceptType) {
        const layerFormID = 'addPresentForm';
        let mode = '';
        let typeStrId = '';

        typeStrId = exceptType.substr(0, 1).toUpperCase() + exceptType.substr(1);

        let parentFormID = 'except' + typeStrId;
        let dataFormID = 'idExcept' + typeStrId;
        let dataInputNm = 'except' + typeStrId;
        let layerTitle = '토스페이 예외 조건 - ';

        // 레이어 창
        if (exceptType == 'goods') {
            layerTitle = layerTitle + '상품';
            mode = 'simple';
        }
        if (exceptType == 'category') {
            layerTitle = layerTitle + '카테고리';
            mode = 'simple';
        }
        if (exceptType == 'brand') {
            layerTitle = layerTitle + '브랜드';
            mode = 'simple';
        }

        if (mode == 'simple') {
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
            "fl": 'tosspay'
        };

        layer_add_info(exceptType, addParam);
    }

    function removeRow(btn) {
        const tr = btn.closest('tr');
        if (tr) tr.remove();
    }   // 
    -->
</script>
