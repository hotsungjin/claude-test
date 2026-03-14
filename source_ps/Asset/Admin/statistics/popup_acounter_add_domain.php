<style>
    .div_center { width: 100%; }
    .div_center_sub { margin: 0 auto; text-align: center; }
    .p_top { padding-top: 20px; }
    .p_bottom { padding-bottom: 20px; }
</style>
<div class="page-header js-affix">
    <h3>분석 대상 도메인 설정</h3>
</div>
<form name="acounterAddDomainForm" id="acounterAddDomainForm" method="post" action="acounter_ps.php">
    <input type="hidden" name="mode" value="aCounterAddDomain" />
    <input type="hidden" name="acDomainFl" value="<?= $acDomainFl ?>" />
    <input type="hidden" name="acRequestDomain" value="<?= $acRequestDomain ?>" />
    <div>
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <tr>
                <th>신청 도메인</th>
                <td class="form-inline bold">
                    <label><?= $acRequestDomain ?></label>
                </td>
            </tr>
            <tr>
                <th>추가 분석<br>도메인 선택</th>
                <td>
                    <div class="form-inline js-service-add-ecom">
                        <?php foreach ($acAddDomain as $aKey => $aVal) { ?>
                            <div class="mgt5">
                                <label class="checkbox-inline"><input type="checkbox" name="acAddDomain[]" value="<?= $aVal ?>" <?= $checked['acAddDomain'][$aVal] ?> />
                                    <?= $aVal ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if (count($acAddDomain) > 2) {?>
                        <p class="mgt15 notice-danger">최대 2개 선택가능</p>
                    <?php } ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="div_center p_top p_bottom">
        <div class="div_center_sub"><button type="submit" class="btn btn-black js-service-add">저장</button></div>
    </div>
</form>


<script type="text/javascript">
    <!--
    // 추가 분석 도메인 수량 체크
    $(function(){
        $('input[name="acAddDomain[]"]').click(function(){
            var limit = 2;
            var acAddDomainCnt = $('input[name="acAddDomain[]"]:checked').length;

            if (acAddDomainCnt > limit) {
                alert('추가 분석 도메인은 최대 ' + limit + '개까지만 설정할 수 있습니다.');
                $(this).prop('checked', false);
            }
        });
    });

    $(document).ready(function(){
        $('#acounterAddDomainForm').validate({
            ignore: ':hidden',
            dialog: false,
            submitHandler: function (form) {
                var limit = 2;
                var acAddDomainCnt = $('input[name="acAddDomain[]"]:checked').length;

                if (acAddDomainCnt < 1) {
                    alert('추가 분석 도메인을 선택해 주세요');
                    return false;
                }

                if (acAddDomainCnt > limit) {
                    alert('추가 분석 도메인은 최대 ' + limit + '개까지만 설정할 수 있습니다.');
                    return false;
                }

                dialog_confirm('분석 대상 도메인 설정을 하시겠습니까?', function (result) {
                    if (result) {
                        $.post('./acounter_ps.php', $('#acounterAddDomainForm').serializeArray(), function (data) {
                            dialog_alert(data.message, '확인', {isReload: true});
                            return false;
                        });
                    }
                });
            },
        });
    });
    //-->
</script>
