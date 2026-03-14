<form id="frmStorage" name="frmStorage" action="./base_ps.php" method="post" class="content-form">
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
    </div>

    <input type="hidden" name="mode" value="file_storage_setting">

    <div class="design-notice-box mgt10" style="margin-bottom:20px;">
        <strong class="c-gdred">이미지 또는 파일의 저장소가 변경되었을 경우에만 사용해 주세요.</strong><br>
        이미지 또는 파일의 저장소가 변경되었을 경우 고도몰에 등록되어 있는 이미지와 파일의 경로만 수정이 가능합니다.<br>
        고도몰에 저장되어 있는 이미지와 파일을 변경된 저장소로 업로드 하지 않으니 주의 하시기 바랍니다.
    </div>

    <div class="table-title gd-help-manual">
        저장소 경로 변경
    </div>
    <table class="table table-cols">
        <tr>
            <th>구분</th>
            <th>항목</th>
            <th>경로 변경</th>
            <th class="text-center">마지막 변경일시</th>
        </tr>
        <?php foreach($storageList as $key => $val) { ?>
            <tr>
                <?php if($key == 'goodsStorage') { ?><td rowspan="<?= count($storageList); ?>">등록 저장소 변경</td><?php } ?>
                <td><?= $val; ?></td>
                <td>
                    <input type="button" class="btn btn-white btn-storage-setting btn-<?=$key;?>" value="변경" data-title="<?=$val;?>" data-target="<?=$key;?>"/>
                    <span class="text-<?=$key;?> display-none">진행중</span>
                </td>
                <td class="text-center"><?= gd_isset($data[$key], '-'); ?></td>
            </tr>
        <?php } ?>
        <?php foreach($urlList as $key => $val) { ?>
            <tr>
                <?php if($key == 'goodsUrl') { ?><td rowspan="<?= count($urlList); ?>">파일 경로 변경</td><?php } ?>
                <td><?= $val; ?></td>
                <td>
                    <input type="button" class="btn btn-white btn-storage-setting btn-<?=$key;?>" value="변경" data-title="<?=$val;?>" data-target="<?=$key;?>"/>
                    <span class="text-<?=$key;?> display-none">진행중</span>
                </td>
                <td class="text-center"><?= gd_isset($data[$key], '-'); ?></td>
            </tr>
        <?php } ?>
    </table>
</form>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        $('.btn-storage-setting').click(function () {
            if($('span[class^=text]').not('.display-none').length > 0) {
                alert('이미 변경중인 항목이 있습니다. 경로 변경 완료 후 시도 바랍니다.');
                return false;
            }
            var title = $(this).data('title');
            var target = $(this).data('target');
            $.get('../policy/layer_base_file_storage_setting.php?title=' + title + '&target=' + target, function () {
                BootstrapDialog.show({
                    title: '저장소 경로 변경하기',
                    size: BootstrapDialog.SIZE_LARGE,
                    message: arguments[0]
                });
            });
        });
    });
    function setting (data) {
        $('.btn-' + data.target).addClass('display-none');
        $('.text-' + data.target).removeClass('display-none');

        $.ajax({
            url: './base_ps.php',
            type: 'post',
            data: data,
        }).success(function () {
            location.reload();
            $('.btn-' + data.target).removeClass('display-none');
            $('.text-' + data.target).addClass('display-none');
        }).error(function () {
            alert('저장소 경로 변경 요청이 실패되었습니다.');
            $('.btn-' + data.target).removeClass('display-none');
            $('.text-' + data.target).addClass('display-none');
        })

    }
    //-->
</script>
