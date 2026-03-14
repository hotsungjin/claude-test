<div class="table-title gd-help-manual">
<span class="mgt10">네이버페이 주문형 설정/관리</span>
    <span class="flo-right">
        <a href="https://marketing.godo.co.kr/board/view.gd?postNo=230&tab=3" target="_blank" class="btn btn-red-box js-manage-inform">네이버페이 주문형 연동 방법</a>
    </span>
    <!--*필독* 네이버페이 버전 설정 안내입니다.
    네이버페이 버전을 2.1로 업그레이드 하실 수 있습니다.
    업그레이드된 버전 변경 관련 유의사항입니다. 반드시 확인하신 후 변경해 주시기 바랍니다.
    1) 네이버페이 2.1 변경 시 아래 부분이 변경됩니다.
    - 지역별 배송비를 2권역 및 3권역 별로 설정하여 사용하실 수 있습니다.
    - 반품 배송비를 설정하여 반품 및 교환 시 자동으로 배송비를 적용하실 수 있습니다.
    ※ 단, 반품 및 교환 시 기존에 사용하던 기타 비용 청구액과 구매자 전달 메시지는 더 이상 사용하실 수 없습니다.-->
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>네이버페이 주문형 버전설정</th>
        <td>
            <label class="radio-inline"><input type="radio" class="form-control" checked/>최신(v2.1)</label>
        </td>
    </tr>
    <tr>
        <th>사용여부</th>
        <td>
            <label class="radio-inline"><input type="radio" class="form-control" name="useYn" value="y" <?= gd_isset($naverPayData['checked']['useYn']['y']) ?>/>사용함</label>
            <label class="radio-inline"><input type="radio" class="form-control" name="useYn" value="n" <?= gd_isset($naverPayData['checked']['useYn']['n']) ?>/>사용안함</label>
            <div class="notice-info">네이버페이 주문형을 아직 신청하지 않은 경우 먼저 신청을 진행해주시기 바랍니다. <a href="https://admin.pay.naver.com/join/step1" target="_blank">바로가기></a></div>
            <div class="notice-info">시스템 연동 단계일 경우, 공지사항 참고 부탁드립니다. <a href="https://marketing.nhn-commerce.com/board/view.gd?postNo=230" target="_blank">공지사항 바로가기></a> </div>
        </td>
    </tr>
    <tr>
        <th>반품 배송비</th>
        <td>
            <input type="text" name="returnPrice" class="form-control js-number-only" data-number="6,200000,0" value="<?= $naverPayData['deliveryData'][$scmNo]['returnPrice'] ?>">
            <p class="notice-info">반품 배송비를 입력하지 않을 경우 네이버페이센터 > 내정보 > 가입정보 변경의 반품 택배비 설정로 설정됩니다.<br>
                무료 배송 상품 반품 시 & 상품 교환 시 반품 배송비의 2배를 청구합니다. <a href="https://admin.pay.naver.com" target="_blank">네이버페이센터 바로가기></a></p>
        </td>
    </tr>
    <tr>
        <th>지역별 배송비 설정</th>
        <td>
            <div class="in-form"><label class="radio-inline"><input type="radio" class="form-control" name="areaDelivery"
                                                                    value="n" <?= gd_isset($checked['areaDelivery']['n']) ?>/>사용안함</label></div>
            <div class="in-form"><label class="radio-inline"><input type="radio" class="form-control" name="areaDelivery" value="2" <?= gd_isset($checked['areaDelivery']['2']) ?>/>2권역 지역별
                    배송비 사용
                    <span class="notice-info">제주권을 포함한 도서산간 지역에 같은 지역별 배송비를 부과합니다. </span></label>
                <div class="form-inline pdl15">지역별 배송비 : <input type="text" name="area22Price" class="form-control js-number-only" data-number="6,200000,0"
                                                                value="<?= $naverPayData['deliveryData'][$scmNo]['area22Price'] ?>"/>원
                </div>
            </div>
            <div class="in-form"><label class="radio-inline"><input type="radio" class="form-control" name="areaDelivery" value="3" <?= gd_isset($checked['areaDelivery']['3']) ?>/>3권역 지역별
                    배송비 사용
                    <span class="notice-info">제주권과 도서산간 지역에 각각 다른 지역별 배송비를 부과합니다 </span></label>
                <div class="form-inline pdl15">2권역(제주권) 지역별 배송비 : <input type="text" name="area32Price" class="form-control js-number-only" value="<?= $naverPayData['deliveryData'][$scmNo]['area32Price'] ?>"
                                                                         data-number="6,200000,0"/>원
                </div>
                <div class="form-inline pdl15">3권역(제주권 외 도서산간) 지역별 배송비 : <input type="text" name="area33Price" class="form-control js-number-only"
                                                                                value="<?= $naverPayData['deliveryData'][$scmNo]['area33Price'] ?>" data-number="6,200000,0"/>원
                </div>
            </div>
            <div class="notice-danger">※ 주의!! : 네이버페이 주문형으로 결제 시 현재 페이지에서 설정한 네이버페이 지역별 배송비를 따릅니다.([기본설정>배송정책>지역별추가배송비관리]에서 설정한 지역별 배송비는 따르지 않습니다.)</div>
            <div class="notice-info">네이버페이 주문형 지역별 배송비는 네이버페이센터 > 서비스 안내 > 매뉴얼 다운로드에서 가맹점 기본 연동가이드를 다운로드 받으시면 확인하실 수 있습니다.</div>
            <a href="https://admin.pay.naver.com" target="_blank">네이버페이센터 바로가기></a>

        </td>
    </tr>
