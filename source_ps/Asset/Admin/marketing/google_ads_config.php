<form id="frmConfig" action="google_ads_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="config" />
    <input type="hidden" name="company" value="google" />
    <input type="hidden" name="mode" value="config" />
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
        구글 상품 피드 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>사용여부</th>
            <td>
                <label class="radio-inline" >
                    <input type="radio" name="feedUseFl" value="y" <?php echo gd_isset($checked['feedUseFl']['y']); ?> />사용함
                </label>
                <label class="radio-inline" >
                    <input type="radio" name="feedUseFl" value="n" <?php echo gd_isset($checked['feedUseFl']['n']); ?> />사용안함
                </label>
            </td>
        </tr>
    </table>


    <div class="table-title dependent">
        구글 상품 DB URL
    </div>

    <table class="table table-cols dependent">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>상품 DB URL 페이지</th>
            <td>
                <?= $googleDbUrl ?>
                <a href="<?= $googleDbUrl ?>" target="_blank" class="btn btn-gray btn-sm">미리보기</a>
                <div class="notice-danger">
                    해당 URL은 구글 머천트 센터 로그인 > 내 비즈니스 > 제품 > 피드 > [제품 추가]버튼 클릭, ‘다른 제품 소스 추가’ > ‘파일의 제품 추가’  선택 > 파일 링크 입력란에 URL을 업로드하실 수 있습니다.
                </div>
                <div class="notice-info">
                    피드는 1일 2회 오전 7시, 오후 3시에 업데이트 됩니다.
                </div>
            </td>
        </tr>
    </table>

    <div class="table-title">
        전환 스크립트 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>스크립트 사용 여부</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="scriptUseFl" value="y" <?php echo $checked['scriptUseFl']['y']; ?> />사용
                </label>
                <label class="radio-inline">
                    <input type="radio" name="scriptUseFl" value="n" <?php echo $checked['scriptUseFl']['n']; ?> />사용안함
                </label>
            </td>
        </tr>
        <tr>
            <th>전환 ID 추가</th>
            <td>
                <div class="form-inline">
                    <label>AW-</label>
                    <input type="text" name="conversionId" class="form-control" value="<?=$data['conversionId'];?>"
                        onkeyup="this.value=this.value.replace(/\s/g, '')" />
                </div>
            </td>
        </tr>
        <tr>
            <th rowspan="5" >삽입 페이지</th>
            <td>
                <div class="form-inline">
                    <div class="form-inline">
                        <label class="checkbox-inline" style="margin-right: 20px;">
                            <input type="checkbox" name="category[purchase]" value="y" <?=$checked['category']['purchase']['y'];?>> 구매완료
                        </label>
                        <input type="text" name="conversionLabel[purchase]" class="form-control" value="<?=$data['conversionLabel']['purchase'];?>" placeholder="전환 라벨을 입력해주세요."/>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="form-inline">
                    <label class="checkbox-inline" style="margin-right: 20px;">
                        <input type="checkbox" name="category[join]" value="y" <?=$checked['category']['join']['y'];?>> 회원가입
                    </label>
                    <input type="text" name="conversionLabel[join]" class="form-control" value="<?=$data['conversionLabel']['join'];?>" placeholder="전환 라벨을 입력해주세요."/>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="form-inline">
                    <label class="checkbox-inline" style="margin-right: 20px;">
                        <input type="checkbox" name="category[cart]" value="y" <?=$checked['category']['cart']['y'];?>> 장바구니
                    </label>
                    <input type="text" name="conversionLabel[cart]" class="form-control" value="<?=$data['conversionLabel']['cart'];?>" placeholder="전환 라벨을 입력해주세요."/>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="form-inline">
                    <label class="checkbox-inline" style="margin-right: 5px;">
                        <input type="checkbox" name="category[order]" value="y" <?=$checked['category']['order']['y'];?>> 주문서 생성
                    </label>
                    <input type="text" name="conversionLabel[order]" class="form-control" value="<?=$data['conversionLabel']['order'];?>" placeholder="전환 라벨을 입력해주세요."/>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="form-inline">
                    <label class="checkbox-inline" style="margin-right: 20px;">
                        <input type="checkbox" name="category[goods]" value="y" <?=$checked['category']['goods']['y'];?>> 상품상세
                    </label>
                    <input type="text" name="conversionLabel[goods]" class="form-control" value="<?=$data['conversionLabel']['goods'];?>" placeholder="전환 라벨을 입력해주세요."/>
                </div>
            </td>
        </tr>
