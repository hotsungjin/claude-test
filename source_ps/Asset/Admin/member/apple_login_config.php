<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link      http://www.godo.co.kr
 */
?>
<form id="form" action="apple_login_config_ps.php" enctype="multipart/form-data" method="post">
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <input type="submit" value="저장" class="btn btn-red"/>
    </div>

    <div class="table-title">
        애플 로그인 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
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
        <tr>
            <th class="require">team ID</th>
            <td>
                <label>
                    <input type="text" name="team_id" id="team_id" value="<?= $data['team_id']; ?>" class="form-control width-2xl useFl"/>
                </label>
            </td>
        </tr>
        <tr>
            <th class="require">client ID</th>
            <td>
                <label>
                    <input type="text" name="client_id" id="client_id" value="<?= $data['client_id']; ?>" class="form-control width-2xl useFl"/>
                </label>
            </td>
        </tr>
        <tr>
            <th class="require">key ID</th>
            <td>
                <label>
                    <input type="text" name="key_id" id="key_id" value="<?= $data['key_id']; ?>" class="form-control width-2xl useFl"/>
                </label>
            </td>
        </tr>
        <tr>
            <th class="require">key File</th>
            <td>
                <label>
                    <input type="file" id="key_file" name="key_file"/>
                </label>
                <div><?= $data['key_file_name'] ?></div>
                <input type="hidden" id="key_file_name" name="key_file_name" value="<?= $data['key_file_name'] ?>">
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
                <table class="table table-cols simpleJoin">
                    <tbody>
                    <tr>
                        <th rowspan="2" width="150px">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="baseInfo" name="baseInfo" value="y" checked="checked" disabled="disabled">
                                기본 정보
                            </label>
                        </th>
                        <td>필수 :
                            <?= implode(", ", $data['items']['baseInfo']['requireY']);?>
                        </td>
                    </tr>
                    <tr>
                        <td>선택 :
                            <?= implode(", ", $data['items']['baseInfo']['requireN']);?>
                        </td>
                    </tr>
                    <?php if($data['items']['businessMember']['use']) { ?>
                    <tr>
                        <th>
                            <label class="checkbox-inline">
                                <input type="checkbox" id="businessInfo" name="businessInfo" value="" disabled="disabled">
                                사업자 정보
                            </label>
                        </th>
                        <td>사업자 회원 가입은 지원되지 않습니다.</td>
                    </tr>
                    <?php } ?>
                    <tr >
                        <th rowspan="2">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="supplementInfo" name="supplementInfo" value="<?=gd_isset($data['supplementInfo']); ?>" >
                                부가정보
                            </label>
                        </th>
                        <td>필수 :
                            <?= implode(", ", $data['items']['supplementInfo']['requireY']);?>
                        </td>
                    </tr>
                    <tr>
                        <td>선택 :
                            <?= implode(", ", $data['items']['supplementInfo']['requireN']);?>
                        </td>
                    </tr>
                    <tr>
                        <th rowspan="2">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="additionalInfo" name="additionalInfo" value="<?=gd_isset($data['additionalInfo']); ?>">
                                추가 정보
                            </label>
                        </th>
                        <td>필수 :
                            <?php
                            foreach ($data['items']['additionInfo']['requireY'] as $val){
                                $requireY[] = $val['name'];
                            }
                            echo implode(", ", $requireY);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>선택 :
                            <?php
                            foreach ($data['items']['additionInfo']['requireN'] as $val){
                                $requireN[] = $val['name'];
                            }
                            echo implode(", ", $requireN);
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="notice-info simpleJoin">
                    정보 별로 노출되는 항목은 <a href="../member/member_joinitem.php" target="_blank" class="notice-ref notice-sm btn-link">회원>회원 관리>회원 가입 항목 관리</a>의 사용/필수 설정에 따릅니다.
                </div>
                <div class="notice-danger">
                    회원가입 항목으로 이메일과 휴대폰번호를 필수 수집 정보로 설정한 경우, 앱 심사에서 통과되지 않을 수 있습니다.<br/>
                    가능한 최소한의 항목으로 회원가입을 진행할 수 있도록 설정하시기 바랍니다.
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="notice-info">
        마이앱 서비스를 이용하시는 경우, 애플 아이디 로그인 설정이 자동으로 세팅됩니다.
    </div>
    <div class="notice-info">
        애플 아이디 로그인 설정 정보가 변경되면 기존 애플 아이디 로그인 연동된 회원은 연동이 해제되며 변경 시 주의하여 주시기 바랍니다.
    </div>
    <div class="notice-info">
        애플 로그인 정책에 따라 아래 URL을 애플 개발자센터에 등록해 주세요.<br>
        <?= $appleNotificationUrl ?>
    </div>
    <div class="notice-info">
        미 등록 시, 애플에서 전달하는 서버 간 알림 수신 및 업데이트가 불가합니다.
    </div>
