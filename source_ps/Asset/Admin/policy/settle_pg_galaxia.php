<script type="text/javascript">
    $(document).ready(function(){
        const toggles = {
            'installmentToggle': '<?php echo $data['installmentFl']; ?>',
            'eggDisplayBannerConf': '<?php echo $data['eggDisplayFl']; ?>'
        };

        for (const id in toggles) {
            if (toggles[id] !== 'y' && (id !== 'eggDisplayBannerConf' || toggles[id] === 'n')) {
                display_toggle(id, 'hide');
            }
        }

        // 가상계좌 입금기한
        $('input[name="vBankDay"]').on('input', function() {
            const value = $(this).val();
            if (isNaN(value) || value <= 0 || value > 30) {
                $(this).val('');
            }
        });
    });
</script>

<div class="table-title gd-help-manual">
    <?php echo $pgNm;?> 일반결제 설정
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-lg"/>
        <col/>
    </colgroup>
    <tr>
        <th>PG사 모듈 버전</th>
        <td>PC BillgatePay webpay (V 3.8.0 - 20250213) / Mobile BillgatePay webpay (V 3.8.0 - 20250213)</td>
    </tr>
    <tr>
        <th>결제수단 설정</th>
        <td>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[pc][useFl]" value="y" <?php echo gd_isset($checked['pc']['y']);?> <?php echo gd_isset($disabled['pc']['y']);?> /> 신용카드</label>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[pb][useFl]" value="y" <?php echo gd_isset($checked['pb']['y']);?> <?php echo gd_isset($disabled['pb']['y']);?> /> 계좌이체</label>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[pv][useFl]" value="y" <?php echo gd_isset($checked['pv']['y']);?> <?php echo gd_isset($disabled['pv']['y']);?> /> 가상계좌</label>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[ph][useFl]" value="y" <?php echo gd_isset($checked['ph']['y']);?> <?php echo gd_isset($disabled['ph']['y']);?> /> 휴대폰</label>
        </td>
    </tr>
    <tr>
        <th><?php echo $pgNm;?> merchantID</th>
        <td class="form-inline">
            <?php if ($data['pgAutoSetting'] === 'y') { ?>
                <span class="text-blue bold"><?php echo $data['pgId'];?></span> <span class="text-blue">(자동 설정 완료)</span>
                <div class="display-inline-block width-sm"><input type="button" onclick="settleKindUpdate();" value="<?php echo $pgNm;?> 정보 갱신" class="btn btn-gray btn-sm" /></div>
            <?php } else { ?>
                <div class="notice-info notice-danger">전자결제(PG) 신청 전 또는 승인대기상태입니다.</div>
                <div class="notice-info">
                    신청 전인 경우 먼저 서비스를 신청하세요.
                    <a href="https://www.nhn-commerce.com/echost/power/add/payment/pg-intro.gd" target="_blank" class="btn btn-gray btn-sm">전자결제서비스(PG) 신청</a>
                </div>
            <?php }?>
        </td>
    </tr>
    <tr>
        <th><?php echo $pgNm;?> merchantKey</th>
        <td>
            <?php if ($data['pgAutoSetting'] === 'y') { ?>
                <span class="text-blue bold"><?php echo $data['pgKey'];?></span> <span class="text-blue">(자동 설정 완료)</span>
            <?php } else { ?>
                <div class="notice-info notice-danger">전자결제(PG) 신청 전 또는 승인대기상태입니다.</div>
            <?php }?>
        </td>
    </tr>
    <tr>
        <th>일반 할부 사용 설정</th>
        <td>
            <label class="radio-inline"><input type="radio" name="installmentFl" value="n" <?php echo gd_isset($checked['installmentFl']['n']);?> onclick="display_toggle('installmentToggle','hide');" /> 일시불 결제</label>
            <label class="radio-inline"><input type="radio" name="installmentFl" value="y" <?php echo gd_isset($checked['installmentFl']['y']);?> onclick="display_toggle('installmentToggle','show');" /> 일반 할부 결제</label>
        </td>
    </tr>
    <tr id="installmentToggle">
        <th>일반 할부 기간 설정</th>
        <td class="form-inline">
            <?php
            for ($i = 2; $i <= $pgPeriod['noInterest']; $i++) {
                $isChecked = !empty($checked['installmentPeroid'][$i]);
                $strClass = $isChecked ? 'bold' : 'nobold';
                $checkedAttribute = $isChecked ? 'checked' : '';

                echo '<label class="display-inline-block width-2xs hand">
                    <input type="checkbox" name="installmentPeroid[]" value="'.$i.'" '.$checkedAttribute.' onclick="checked_bold(this)" />
                    <span class="'.$strClass.'">'.$i.'개월</span>
                  </label>';

                if ($i % 12 == 0) {
                    echo '<br/>';
                }
            }
            ?>
        </td>
    </tr>
    <tr>
        <th>가상계좌 입금기한</th>
        <td class="form-inline">
            <input type="text" name="vBankDay" value="<?php echo $data['vBankDay'];?>" maxlength="2" max="31" class="form-control js-number width-3xs" /> 일
        </td>
    </tr>
    <tr>
        <th>가상계좌 입금내역<br />실시간 통보 URL</th>
        <td class="input_area snote">
            <?php
            echo '<div class="font-eng bold">' . $vBankReturnUrl . '</div>';
            echo '<div class="notice-info">' . $pgNm . ' 상점 관리자모드 [우측 상단 [기술지원] > 좌측 암호화 정보 확인 – 상용서버 > 설정 MID 우측 통지방식 [HTTP] 클릭 후 추가] 이후 적용됩니다.</div>';
            echo '<div class="notice-info">쇼핑몰도메인은 대표도메인 사용을 권장합니다.</div>';
            ?>
        </td>
    </tr>
    <tr>
        <th>앱 스키마 설정<br />app scheme</th>
        <td>
            <input type="text" name="appScheme" value="<?php echo $data['appScheme'];?>" class="form-control width-2xl" />
        </td>
    </tr>
    <tr>
        <th>복합과세안내</th>
        <td>
            <div>필독 : PG사와의 과세 & 복합과세 계약 유의사항</div>
            <div class="notice-info notice-danger">복합과세(면/과세 상품 동시 취급)로 쇼핑몰을 운영하신다면, 반드시 먼저 PG사와 복합과세 계약을 신청하시기 바랍니다.</div>
            <div class="notice-info">복합과세로 PG사와 계약이 되어 있지 않은 상태에서 복합과세로 설정 시 일부 면세 상품의 부분취소가 어려울 수 있습니다.</div>
        </td>
    </tr>
    <tr>
        <th>가상계좌 환불기능 사용설정</th>
        <td>
            <label class="radio-inline"><input type="radio" name="vacctRefundFl" value="y" <?php echo gd_isset($checked['vacctRefundFl']['y']); ?> />사용함</label>
            <label class="radio-inline"><input type="radio" name="vacctRefundFl" value="n" <?php echo gd_isset($checked['vacctRefundFl']['n']); ?> />사용안함</label>

            <div class="notice-info">가상계좌 환불 기능은 빌게이트의 별도 부가서비스 입니다. 사용을 위해서는 추가 계약이 필요하므로 빌게이트 측으로 문의 후 설정하여 사용 바랍니다.</div>
            <div class="notice-info">부가서비스를 추가 계약 하지 않고 사용함으로 설정하여 환불 처리를 하는 경우 정상적으로 환불처리가 되지 않으므로 유의 바랍니다.</div>
        </td>
    </tr>
    <tr>
        <th><?php echo $pgNm;?> 사이트</th>
        <td>
            <a href="https://cpadmin.billgate.net" target="_blank" class="btn btn-gray btn-sm"><?php echo $pgNm;?> 상점 관리자모드 바로가기</a>
            <a href="https://www.billgate.net/billgate/main/home.do" target="_blank" class="btn btn-gray btn-sm"><?php echo $pgNm;?> 사이트 바로가기</a>
        </td>
    </tr>
