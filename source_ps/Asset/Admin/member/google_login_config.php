<form id="form" name="form" action="google_login_config_ps.php" method="post">
    <input type="hidden" name="mallSno" value="<?= $mallSno ?>"/>
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <input type="submit" value="저장" class="btn btn-red" />
    </div>

    <div class="table-title gd-help-manual">
        구글 아이디 로그인 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md" />
            <col />
        </colgroup>
        <tbody>
        <tr>
            <th>사용설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="useFl" id="useFlY" value="y" <?= $checked['useFl']['y']; ?>>
                    사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="useFl" id="useFlN" value="n" <?= $checked['useFl']['n']; ?>>
                    사용안함
                </label>
            </td>
        </tr>
        <tr class="googleClientConfig">
            <th class="require">Client ID</th>
            <td>
                <label>
                    <input type="text" name="clientId" id="clientId" value="<?= $data['clientId']; ?>" class="form-control width-2xl useFl" <?php echo $data['clientId'] ? 'readonly' : ''; ?>/>
                </label>
                <a href="https://console.cloud.google.com/projectselector2/apis/dashboard?inv=1&invt=AbnrgQ&supportedpurview=project" target="_blank" id="googleClientRequest" class="btn btn-gray btn-sm">Client ID <?= $data['clientId'] == '' ? '신청' : '재신청' ?></a>
            </td>
        </tr>
        <tr class="googleClientConfig">
            <th class="require">Client 보안 비밀번호</th>
            <td>
                <label>
                    <input type="text" name="clientSecret" id="clientSecret" value="<?= $data['clientSecret']; ?>" class="form-control width-2xl useFl" <?php echo $data['clientSecret'] ? 'readonly' : ''; ?>/>
                </label>
            </td>
        </tr>
        <tr class="joinConfig">
            <th>회원가입 설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="simpleLoginFl" id="simpleLoginFlY" value="y" <?= $checked['simpleLoginFl']['y']; ?>>
                    간편 회원가입
                </label>
                <label class="radio-inline">
                    <input type="radio" name="simpleLoginFl" id="simpleLoginFlN" value="n" <?= $checked['simpleLoginFl']['n']; ?>>
                    일반 회원가입
                </label>
                <div class="notice-info simpleJoin">
                    간편 회원가입 사용 시 아이디와 이름 항목은 회원가입 시 필수로 노출되며<br>이메일, 휴대폰번호, 성별, 생일 항목은 회원>회원 관리>회원 가입 항목 관리의 사용/필수 설정에 따라 노출됩니다
                </div>
            </td>
        </tr>
        <tr class="joinPolicy">
            <th>회원가입 항목 설정</th>
            <td>
                <table class="table table-cols">
                    <tbody>
                    <?php if ($mallCnt > 1) { ?>
                        <ul class="multi-skin-nav nav nav-tabs" style="margin-bottom:20px;">
                            <?php foreach ($mallList as $key => $mall) { ?>
                                <li role="presentation" class="js-popover <?php echo $mallSno == $mall['sno'] ? 'active' : 'passive'; ?>" data-html="true" data-content="<?php echo $mall['mallName']; ?>" data-placement="top">
                                    <a href="./google_login_config.php?mallSno=<?php echo $mall['sno']; ?>">
                                        <span class="flag flag-16 flag-<?php echo $mall['domainFl']?>"></span>
                                        <span class="mall-name"><?php echo $mall['mallName']; ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                    <tr>
                        <th rowspan="2" width="150px">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="baseInfo" name="baseInfo" value="y" checked="checked" disabled="disabled">
                                기본 정보
                            </label>
                        </th>
                        <td>필수 : <?= implode(", ", $data['items']['baseInfo']['requireY']); ?> </td>
                    </tr>
                    <tr>
                        <td>선택 : <?= implode(", ", $data['items']['baseInfo']['requireN']); ?> </td>
                    </tr>
                    <tr>
                        <th rowspan="2">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="supplementInfo" name="supplementInfo" value="<?= gd_isset($data['supplementInfo']); ?>">
                                부가정보
                            </label>
                        </th>
                        <td>필수 : <?= implode(", ", $data['items']['supplementInfo']['requireY']); ?> </td>
                    </tr>
                    <tr>
                        <td>선택 : <?= implode(", ", $data['items']['supplementInfo']['requireN']); ?> </td>
                    </tr>
                    <tr>
                        <th rowspan="2">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="additionalInfo" name="additionalInfo" value="<?= gd_isset($data['additionalInfo']); ?>">
                                추가 정보
                            </label>
                        </th>
                        <td>필수 : <?= implode(", ", $data['items']['additionInfo']['requireY']); ?> </td>
                    </tr>
                    <tr>
                        <td>선택 : <?= implode(", ", $data['items']['additionInfo']['requireN']); ?> </td>
                    </tr>
                    </tbody>
                </table>
                <div class="notice-info">
                    정보 별로 노출되는 항목은 <a href="../member/member_joinitem.php" target="_blank" class="notice-ref notice-sm btn-link">회원>회원 관리>회원 가입 항목 관리</a>의 사용/필수 설정에 따릅니다.
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="notice-info">
        구글 아이디 로그인 사용 시 Google Cloud > API 및 서비스 > 사용자 인증 정보 > 사용자 인증 정보 만들기 에서 OAuth 클라이언트 ID 추가 후 "승인된 리디렉션 URI" 항목에 반드시 다음 URL(/member/google/google_login.php) 을 추가하셔야만 정상적으로 사용 가능합니다.
    </div>
</form>

<script type="text/javascript">
    function useLoginFl() { // 사용설정
        const isUseEnabled = ($('input:radio[name="useFl"]:checked').val() === 'y');
        const isSimpleLogin = ($('input:radio[name="simpleLoginFl"]:checked').val() === 'y');

        if (isUseEnabled) {
            $('.googleClientConfig').show();
            if (isSimpleLogin) {
                $('.joinPolicy').hide();
            } else {
                $('.joinPolicy').show();
            }
            $('.joinConfig').show();
            $('.notice-info').show();
        } else {
            $('.googleClientConfig').hide();
            $('.joinPolicy').hide();
            $('.joinConfig').hide();
            $('.notice-info').hide();
        }
    }

    function useSimpleJoin() { // 회원가입 설정 간편/일반
        const isUseEnabled = $('input:radio[name="useFl"]:checked').val() === 'y';
        const isSimpleLogin = $('input:radio[name="simpleLoginFl"]:checked').val() === 'y';

        if (isUseEnabled) {
            if (isSimpleLogin) {
                $('.joinPolicy').hide();
                $('.simpleJoin').show();
            } else {
                $('.joinPolicy').show();
                $('.simpleJoin').hide();
            }
        }
    }

    function useAdditionInfo() { //추가정보 사용
        const isAdditional = ($('input:checkbox[name="additionalInfo"]').val() === 'y');
        $('#additionalInfo').prop('checked', isAdditional);
    }

    function useSupplementInfo() { //부가정보 사용
        const isSupplement = ($('input:checkbox[name="supplementInfo"]').val() === 'y');
        $('#supplementInfo').prop('checked', isSupplement);
    }

    $(document).ready(function() {
        useLoginFl();
        useSimpleJoin();
        useAdditionInfo();
        useSupplementInfo();
        $("#form").validate({
            dialog: false,
            ignore: '.ignore',
            rules: {
                clientId: {
                    required: function() {
                        return $(':radio[name="useFl"]:checked').val() == 'y';
                    }
                },
                clientSecret: {
                    required: function() {
                        return $(':radio[name="useFl"]:checked').val() == 'y';
                    }
                }
            },
            messages: {
                clientId: {
                    required: "Client ID를 입력해주세요"
                },
                clientSecret: {
                    required: "Client 보안 비밀번호를 입력해주세요."
                }
            },
            submitHandler: function(form) {
                form.target = 'ifrmProcess';
                form.submit();
            }
        });

        $('input:radio[name="simpleLoginFl"]').click(function(e) {
            useSimpleJoin();
        });
        $('input:radio[name="useFl"]').click(function(e) {
            useLoginFl();
        });

        $('#googleClientRequest').click(function (e) {
            $('#clientId').removeAttr('readonly');
            $('#clientSecret').removeAttr('readonly');
        });

        var mallSno = $('input:hidden[name="mallSno"]').val();
        var url = document.URL;
        var start = url.indexOf('mallSno');
        var urlValue = url.substring(start + 8, start + 9);

        if (mallSno > 1) { // mallSno값이 넘어온 경우 해외몰탭 출력
            $('#simpleLoginFlN').attr("checked", true);
            $('.joinPolicy').show();
        } else if (urlValue == 1) { // 기준몰탭 클릭시 출력
            $('#simpleLoginFlN').attr("checked", true);
            $('.joinPolicy').show();
        }

        $(document).on('click', 'input:checkbox[name="supplementInfo"]', function(e) {
            if ($(this).prop("checked")) {
                $(this).val('y');
            } else {
                $(this).val('n');
            }
        });
        $(document).on('click', 'input:checkbox[name="additionalInfo"]', function(e) {
            if ($(this).prop("checked")) {
                $(this).val('y');
            } else {
                $(this).val('n');
            }
        });
        $(document).on('click', 'input:checkbox[name="businessInfo"]', function(e) {
            if ($(this).prop("checked")) {
                $(this).val('y');
            } else {
                $(this).val('n');
            }
        });
    });
</script>