</form>

<div class="information">
    <h4>안내</h4>
    <div class="content">
        <div>
            <strong style="font-family: NanumGothic;">애플 아이디 로그인 설정 시 보안서버 사용에 따른 안내</strong>
        </div>
        <div style="line-height: 20px;">
            ㆍ쇼핑몰 사용자가 애플 계정 관리 페이지와 연결 및 연결 끊기를 진행하기 위해서는 쇼핑몰 보안서버가 설치돼 있어야 합니다.<br>
        </div>
        <div style="padding-top: 9px;">
            <strong style="font-family:NanumGothic;">보안서버 설치 확인 및 사용 방법</strong>
        </div>
        <div style="line-height: 20px;">
            ㆍ기본설정 → 보안서버관리 → PC쇼핑몰 보안서버 관리에서 하실 수 있습니다.<br>
            ㆍ기본설정 → 보안서버관리 → 모바일쇼핑몰 보안서버 관리에서 하실 수 있습니다.<br>
        </div>
    </div>
</div>

<script type="text/javascript">

    function useLoginFl() {
        if($('input:radio[name="useFl"]:checked').val()=='y') {
            $('#form input[type=text]').prop("readonly", false);
            $('#key_file').prop("disabled", false);
            $('input:radio[name="simpleLoginFl"]').prop("disabled", false);
            $('.simpleJoin input[name=supplementInfo]').prop("disabled", false);
            $('.simpleJoin input[name=additionalInfo]').prop("disabled", false);
        } else {
            $('#form input[type=text]').prop("readonly", true);
            $('#key_file').prop("disabled", true);
            $('input:radio[name="simpleLoginFl"]').prop("disabled", true);
            $('.simpleJoin input[name=supplementInfo]').prop("disabled", true);
            $('.simpleJoin input[name=additionalInfo]').prop("disabled", true);
        }
    }

    function useSimpleJoin() {
        if ($('input:radio[name="simpleLoginFl"]:checked').val() == 'y') {
            $('.simpleJoin').hide();
        } else {
            $('.simpleJoin').show();
        }
    }
    function useAdditionInfo() { //추가정보 사용
        if($('input:checkbox[name="additionalInfo"]').val() == 'y') {
            $('#additionalInfo').prop('checked', true);
        } else {
            $('#additionalInfo').prop('checked', false);
        }
    }
    function useSupplementInfo() { //부가정보 사용
        if ($('input:checkbox[name="supplementInfo"]').val() == 'y') {
            $('#supplementInfo').prop('checked', true);
        } else {
            $('#supplementInfo').prop('checked', false);
        }
    }

    $(document).ready(function () {
        useLoginFl();
        useSupplementInfo();
        useAdditionInfo();
        useSimpleJoin();

        $(document).on('click','.simpleJoin input:checkbox', function (e) {
            var value = $(this).prop("checked") ? 'y' : 'n';
            $(this).val(value);
        });

        $("#form").validate({
            dialog: false,
            ignore: '.ignore',
            rules: {
                team_id: {
                    required: function () {
                        return $(':radio[name="useFl"]:checked').val() == 'y';
                    }
                },
                client_id: {
                    required: function () {
                        return $(':radio[name="useFl"]:checked').val() == 'y';
                    }
                },
                key_id: {
                    required: function () {
                        return $(':radio[name="useFl"]:checked').val() == 'y';
                    }
                },
                key_file: {
                    required: function () {
                        return ($(':radio[name="useFl"]:checked').val() == 'y'
                            && $('#key_file_name').val() == undefined);
                    },
                    extension: "p8"
                },
            },
            messages: {
                team_id: {
                    required: "team ID를 입력해주세요"
                },
                client_id: {
                    required: "client ID를 입력해주세요"
                },
                key_id: {
                    required: "key ID를 입력해주세요"
                },
                key_file: {
                    required: "Key File을 등록해주세요",
                    extension:"Key File은 .p8 확장자만 등록 가능합니다."
                }
            },
            submitHandler: function (form) {
                checkUseFlSame();
                form.target = 'ifrmProcess';
                form.submit();
            }
        });

        $('input:radio[name="useFl"]').click(function (e) {
            useLoginFl();
        });
        $('input:radio[name="simpleLoginFl"]').click(function (e) {
            useSimpleJoin();
        });
    });
</script>