</table>

<div class="table-title gd-help-manual">
    에스크로 설정
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-lg"/>
        <col/>
    </colgroup>
    <tr>
        <th>PG사 모듈 버전</th>
        <td>PC BillgatePay webpay (V 3.8.0 - 20250213) / Mobile BillgatePay webpay (V 3.8.0 - 20250213)</td>
    </tr>
    <tr>
        <th>사용 설정</th>
        <td>
            <label class="radio-inline"><input type="radio" name="escrowFl" value="y" <?php echo gd_isset($checked['escrowFl']['y']);?> /> 사용함</label>
            <label class="radio-inline"><input type="radio" name="escrowFl" value="n" <?php echo gd_isset($checked['escrowFl']['n']);?> /> 사용안함</label>
        </td>
    </tr>
    <tr>
        <th>결제수단 설정</th>
        <td>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[eb][useFl]" value="y" <?php echo gd_isset($checked['eb']['y']);?> <?php echo gd_isset($disabled['eb']['y']);?> /> 계좌이체</label>
            <label class="checkbox-inline"><input type="checkbox" name="settleKind[ev][useFl]" value="y" <?php echo gd_isset($checked['ev']['y']);?> <?php echo gd_isset($disabled['ev']['y']);?> /> 가상계좌</label>
        </td>
    </tr>
    <tr>
        <th>구매 안전 표시</th>
        <td>
            <label class="radio-inline">
                <input type="radio" name="eggDisplayFl" value="a" <?php echo gd_isset($checked['eggDisplayFl']['a']);?> onclick="display_toggle('eggDisplayBannerConf','show');" /> 표시함
            </label>
            <label class="radio-inline">
                <input type="radio" name="eggDisplayFl" value="n" <?php echo gd_isset($checked['eggDisplayFl']['n']);?> onclick="display_toggle('eggDisplayBannerConf','hide');" /> 표시안함
            </label>
        </td>
    </tr>
    <tr id="eggDisplayBannerConf">
        <th>구매 안전 표시 로고</th>
        <td class="form-inline">
            <input type="hidden" name="eggDisplayBannerFl" value="self" />
            <div>
                <textarea name="eggDisplayBannerTmp" rows="5" class="form-control width90p"><?php echo gd_htmlspecialchars_decode($data['eggDisplayBanner']);?></textarea>
            </div>
            <div class="notice-info">
                [<?php echo $pgNm;?> 상점 관리자 로그인 > 에스크로]에서 제공 받은 내용을 넣으세요.
                <a href="https://cpadmin.billgate.net/additional/buySafeConfirm/buySafeConfirm.gx" class="btn btn-gray btn-sm" target="_blank">[<?php echo $pgNm;?> 표시 로고 바로가기]</a>
            </div>
        </td>
    </tr>