</table>
<div class="table-title gd-help-manual">네이버페이 주문형 인증설정</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>네이버페이센터 가맹점 ID</th>
        <td><input type="text" class="form-control" name="naverId" value="<?= $naverPayData['naverId'] ?>"></td>
    </tr>
    <tr>
        <th>가맹점 인증키</th>
        <td><input type="text" class="form-control" name="connectId" value="<?= $naverPayData['connectId'] ?>"></td>
    </tr>
    <tr>
        <th>버튼 인증키</th>
        <td><input type="text" class="form-control" name="imageId" value="<?= $naverPayData['imageId'] ?>"></td>
    </tr>
</table>
<div class="notice-info" style="margin-bottom:30px">'네이버공통인증키'는 마케팅 > 네이버 공통유입 스크립트 > 공통유입 스크립트 설정 페이지 내  <b>네이버 공통 인증키</b> 에 등록해주세요.</div>
<div class="table-title gd-help-manual">네이버페이 주문형 버튼 선택</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>PC 용
            버튼선택
        </th>
        <td>
            <div style="padding:5px 0 5px 0;">
                <select name="imgType" id="imgType" onchange="set_imgColorOtion(this);preview();">
                    <option value='A' <?= $naverPayData['selected']['imgType']['A'] ?>>A타입</option>
                    <option value='B' <?= $naverPayData['selected']['imgType']['B'] ?>>B타입</option>
                    <option value='C' <?= $naverPayData['selected']['imgType']['C'] ?>>C타입</option>
                    <option value='D' <?= $naverPayData['selected']['imgType']['D'] ?>>D타입</option>
                    <option value='E' <?= $naverPayData['selected']['imgType']['E'] ?>>E타입</option>
                </select>
                <select name="imgColor" id="imgColor" onchange="preview()">
                    <option value='1'>1색상</option>
                    <option value='2'>2색상</option>
                    <option value='3'>3색상</option>
                </select>
            </div>
            <div style="padding:0 0 5px 0;" id="previewImg"></div>
        </td>
    </tr>
    <tr>
        <th>모바일 용
            버튼선택
        </th>
        <td class="form-inline">
            <div style="padding:5px 0 5px 0;">
                <select name="mobileImgType" id="mobileImgType" onchange="set_mobileImgColorOtion(this);mobilePreview();">
                    <option value="MA" <?= $naverPayData['selected']['mobileImgType']['MA'] ?>>MA타입</option>
                    <option value="MB" <?= $naverPayData['selected']['mobileImgType']['MB'] ?>>MB타입</option>
                </select>
                <select name="mobileImgColor" id="mobileImgColor" onchange="mobilePreview()">
                    <option value="1">1색상</option>
                    <option value="2">2색상</option>
                </select>
            </div>
            <div style="padding:0 0 5px 0;" id="previewMobileImg"></div>
            <div style="padding: 10px 0" class="noline">
                <span>버튼링크 타겟 : </span>
                <input type="radio" name="mobileButtonTarget" value="self" id="mobileButtonTarget-self" <?php echo $naverPayData['checked']['mobileButtonTarget']['self']; ?>/>
                <label for="mobileButtonTarget-self" class="radio-inline">현재창</label>
                <input type="radio" name="mobileButtonTarget" value="new" id="mobileButtonTarget-new" <?php echo $naverPayData['checked']['mobileButtonTarget']['new']; ?>/>
                <label for="mobileButtonTarget-new" class="radio-inline">새창</label>
            </div>
        </td>
    </tr>