</table>
</form>
<form id="checkTxtForm" action="google_ads_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="checkTxtFile"/>
</form>

<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/ncds/MarketingGuideModalManager.js"></script>
<script type="text/javascript">
<!--
$(document).ready(function() {
    // 사용여부 라디오 버튼 이벤트 처리
    $('input[name="feedUseFl"]').on('change', function() {
        if ($(this).val() === 'y') {
            // 사용 선택 시 항목들 보이기
            $('.dependent').show();
        } else {
            // 사용안함 선택 시 항목들 숨기기
            $('.dependent').hide();
        }
    });

    // 페이지 로드 시 초기 상태 설정
    $('input[name="feedUseFl"]:checked').trigger('change');

    // 스크립트 사용여부 라디오 버튼 이벤트 처리
    $('input[name="scriptUseFl"]').on('change', function() {
        if ($(this).val() === 'y') {
            // 사용 선택 시 항목들 보이기
            $('tr:has([name="conversionId"])').show();
            $('tr:has([name^="category"])').show();
        } else {
            // 사용안함 선택 시 항목들 숨기기
            $('tr:has([name="conversionId"])').hide();
            $('tr:has([name^="category"])').hide();
        }
    });

    // 페이지 로드 시 초기 상태 설정(scriptUseFl)
    $('input[name="scriptUseFl"]:checked').trigger('change');

    // 체크박스 클릭 이벤트 처리
    $('input[type="checkbox"][name^="category"]').on('change', function() {
        var $input = $(this).closest('.form-inline').find('input[type="text"]');

        // 체크박스가 체크되면 입력 필드 활성화
        if ($(this).is(':checked')) {
            $input.prop('disabled', false);
        } else {
            // 체크박스가 해제되면 입력 필드 비활성화 및 값 초기화
            $input.prop('disabled', true).val('');
        }
    });

    // 페이지 로드 시 초기 상태 설정
    $('input[type="checkbox"][name^="category"]').each(function() {
        var $input = $(this).closest('.form-inline').find('input[type="text"]');
        $input.prop('disabled', !$(this).is(':checked'));
    });

    // 입력 값 검증
    $('#frmConfig').validate({
        submitHandler: function (form) {
            // 스크립트 사용이 체크된 경우에만 검증
            if ($('input[name="scriptUseFl"]:checked').val() === 'y') {
                // 전환 ID 검증
                const conversionId = $('input[name="conversionId"]').val().trim();
                if (!conversionId) {
                    alert('전환 ID를 입력해주세요');
                    $('input[name="conversionId"]').focus();
                    return false;
                }

                // 체크된 삽입 페이지 항목 중 전환 라벨이 비어있는지 검증
                let hasEmptyLabel = false;

                $('input[type="checkbox"][name^="category"]:checked').each(function() {
                    const labelInput = $(this).closest('.form-inline').find('input[type="text"]');
                    if (!labelInput.val().trim()) {
                        hasEmptyLabel = true;
                        return false;
                    }
                });

                if (hasEmptyLabel) {
                    alert('미 입력된 전환 라벨이 있습니다.<br/>전환 라벨을 입력해주세요.');
                    return false;
                }

                // 최소 하나의 삽입 페이지가 선택되었는지 검증
                let isAnyPageSelected = false;
                $('input[type="checkbox"][name^="category"]').each(function() {
                    if ($(this).is(':checked')) {
                        const labelInput = $(this).closest('.form-inline').find('input[type="text"]');
                        if (labelInput.val().trim()) {
                            isAnyPageSelected = true;
                            return false;
                        }
                    }
                });

                if (!isAnyPageSelected) {
                    alert('스크립트가 삽입될 페이지를 1개 이상 선택해주세요.');
                    return false;
                }
            }

            form.submit();
        }
    });
});

function adsActivate() {
    if ($(':radio[name=feedUseFl]:checked').val() == 'y' && $(':radio[name=feedUseFl]:checked').val() != '<?=$data['feedUseFl']?>') {
        console.log('check & create feed');
        BootstrapDialog.show({
            title: '로딩중',
            message: '<img src="<?=PATH_ADMIN_GD_SHARE?>script/slider/slick/ajax-loader.gif"> <b>구글 상품 피드 생성 중입니다. 잠시만 기다려주세요.</b>',
            closable: true
        });
        $("#checkTxtForm").submit(); // txt 파일 생성 요청
    }
}

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