</table>

<div class="table-title gd-help-manual">
    현금영수증 설정
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-lg"/>
        <col/>
    </colgroup>
    <tr>
        <th>PG사 모듈 버전</th>
        <td>PC BillgatePay webpay (V 3.8.0 - 20250213) / Mobile BillgatePay webpay (V 3.8.0 - 20250213)</td>
    </tr>
    <tr>
        <th>사용 설정</th>
        <td>
            <label class="radio-inline"><input type="radio" name="cashReceiptFl" value="y" <?php echo gd_isset($checked['cashReceiptFl']['y']);?> /> 사용함</label>
            <label class="radio-inline"><input type="radio" name="cashReceiptFl" value="n" <?php echo gd_isset($checked['cashReceiptFl']['n']);?> /> 사용안함</label>
        </td>
    </tr>
    <tr>
        <th>필수 신청</th>
        <td>
            <label class="radio-inline"><input type="radio" name="cashReceiptAboveFl" value="y" <?php echo gd_isset($checked['cashReceiptAboveFl']['y']);?> /> 사용함</label>
            <label class="radio-inline"><input type="radio" name="cashReceiptAboveFl" value="n" <?php echo gd_isset($checked['cashReceiptAboveFl']['n']);?> /> 사용안함</label>
            <div class="notice-info">현금성 거래 시, 고객이 주문서 작성 시점에 현금영수증을 필수로 신청하도록 설정하는 기능입니다.</div>
        </td>
    </tr>
    <tr>
        <th>신청 기간 제한</th>
        <td>
            <div class="form-inline">
                결제완료일로 부터
                <?php echo gd_select_box('cashReceiptPeriod','cashReceiptPeriod', gd_array_change_key_value($cashReceiptPeriod),'일',$data['cashReceiptPeriod']);?>
                이내 신청 가능
            </div>
        </td>
    </tr>
    <tr>
        <th>현금영수증 발급방법</th>
        <td>
            <label class="radio-inline"><input type="radio" name="cashReceiptAutoFl" value="y" <?php echo gd_isset($checked['cashReceiptAutoFl']['y']);?> /> 자동 발급</label>
            <label class="radio-inline"><input type="radio" name="cashReceiptAutoFl" value="n" <?php echo gd_isset($checked['cashReceiptAutoFl']['n']);?> /> 관리자 직접 발급</label>
        </td>
    </tr>
</table>
