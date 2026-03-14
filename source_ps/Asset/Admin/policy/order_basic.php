<form id="frmOrderBasic" name="frmOrderBasic" action="order_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="mode" value="updateOrderBasic"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <input type="submit" value="저장" class="btn btn-red">
    </div>
    <!-- 20201111 추가 -->
    <?php if ($orderUpgrade['flag'] == 'F') { ?>
    <script type="text/javascript">
        <!--
        $(document).ready(function () {
            $('#upgradeClick').click(function () {
                <?php if ($orderUpgrade['multiShipping'] == 'T') { ?>
                var alert_message = "복수배송지 '사용중'으로 설정된 상점입니다.<br/><br/><div style='font-size:13px;color:#ff0900;font-weight: bold;'>복수배송지 사용 설정 '사용안함'으로 설정 변경 후 주문 업그레이드 신청 주시기 바랍니다.<br/>주문 업그레이드 완료 후 에는 복수배송지 사용이 불가 합니다.</div><br/>※ 기본설정 > 주문정책 > 주문기본설정 > <span style='font-weight: bold; text-decoration:underline;'>복수배송지 사용설정</span>";
                dialog_alert(alert_message, "경고");
                <?php } elseif ($orderUpgrade['tunning'] == 'T') { ?>
                var alert_message = "<b>작업중인 개발 소스 또는 튜닝 된 파일 확인 주시기 바랍니다.</b><br/>정상적인 주문 업그레이드 기능을 사용 하시려면 업그레이드 진행 전 튜닝 된 파일의 소스 수정 및 튜닝 소스를 원본 소스로 변경 후 업그레이드 진행이 되야 정상적으로 동작 구현이 가능합니다.<br/><br/><div style='color:#ff0900;font-weight: bold;'>주문 업그레이드 진행 전 운영 소스를 반드시 백업 해 놓고 진행해 주시기 바랍니다.<br/><br/>/Component/Order/Order.php<br/>/Component/Order/OrderAdmin.php<br/>두 파일은 업그레이드 이후에는<br/>/Component/Order/OrderNew.php<br/>/Component/Order/OrderAdminNew.php<br/>파일로 교체되오니 튜닝 시 참고 부탁드립니다.</div><br/>&#8251; 업그레이드 완료 후 변경된 파일에 튜닝 소스를 적용해 주시기 바랍니다.<br/><br/><div style='width:100%;border:1px solid;border-radius: 5px;padding: 10px;'>&bull; 파일 개수 : <?php echo count($orderUpgrade['tunningFiles']);?><br/>&bull; 파일 상세 : <?php foreach ($orderUpgrade['tunningFiles'] as $tfVal) { ?><?php echo $tfVal; ?><br/><?php } ?></div>";
                dialog_confirm(alert_message, function(result) {
                    if (result) {
                        upgradeConfirm();
                    }
                }, "주문업데이트 적용 전 확인 필요", {'cancelLabel':'개발소스 확인하기', 'confirmLabel':'주문 업그레이드 진행하기'});
                <?php } else { ?>
                upgradeConfirm();
                <?php } ?>
            });

            function upgradeConfirm() {
                dialog_confirm('주문업그레이드 기능을 사용하시겠습니까?', function(result){
                    if (result) {
                        var ajaxUrl = ".order_ps.php?mode=orderUpgrade";

                        $.ajax({
                            method: "get",
                            cache: false,
                            url: ajaxUrl,
                            dataType: 'json',
                            success: function (data) {
                                if (data.error == 0) {
                                    alert('주문업그레이드 기능 적용이 완료 되었습니다.');
                                    location.reload();
                                } else {
                                    alert(data.message);
                                }
                            },
                            error: function (data) {
                                alert(data.message);

                            }
                        });
                    }
                });
            }
        });
        //-->
    </script>
    <style>
        .banner-area {position:relative;background:#f6f6f6;}
        .banner-area + .table-title {margin-top:30px;}
    </style>
    <div class="banner-area">
        <img src="/admin/gd_share/image/img_banner_upgrade.png" alt="주문업그레이드 배너" usemap="#upgrade-banner">
        <map name="upgrade-banner" id="upgrade-banner">
            <area shape="rect" coords="88,248,379,308" href="#none" alt="" id="upgradeClick">
        </map>
    </div>
    <?php } ?>
    <!-- // 20201111 추가 -->
    <div class="table-title gd-help-manual">
        주문 설정
    </div>
    <table class="table table-cols">
        <colgroup><col class="width-md" /><col /></colgroup>
        <tbody>
        <tr>
            <th>
                결제페이지<br />청약의사 재확인 설정
            </th>
            <td>
                <div class="form-inline radio">
                    <label class="radio-inline">
                        <input type="radio" name="reagreeConfirmFl" value="y" <?=$checked['reagreeConfirmFl']['y']?>> 사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="reagreeConfirmFl" value="n" <?=$checked['reagreeConfirmFl']['n']?>> 사용안함
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <th>자동배송완료</th>
            <td>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="autoDeliveryCompleteFl" value="y" <?=$checked['autoDeliveryCompleteFl']['y']?>> '배송중'으로 주문상태 변경한 뒤
                    </label>
                    <input type="text" name="autoDeliveryCompleteDay" class="form-control width-2xs js-number" value="<?=$data['autoDeliveryCompleteDay']?>" title="" required="required" > 일 후 '배송완료'로 자동 주문상태 변경
                </div>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="autoDeliveryCompleteFl" value="n" <?=$checked['autoDeliveryCompleteFl']['n']?>> 사용안함
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <th>자동구매확정</th>
            <td>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="autoOrderConfirmFl" value="y" <?=$checked['autoOrderConfirmFl']['y']?>> '배송완료'로 주문상태 변경한 뒤
                    </label>
                    <input type="text" name="autoOrderConfirmDay" class="form-control width-2xs js-number" value="<?=$data['autoOrderConfirmDay']?>" title="" required="required" > 일 후 '구매확인'으로 자동 주문상태 변경
                </div>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="autoOrderConfirmFl" value="n" <?=$checked['autoOrderConfirmFl']['n']?>> 사용안함
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <th>환불 진행 재확인<br/>사용설정</th>
            <td>
                <div class="form-inline radio">
                    <label class="radio-inline">
                        <input type="radio" name="refundReconfirmFl" value="y" <?=$checked['refundReconfirmFl']['y']?>> 사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="refundReconfirmFl" value="n" <?=$checked['refundReconfirmFl']['n']?>> 사용안함
                    </label>
                </div>
                <div class="notice-info">
                    환불 처리 시 환불 진행 여부를 한번 더 확인하여 안전하게 환불 처리를 할 수 있습니다.
                </div>
            </td>
        </tr>
        <?php if (gd_is_plus_shop(PLUSSHOP_CODE_USEREXCHANGE)) { ?>
        <tr>
            <th>고객 교환/반품/환불<br>신청기능 사용설정</th>
            <td>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="userHandleFl" value="y" <?=$checked['userHandleFl']['y']?>> 사용함
                    </label>
                    <div class="user-handle-view <?php echo $data['userHandleFl'] == 'n' ? 'display-none' : ''; ?>">
                        <label><input type="checkbox" name="userHandleAdmFl" value="y" <?=$checked['userHandleAdmFl']['y']?>> 고객 교환/반품/환불 신청 주문 표기</label>
                        <?php if (gd_is_plus_shop(PLUSSHOP_CODE_SCM)) { ?>
                            (<label><input type="checkbox" name="userHandleScmFl" value="y" <?=$checked['userHandleScmFl']['y']?>> 공급사 관리자 동일적용</label>)
                        <?php } ?>
                        <div class="notice-info">체크 시 주문리스트와 주문상세페이지에 고객 클레임 신청정보(고객 교환/반품/환불 신청정보)가 표기됩니다.</div>
                    </div>
                </div>
                <div class="form-inline radio">
                    <label>
                        <input type="radio" name="userHandleFl" value="n" <?=$checked['userHandleFl']['n']?>> 사용안함
                    </label>
                    <span class="notice-info">사용안함 선택 시 쇼핑몰에서 구매자가 직접 교환/반품/환불 신청할 수 없습니다.</span>
                </div>
            </td>
        </tr>
        <tr class="user-handle-view <?php echo $data['userHandleFl'] == 'n' ? 'display-none' : ''; ?>">
            <th>자동환불 사용설정</th>
            <td>
                <div class="form-inline radio">
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoFl" value="y" <?=$checked['userHandleAutoFl']['y']?>> 사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoFl" value="n" <?=$checked['userHandleAutoFl']['n']?>> 사용안함
                    </label>
                </div>
                <div class="notice-info">사용함으로 설정 시 구매자가 환불을 요청하는 경우 별도의 승인없이 바로 환불완료 처리 됩니다.<br/>- 자동 환불 완료는 <span class="text-red">전체환불 요청</span> 시에만 처리 되며, 주문상태가 <span class="text-red">"입금완료 상태의 그룹(결제완료)"인 경우에만</span> 자동 환불 처리 됩니다.</div>
            </td>
        </tr>
        <tr class="user-handle-view <?php echo $data['userHandleFl'] == 'n' ? 'display-none' : ''; ?>">
            <th>자동환불 상품범위</th>
            <td>
                <div class="form-inline radio">
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoScmFl" value="y" <?=$checked['userHandleAutoScmFl']['y']?>> 제한 없음
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoScmFl" value="n" <?=$checked['userHandleAutoScmFl']['n']?>> 공급사 상품 제외
                    </label>
                </div>
                <div class="notice-info">공급사 상품 제외 선택 시, 환불 요청 주문 건에 공급사 상품이 포함된 경우 운영자 승인을 통한 환불로 처리하셔야 합니다.</div>
            </td>
        </tr>
        <tr class="user-handle-view <?php echo $data['userHandleFl'] == 'n' ? 'display-none' : ''; ?>">
            <th>자동환불 결제수단</th>
            <td>
                <div class="form-inline radio">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="userHandleAutoSettle[]" value="c" <?=$checked['userHandleAutoSettle']['c']?> disabled> PG결제 (신용카드)
                    </label>
                    <?php if($paycoAutoCancelable == true){ ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="userHandleAutoSettle[]" value="p" <?=$checked['userHandleAutoSettle']['p']?>> 페이코
                    </label>
                    <?php } ?>
                    <?php if($naverAutoCancelable == true){ ?>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="userHandleAutoSettle[]" value="n" <?=$checked['userHandleAutoSettle']['n']?>> 네이버페이
                        </label>
                    <?php } ?>
                    <?php if($kakaoAutoCancelable == true){ ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="userHandleAutoSettle[]" value="k" <?=$checked['userHandleAutoSettle']['k']?>> 카카오페이
                    </label>
                    <?php } ?>
                    <?php if ($tossAutoCancelable === true): ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="userHandleAutoSettle[]" value="t" <?=$checked['userHandleAutoSettle']['t']?>> 토스페이
                    </label>
                    <?php endif; ?>
                </div>
                <div class="notice-info">무통장 입금/계좌이체/가상계좌/에스크로/휴대폰결제/네이버페이 주문형으로 결제한 주문은 자동 환불이 불가합니다.</div>
            </td>
        </tr>
        <tr class="user-handle-view <?php echo $data['userHandleFl'] == 'n' ? 'display-none' : ''; ?>">
            <th>자동환불 추가설정</th>
            <td>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">재고 수량 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoStockFl" value="y" <?=$checked['userHandleAutoStockFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoStockFl" value="n" <?=$checked['userHandleAutoStockFl']['n']?>> 복원안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">쿠폰 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoCouponFl" value="y" <?=$checked['userHandleAutoCouponFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="userHandleAutoCouponFl" value="n" <?=$checked['userHandleAutoCouponFl']['n']?>> 복원안함
                    </label>
                </div>
            </td>
        </tr>
        <?php } ?>
        <?php if (gd_is_plus_shop(PLUSSHOP_CODE_MULTISHIPPING) && $orderUpgrade['flag'] == 'F') { ?>
            <tr>
                <th>복수배송지 사용설정</th>
                <td>
                    <div class="form-inline radio">
                        <label>
                            <input type="radio" name="useMultiShippingFl" value="y" <?=$checked['useMultiShippingFl']['y']?>> 사용함
                        </label>
                    </div>
                    <div class="form-inline radio">
                        <label>
                            <input type="radio" name="useMultiShippingFl" value="n" <?=$checked['useMultiShippingFl']['n']?>> 사용안함
                        </label>
                    </div>
                    <div class="notice-info">
                        한 주문에 복수의 배송지를 적용할 수 있는 기능의 사용여부를 설정합니다.
                    </div>
                    <div class="notice-info">
                        <span class="c-gdred">
                            복수배송지 기능을 원활히 사용하시려면, 주문리스트 > 상품주문번호별 리스트 및 주문 상세정보의 [조회항목설정]에서 '배송지' 항목을 반드시 추가해주시기 바랍니다.
                        </span>
                    </div>
                </td>
            </tr>
        <?php } ?>
        <?php if ($data['safeNumberFl'] == 'y') { ?>
        <tr>
            <th>안심번호 서비스 사용</th>
            <td>
                <?php if ($data['safeNumberServiceFl'] == 'off') { ?>
                <div class="text-danger">안심번호 서비스를 일시적으로 사용할 수 없습니다.</div>
                <?php } else { ?>
                <input type="hidden" name="safeNumberFl" value="<?=$data['safeNumberFl'];?>">
                <div class="form-inline radio">
                    <label class="radio-inline">
                        <input type="radio" name="useSafeNumberFl" value="y" <?=$checked['useSafeNumberFl']['y']?>> 사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="useSafeNumberFl" value="n" <?=$checked['useSafeNumberFl']['n']?>> 사용안함
                    </label>
                </div>
                <span class="notice-info">고객 연락처 대신 가상의 전화번호를 부여하여 고객의 개인정보를 보호할 수 있도록 하는 서비스입니다. 예) 0504-XXXX-XXXX</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>

    <div class="table-title gd-help-manual">
        클레임 처리 기본 노출 설정
    </div>
    <table class="table table-cols">
        <colgroup><col class="width-md" /><col /></colgroup>
        <tbody>
        <tr>
            <th>
                관리자 주문취소 시
            </th>
            <td>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">재고 수량 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnStockFl" value="y" <?=$checked['c_returnStockFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnStockFl" value="n" <?=$checked['c_returnStockFl']['n']?>> 복원안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">쿠폰 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnCouponFl" value="y" <?=$checked['c_returnCouponFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnCouponFl" value="n" <?=$checked['c_returnCouponFl']['n']?>> 복원안함
                    </label>
                </div>
                <?php if (gd_is_plus_shop(PLUSSHOP_CODE_GIFT) === true) { ?>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">사은품 지급 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnGiftFl" value="y" <?=$checked['c_returnGiftFl']['y']?>> 지급함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="c_returnGiftFl" value="n" <?=$checked['c_returnGiftFl']['n']?>> 지급안함
                    </label>
                </div>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th>
                관리자 교환접수 시
            </th>
            <td>
                <div class="form-inline radio">
                    <span class="width300 display-inline-block">교환취소 상품에 사용한 쿠폰 복원 여부</span>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnCouponFl" value="y" <?=$checked['e_returnCouponFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnCouponFl" value="n" <?=$checked['e_returnCouponFl']['n']?>> 복원안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width300 display-inline-block">교환취소 상품의 사은품 지급여부</span>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnGiftFl" value="y" <?=$checked['e_returnGiftFl']['y']?>> 지급함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnGiftFl" value="n" <?=$checked['e_returnGiftFl']['n']?>> 지급안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width300 display-inline-block">교환추가 상품에 적용된 마일리지 지급 여부</span>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnMileageFl" value="y" <?=$checked['e_returnMileageFl']['y']?>> 지급함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnMileageFl" value="n" <?=$checked['e_returnMileageFl']['n']?>> 지급안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width300 display-inline-block">교환추가 상품에 적용된 쿠폰 마일리지 지급 여부</span>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnCouponMileageFl" value="y" <?=$checked['e_returnCouponMileageFl']['y']?>> 지급함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="e_returnCouponMileageFl" value="n" <?=$checked['e_returnCouponMileageFl']['n']?>> 지급안함
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <th>
                관리자 환불완료 시
            </th>
            <td>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">재고 수량 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="r_returnStockFl" value="y" <?=$checked['r_returnStockFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="r_returnStockFl" value="n" <?=$checked['r_returnStockFl']['n']?>> 복원안함
                    </label>
                </div>
                <div class="form-inline radio">
                    <span class="width-lg display-inline-block">쿠폰 복원 설정</span>
                    <label class="radio-inline">
                        <input type="radio" name="r_returnCouponFl" value="y" <?=$checked['r_returnCouponFl']['y']?>> 복원함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="r_returnCouponFl" value="n" <?=$checked['r_returnCouponFl']['n']?>> 복원안함
                    </label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <span class="notice-info">주문 클레임 처리 시 재고, 쿠폰복원 등 처리방안의 기본 선택값을 설정할 수 있습니다. 클레임 처리 화면 접근 시 설정한 값이 기본으로 선택되어 노출됩니다.</span>
</form>
<script type="text/javascript">
    <!--
    $(document).ready(function () {
        $('#frmOrderBasic').validate({
            submitHandler: function (form) {
                form.submit();
            },
            rules: {
                autoDeliveryCompleteDay: {
                    min: 1
                },
                autoOrderConfirmDay: {
                    min: 1
                }
            },
            messages: {
                autoDeliveryCompleteDay: {
                    min: "자동배송완료일을 1일 이상으로 입력해주세요."
                },
                autoOrderConfirmDay: {
                    min: "자동구매확정일을 1일 이상으로 입력해주세요."
                }
            }
        });

        $('input[name="userHandleFl"]').click(function(){
            switch (this.value) {
                case 'y':
                    $('.user-handle-view').removeClass('display-none');
                    break;
                case 'n':
                    $('.user-handle-view').addClass('display-none');
                    break;
            }
        });
    });
    //-->
</script>
