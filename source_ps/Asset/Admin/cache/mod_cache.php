<div class="page-header js-affix">
    <h3><span class="gd-help-manual"><?php echo end($naviMenu->location); ?></span></h3>
    <div class="btn-group">
        <input type="button" id="saveCacheConfig" value="저장" class="btn btn-red"/>
    </div>
</div>
<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>캐시 설정</th>
        <td class="form-inline">
            <label title="사용함" class="radio-inline">
                <input type="radio" name="modCache" value="y" <?php if ($isEnabled) echo 'checked=\'checked\''?> />
                사용함
            </label>
            <label title="사용안함" class="radio-inline">
                <input type="radio" name="modCache" value="n" <?php if (!$isEnabled) echo 'checked=\'checked\''?> />
                사용안함
            </label>
        </td>
    </tr>
</table>
<div class="notice-info">캐시 설정이 ‘사용함’일 경우 관리자에서 변경된 사항이 일시적으로 반영되지 않을 수 있습니다.</div>
<script>
    $(document).ready(function() {
        $('#saveCacheConfig').click(function (e) {
            let errorMsg = "저장에 실패하였습니다. 잠시 후 다시 시도해 주세요.";
            let url = 'cache_config_ps.php';
            let redirect_url = 'mod_cache.php'
            let formData = new FormData();
            formData.append('mode', 'mod_cache');
            formData.append('status', $('input[name="modCache"]:checked').val());
            $.ajax({
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                dataType: 'json',
                success: function (data) {
                    if (data.result === 'success') {
                        alert('저장이 완료 되었습니다.');
                    } else {
                        alert(data.message);
                    }
                    setTimeout(function() {
                        location.href = redirect_url;
                    }, 2000);
                },
                error: function(data) {
                    alert(errorMsg);
                    return false;
                }
            });
        });
    });
</script>
