<style rel="stylesheet" type="text/css">
    .acounterDiv {display:none;}
    .agree-div {height:50px;}
    textarea {width:100%; height:100px; overflow-y:scroll; border:1px solid #D5D5D5;}
    .ace-agreement {white-space:pre; height:200px;}
    .red { display:none; color: #8E0022;}
    .p_bottom { padding-bottom: 10px; }
    .p_top { padding-top: 20px; }
</style>

<form name="acounterForm" id="acounterForm" method="post" action="acounter_ps.php">
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <div class="btn-group">
            <?php if($mode == 'aCounterModify'){?>
                <button type="button" class="btn btn-red btn-modify">저장</button>
            <?php }?>
        </div>
    </div>

    <input type="hidden" name="mode" value="<?=$mode?>" />
    <input type="hidden" name="aCounterGCode" value="<?=$gCode?>" />
    <input type="hidden" name="aCounterDomain" value="" />
    <input type="hidden" name="domainFl" value="" />
    <!-- 에이스카운터 서비스 신청 전 -->
    <div class="acounterJoinDiv">
        <div class="table-title gd-help-manual">
            가입정보
        </div>
        <table class="table table-cols">
            <colgroup>
                <col class="width-md"/>
                <col/>
                <col class="width-md"/>
                <col/>
            </colgroup>
            <tr>
                <th class="require">가입자명</th>
                <td class="form-inline">
                    <input type="text" name="customerNm" value="" maxlength=20  class="form-control width-lg"/>
                </td>
                <th class="require">E-Mail</th>
                <td colspan="3" class="form-inline">
                    <input type="text" name="customerEmail[]" value="" <?php echo $readonly . $disabled; ?> maxlength=64 class="form-control width-lg"/> @
                    <input type="text" id="customerEmail" name="customerEmail[]" value="" <?php echo $readonly . $disabled; ?> maxlength=255 class="form-control width-md js-email-domain"/>
                    <?php echo gd_select_box('email_domain', null, $emailDomain, null, '',null,$disabled,'email_domain'); ?>
                </td>
            </tr>
            <tr>
                <th class="require">휴대폰번호</th>
                <td class="form-inline">
                    <input type="text" name="customerPhone" value="" maxlength=20 class="form-control width-lg"/>
                </td>
                <th>뉴스레터 수신여부</th>
                <td class="form-inline">
                    <label class="radio-inline">
                        <input type="radio" name="customerMailing" value="y" checked="checked" />수신허용
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="customerMailing" value="n" />수신거부
                    </label>
                </td>
            </tr>
        </table>
        <div class="notice-info">서비스 안내 (결제정보/종료일) 발송 시 E-mail 및 휴대폰번호로 안내해 드립니다.</div>
        <div class="notice-danger p_bottom">에이스카운터 데이터 분석은 서비스 신청 도메인을 기준으로 진행 됩니다. 추가로 연결 된 도메인에 대한 데이터 분석은 추후 지원 될 예정입니다.</div>
        <div class="p_top">
            <h5 class="table-title">에이스카운터 약관동의 및 서비스신청</h5>
            <div id="aceAgreement" class="form-inline panel pd10 overflow-y-scroll ace-agreement">
                <?=$terms?>
            </div>
            <div class="agree-div">
                <input type="checkbox" id="acounterServiceAgree" name="acounterServiceAgree" />
                <label for="acounterServiceAgree">(필수)에이스카운터 서비스 신청 및 이용약관에 동의합니다.</label>
            </div>

            <h5 class="table-title">개인정보 제3자 제공</h5>
            <div id="aceAgreement" class="form-inline panel pd10 overflow-y-scroll ace-agreement">
                <?=$privateTerms?>
            </div>
            <div class="agree-div">
                <input type="checkbox" id="acounterPrivateAgree" name="acounterPrivateAgree" />
                <label for="acounterPrivateAgree">(필수) 개인정보 제3자 제공에 동의합니다.</label>
            </div>
            <div class="ta-c"><button type="button" class="btn btn-lg btn-red btn-register">서비스 신청</button></div>
        </div>
    </div>

    <!-- 에이스카운터 서비스 신청 후 -->
    <div class="acounterSettingDiv">
        <div class="p_bottom">
            <select class="form-control" name="aCounterServiceAdd">
                <?php foreach ($aCounterServiceDomain as $key => $val) { ?>
                    <option value="<?= $key ?>"><?= $val ?></option>
                <?php } ?>
            </select>
        </div>
        <table class="table table-cols">
            <colgroup><col class="width-md" /><col/></colgroup>
            <tr>
                <th>사용 여부</th>
                <td>
                    <label class="radio-inline"><input type="radio" name="aCounterFl" value="y" <?php echo gd_isset($checked['aCounterUseFl']['y']);?> />사용함</label>
                    <label class="radio-inline"><input type="radio" name="aCounterFl" value="n" <?php echo gd_isset($checked['aCounterUseFl']['n']);?> />사용안함</label>
                </td>
            </tr>
            <tr>
                <th>서비스명</th>
                <td class="js-kind"><?=$kind?></td>
            </tr>
            <tr>
                <th>GCODE</th>
                <td class="js-gCode"><?=$gCode?></td>
            </tr>
            <tr>
                <th>사용기간</th>
                <td class="js-expDt"><?=$expDt?> 까지 <span class="red ">(사용기간 만료)</span></td>
            </tr>
            <tr>
                <th>회원 아이디별<br />분석 설정</th>
                <td>
                    <label class="radio-inline"><input type="radio" name="aCounterMemIdAnalyticsFl" value="1" <?php echo gd_isset($checked['aCounterMemIdAnalyticsFl']['1']);?> />사용함</label>
                    <label class="radio-inline"><input type="radio" name="aCounterMemIdAnalyticsFl" value="0" <?php echo gd_isset($checked['aCounterMemIdAnalyticsFl']['0']);?> />사용안함</label>
                    <div class="notice-info mgb10">사용을 위해서는 <span class="text-darkred">에이스카운터 관리자> 서비스관리> 회원 아이디별 분석신청</span>에서 별도 신청 이후 사용이 가능하므로 에이스카운터 측으로 신청 후 설정하여 사용 바랍니다.</div>
                    <div class="notice-info mgb10">회원 아이디별 신청이 승인된 후 에이스카운터 담당자의 안내에 따라 ‘사용함’을 설정하신 경우 회원 아이디별 분석이 가능합니다.</div>
                    <div class="notice-info mgb10">회원 아이디별 분석 설정을 사용하시는 경우 에이스카운터 담당자의 안내에 따라 쇼핑몰 <a href="/policy/base_agreement_with_private.php?mallSno=1&mode=private" target="_blank" class="btn-link">개인정보 처리방침</a>내용에 약관 추가가 필요합니다.</div>
                </td>
            </tr>
            <tr class="hidden">
                <th>분석 사이트 관리</th>
                <td>
                    <button type="button" class="btn btn-sm btn-black js-ace-site-link">분석 사이트 관리</button>
                    <div class="notice-info mgb10">대표/서브 도메인 변경 혹은 추가 시 분석 사이트 관리에서 추가해 주셔야 원활한 서비스 이용이 가능합니다.</div>
                    <div class="notice-info mgb10">해외몰 추가 시 기본 또는 대표 도메인을 사용하실 경우 별도 url을 추가하지 않아도 데이터가 수집됩니다.</div>
                </td>
            </tr>
            <tr>
                <th>서비스 추가</th>
                <td>
                    <button type="button" class="btn btn-sm btn-black js-ace-service_add">서비스 추가</button>
                    <div class="notice-info mgb10">해외몰, 모바일 웹 사이트를 구분하여 데이터 측정을 희망하는 경우, 서비스를 추가로 신청해주세요.</div>
                    <div class="notice-info mgb10">이커머스와 모바일 웹은 서비스 별로 이용요금이 책정됩니다.</div>
                </td>
            </tr>
            <tr>
                <th>분석 대상 도메인 설정</th>
                <td>
                    <button type="button" class="btn btn-sm btn-black js-ace-site_add">추가 도메인 설정</button>
                    <div class="notice-info mgb10">서비스 신청 도메인 외 추가 도메인에서도 데이터 분석을 희망하는 경우 설정해주세요.</div>
                    <div class="notice-info mgb10">추가 분석 대상으로 등록 된 도메인은 에이스카운터 관리자 > 서비스 정보 수정 페이지에서 확인할 수 있습니다.</div>
                </td>
            </tr>
        </table>
    </div>
</form>
<form name="loginForm" id="loginForm" method="post">
    <input type="hidden" name="godoSno" value="<?=$godoSno?>" class="loginCheck" />
</form>
<script type="text/javascript">
    $(function () {
        // 에이스카운터 신청여부에 따른 관리단 노출 제어
        if($("#acounterForm input[name='mode']").val() == 'aCounterRegist') {
            $(".acounterJoinDiv").show();
            $(".acounterSettingDiv").hide();
        }else if($("#acounterForm input[name='mode']").val() == 'aCounterModify'){
            $(".acounterSettingDiv").show();
            $(".acounterJoinDiv").hide();
        }

        // 에이스카운터 셀렉트박스 1개 미만일떄 disabled 처리
        if($('select[name="aCounterServiceAdd"] option').size() <= 1){
            $('select[name="aCounterServiceAdd"]').attr('disabled', true);
        }
        var selectUrl = $("select[name='aCounterServiceAdd'] option:selected").val();
        $('input[name="aCounterDomain"]').val(selectUrl);

        // 에이스카운터 포커싱 해제
        $("input[name='customerNm']").bind('keyup', function () {
            $('input[name="customerNm"]').css('border','1px solid #D5D5D5');
            $(this).val($(this).val().replace(/[^가-힣ㄱ-ㅎㅏ-ㅣa-zA-Z\u119E\u11A2\u318D\u2022\u2025a\u00B7\uFE55]/gi, ''));
        });

        $("input[name='customerEmail[]']").bind('keyup', function () {
            $('input[name="customerEmail[]"]').css('border','1px solid #D5D5D5');
            $(this).val($(this).val().replace(/[가-힣ㄱ-ㅎㅏ-ㅣ\u119E\u11A2\u318D\u2022\u2025\u00B7\uFE55]/, ''));
        });

        $("input[name='customerPhone']").bind('keyup', function () {
            $('input[name="customerPhone"]').css('border','1px solid #D5D5D5');
            $(this).val($(this).val().replace(/[^0-9]/, ''));
        });

        $("input[name='acounterServiceAgree']").mouseleave(function(){
            $("input:checkbox[name='acounterServiceAgree']").css('border','1px solid #D5D5D5');
        });
        $("input[name='acounterPrivateAgree']").mouseleave(function () {
            $("input:checkbox[name='acounterPrivateAgree']").css('border','1px solid #D5D5D5');
        });

        // e-mail 직접입력 시, 세팅
        $('.email_domain').change(function () {
            var val =  $(this).val() == 'self' ? '' :  $(this).val();
            $(this).closest('td').find('.js-email-domain').val(val);
        });

        // 에이스카운터 사용신청
        $(".btn-register").click(function() {
            if(($("input[name='customerNm']").val()).length == 0){
                alert("가입자명을 입력해주세요.");
                $("input[name='customerNm']").focus();
                $('input[name="customerNm"]').css('border','1px solid #fa2828');
                return false;
            }
            if(($("input[name='customerEmail[]']").val()).length == 0){
                alert("E-Mail을 입력해주세요.");
                $("input[name='customerEmail[]']").focus();
                $('input[name="customerEmail[]"]').css('border','1px solid #fa2828');
                return false;
            }
            if(($("input[name='customerPhone']").val()).length == 0){
                alert("휴대폰번호를 입력해주세요.");
                $("input[name='customerPhone']").focus();
                $('input[name="customerPhone"]').css('border','1px solid #fa2828');
                return false;
            }
            if(!$("#acounterServiceAgree").is(":checked")) {
                alert("이용약관에 동의해주세요.");
                $("input[name='acounterServiceAgree']").focus();
                $("input:checkbox[name='acounterServiceAgree']").css('border','1px solid #fa2828');
                return false;
            }
            if(!$("#acounterPrivateAgree").is(":checked")) {
                alert("개인정보 제3자 제공에 동의해주세요.");
                $("input[name='acounterPrivateAgree']").focus();
                $("input:checkbox[name='acounterPrivateAgree']").css('border','1px solid #fa2828');
                return false;
            }
            $("#acounterForm").submit();
        });

        // 설정 저장
        $(".btn-modify").click(function() {
            if($('select[name="aCounterServiceAdd"]').val()){
                var domain = $('select[name="aCounterServiceAdd"] option:selected').text();
            }

            var mallNm = domain.split(" ")[1];
            var domainFl = '';
            if(mallNm == '[기준몰]'){
                domainFl = 'kr';
            }else if(mallNm == '[영문몰]'){
                domainFl = 'us';
            }else if(mallNm == '[중문몰]'){
                domainFl = 'cn';
            }else if(mallNm == '[일문몰]'){
                domainFl = 'jp';
            }
            $('#acounterForm input[name=domainFl]').val(domainFl);

            if($(":radio[name=aCounterFl]:checked").val() == 'n') {
                if(confirm("사용여부를 [사용안함]으로 설정하시면 에이스카운터에 쇼핑몰 정보가 쌓이지 않아 통계분석이 되지 않습니다.\n계속하시겠습니까?")) {
                    $("#acounterForm").submit();
                }
            } else {
                $("#acounterForm").submit();
            }
        });

        // 분석 사이트 관리
        /*
        $(".js-ace-site-link").click(function () {
            $.post('../statistics/acounter_ps.php', {
                mode: 'aCounterLogin'
            }, function (result) {
                if(result) {
                    var popup = window.open('about:blank', "aCounterLogin");
                    result = result.replace(/\s+/, "");//왼쪽 공백제거
                    result = result.replace(/\s+$/g, "");//오른쪽 공백제거
                    result = result.replace(/\n/g, "");//행바꿈제거
                    result = result.replace(/\r/g, "");//엔터제거
                    popup.document.writeln(result);
                    setTimeout(function () {
                        popup.init();
                    }, 3000)
                }
            });
        });
        */

        // 서비스 추가
        $(".js-ace-service_add").click(function () {
            var checks = true;
            $('.loginCheck').each(function () {
                if($(this).val() == '' || $(this).val() == undefined) {
                    checks = false;
                    return false;
                }
            });
            if(checks) {
                window.open('../statistics/popup_service_add.php', 'popup_goods_select', 'width=650, height=450, scrollbars=yes');
            }
        });

        // 분석 대상 도메인 설정
        $(".js-ace-site_add").click(function () {
            var checks = true;
            $('.loginCheck').each(function () {
                if($(this).val() == '' || $(this).val() == undefined) {
                    checks = false;
                    return false;
                }
            });
            if(checks) {
                // 선택된 도메인
                if($('select[name="aCounterServiceAdd"]').val()){
                    var acRequestDomain = $('select[name="aCounterServiceAdd"] option:selected').val();
                }
                window.open('../statistics/popup_acounter_add_domain.php?acRequestDomain=' + acRequestDomain, 'popup_acounter_add_domain', 'width=650, height=450, scrollbars=yes');
            }
        });

        expDtChk();

        // 셀렉트박스 클릭 이벤트
        $("select[name='aCounterServiceAdd']").change(function () {
            var kind = '';
            $.post('../statistics/acounter_ps.php', {
                mode: 'aCounterSelect',
                domain: $(this).val(),
            }, function (result) {
                var list = $.parseJSON(result);
                if(list.data.kind == 'ecom'){
                    kind = '이커머스';
                }else if(list.data.kind == 'mweb'){
                    kind = '모바일웹';
                }
                var useFl = list.data.useFl;
                var memIdAnalyticsFl = list.data.memIdAnalyticsFl;
                $('select[name="aCounterServiceAdd"]').val(list.data.domain);
                $('input[name="aCounterDomain"]').val(list.data.domain);
                $('.js-kind').text(kind);
                $('.js-gCode').text(list.data.gCode);
                $('.js-expDt').text(list.data.expDt + ' 까지');
                expDtChk('listExpDt');
                $('input:radio[name="aCounterFl"][value="'+useFl+'"]').prop('checked', true);
                $('input:radio[name="aCounterMemIdAnalyticsFl"][value="'+memIdAnalyticsFl+'"]').prop('checked', true);
            });
        });
    });

    function expDtChk(mode)
    {
        // 만료일
        var today = new Date();
        var month = today.getMonth() + 1;
        var day = today.getDate();

        if (month < 10)  month = '0' + month;
        if (day < 10)    day = '0' + day;

        var nowDt = today.getFullYear() + '-' + month + '-' + day;
        var expDt = $('.js-expDt').text().split(' ')[0];
        if(nowDt < expDt){
            $('.red').css('display', 'none');
        }else{
            if(mode == 'listExpDt') {
                $('.js-expDt').append(' <span class="red">(사용기간 만료)</span>');
            }
            $('.red').css('display', 'inline');
        }
    }
</script>