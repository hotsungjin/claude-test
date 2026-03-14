<form name="frmExchangeRateConfig" id="frmExchangeRateConfig" target="ifrmProcess" action="exchange_rate_ps.php" method="post">
    <input type="hidden" name="mode" value="insert">
    <div class="page-header js-affix">
        <h3><?= end($naviMenu->location); ?></h3>
        <div class="btn-group">
            <button type="submit" class="btn btn-red">저장</button>
        </div>
    </div>
    <div class="table-title gd-help-manual mgt30">
        환율 설정
    </div>
    <table class="table table-rows table-fixed">
        <thead>
        <tr>
            <th class="width5p">번호</th>
            <th class="width15p">화폐</th>
            <th class="width15p">환율처리방식</th>
            <th>환율설정</th>
            <th class="width15p">추가환율조정</th>
            <th class="width15p">최종적용환율(원)</th>
        </tr>
        </thead>
        <tbody>
        <tr align="center">
            <td>1</td>
            <td>KRW - Won (￦)</td>
            <td>-</td>
            <td class="form-inline">1 KRW = <input type="text" value="1" disabled="disabled" class="text-right form-control width-xs"> KRW</td>
            <td> - </td>
            <td></td>
        </tr>
        <?php
        $num = 2;
        foreach ($globalCurrencyData as $currencyVal) {
            $isManual = $config->{$currencyVal['globalCurrencyString']}->type == 'manual';
            ?>
            <input type="hidden" name="currency[]" value="<?=$currencyVal['globalCurrencyString'];?>" />
            <tr align="center">
                <td><?=$num;?></td>
                <td><?=$currencyVal['globalCurrencyString'];?> - <?=$currencyVal['globalCurrencyName'];?> (<?=$currencyVal['globalCurrencySymbol'];?>)</td>
                <td>
                    <select class="exchangeRateConfigType"
                            name="type[<?=$currencyVal['globalCurrencyString'];?>]">
                        <option value="auto" <?=$isManual ? '' : 'selected="selected"';?>>자동환율</option>
                        <option value="manual" <?=$isManual ? 'selected="selected"' : '';?>>수동환율</option>
                    </select>
                </td>
                <td class="form-inline">
                    1 <?=$currencyVal['globalCurrencyString'];?> =
                    <input type="text" class="text-right form-control width-xs"
                           name="manual[<?=$currencyVal['globalCurrencyString'];?>]"
                        <?php if ($isManual) :?>
                            value="<?=$config->{$currencyVal['globalCurrencyString']}->manual;?>"
                        <?php else :?>
                            value="<?=$publicData->{$currencyVal['globalCurrencyString']};?>" disabled="disabled"
                        <?php endif;?>
                    /> KRW
                </td>
                <td class="form-inline">
                    <input type="text" class="text-right form-control width-2xs"
                           name="adjustment[<?=$currencyVal['globalCurrencyString'];?>]"
                           value="<?=$config->{$currencyVal['globalCurrencyString']}->adjustment;?>"
                    />
                </td>
                <td><strong><?=$storeData->{$currencyVal['globalCurrencyString']};?></strong></td>
            </tr>
            <?php
            $num++;
        }
        ?>
        </tbody>
    </table>
</form>
<div class="notice-info">
    환율의 경우, 그 특성상 환차손이 발생할 수 있으므로, 이에 대한 각별한 유의가 필요합니다.<br/>
    환율 정보를 변경할 경우, 그 즉시 모든 상품의 기준금액을 기준으로 일괄적용 됩니다.
</div>
<div class="notice-danger">
    자동환율은 행정안전부 공공데이터 포털의 관세 환율정보 API를 기준으로 매일 갱신 됩니다.<br/>
    <span class="text-blue">네트워크 장애 등으로 인하여 부득이하게 해당 정보를 정상적으로 인지하지 못할 경우에는 그 전일 최종적으로 성공한 값을 표시합니다.</span>
</div>
<div class="notice-info">
    상품에 적용 되는 환율은 “최종적용환율(원)” 입니다. 따라서, 추가환율조정(+/- 숫자 입력 모두 가능)을 통해 환율적용을 유동적으로 변경할 수 있습니다.<br/>
    추가환율조정 값을 입력하는 경우, 환율설정 항목에 적용된 값을 기준으로 환율이 해당 값만큼 반영되어 최종적용환율이 됩니다.<br/>
    <span class="text-blue">(상품에 최종 적용되는 환율 = 최종적용환율(원) = 환율설정 + 추가환율조정)</span>
</div>

<div id="rateLog" class="table-title gd-help-manual mgt30">
    환율 변경 이력
</div>
<table class="table table-rows table-fixed">
    <thead>
    <tr>
        <th style="width: 150px;">작업구분</th>
        <th style="width: 60px;">화폐</th>
        <th>변경이력</th>
        <th class="width15p">변경일시</th>
        <th class="width15p">변경자</th>
        <th class="width15p">변경IP</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($exchangeRateLog as $logVal) { ?>
        <tr style="text-align: center;">
            <td><?= $logVal['type']; ?></td>
            <?php if (is_array($logVal['comment'])) { ?>
                <td style="padding: 0px; margin: 0px;" colspan="2">
                    <table class="table-rows" style="width: 100%; height: 100%; border:none; margin: 0px;">
                        <?php foreach ($logVal['comment'] as $currency => $diffList) { ?>
                            <tr>
                                <td style="width: 60px; padding: 0px;"><?= $currency ?></td>
                                <td style="padding: 0px; text-align: left;">
                                    <?php foreach ($diffList as $value) { ?>
                                        <p>[<?= $value['typeName'] ?>]<?= $value['prev'] ?> → <?= $value['now'] ?></p>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </td>
            <?php } else { ?>
                <td>-</td>
                <td style="text-align: left"><?= $logVal['comment']; ?></td>
            <?php } ?>

            <td><?= $logVal['regDt']; ?></td>
            <td><?= $logVal['managerId']; ?></td>
            <td><?= $logVal['managerIp']; ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<div class="center"><?= $page->getPage(); ?></div>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        // 자동/수동환율 셀렉트박스 변경시 입력폼 활성/비활성 처리
        $('.exchangeRateConfigType').change(function(){
            var manualInput = $(this).closest('tr').find('input:eq(0)');
            if ($(this).val() == 'manual') {
                manualInput.prop('disabled', false);
            } else {
                manualInput.prop('disabled', true);
            }
        });

        $('#rateLog').append('<span class="notice-info mgl15">환율 변경 이력은 최근 12개월까지의 내역을 확인 할 수 있습니다.</span>')
    });
    //-->
</script>
