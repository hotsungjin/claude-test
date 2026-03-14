<form id="frmSsl" name="frmSsl">
    <input type="hidden" name="mode" value="sslSetting"/>
    <input type="hidden" name="sslConfigDomain" value="<?= $sslData['sslConfigDomain']; ?>"/>
    <input type="hidden" name="sslConfigType" value="<?= $sslData['sslConfigType']; ?>"/>
    <input type="hidden" name="checkAlert" value="<?= $sslData['checkAlert']; ?>"/>
    <input type="hidden" name="sslConfigApplyLimit" value="y"/>
    <div id="sslForm">
        <table class="table table-cols">
            <colgroup>
                <col class="width-md"/>
                <col/>
            </colgroup>
            <tr>
                <th>보안서버 사용설정</th>
                <td>
                    <label class="radio-inline">
                        <input type="radio" name="sslConfigUse" value="n" <?= $checked['sslConfigUse']['n']; ?> />사용안함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="sslConfigUse" value="y" <?= $checked['sslConfigUse']['y']; ?> />사용함
                    </label>
                </td>
            </tr>
            <tr>
                <th>서비스 상태</th>
                <td><?= $sslData['sslConfigStatusString']; ?></td>
            </tr>
            <tr class="godo">
                <th>보안서버 사용기간</th>
                <td>
                    <?php if ($sslData['sslConfigType'] == 'godo') { ?>
                        <?= $sslData['sslConfigStartDate'] ?> - <?= $sslData['sslConfigEndDate'] ?>
                        <p class="notice-danger">보안 인증서가 만료되는 경우, 일정 시간 동안 페이지 접속이 불가할 수 있으니 만료 전 갱신하시기 바랍니다.</p>
                    <?php } else { ?>
                        자동연장
                        <p class="notice-danger">무료 SSL 보안서버 인증서는 만료일 전에 자동으로 연장됩니다.</p>
                    <?php } ?>
                </td>
            </tr>
            <tr class="godo">
                <th>보안서버 도메인</th>
                <td><span class="eng bold">https://<?= gd_isset($sslData['sslConfigDomain']) ?></span></td>
            </tr>
            <tr class="godo">
                <?php if (strpos($sslData['sslConfigServerExists'], 'whois') !== false) { ?>
                    <th> 인증마크 표시 설정</th>
                    <td>
                        <label class="radio-inline">
                            <input type="radio" name="sslGodoImageUse"
                                   value="n" <?= gd_isset($checked['sslConfigImageUse']['n']); ?> />표시안함
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="sslGodoImageUse"
                                   value="y" <?= gd_isset($checked['sslConfigImageUse']['y']); ?> />표시함
                        </label>
                    </td>
                <?php } else { ?>
                    <th>보안서버 포트</th>
                    <td><span class="num bold"><?= gd_isset($sslData['sslConfigPort']) ?></span></td>
                <?php } ?>
            </tr>
            <?php if ($sslData['sslConfigType'] == 'godo') { ?>
                <tr class="godo">
                    <th>인증마크 표시</th>
                    <td>
                        <?php if (strpos($sslData['sslConfigServerExists'], 'whois') !== false) { ?>
                            <div class="ssl-image" style="padding-top:10px;">
                                미리보기 (131*45)
                                <input type="hidden" name="sslConfigImageType"
                                       value="comodo" <?= gd_isset($checked['sslConfigImageType']['comodo']); ?> />
                                <div><img src="/admin/gd_share/img/logo_comodo.png"></div>
                            </div>
                        <?php } else { ?>
                            <label class="radio-inline">
                                <input type="radio" name="sslGodoImageUse"
                                       value="n" <?= gd_isset($checked['sslConfigImageUse']['n']); ?> />표시안함
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="sslGodoImageUse"
                                       value="y" <?= gd_isset($checked['sslConfigImageUse']['y']); ?> />표시함
                            </label>
                            <div class="ssl-image" style="padding-top:10px;">
                                <label class="radio-inline" style="vertical-align:top;">
                                    <input type="radio" name="sslConfigImageType"
                                           value="globalSignAlpha" <?= gd_isset($checked['sslConfigImageType']['globalSignAlpha']); ?> />GlobalSign(Alpha
                                    SSL)
                                    <div><img src="/data/commonimg/logo_alpha.png"></div>
                                </label>
                                <label class="radio-inline" style="vertical-align:top;">
                                    <input type="radio" name="sslConfigImageType"
                                           value="globalSignQuick" <?= gd_isset($checked['sslConfigImageType']['globalSignQuick']); ?> />GlobalSign(Quick
                                    SSL)
                                    <div><img src="/data/commonimg/logo_quick.png"></div>
                                </label>
                                <label class="radio-inline" style="vertical-align:top;">
                                    <input type="radio" name="sslConfigImageType"
                                           value="comodo" <?= gd_isset($checked['sslConfigImageType']['comodo']); ?> />Sectigo
                                    <div><img src="/admin/gd_share/img/logo_comodo.png"></div>
                                </label>

                            </div>
                        <?php } ?>
                        <div class="notice-info">
                            ! 스킨 디자인 수정 및 변경에 따른 수동으로 인증마크 표시 적용방법<br/> - 스킨 소스를 변경하였거나, 스킨을 구매했을 경우, 또는 새로 스킨을 만든 경우를 위한 표시
                            방법입니다.<br/> -
                            스킨에 따라 하단소스의 Table구조가 다르니, 이 부분 유의해서 원하는 위치에 치환코드를 넣어주세요.<br/> - 위에서 인증마크 표시여부를 '표시함'으로 설정
                            후,<br/> &nbsp;&nbsp;[디자인관리
                            > 전체레이아웃 > 하단디자인 > 하단 기본타입] 을 눌러 치환코드 {=displaySSLSeal} 를 삽입하세요.
                            <a href="../design/design_page_edit.php?designPageId=outline/footer/standard.html"> 바로가기></a>
                        </div>
                    </td>
                </tr>
                <!-- //유료보안서버 -->
            <?php } ?>
        </table>
    </div>
    <div class="text-center" style="padding: 20px 0 25px 0;border-top: 1px solid #e6e6e6">
        <button type="button" class="btn btn-xl btn-white js-layer-close mgr5">닫기</button>
        <button type="submit" class="btn btn-xl btn-black">저장</button>
    </div>
