<style>
    body { overflow: hidden; }
    .div_center { width: 100%; }
    .div_center_sub { margin: 0 auto; text-align: center; }
    .p_top { padding-top: 20px; }
    .p_bottom { padding-bottom: 20px; }
    .display_none { display: none; }
</style>
<div class="page-header js-affix">
    <h3>서비스 추가</h3>
</div>
<form name="acounterServiceAddForm" id="acounterServiceAddForm" method="post" action="acounter_ps.php">
    <input type="hidden" name="mode" value="aCounterServiceAdd" />
    <input type="hidden" name="domainFl" value="" />
    <div>
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col/>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <tr>
                <th>서비스상품</th>
                <td>
                    <label class="radio-inline"><input type="radio" name="aCounterKind" value="e" checked="checked" />이커머스</label>
                    <label class="radio-inline"><input type="radio" name="aCounterKind" value="m" />모바일 웹</label>
                </td>
            </tr>
            <tr>
                <th>도메인</th>
                <td colspan="3">
                    <div class="form-inline js-service-add-ecom">
                        <select class="form-control" name="aCounterServiceAddE" <?= empty($aCounterServiceAddDomainE) === true ? 'disabled' : ''; ?>>
                            <option value="">도메인 선택</option>
                            <?php foreach ($aCounterServiceAddDomainE as $eKey => $eVal) { ?>
                                <option value="<?= $eVal ?>"><?= $eVal ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-inline js-service-add-mweb display_none">
                        <select class="form-control" name="aCounterServiceAddM" <?= empty($aCounterServiceAddDomainM) === true ? 'disabled' : ''; ?>>
                            <option value="">도메인 선택</option>
                            <?php foreach ($aCounterServiceAddDomainM as $mKey => $mVal) { ?>
                                <option value="<?= $mVal ?>"><?= $mVal ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="div_center p_top p_bottom">
        <div class="div_center_sub"><button type="submit" class="btn btn-black js-service-add">신청</button></div>
    </div>
</form>


<script type="text/javascript">
    <!--
    $(document).ready(function(){
        $('#acounterServiceAddForm').validate({
            ignore: ':hidden',
            dialog: false,
            submitHandler: function (form) {
                // domainFl 넘김
                if($('select[name="aCounterServiceAddE"]').val()){
                    var domain = $('select[name="aCounterServiceAddE"]').val();
                }
                if($('select[name="aCounterServiceAddM"]').val()){
                    var domain = $('select[name="aCounterServiceAddM"]').val();
                }
                var mallNm = domain.split(" ")[0];
                var domainFl = '';
                if(mallNm ==  '[기준몰]'){
                    domainFl = 'kr';
                }else if(mallNm ==  '[영문몰]'){
                    domainFl = 'us';
                }else if(mallNm ==  '[중문몰]'){
                    domainFl = 'cn';
                }else if(mallNm ==  '[일문몰]'){
                    domainFl = 'jp';
                }
                $('#acounterServiceAddForm input[name=domainFl]').val(domainFl);
                form.target = 'ifrmProcess';
                dialog_confirm('서비스 추가 하시겠습니까?', function (result) {
                    if (result) {
                        form.submit();
                    }
                });
            },
        });

        $('input:radio[name="aCounterKind"]').click(function () {
            if($('input:radio[name="aCounterKind"]:checked').val() == 'e'){
                $('.js-service-add-ecom').css('display', 'block');
                $('.js-service-add-mweb').css('display', 'none');
            }else{
                $('.js-service-add-ecom').css('display', 'none');
                $('.js-service-add-mweb').css('display', 'block');
            }
        });
    });

    //-->
</script>
