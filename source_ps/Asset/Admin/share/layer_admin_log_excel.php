<div>
    <table class="table table-rows table-fixed">
        <thead>
        <tr>
            <th class="width30p center">일자</th>
            <th class="width20p center">처리자</th>
            <th class="width40p center">파일명</th>
            <th class="width40p center">다운로드 사유</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (gd_isset($data['list']) && is_array($data['list'])) {
            foreach ($data['list'] as $key => $val) {
                $val['data'] = json_decode($val['data'], true);
                $postData = $val['data']['POST'];
                ?>
                <tr>
                    <td class="center"><?=$val['regDt']?></td>
                    <td class="center"><?=$val['managerId']?><br><?=$val['ip']?></td>
                    <td class="center"><?=$postData['downloadFileName']?></td>
                    <td class="center"><?=$postData['excelDownloadReason'];?></td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td class="center" colspan="4">엑셀 다운로드 내역이 없습니다.</td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <div class="text-center"><?=$page->getPage('layer_list_search(\'PAGELINK\')');?></div>
</div>
<script type="text/javascript">
    function layer_list_search(pagelink) {
        if (typeof pagelink == 'undefined') {
            pagelink = '';
        }
        var parameters = {
            'layerFormID': '<?php echo $layerFormID?>',
            'view': '<?php echo $view?>',
            'pagelink': pagelink
        };

        $.get('../share/layer_admin_log_excel.php', parameters, function (data) {
            $('#<?php echo $layerFormID?>').html(data);
        });
    }
</script>