</form>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        $("#frmSsl").validate({
            submitHandler: function (form) {
                sslConfigType = $('input:hidden[name="sslConfigType"]').val();
                sslConfigUse = $('input:radio[name="sslConfigUse"]:checked').val();
                sslConfigMallFl = '<?= $sslData["sslConfigMallFl"]; ?>';
                checkAlert = $('input:hidden[name="checkAlert"]').val();
                if (sslConfigType === 'godo' && sslConfigUse === 'y' && sslConfigMallFl === 'kr' && checkAlert > 0) {
                    dialog_confirm('유료 보안서버 사용 시, 사용중인 기준몰의 무료보안서버는 해제됩니다.<br/>정말 사용하시겠습니까?', function (result) {
                        if (result) {
                            $.post('../policy/ssl_ps.php', $('#frmSsl').serializeArray(), function (data) {
                                if (data.result === 'ok') {
                                    dialog_alert(data.message, '확인', {isReload: true});
                                    return false;
                                } else {
                                    process_close();
                                    alert(data.message);
                                }
                            });
                        }
                    });
                } else {
                    $.post('../policy/ssl_ps.php', $('#frmSsl').serializeArray(), function (data) {
                        if (data.result === 'ok') {
                            dialog_alert(data.message, '확인', {isReload: true});
                            return false;
                        } else {
                            process_close();
                            alert(data.message);
                        }
                    });
                }
                return false;
            },
            dialog: false,
        });

        $('#requestPaySsl').click(function () {
            window.open('https://hosting.godo.co.kr/valueadd/ssl_service.php?iframe=yes', '_blank');
            return false;
        });

        // 숫자만 입력
        $('input[name=\'sslConfigPort\']').number_only();

        $('input[name=\'sslGodoImageUse\']').click(function () {
            changeSslImageType();
        });

        changeSslImageType();
    });

    /**
     * 보안서버 타입 선택
     */
    function changeSslImageType() {
        if ($('input[name="sslGodoImageUse"]:checked').val() === 'y') {
            $('.ssl-image').show();
            $('input[name="sslConfigImageType"]').prop('disabled', false);
        } else {
            $('.ssl-image').hide();
            $('input[name="sslConfigImageType"]').prop('disabled', true);
        }

    }

    //-->
</script>
