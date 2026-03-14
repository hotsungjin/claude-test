<form id="frmConfig" action="dburl_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="config"/>
    <input type="hidden" name="company" value="targetingGates"/>
    <input type="hidden" name="mode" value="config"/>
    <input type="hidden" name="ref" value="<?= $ref ?>"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
            <small></small>
        </h3>
        <div class="btn-group">
            <input type="button" id="serviceGuide" value="가이드" class="btn btn-white save marketing-guide" data-url="<?=$guideUrl?>" />
            <input type="submit" value="저장" class="btn btn-red">
        </div>
    </div>

    <div class="table-title">
        <?php echo end($naviMenu->location); ?>
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>타게팅게이츠<br />사용설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="tgFl" value="y" <?php echo gd_isset($checked['tgFl']['y']); ?>/>사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="tgFl" value="n" <?php echo gd_isset($checked['tgFl']['n']); ?>/>사용안함
                </label>
            </td>
        </tr>
        <tr class="dependent">
            <th>서비스 적용범위</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="tgRange" value="all" <?php echo gd_isset($checked['tgRange']['all']); ?>/>PC + 모바일
                </label>
                <label class="radio-inline">
                    <input type="radio" name="tgRange" value="pc" <?php echo gd_isset($checked['tgRange']['pc']); ?>/>PC
                </label>
                <label class="radio-inline">
                    <input type="radio" name="tgRange" value="mobile" <?php echo gd_isset($checked['tgRange']['mobile']); ?>/>모바일
                </label>
            </td>
        </tr>
        <tr class="dependent">
            <th>광고주코드</th>
            <td>
                <input type="text" name="tgCode" class="form-control" style="width:250px;" value="<?php echo gd_isset($data['tgCode']); ?>"/>
                <div class="notice-info" >
                    타게팅게이츠에서 제공하는 광고주코드를 정확히 입력하여 주시기 바랍니다.
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="table-title dependent">
    타게팅게이츠 상품DB URL
</div>

<table class="table table-cols dependent">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>상품DB URL페이지</th>
        <td>
            <?php
            $dbUrlFile = UserFilePath::data('dburl', 'targetingGates', 'targetingGates_all');
            echo '<div><a href="' . $mallDomain . 'partner/tg.php" target="_blank">' . $mallDomain . 'partner/tg.php</a> <a href="' . $mallDomain . 'partner/tg.php" target="_blank" class="btn btn-gray btn-sm">미리보기</a></div>';
            ?>
        </td>
    </tr>
</table>

<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/ncds/MarketingGuideModalManager.js"></script>
<script type="text/javascript">
    <!--
    $(document).ready(function () {
        // 사용여부 라디오 버튼 이벤트 처리
        $('input[name="tgFl"]').on('change', function() {
            if ($(this).val() === 'y') {
                // 사용 선택 시 항목들 보이기
                $('.dependent').show();
            } else {
                // 사용안함 선택 시 항목들 숨기기
                $('.dependent').hide();
            }
        });

        // 페이지 로드 시 초기 상태 설정
        $('input[name="tgFl"]:checked').trigger('change');
    });

    document.addEventListener('DOMContentLoaded', () => {
        const modal = new GodoMarketing.MarketingGuideModalManager();

        document.querySelectorAll('.marketing-guide').forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                modal.open(url);
            });
        });
    });
    //-->
</script>