</table>

<div class="table-title gd-help-manual">
    네이버페이 주문형 예외상품설정
    <span class="flo-right">
        <button type="button" class="btn btn-white" onclick="getPersentExceptLayer('pay')">네이버페이 결제형 예외조건 불러오기</button>
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
                <thead <?php if (is_array($naverPayData['exceptGoodsNo']) == false) {
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
                if (is_array($naverPayData['exceptGoodsNo'])) {
                    foreach ($naverPayData['exceptGoodsNo'] as $key => $val) {
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
                <tfoot <?php if (is_array($naverPayData['exceptGoodsNo']) == false) {
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
                <thead <?php if (is_array($naverPayData['exceptCateCd']) == false) {
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
                if (is_array($naverPayData['exceptCateCd'])) {
                    foreach ($naverPayData['exceptCateCd']['code'] as $key => $val) {
                        echo '<tr id="idExceptCategory_' . $val . '">' . chr(10);
                        echo '<td>' . ($key + 1) . '<input type="hidden" name="exceptCategory[]" value="' . $val . '" /></td>' . chr(10);
                        echo '<td>' . $naverPayData['exceptCateCd']['name'][$key] . '</td>' . chr(10);
                        echo '<td><input type="button" class="btn btn-sm btn-gray" onclick="field_remove(\'idExceptCategory_' . $val . '\');" value="삭제" /></td>' . chr(10);
                        echo '</tr>' . chr(10);
                    }
                }
                ?>
                </tbody>
                <tfoot <?php if (is_array($naverPayData['exceptCateCd']) == false) {
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
    <a href="https://admin.pay.naver.com/introduction/restrictedOrder" target="_blank" class="btn-link-underline">네이버페이 주문형 취급 제한/불가 상품 ></a>
</div>

<div class="table-title gd-help-manual">네이버페이 주문형 할인 설정</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>상품별 할인 사용여부</th>
        <td>
            <label class="radio-inline"><input type="radio" name="saleFl" value="y" <?=$naverPayData['checked']['saleFl']['y']?>>사용함</label>
            <label class="radio-inline"><input type="radio" name="saleFl" value="n" <?=$naverPayData['checked']['saleFl']['n']?>>사용안함</label>
        </td>
    </tr>
</table>

<div class="table-title gd-help-manual">네이버페이 주문형 주문정보 연동 설정</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>네이버페이 주문형 API 연동</th>
        <td>
            <?php if ($naverPayData['useApi'] == 'y') { ?>
                네이버 체크아웃 API 연동중입니다.
            <?php } else { ?>
                <button type="button" id="naverCheckoutApiRequest" class="btn btn-default">네이버페이 주문형 API 연동</button>
            <?php } ?>
            <div class="notice-info">네이버페이 주문형 API 연동 시 네이버페이 주문 및 구매평을 고도몰 관리자에서 확인할 수 있습니다.</div>
            <div class="notice-danger">네이버페이 주문형 주문 건은 주문자동취소, 자동배송완료, 자동구매확정 처리 제외되므로 유의 바랍니다.</div>
            <div class="notice-info">네이버페이 주문형 주문은 네이버페이센터에서 처리하는 것을 권장합니다. <a href="https://admin.pay.naver.com" target="_blank">네이버페이센터 바로가기></a>
            </div>

        </td>
    </tr>
    <?php if ($naverPayData['useApi'] == 'y') { ?>
        <tr>
            <th>주문 재고연동</th>
            <td>
                <label class="radio-inline"><input type="radio" class="form-control" name="linkStock" value="y" <?= $naverPayData['checked']['linkStock']['y'] ?>/>사용함</label>
                <label class="radio-inline"><input type="radio" class="form-control" name="linkStock" value="n" <?= $naverPayData['checked']['linkStock']['n'] ?>/>사용안함</label>
                <div class="notice-info">네이버페이 주문형 주문이 고도몰로 수집되기까지는 일정 시간이 소요 됩니다. 상품의 재고에 네이버페이 주문 건 반영이 지연됨으로써, 상품의 재고보다 많은 주문이 발생할 수 있습니다</div>
            </td>
        </tr>
        <tr>
            <th>구매평 연동</th>
            <td>
                <label class="radio-inline"><input type="radio" class="form-control" name="reviewFl" value="y" <?= $naverPayData['checked']['reviewFl']['y'] ?>/>사용함</label>
                <label class="radio-inline"><input type="radio" class="form-control" name="reviewFl" value="n" <?= $naverPayData['checked']['reviewFl']['n'] ?>/>사용안함</label>
                <br>

                <div style="margin-top:10px;border-top:1px solid #ddd;padding-top:5px">구매평 연동 게시판 설정</div>

                <label class="radio-inline"><input type="radio" class="form-control" name="reviewChannel" value="shop" <?= $naverPayData['checked']['reviewChannel']['shop'] ?>/>상품후기(goodsReview)게시판 <a href="../board/article_list.php?bdId=goodsreview" target="_blank"> <u>바로가기</u></a></label>
                <?php if($isPlusReview){?>
                    <label class="radio-inline"><input type="radio" class="form-control" name="reviewChannel" value="plusReview" <?= $naverPayData['checked']['reviewChannel']['plusReview'] ?> <?=$disablePlusReview?>/>플러스리뷰 게시판 <a href="../board/plus_review_list.php" target="_blank"><u>바로가기</u></a></label>
                <?php }?>
                <div class="notice-info">
                    프리미엄 구매평에 포함된 동영상은 연동되지 않으니 주의하시기 바랍니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>결제 승인 후,<br>자동 환불 기능</th>
            <td>
                <label class="radio-inline"><input type="radio" class="form-control" name="autoRefund" value="y" <?= $naverPayData['checked']['autoRefund']['y'] ?>/>사용함</label>
                <label class="radio-inline"><input type="radio" class="form-control" name="autoRefund" value="n" <?= $naverPayData['checked']['autoRefund']['n'] ?>/>사용안함</label>
                <div class="notice-info">'결제승인 후 자동환불 기능' 사용 시, 재고 수량을 초과하여 발생한 주문은 자동으로 환불됩니다.</div>
                <div class="notice-danger">다만, 네이버페이 정책상 <결제 승인 후 자동환불>의 경우에는 판매자 귀책사유에 해당되어 판매자 패널티가 부과될 수 있으므로 사용여부를 신중히 고려하시기 바랍니다.
                </div>
                <div class="notice-danger">네이버페이 API 정책상, 재고 차감시점이 "주문시점"인 경우에는 입금대기(무통장)에 대해서는 자동취소 기능을 제공하지는 않습니다.

                </div>
            </td>
        </tr>
    <?php } ?>
</table>

<script>
    $(function () {
        $("#frmNaver").validate({
            submitHandler: function (form) {
                form.target = 'ifrmProcess';
                form.submit();
            },
            rules: {
                naverId: {
                    required: function(){
                        return $("input[name=useYn][value=y]").is(':checked') ==  true;
                    },
                },
            },
            messages: {
                naverId: {
                    required: "네이버페이를 사용하시려면 네이버 가맹점 ID를 입력해주세요.",
                },
            },
        });


        $('#naverCheckoutApiRequest').bind('click',function(){
            if($('input[name=naverId]').val()==''){
                alert('네이버 가맹점 ID를 등록하셔야 네이버페이 API 연동이 가능합니다');
                return;
            }

            if($('input[name=connectId]').val()==''){
                alert('연동 인증키를 등록하셔야 네이버페이 API 연동이 가능합니다');
                return;
            }

            if($('input[name=imageId]').val()==''){
                alert('이미지 인증키를 등록하셔야 네이버페이 API 연동이 가능합니다');
                return;
            }

            ifrmProcess.location.href='naver_pay_ps.php?mode=applyApi';
        });

        present_conf('<?php echo $naverPayData['presentFl'];?>');

        <?php  if (is_array($naverPayData['exceptGoodsNo'])) { ?> $('input[name="presentExceptFl[]"][value=goods]').click();<?php  } ?>
        <?php  if (is_array($naverPayData['exceptCateCd'])) { ?> $('input[name="presentExceptFl[]"][value=category]').click();<?php  } ?>

        var sel_imgType = document.getElementById('imgType');
        var sel_imgColor = document.getElementById('imgColor');
        var imgColor_value = "<?=$naverPayData['imgColor']?>";

        set_imgColorOtion(sel_imgType);
        if (imgColor_value) {
            sel_imgColor.options[imgColor_value - 1].selected = true;
        }

        preview();

        var sel_mobileImgType = document.getElementById('mobileImgType');
        var sel_mobileImgColor = document.getElementById('mobileImgColor');
        var mobileImgColor_value = "<?=$naverPayData['mobileImgColor']?>";

        set_mobileImgColorOtion(sel_mobileImgType);
        if (mobileImgColor_value) {
            sel_mobileImgColor.options[mobileImgColor_value - 1].selected = true;
        }

        mobilePreview();
        $('input[name=areaDelivery]').bind('click', function () {
            val = $(this).val();
            $('[name=area22Price]').prop('disabled', false);
            $('[name=area32Price]').prop('disabled', false);
            $('[name=area33Price]').prop('disabled', false);
            switch (val) {
                case 'n' :
                    $('[name=area22Price]').prop('disabled', true);
                    $('[name=area32Price]').prop('disabled', true);
                    $('[name=area33Price]').prop('disabled', true);
                    break;
                case '2' :
                    $('[name=area32Price]').prop('disabled', true);
                    $('[name=area33Price]').prop('disabled', true);
                    break;
                case '3' :
                    $('[name=area22Price]').prop('disabled', true);
                    break
            }
        });
        $('input[name=areaDelivery]:checked').trigger('click');

    });


    function present_conf(thisValue) {
        $('div[id*=\'presentFl_\']').removeClass();
        $('div[id*=\'presentFl_\']').addClass('display-none');
        $('div[id*=\'presentFl_' + thisValue + '\']').removeClass();
        $('div[id*=\'presentFl_' + thisValue + '\']').addClass('display-block');

        /*  $('span[id*=\'presentFlExcept_\']').removeClass();
         $('span[id*=\'presentFlExcept_\']').addClass('display-inline');
         $('span[id*=\'presentFlExcept_'+thisValue+'\']').removeClass();
         $('span[id*=\'presentFlExcept_'+thisValue+'\']').addClass('display-none');*/
    }

    function set_imgColorOtion(se1) {
        var t = 1;
        var i = 0;
        var k = 0;
        var se2 = document.getElementsByName('imgColor')[0];
        if (se1.selectedIndex >= 2) t = 3;

        for (i = se2.length - 1; i > -1; i--) {
            se2.options[i].value = null;
            se2.options[i] = null;
        }
        for (i = 0; i < t; i++) {
            k = i + 1;
            se2.options[i] = new Option(k + '색상');
            se2.options[i].value = i + 1;
        }
    }

    function preview() {
        var se1 = document.getElementsByName('imgType')[0];
        var se2 = document.getElementsByName('imgColor')[0];
        var img = '';
        img = se1.options[se1.selectedIndex].value + se2.options[se2.selectedIndex].value;
        document.getElementById('previewImg').innerHTML = "<img src='http://gongji.godo.co.kr/userinterface/naverCheckout/images/" + img + "'/>";
    }

    function set_mobileImgColorOtion(se1) {
        var t = 1;
        var i = 0;
        var k = 0;
        var se2 = document.getElementsByName('mobileImgColor')[0];
        t = 1;

        for (i = se2.length - 1; i > -1; i--) {
            se2.options[i].value = null;
            se2.options[i] = null;
        }
        for (i = 0; i < t; i++) {
            k = i + 1;
            se2.options[i] = new Option(k + '색상');
            se2.options[i].value = i + 1;
        }
    }

    function mobilePreview() {
        var se1 = document.getElementsByName('mobileImgType')[0];
        var se2 = document.getElementsByName('mobileImgColor')[0];
        var img = '';
        img = se1.options[se1.selectedIndex].value + se2.options[se2.selectedIndex].value;
        document.getElementById('previewMobileImg').innerHTML = "<img src='http://gongji.godo.co.kr/userinterface/naverCheckout/images/" + img + "'/>";
    }
</script>