<form id="frmConfig" action="dburl_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="config" />
    <input type="hidden" name="company" value="kakaoMoment" />
    <input type="hidden" name="mode" value="config" />
    <input type="hidden" name="ref" value="<?= $ref ?>"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
            <small></small>
        </h3>
        <div class="btn-group">
            <input type="button" id="serviceGuide" value="가이드" class="btn btn-white save marketing-guide" data-url="<?=$guideUrl?>" />
            <input type="submit" value="저장" class="btn btn-red btn-save-config">
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
            <th>카카오 픽셀<br />사용설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="kakaoMomentFl" value="y" <?php echo gd_isset($checked['kakaoMomentFl']['y']); ?>/>사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="kakaoMomentFl" value="n" <?php echo gd_isset($checked['kakaoMomentFl']['n']); ?>/>사용안함
                </label>
                <div class="notice-info">
                    '사용함' 설정 시 카카오 픽셀 스크립트가 활성화됩니다.
                </div>
            </td>
        </tr>
        <tr class="dependent">
            <th>서비스 적용범위</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="kakaoMomentRange" value="all" <?php echo gd_isset($checked['kakaoMomentRange']['all']); ?> <?php echo gd_isset($disabled); ?>/>PC + 모바일
                </label>
                <label class="radio-inline">
                    <input type="radio" name="kakaoMomentRange" value="pc" <?php echo gd_isset($checked['kakaoMomentRange']['pc']); ?> <?php echo gd_isset($disabled); ?>/>PC
                </label>
                <label class="radio-inline">
                    <input type="radio" name="kakaoMomentRange" value="mobile" <?php echo gd_isset($checked['kakaoMomentRange']['mobile']); ?> <?php echo gd_isset($disabled); ?>/>모바일
                </label>
            </td>
        </tr>
        <tr class="dependent">
            <th>고유코드(Track ID)</th>
            <td>
                <input type="text" name="kakaoMomentCode" maxlength="20" class="form-control js-number-only" style="width:250px;" value="<?php echo gd_isset($data['kakaoMomentCode']); ?>" <?php echo gd_isset($disabled); ?>/>
                <div class="notice-info" >
                    카카오 픽셀에서 제공하는 고유코드(Track ID)를 정확히 입력하여 주시기 바랍니다.
                </div>
            </td>
        </tr>
    </table>
</form>
<div class="notice-info">
    별도로 카카오 픽셀 스크립트를 설치한 경우, 데이터가 중복으로 집계 될 수 있습니다.
</div>

<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/ncds/MarketingGuideModalManager.js"></script>
<script type="text/javascript">
    <!--
    $(document).ready(function () {
        // 미사용시 범위 및 코드 disabled 처리
        $('input[name="kakaoMomentFl"]').on('click', function () {
            if ($(this).val() === 'n') {
                $('input[name="kakaoMomentRange"]').attr('disabled', 'disabled');
                $('input[name="kakaoMomentCode"]').attr('disabled', 'disabled');
            } else {
                $('input[name="kakaoMomentRange"]').removeAttr('disabled');
                $('input[name="kakaoMomentCode"]').removeAttr('disabled');
            }
        });
        $(document).on('click','.btn-save-config', function (e) {
            if($('input[name=kakaoMomentFl]:checked').val() == "y" && $('input[name=kakaoMomentCode]').val() == "") {
                alert("고유코드(Track ID)를 입력해 주세요.");
                return false;
            }
        });

        // 사용여부 라디오 버튼 이벤트 처리
        $('input[name="kakaoMomentFl"]').on('change', function() {
            if ($(this).val() === 'y') {
                // 사용 선택 시 항목들 보이기
                $('.dependent').show();
            } else {
                // 사용안함 선택 시 항목들 숨기기
                $('.dependent').hide();
            }
        });

        // 페이지 로드 시 초기 상태 설정
        $('input[name="kakaoMomentFl"]:checked').trigger('change');
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
