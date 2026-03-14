<div class="table-title">
    <?=$title;?>
</div>
<table class="table table-rows">
    <thead>
    <tr>
        <th class="width20p">구분</th>
        <th>도메인</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>변경 대상 도메인</td>
        <td>
            <div class="form-inline">
                <?=gd_select_box('beforeProtocol', 'beforeProtocol', $conf['storage'], null, 'local', '=저장소 선택=', '', 'form-control'); ?>
                <input type="text" name="beforeDomain" class="form-control width-xl" placeholder="http(s)를 포함하여 도메인을 입력하세요." disabled="disabled">
            </div>
        </td>
    </tr>
    <tr>
        <td>변경 도메인</td>
        <td>
            <div class="form-inline">
                <?=gd_select_box('afterProtocol', 'afterProtocol', $conf['storage'], null, 'local', '=저장소 선택=', '', 'form-control'); ?>
                <input type="text" name="afterDomain" class="form-control width-xl" placeholder="http(s)를 포함하여 도메인을 입력하세요." disabled="disabled">
            </div>
        </td>
    </tr>
    </tbody>
</table>
<div>
    <p class="notice-info">저장소 경로 변경은 1회 1번 변경이 가능합니다.</p>
    <p class="notice-info">변경 대상 도메인이 다수일 경우 도메인의 수만큼 반복해서 변경 도메인으로 변경하시기 바랍니다.</p>
    <p class="notice-info">도메인을 직접 입력할 경우 입력한 도메인을 저장하지 않음으로 신중하게 변경하시기 바랍니다.</p>
</div>
<div class="text-center">
    <input type="button" value="취소" class="btn btn-lg btn-white js-close" />
    <input type="button" value="확인" class="btn btn-lg btn-black js-submit" />
</div>
<script type="text/javascript">
    <!--
    $(document).ready(function(){
        //취소
        $('.js-close').click(function(){
            layer_close();
        });

        $('.js-submit').click(function (e) {
            var data = {
                mode: 'storageSetting',
                target: '<?=$target;?>',
                name: '<?=$title;?>',
                beforeProtocol: $('#beforeProtocol').val(),
                afterProtocol: $('#afterProtocol').val(),
                beforeDomain: $.trim($('input[name=beforeDomain]').val()),
                afterDomain: $.trim($('input[name=afterDomain]').val())
            };
            if(!data.beforeDomain) {
                alert('변경 대상 도메인을 입력해 주세요.');
                return false;
            }
            if(!data.afterDomain) {
                alert('변경 도메인을 입력해 주세요.');
                return false;
            }
            if(data.beforeDomain == data.afterDomain) {
                alert('변경전 변경후 도메인이 같습니다. 다시 선택해 주세요.');
                return false;
            }
            if(data.beforeProtocol == 'url' || data.afterProtocol == 'url') {
                if((data.beforeProtocol == 'url' && data.beforeDomain.match(/http:\/\//) == null && data.beforeDomain.match(/https:\/\//) == null) || (data.afterProtocol == 'url' && data.afterDomain.match(/http:\/\//) == null && data.afterDomain.match(/https:\/\//) == null)) {
                    alert('http(s)를 포함하여 도메인을 입력해 주세요.');
                    return false;
                }
                var pattern = /^(((http(s?))\:\/\/)?)([0-9a-zA-Z\-]+\.)+[a-zA-Z]{2,6}(\:[0-9]+)?(\/\S*)?/;
                if((data.beforeProtocol == 'url' && pattern.test(data.beforeDomain) == false) || (data.afterProtocol == 'url' && pattern.test(data.afterDomain) == false)) {
                    alert('올바르지 않은 도메인 유형입니다. 확인 후 다시 시도해 주세요');
                    return false;
                }
            }
            layer_close();
            setting(data);
            alert('저장소 경로 변경 요청이 완료되었습니다.');
        });

        $('#beforeProtocol').on('change', function () {
            image_storage_selector(this);
        });
        $('#afterProtocol').on('change', function () {
            image_storage_selector(this);
        });

        function image_storage_selector(obj) {
            var storageName = $(obj).val();
            var input = $(obj).next();
            if (storageName == '') {
                return;
            }
            if (storageName == 'url') {
                input.val('').attr('disabled', false);
            } else if(storageName == 'local') {
                input.val('/').attr('disabled', true);
            } else {
                input.val(storageName).attr('disabled', true);
            }
        }

        function init() {
            $('#beforeProtocol').trigger('change');
            $('#afterProtocol').trigger('change');
        }

        init();
    });

    //-->
</script>
