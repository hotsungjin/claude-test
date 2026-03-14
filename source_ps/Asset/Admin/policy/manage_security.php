<!-- //@formatter:off -->
<form id="frmManageSecurity" name="frmManageSecurity" action="manage_ps.php" method="post">
    <input type="hidden" name="mode" value="<?= $mode; ?>"/>
    <input type="hidden" name="smsSecurity" value="<?php echo $checked['smsSecurity']['y'] != "" ? "y" : "n"; ?>"/>
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <div class="btn-group">
            <button type="button" class="btn btn-red js-submit">저장</button>
        </div>
    </div>
    <div class="notice-box mgb20">
        <h2 class="font12 mgt5">관리자 운영 보안이란?</h2>
        <p>
            정보통신망을 통해 외부에서 개인정보처리시스템(쇼핑몰 등)에 접속 시 단순히 아이디와 비밀번호만을 이용할 경우, 해킹에 의해 접속정보가 유출되어 쇼핑몰이 쉽게 위험에 노출되게 됩니다.<br/>
            이러한 위험성을 감소시키기 위해 아이디/비밀번호를 이용하는 인증과 별도로 휴대폰, 일회용비밀번호(OTP), IP인증 등을 활용한 추가적인 안전한 인증수단을 적용하여야 합니다.<br/>
            해당 법령을 준수하지 않을 경우 개인정보 보호법 제 75조에 따라 5천만원 이하의 과태료에 처할 수 있습니다.<br/>
            개인정보 보호법 및 개인정보 보호법 시행령 등 관련 법률 내용을 필독하시고 <span class="text-red">"관리자 보안로그인" 또는 "관리자 IP 접속제한” 을 설정하시기를 권장합니다.</span>
            <a href="https://www.law.go.kr/법령/개인정보%20보호법" target="_blank" class="btn-link">내용 확인 ></a>
        </p>
    </div>
    <div class="table-title gd-help-manual">
        관리자 보안인증 설정
    </div>
    <table class="table table-cols mgb30">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>인증수단</th>
            <td>
                <label class="checkbox-inline">
                    <input type="checkbox" name="smsSecurityFl" value="y" <?php if($dataSecurity['superCellPhoneFl'] === false) echo "disabled='disabled'"; ?> <?php echo gd_isset($checked['smsSecurityFl']['y']); ?> /> SMS인증
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="emailSecurityFl" value="y" <?php if($dataSecurity['superEmailFl'] === false) echo "disabled='disabled'"; ?> <?php echo gd_isset($checked['emailSecurityFl']['y']); ?> > 이메일인증
                </label>

                <div class="pdt5">
                    <?php if ($dataSecurity['superCellPhoneFl'] === false || $dataSecurity['superEmailFl'] === false) { ?>
                        <div class="notice-danger">
                            최고운영자 정보에 인증정보(<?php echo implode(', ', $dataSecurity['superSecurityText']); ?>)가 없습니다. 인증정보를 먼저 등록해주세요. <b><a href="<?= $dataSecurity['superManagerModifyUrl'] ?>" target="_blank" class="btn-link">최고운영자 정보 수정하기 ></a></b><br> 최고운영자의 인증정보가 등록되지 않으면 보안로그인/화면보안접속 설정과 관계없이 보안인증화면이 노출되지 않습니다
                        </div>
                    <?php } ?>
                    <p class="notice-info">
                        로그인한 운영자 정보에 인증정보(SMS, 이메일)가 없는 경우, 보안인증화면이 노출되지 않습니다.<br/>관리자 보안인증 설정을 사용 시, 운영자 정보에 인증정보를 등록해주세요. <a href="../policy/manage_list.php" target="_blank" class="btn-link">운영자 정보 수정하기 ></a>
                    </p>
                    <p class="notice-info">
                        인증번호 SMS는 잔여포인트가 있어야 발송됩니다. (잔여포인트 : <?php echo number_format($smsPoint, 1) ?>) <a href="../member/sms_charge.php" target="_blank" class="btn-link">SMS포인트 충전하기 ></a>
                    </p>
                    <p class="notice-info">
                        SMS잔여포인트가 없는 경우 자동으로 이메일이 인증수단으로 노출되며, 포인트 충전 이후 설정한 인증수단으로 노출됩니다.
                    </p>
                    <p class="notice-info">
                        이메일인증은 주문처리 및 프로모션 등으로 인해 이메일이 대량으로 발송될 경우 인증번호 전송이 지연될 수 있으므로 SMS인증을 사용하시길 권장합니다.
                    </p>
                </div>
            </td>
        </tr>
        <tr>
            <th>보안로그인</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="smsSecurity" value="y" class="js-security" <?php if ($smsAuthCount < 1) echo "disabled='disabled'"; ?> <?php echo gd_isset($checked['smsSecurity']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="smsSecurity" value="n" class="js-security" <?php echo gd_isset($checked['smsSecurity']['n']); ?> />사용안함
                </label>
            </td>
        </tr>
        <tr id="authLoginPeriodRow">
            <th>보안로그인 재인증 추가</th>
            <td>
                <label>최종 보안로그인 기준
                <select name="authLoginPeriod" data-period="<?= gd_isset($dataSecurity['authLoginPeriod'], 0) ?>">
                    <option value="0">로그인 시 마다</option>
                    <option value="1">1일 이후</option>
                    <option value="7">7일 이후</option>
                    <option value="30">30일 이후</option>
                    <option value="60">60일 이후</option>
                    <option value="90">90일 이후</option>
                </select>
                로그인 보안인증 화면 노출</label>
            </td>
        </tr>
        <tr class="js-security">
            <th>화면보안접속</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="screenSecurity" value="y" class="js-security" <?php if ($smsAuthCount < 1) echo "disabled='disabled'"; ?> <?php echo gd_isset($checked['screenSecurity']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="screenSecurity" value="n" class="js-security" <?php echo gd_isset($checked['screenSecurity']['n']); ?> />사용안함
                </label>
                <div class="pdt5">
                    <p class="notice-info">
                        화면보안접속 사용 시 보안접속 인증화면이 노출되는 화면<br>
                        - 기본설정>관리정책>운영자관리/운영자등록<br>
                        - 기본설정>관리정책>운영보안설정<br>
                        - 기본설정>결제정책>무통장입금은행관리<br>
                        - 회원>SMS관리>개별/전체 SMS 발송
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="table-title gd-help-manual">
        관리자 로그인 설정
    </div>
    <table class="table table-cols mgb30">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>관리자 자동 로그아웃</th>
            <td>
                <label class="radio hand">
                    <input type="radio" name="sessionLimitUseFl" value="n" <?php if($dataSecurity['sessionLimitUseFl'] === false) echo "disabled='disabled'"; ?> <?php echo gd_isset($checked['sessionLimitUseFl']['n']); ?> /> 기본설정
                </label>
                <label class="radio pdt10 hand">
                    <input type="radio" name="sessionLimitUseFl" value="y" <?php echo gd_isset($checked['sessionLimitUseFl']['y']); ?> > 로그인 후
                    <select name="sessionLimitTime">
                        <option value="1800" <?php echo gd_isset($selected['sessionLimitTime']['1800']); ?>>30</option>
                        <option value="3600" <?php echo gd_isset($selected['sessionLimitTime']['3600']); ?>>60</option>
                        <option value="5400" <?php echo gd_isset($selected['sessionLimitTime']['5400']); ?>>90</option>
                        <option value="7200" <?php echo gd_isset($selected['sessionLimitTime']['7200']); ?>>120</option>
                        <option value="10800" <?php echo gd_isset($selected['sessionLimitTime']['10800']); ?>>180</option>
                    </select> 분간 클릭이 없으면 자동 로그아웃
                </label>
                <div class="notice-danger notice-sm mgt10">기본설정 시 모든 브라우저를 닫거나 수동으로 로그아웃을 한 경우 로그아웃 됩니다. 또한, 보안상의 이유로 일정 시간이 경과 되면 자동으로 로그아웃 됩니다.</div>
            </td>
        </tr>
        <tr>
            <th>장기 미로그인 기간</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="noVisitPeriod" value="89" <?php echo gd_isset($checked['noVisitPeriod']['89']); ?> />3개월
                </label>
                <label class="radio-inline">
                    <input type="radio" name="noVisitPeriod" value="179" <?php echo gd_isset($checked['noVisitPeriod']['179']); ?> />6개월
                </label>
                <label class="radio-inline">
                    <input type="radio" name="noVisitPeriod" value="364" <?php echo gd_isset($checked['noVisitPeriod']['364']); ?> />1년
                </label>
                <label class="radio-inline">
                    <input type="radio" name="noVisitPeriod" value="729" <?php echo gd_isset($checked['noVisitPeriod']['729']); ?> />2년
                </label>
            </td>
        </tr>
        <tr>
            <th>장기 미로그인 운영자<br>안내 사용여부</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="noVisitAlarmFl" value="y" <?php echo gd_isset($checked['noVisitAlarmFl']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="noVisitAlarmFl" value="n" <?php echo gd_isset($checked['noVisitAlarmFl']['n']); ?> />사용안함
                </label>
                <div class="pdt5">
                    <p class="notice-info">
                        사용함 설정 시, 장기 미로그인 운영자 안내 팝업이 노출됩니다.<br>
                        사용을 원하지 않는 경우 사용안함으로 설정해주시면 됩니다.
                    </p>
                    <p class="notice-info">
                        장기 미로그인 운영자 수에 최고운영자/대표운영자도 포함되며, 최고운영자는 삭제 및 로그인 제한 처리가 불가 합니다.
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="table-title gd-help-manual">
        IP 접속제한 설정
    </div>
    <table class="table table-cols mgb15">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>관리자 IP 접속제한</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="ipAdminSecurity" value="y" <?php echo gd_isset($checked['ipAdminSecurity']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="ipAdminSecurity" value="n" <?php echo gd_isset($checked['ipAdminSecurity']['n']); ?> />사용안함
                </label>
            </td>
        </tr>
        <tr class="ipAdmin">
            <th>관리자 접속가능 IP 등록</th>
            <td>
                <button type="button" class="btn btn-sm btn-white btn-icon-plus js-admin-add mgb10">추가</button>
                <ul class="ipAdmin list-unstyled clear-both">
                    <?php if (is_array($dataSecurity['ipAdmin']) === true) {
                        foreach ($dataSecurity['ipAdmin'] as $key => $val) {
                            ?>
                            <li class="form-inline">
                                <input type="text" name="ipAdmin[]" value="<?php echo $val[0]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipAdmin[]" value="<?php echo $val[1]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipAdmin[]" value="<?php echo $val[2]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipAdmin[]" value="<?php echo $val[3]; ?>" class="form-control width5p number" maxlength="3"/>
                                <span class="js-bandWidth<?php if (trim($dataSecurity['ipAdminBandWidth'][$key]) === '') { ?> display-none<?php } ?>">
                                    ~
                                <input type="text" name="ipAdminBandWidth[]" value="<?php echo $dataSecurity['ipAdminBandWidth'][$key]; ?>" class="form-control width5p number js-bandWidthText" maxlength="3"/></span>
                                <input type="checkbox" name="ipAdminBandWidthFl[]" value="y" <?php echo gd_isset($checked['ipAdminBandWidthFl'][$key]); ?> />대역 지정
                                <button class="btn btn-sm btn-white btn-icon-minus js-admin-del">삭제</button>
                            </li>
                        <?php }
                    } ?>
                </ul>
                <span class="notice-info mgb10">등록된 IP만 관리자에 접속 가능합니다. (현재 접속 IP : <?= $remoteAddr; ?>)</span><br/>
                <span class="notice-info mgb10">관리자 IP 접속제한 설정의 경우, 관리자앱 로그인 시에는 적용되지 않습니다.</span>
            </td>
        </tr>
        <tr>
            <th>쇼핑몰 IP 접속제한</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="ipFrontSecurity" value="y" <?php echo gd_isset($checked['ipFrontSecurity']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="ipFrontSecurity" value="n" <?php echo gd_isset($checked['ipFrontSecurity']['n']); ?> />사용안함
                </label>
            </td>
        </tr>
        <tr class="ipFront">
            <th>쇼핑몰 접속제한 IP 등록</th>
            <td>
                <button type="button" class="btn btn-sm btn-white btn-icon-plus js-front-add mgb10">추가</button>
                <span class="notice-danger mgb10">등록된 IP는 쇼핑몰에 접속할 수 없으므로 등록 시 주의하시기 바랍니다.</span>
                <ul class="ipFront list-unstyled clear-both">
                    <?php if (is_array($dataSecurity['ipFront']) === true) {
                        foreach ($dataSecurity['ipFront'] as $key => $val) {
                            ?>
                            <li class="form-inline">
                                <input type="text" name="ipFront[]" value="<?php echo $val[0]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipFront[]" value="<?php echo $val[1]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipFront[]" value="<?php echo $val[2]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipFront[]" value="<?php echo $val[3]; ?>" class="form-control width5p number" maxlength="3"/>
                                <span class="js-bandWidth<?php if(trim($dataSecurity['ipFrontBandWidth'][$key]) === ''){ ?> display-none<?php } ?>">
                                ~
                                    <input type="text" name="ipFrontBandWidth[]" value="<?php echo $dataSecurity['ipFrontBandWidth'][$key]; ?>" class="form-control width5p number js-bandWidthText" maxlength="3"/></span>
                                <input type="checkbox" name="ipFrontBandWidthFl[]" value="y" <?php echo gd_isset($checked['ipFrontBandWidthFl'][$key]); ?> />대역 지정
                                <button class="btn btn-sm btn-white btn-icon-minus js-front-del">삭제</button>
                            </li>
                        <?php }
                    } ?>
                </ul>
            </td>
        </tr>
        <tr>
            <th>국가별 <br /> 접속제한 설정</th>
            <td colspan="3">
                <div class="js-download-filed-select">
                    <div style="width:300px;float:left"/>
                    <div class="table-action mgb0 mgt0" style="background:#fff;border:0px;">
                        <div class="pull-left">
                            <span class="item_other items">허용국가</span>
                        </div>
                    </div>
                    <div class="js-field-select-wapper">
                        <table class="js-field-default">
                            <tbody>
                            <?php
                            foreach ($dataSecurity['countryAccessAllowed'] as $code => $countryName) {
                                $backgroundHtml = ($dataSecurity['countryAccessBlocking'][$code]) ? "style='background: rgb(255, 255, 255);'" : "";
                                $addClassName = ($dataSecurity['countryAccessBlocking'][$code]) ? " select-item" : "";
                                ?>
                                <tr class="default_field_<?=$code?> <?=$addClassName?>" data-field-key="<?=$code?>"
                                    data-field-name="<?=$countryName?>" <?=$backgroundHtml?>>
                                    <td style="padding:10px;"><?=$countryName?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div  style="width:70px;float:left;text-align:center;padding-top:100px;">

                    <p><button class="btn btn-sm btn-white btn-icon-left js-move-left">추가</button></p>

                    <p><button class="btn btn-sm btn-white btn-icon-right js-move-right">삭제</button></p>

                </div>
                <div style="width:300px;float:left;"/>
                <div class="table-action mgb0 mgt0" style="background:#fff;border:0px;">
                    <div class="pull-left">
                        차단국가
                    </div>
                </div>
                <div class="js-field-select-wapper">
                    <table class="js-field-select table table-rows" data-toggle="" data-use-row-attr-func="false" data-reorderable-rows="true">
                        <tbody>
                        <?php
                        foreach ($dataSecurity['countryAccessBlocking'] as $code => $countryName) {
                            ?>
                            <tr class="move-row" data-field-key="<?=$code?>" data-field-name="<?=$countryName?>">
                                <td style="padding:10px;"><?=$countryName?><input type="hidden" name="countryAccessBlocking[]" value="<?=$code?>"></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                </div>
                <div class="notice-info" style="clear:both;">
                    Shift 버튼을 누른 상태에서 선택하면 여러 항목을 동시에 선택할 수 있습니다.
                </div>
            </td>
        </tr>
        <tr class="ipLoginTryAdmin">
            <th>관리자 IP 접근시도 예외 등록</th>
            <td>
                <input type="hidden" name="ipLoginTryAdminSecurity" value="y">
                <button type="button" class="btn btn-sm btn-white btn-icon-plus js-logintry-add mgb10">추가</button>
                <ul class="ipLoginTryAdmin list-unstyled clear-both">
                    <?php if (is_array($dataSecurity['ipLoginTryAdmin']) === true) {
                        foreach ($dataSecurity['ipLoginTryAdmin'] as $key => $val) {
                            ?>
                            <li class="form-inline">
                                <input type="text" name="ipLoginTryAdmin[]" value="<?php echo $val[0]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipLoginTryAdmin[]" value="<?php echo $val[1]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipLoginTryAdmin[]" value="<?php echo $val[2]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipLoginTryAdmin[]" value="<?php echo $val[3]; ?>" class="form-control width5p number" maxlength="3"/>
                                <span class="js-bandWidth<?php if(trim($dataSecurity['ipLoginTryAdminBandWidth'][$key]) === ''){ ?> display-none<?php } ?>">
                                ~
                                    <input type="text" name="ipLoginTryAdminBandWidth[]" value="<?php echo $dataSecurity['ipLoginTryAdminBandWidth'][$key]; ?>" class="form-control width5p number js-bandWidthText" maxlength="3"/></span>
                                <input type="checkbox" name="ipLoginTryAdminBandWidthFl[]" value="y" <?php echo gd_isset($checked['ipLoginTryAdminBandWidthFl'][$key]); ?> />대역 지정
                                <button class="btn btn-sm btn-white btn-icon-minus js-logintry-del">삭제</button>
                            </li>
                        <?php }
                    } ?>
                </ul>
                <span class="notice-info mgb10">특정 IP에 대한 로그인 접속시도 제한을 해지합니다.<br/>외부연동 서비스를 사용할 때 설정하시면 로그인시도로 인한 로그인차단을 해지할 수 있으나, 보안상 해당 설정을 권장하지 않습니다.</span><br/>
                <span class="notice-info mgb10">관리자 IP 접속제한에 대한 예외 처리가 아닌, 접근시도에 대한 예외처리입니다.</span>
            </td>
        </tr>
        <?php if($proPlus) { ?>
        <tr>
            <th>SFTP IP 접속제한</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="ipSftpSecurity" value="y" <?php echo gd_isset($checked['ipSftpSecurity']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="ipSftpSecurity" value="n" <?php echo gd_isset($checked['ipSftpSecurity']['n']); ?> />사용안함
                </label>
                <div class="notice-danger">'사용안함' 선택 시 SFTP 접속가능 IP로 등록 된 모든 IP삭제 되며, 이 경우 SFTP 접근이 아예 불가하게 되오니 주의하시기 바랍니다.</div>
            </td>
        </tr>
        <tr class="ipSftp">
            <th>SFTP 접속가능 IP 등록</th>
            <td>
                <button type="button" class="btn btn-sm btn-white btn-icon-plus js-sftp-add mgb10">추가</button>
                <ul class="ipSftp list-unstyled clear-both">
                    <?php if (is_array($dataSecurity['ipSftp']) === true) {
                        foreach ($dataSecurity['ipSftp'] as $key => $val) {
                            ?>
                            <li class="form-inline">
                                <input type="text" name="ipSftp[]" value="<?php echo $val[0]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipSftp[]" value="<?php echo $val[1]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipSftp[]" value="<?php echo $val[2]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipSftp[]" value="<?php echo $val[3]; ?>" class="form-control width5p number" maxlength="3"/>
                                <button class="btn btn-sm btn-white btn-icon-minus js-sftp-del">삭제</button>
                            </li>
                        <?php }
                    } ?>
                </ul>
                <span class="notice-info mgb10">대역 지정은 불가하므로, SFTP에 접속을 허용할 모든 IP를 각각 등록하여 주시기 바랍니다.</span><br/>
                <span class="notice-info mgb10">UTM 보안서비스를 이용하시는 경우 반드시 1:1문의를 통하여 보안장비에 IP허용을 요청하여 주시기 바랍니다.</span>
            </td>
        </tr>
        <?php } ?>
    </table>
    <div class="notice-danger mgl15">
        공인 IP에 대해서만 작동하며, 사설 IP 등록 시 작동하지 않습니다.<br> (사설 IP 대역 : 10.0.0.0 ~ 10.255.255.255, 172.16.0.0 ~ 172.31.255.255, 192.168.0.0 ~ 192.168.255.255)
    </div>
    <div class="notice-danger mgl15">
        유동적으로 변경되는 IP 등록 시 접속이 제한되실 수 있으니 주의바랍니다.
    </div>
    <div class="notice-danger mgl15 mgb15">
        IP접속제한 설정 시 잘못된 IP 등록으로 사이트 접속 및 운영에 문제가 생길 수 있으므로 주의 바랍니다.
    </div>
    <div class="linepd30"></div>

    <div class="table-title gd-help-manual">
        쇼핑몰 보안 설정
    </div>
    <table class="table table-cols mgb15">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>xframe 옵션 설정</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="xframeFl" value="y" <?php echo gd_isset($checked['xframeFl']['y']); ?> />사용함 <span class="text-red">(권고)</span>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="xframeFl" value="n" <?php echo gd_isset($checked['xframeFl']['n']); ?> />사용안함 <span class="text-red">(DDoS 및 Clickjacking 등 웹보안 취약)</span>
                </label>
                <p class="notice-info">DDoS 및 Clickjacking 등 웹보안을 지키기 위한 권고사항으로 xframe 옵션을 사용하시기 바랍니다.</p>
                <p class="notice-info">옵션 설정 사용 시 같은 도메인을 사용할 경우에만 정상 동작되며, 다른 도메인은 사용할 경우 동작되지 않습니다.</p>
                <p class="notice-info">옵션을 설정하지 않을 경우 모든 도메인에 정상 동작 하지만 웹보안 취약점이 발생될 수 있습니다.</p>
            </td>
        </tr>
        <tr>
            <th>회원 비밀번호 변경 추가 검증 설정</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="memberCertificationValidationFl" value="y" <?php echo gd_isset($checked['memberCertificationValidationFl']['y']); ?> onclick="announceSkinPatch('memberCertificationValidationFl')"/>사용함 <span class="text-red">(권고)</span>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="memberCertificationValidationFl" value="n" <?php echo gd_isset($checked['memberCertificationValidationFl']['n']); ?> />사용안함
                </label>
                <p class="notice-info">회원 비밀번호 변경 시, 세션 정보 탈취를 통해 다른 회원의 비밀번호를 변경할 수 없도록 검증 절차를 추가 합니다.</p>
                <p class="notice-info">추가 되는 검증 절차는 내부 시스템으로만 진행되며, 쇼핑몰 회원이 별도로 진행하는 단계는 추가되지 않습니다. </p>
                <p class="notice-info">쇼핑몰의 보안강화를 위해 사용하는 것을 권장 드리며, 사용하지 않을 경우 보안에 취약할 수 있습니다. </p>
                <p class="notice-info">해당 설정은 반드시 <span class="text-red">PC와 모바일 쇼핑몰 모두 스킨패치 적용 후 변경</span>하여 주시기 바랍니다. <a href="https://www.nhn-commerce.com/customer/patch-view.gd?sno=3970" target="_blank">바로가기></a></p>
            </td>
        </tr>
        <tr>
            <th>휴대폰 본인인증정보 보안 설정</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="authCellPhoneFl" value="y" <?php echo gd_isset($checked['authCellPhoneFl']['y']); ?> onclick="announceSkinPatch('authCellPhoneFl')"/>사용함 <span class="text-red">(권고)</span>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="authCellPhoneFl" value="n" <?php echo gd_isset($checked['authCellPhoneFl']['n']); ?> />사용안함
                </label>
                <p class="notice-info">휴대폰 본인인증 시, 인증 된 정보를 변경하여 가입할 수 없도록 보안 로직을 실행합니다.</p>
                <p class="notice-info">쇼핑몰의 보안강화를 위해 사용하는 것을 권장 드리며, 사용하지 않을 경우 보안에 취약할 수 있습니다. </p>
                <p class="notice-info">해당 설정은 반드시 <span class="text-red">PC와 모바일 쇼핑몰 모두 스킨패치 적용 후 변경</span>하여 주시기 바랍니다. <a href="https://www.nhn-commerce.com/customer/patch-view.gd?sno=3971" target="_blank">바로가기></a></p>
            </td>
        </tr>
    </table>

    <div class="table-title gd-help-manual">
        쇼핑몰 화면 보안 설정
    </div>
    <table class="table table-cols mgb15">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>마우스 드래그 차단</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="unDragFl" value="y" <?php echo gd_isset($checked['unDragFl']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="unDragFl" value="n" <?php echo gd_isset($checked['unDragFl']['n']); ?> />사용안함
                </label>
                <p class="notice-info">"사용함" 선택 시 마우스로 드래그하여 텍스트 내용을 선택할 수 없습니다.</p>
            </td>
        </tr>
        <tr>
            <th>오른쪽 마우스 차단</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="unContextmenuFl" value="y" <?php echo gd_isset($checked['unContextmenuFl']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="unContextmenuFl" value="n" <?php echo gd_isset($checked['unContextmenuFl']['n']); ?> />사용안함
                </label>
                <p class="notice-info">"사용함" 선택 시 마우스 오른쪽 버튼을 클릭할 수 없습니다.</p>
            </td>
        </tr>
        <tr>
            <th>관리자 차단해제</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="managerUnblockFl" value="y" <?php echo gd_isset($checked['managerUnblockFl']['y']); ?> />사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="managerUnblockFl" value="n" <?php echo gd_isset($checked['managerUnblockFl']['n']); ?> />사용안함
                </label>
                <p class="notice-info">"사용함" 선택 시 관리자로 접속하면 '마우스 드래그 차단'과 '오른쪽 마우스 차단'을 해제합니다.</p>
            </td>
        </tr>
    </table>
    <div class="notice-info mgl15 mgb15">
        인터넷 익스플로러(IE) 외 기타 브라우저에서는 지원되지 않을 수 있습니다.
    </div>
    <div class="linepd30"></div>
    <div class="table-title gd-help-manual">
        다운로드 보안 설정
    </div>
    <table class="table table-cols mgb15" id="excelDownloadSecuritySetting">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>사용여부</th>
            <td class="form-inline">
                <label class="radio-inline">
                    <input type="radio" name="excel[use]" value="y" <?= gd_isset($checked['excel']['use']['y']); ?> />
                    사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="excel[use]" value="n" <?= gd_isset($checked['excel']['use']['n']); ?> />
                    사용안함
                </label>
                <div class="notice-info">정보통신망법 개인정보 보호조치에 따라 개인정보의 안전성 확보를 위한 다운로드 보안 설정을 ‘사용’하시길 권장합니다.</div>
            </td>
        </tr>
        <tr>
            <th>보안범위 설정</th>
            <td class="form-inline">
                <table class="table table-cols" id="excelScopeSetting">
                    <colgroup>
                        <col class="width-sm">
                        <col>
                    </colgroup>
                    <tr>
                        <th>본사</th>
                        <td>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="order" name="excel[scope][company][]" <?= gd_isset($checked['excel']['scope']['company']['order']); ?> />
                                주문/배송
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="member" name="excel[scope][company][]" <?= gd_isset($checked['excel']['scope']['company']['member']); ?> />
                                회원
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="board" name="excel[scope][company][]" <?= gd_isset($checked['excel']['scope']['company']['board']); ?> />
                                게시판
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="scm" name="excel[scope][company][]" <?= gd_isset($checked['excel']['scope']['company']['scm']); ?> />
                                공급사
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="crema" name="excel[scope][company][]" <?= gd_isset($checked['excel']['scope']['company']['crema']); ?> />
                                크리마 간편리뷰
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>공급사</th>
                        <td>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="order" name="excel[scope][provider][]" <?= gd_isset($checked['excel']['scope']['provider']['order']); ?> />
                                주문/배송
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="board" name="excel[scope][provider][]" <?= gd_isset($checked['excel']['scope']['provider']['board']); ?> />
                                게시글
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" value="scm" name="excel[scope][provider][]" <?= gd_isset($checked['excel']['scope']['provider']['scm']); ?> />
                                정산
                            </label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <th>관리자 인증수단</th>
            <td class="form-inline">
                <label class="checkbox-inline">
                    <input type="checkbox" name="excel[auth][]" value="sms"
                           data-target="#excelDownloadSecuritySmsAuthSetting" <?= gd_isset($checked['excel']['auth']['sms']); ?> />
                    SMS인증
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="excel[auth][]" value="email"
                           data-target="#excelDownloadSecurityEmailAuthSetting" <?php if ($dataSecurity['superEmailFl'] === false) echo "disabled='disabled'"; ?> <?= gd_isset($checked['excel']['auth']['email']); ?> >
                    이메일인증
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="excel[auth][]" value="ip" data-target="#excelDownloadSecurityIpAuthSetting" <?= gd_isset($checked['excel']['auth']['ip']); ?> />
                    IP인증
                </label>
                <?php if ($dataSecurity['superCellPhoneFl'] === false) { ?>
                        <div class="notice-danger">최고운영자 정보에 SMS인증정보(휴대폰번호)가 없습니다. 인증정보를 먼저 등록해주세요.<b><a href="<?= $dataSecurity['superManagerModifyUrl'] ?>" target="_blank" class="btn-link">최고운영자 정보 수정하기 ></a></b><br/> 운영자 휴대폰 번호 인증은 최고운영자의 휴대폰번호가 등록된 경우에만 사용 가능합니다.
                        </div>
                <?php } ?>
                <?php if ($dataSecurity['superEmailFl'] === false) { ?>
                    <div class="notice-danger"> 최고운영자 정보에 이메일 인증정보가 없습니다. 인증정보를 먼저 등록해주세요. <b> <a href="<?= $dataSecurity['superManagerModifyUrl'] ?>" target="_blank" class="btn-link">최고운영자 정보 수정하기 ></a></b><br/> 이메일인증은 최고운영자의 이메일 정보가 등록된 경우에만 사용 가능합니다.
                    </div>
                <?php } ?>
                <div class="notice-info">
                    이메일인증은 주문처리 및 프로모션 등으로 인해 이메일이 대량으로 발송될 경우 인증번호 전송이 지연될 수 있으므로 SMS인증을 사용하시길 권장합니다.
                </div>
            </td>
        </tr>
        <tr id="excelDownloadSecuritySmsAuthSetting" class="display-none">
            <th>SMS인증 설정</th>
            <td>
                <input type="hidden" name="excel[smsAuth]" id="excelSmsAuthManager" value="manager" />
                운영자 정보에 등록된 SMS로 관리자 보안 인증번호가 발송됩니다.
                <p class="notice-info">운영자 인증번호 SMS는 잔여포인트가 있어야 발송됩니다. (잔여포인트 : <?= number_format($smsPoint) ?>) <a href="../member/sms_charge.php" target="_blank" class="btn-link">SMS포인트 충전하기 ></a></p>
                <p class="notice-info">로그인한 운영자 정보에 SMS 인증정보(휴대폰번호)가 없는 경우, 엑셀 다운로드가 불가합니다.</p>
            </td>
        </tr>
        <tr id="excelDownloadSecurityEmailAuthSetting" class="display-none">
            <th>이메일인증 설정</th>
            <td>운영자 정보에 등록된 이메일로 관리자 보안 인증메일이 발송됩니다.
                <p class="notice-info">로그인한 운영자 정보에 이메일 인증정보가 없는 경우, 엑셀 다운로드가 불가합니다.</p>
            </td>
        </tr>
        <tr id="excelDownloadSecurityIpAuthSetting" class="display-none">
            <th>IP인증 설정</th>
            <td>
                <button type="button" class="btn btn-sm btn-white btn-icon-plus mgb10 js-excel-add">추가</button>
                <span class="notice-info mgb10">등록된 IP만 설정한 보안범위의 엑셀 다운로드가 가능합니다. (현재 접속 IP : <?= $remoteAddr; ?>)</span>
                <ul class="ipExcel list-unstyled clear-both">
                    <?php if (is_array($dataSecurity['ipExcel']) === true) {
                        foreach ($dataSecurity['ipExcel'] as $key => $val) {
                            ?>
                            <li class="form-inline">
                                <input type="text" name="ipExcel[]" value="<?= $val[0]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipExcel[]" value="<?= $val[1]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipExcel[]" value="<?= $val[2]; ?>" class="form-control width5p number" maxlength="3"/>
                                <input type="text" name="ipExcel[]" value="<?= $val[3]; ?>" class="form-control width5p number" maxlength="3"/>
                                <span class="js-bandWidth<?php if (trim($dataSecurity['ipExcelBandWidth'][$key]) === '') { ?> display-none<?php } ?>">
                                    ~
                                    <input type="text" name="ipExcelBandWidth[]" value="<?= $dataSecurity['ipExcelBandWidth'][$key]; ?>" class="form-control width5p number js-bandWidthText" maxlength="3"/>
                                </span>
                                <input type="checkbox" name="ipExcelBandWidthFl[]" value="y" <?= gd_isset($checked['ipExcelBandWidthFl'][$key]); ?> />
                                대역 지정
                                <button class="btn btn-sm btn-white btn-icon-minus js-excel-del">삭제</button>
                            </li>
                        <?php }
                    } ?>
                </ul>
            </td>
        </tr>
    </table>
</form>
<!-- //@formatter:on -->

<style>
    .js-field-select-wapper {
        height:300px;
        overflow:scroll;
        overflow-x:hidden;
        border:1px solid #dddddd;
        margin-bottom: 10px;
    }

    .js-field-default,  .js-field-select {
        width:100%;
    }

    .js-field-default td ,  .js-field-select td {
        border:1px solid #dddddd;
    }

    .select-item { color:#999; }

</style>

<script type="text/javascript">
    <!--

    var manage_security = {
        "use_login_authentication": false,
        "authentication_login_period": 0,
        "element": {
            "$sms_security": $('input:radio[name=smsSecurity]'),
            "$auth_login_period": $('select[name=authLoginPeriod]')
        }
    };

    manage_security.element.$sms_security.filter(':checked').trigger('click');
    manage_security.element.$auth_login_period.val(manage_security.element.$auth_login_period.data('period'));

    $.validator.addMethod("ipAdmin", function () {
        var value = $(':radio[name="ipAdminSecurity"]:checked').val();
        if (value === 'n') {
            return true;
        }
        var $ipAdminArr = $('input[name="ipAdmin[]"]');
        if ($ipAdminArr.length < 1) {
            return false;
        }
        var result = true;
        $ipAdminArr.each(function (idx, item) {
            if (item.value === '') {
                result = false;
                return false;
            }
        });

        return result;
    }, '관리자 접속가능 IP를 추가해주세요.');
    $.validator.addMethod("ipFront", function () {
        var value = $(':radio[name="ipFrontSecurity"]:checked').val();
        if (value === 'n') {
            return true;
        }
        var $ipFrontArr = $('input[name="ipFront[]"]');
        if ($ipFrontArr.length < 1) {
            return false;
        }
        var result = true;
        $ipFrontArr.each(function (idx, item) {
            if (item.value === '') {
                result = false;
                return false;
            }
        });

        return result;
    }, '쇼핑몰 접속제한 IP를 추가해주세요.');
    $.validator.addMethod("ipSftp", function () {
        var value = $(':radio[name="ipSftpSecurity"]:checked').val();
        if (value === 'n') {
            return true;
        }
        var $ipSftpArr = $('input[name="ipSftp[]"]');
        if ($ipSftpArr.length < 1) {
            return false;
        }
        var result = true;
        $ipSftpArr.each(function (idx, item) {
            if (item.value === '') {
                result = false;
                return false;
            }
        });

        return result;
    }, 'SFTP 접속가능 IP를 입력하세요.');
    $.validator.addMethod("excelUse", function () {
        var value = $(':radio[name="excel[use]"]:checked').val();
        if (value === 'n') {
            return true;
        }
        return $('#excelScopeSetting').find(':checked').length > 0;
    }, '엑셀 다운로드 보안 범위를 설정해주세요.');
    $.validator.addMethod("excelAuth", function () {
        var value = $(':radio[name="excel[use]"]:checked').val();
        if (value === 'n') {
            return true;
        }
        return $('input[name="excel[auth][]"]:checked').length > 0;
    }, '엑셀 다운로드 관리자 인증 수단을 설정해주세요.');
    $.validator.addMethod("excelAuthIp", function () {
        if (!$('input[name="excel[auth][]"]:eq(2)').prop('checked')) {
            return true;
        }
        var $ip = $('input[name="ipExcel[]"]');
        if ($ip.length < 1) {
            return false;
        }
        var result = true;
        $ip.each(function (idx, item) {
            if (item.value === '') {
                result = false;
                return false;
            }
        });
        return result;
    }, '엑셀 다운로드 허용 IP를 추가해주세요.');
    $.validator.addMethod("ipLoginTryAdmin", function () {
        if ($('input[name="ipLoginTryAdmin[]"]').length < 1) {
            return true;
        }
        var $ipLoginTryAdminArr = $('input[name="ipLoginTryAdmin[]"]');
        if ($ipLoginTryAdminArr.length < 1) {
            return false;
        }
        var result = true;
        $ipLoginTryAdminArr.each(function (idx, item) {
            if (item.value === '') {
                result = false;
                return false;
            }
        });

        return result;
    }, '관리자 접근시도 예외등록 IP를 추가해주세요.');

    // 리스트 클릭 활성/비활성화
    var iciRow = '';
    var preRow = '';

    $(document).ready(function () {
        $('.js-submit').click(function() {
            if (
                $('input:hidden[name="smsSecurity"]').val() == 'y'
                && $('input:radio[name="smsSecurity"]:checked').val() == 'n'
            ) {
                // 보안 로그인 설정을 사용함 > 사용안함으로 설정 변경한 경우
                BootstrapDialog.confirm({
                    title: '경고',
                    message:
                        '<span class="text-red">관리자 보안 로그인을 사용하지 않을 경우,</span><br>' +
                        '<span class="text-red">개인정보 보호법 제 29조 및 제 75조에 따라 5천만 원 이하의 과태료에 처할 수 있습니다.</span><br>' +
                        '<a class="text-blue" href="https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%20%EB%B3%B4%ED%98%B8%EB%B2%95">내용 확인 &gt;</a><br><br>' +
                        '<span>관리자 보안 로그인 설정을 “사용안함”으로 변경하시겠습니까?</span>'
                    ,
                    btnOKLabel: '확인',
                    closable: false,
                    callback: function(result){
                        if (result) {
                            $('input:hidden[name="smsSecurity"]').val('n');
                            $('#frmManageSecurity').submit();
                        }
                    }
                });
            } else {
                $('#frmManageSecurity').submit();
            }
        });

        // 스크롤저장버튼
        $("#frmManageSecurity").validate({
            rules: {
                "excel[use]": {
                    excelUse: true,
                    excelAuth: true,
                    excelAuthIp: true
                },
                "ipAdminSecurity": {
                    ipAdmin: function(){
                        return $(':radio[name="ipAdminSecurity"]:checked').val() === 'y';
                    }
                },
                "ipFrontSecurity": {
                    ipFront: function(){
                        return $(':radio[name="ipFrontSecurity"]:checked').val() === 'y';
                    }
                },
                "ipSftpSecurity": {
                    ipSftp: function(){
                        return $(':radio[name="ipSftpSecurity"]:checked').val() === 'y';
                    }
                },
                "ipLoginTryAdminSecurity": {
                    ipLoginTryAdmin: function(){
                        return $('input[name="ipLoginTryAdmin[]"]').length > 0;
                    }
                }
            },
            submitHandler: function (form) {
                //대역 IP 범위 체크
                $validateMessage = '';
                if ($('input[name*=BandWidth]').length > 0) {
                    $.each($('input[name*=BandWidth]'), function () {
                        if ($.trim($(this).val()) !== '') {
                            if (parseInt($(this).closest('li').find('input[type="text"]').eq(3).val()) > parseInt($(this).val())) {
                                $validateMessage = '정확한 IP 대역을 입력해주세요.';
                                return false;
                            }
                        }
                    });
                }

                if ($.trim($validateMessage) === '') {
                    form.target = 'ifrmProcess';
                    form.submit();
                }
                else {
                    setTimeout(function () {
                        layer_close();
                    }, 500);
                    setTimeout(function () {
                        alert($validateMessage);
                    }, 1000);
                }
            }
        });

        $('input:radio[name="ipAdminSecurity"]').click(function (e) {
            changeIpAdminSecurity();
        });
        $('input:radio[name="ipFrontSecurity"]').click(function (e) {
            changeIpFrontSecurity();
        });
        $('input:radio[name="ipSftpSecurity"]').click(function (e) {
            changeIpSftpSecurity();
        });
        $('.js-admin-add').click(function (e) {
            ipAdminAdd();
        });
        $('.js-admin-del').click(function (e) {
            $(this).closest('li').remove();
        });
        $('.js-front-add').click(function (e) {
            ipFrontAdd();
        });
        $('.js-front-del').click(function (e) {
            $(this).closest('li').remove();
        });
        $('.js-sftp-add').click(function (e) {
            ipSftpAdd();
        });
        $('.js-sftp-del').click(function (e) {
            $(this).closest('li').remove();
        });
        $('.js-excel-add').click(function (e) {
            ipExcelAdd();
        });
        $('.js-excel-del').click(function (e) {
            $(this).closest('li').remove();
        });
        $('.js-logintry-add').click(function (e) {
            ipLogintryAdd();
        });
        $('.js-logintry-del').click(function (e) {
            $(this).closest('li').remove();
        });
        $('input:checkbox[name="smsSecurityFl"],input:checkbox[name="emailSecurityFl"]').click(function (e) {
            changeSecurity();
        });
        $('input:radio[name="sessionLimitUseFl"]').click(function (e) {
            changeSessionLimit();
        });
        $('input:radio[name=smsSecurity]').click(function (e) {
            manage_security.use_login_authentication = e.target.value === 'y';
            if (manage_security.use_login_authentication) {
                $('#authLoginPeriodRow').removeClass('display-none');
            } else {
                $('#authLoginPeriodRow').addClass('display-none');
            }
        });
        $(document).on("click", 'input[name*="BandWidthFl"]', function () {
            if ($(this).prop('checked')) {
                $(this).siblings('.js-bandWidth').removeClass('display-none');
            }
            else {
                $(this).closest('li').find('.js-bandWidthText').val('');
                $(this).siblings('.js-bandWidth').addClass('display-none');
            }
        });

        var $cb_excel_auth = $(':checkbox[name="excel[auth][]"]');
        $cb_excel_auth.change(function (e) {
            var $target = $(e.target.dataset.target);
            if (e.target.checked) {
                $target.removeClass('display-none');
            } else {
                $target.addClass('display-none');
            }
        });
        $cb_excel_auth.filter(':checked').trigger('change');
        var $rd_excel_use = $(':radio[name="excel[use]"]');
        $rd_excel_use.click(function (e) {
            var $excelDownloadSecurityScopeSetting = $('#excelDownloadSecuritySetting');
            $excelDownloadSecurityScopeSetting.find('tr:gt(0):lt(7)').addClass('display-none');
            if (e.target.checked && e.target.value === 'y') {
                $excelDownloadSecurityScopeSetting.find('tr:gt(0):lt(4)').removeClass('display-none');
                $cb_excel_auth.filter(':checked').trigger('change');
            }
        });
        $rd_excel_use.filter(':checked').trigger('click');

        changeIpAdminSecurity();
        changeIpFrontSecurity();
        changeIpSftpSecurity();
        changeSecurity();
        changeSessionLimit();

        var $checkbox_sms_security = $('input[name=smsSecurity]:checked');
        $checkbox_sms_security.trigger('click');
        manage_security.use_login_authentication = $checkbox_sms_security.val() === 'y';
        manage_security.authentication_login_period = $('select[name=authLoginPeriod').data('period');

        // 국가별 허용접속 / 차단
        var lastDefaultRow;
        $('.js-field-default').on('click', 'tr', function (event) {

            $(".js-field-select tbody tr").siblings().each(function () {
                $(this).removeClass('warning').css('background','#fff');
            });
            preRow = iciRow = '';

            if (event.shiftKey) {

                var ia = lastDefaultRow.index();
                var ib = $(this).index();

                var bot = Math.min(ia, ib);
                var top = Math.max(ia, ib);

                for (var i = bot; i <= top; i++) {
                    $('.js-field-default tbody tr').eq(i).addClass('default_select');
                    $('.js-field-default tbody tr').eq(i).css('background','#fcf8e3');
                }

            } else {
                if($(this).hasClass('default_select')) {
                    $(this).removeClass('default_select');
                    $(this).css('background','#ffffff');
                } else {
                    $(this).addClass('default_select');
                    $(this).css('background','#fcf8e3');
                }
            }

            lastDefaultRow = $(this);
        });

        var lastSelectedRow = "";
        $(document).on('click', '.js-field-select tbody tr', function (event) {

            if (iciRow) preRow = iciRow;
            iciRow = $(this);

            if (event.shiftKey) {

                var ia = lastSelectedRow.index();
                var ib = $(this).index();

                var bot = Math.min(ia, ib);
                var top = Math.max(ia, ib);

                for (var i = bot; i <= top; i++) {
                    $('.js-field-select tbody tr').eq(i).addClass('warning');
                    $('.js-field-select tbody tr').eq(i).css('background','#fcf8e3');
                }

            } else {
                if($(this).hasClass('warning')) {
                    $(this).removeClass('warning');
                    $(this).css('background','#ffffff');
                } else {
                    $(this).addClass('warning');
                    $(this).css('background','#fcf8e3');
                }
            }

            lastSelectedRow = $(this);

            if($(".js-field-select tr.warning").length == 0 ) {
                preRow = iciRow = '';
            }

        });

        $(".js-move-left").click(function(e){

            if($(".js-field-default tr.default_select").length == 0 ) {
                alert("이동할 항목을 선택해주세요.");
                return false;
            }

            var checkCnt = 0;

            $(".js-field-default tr.default_select").each(function () {

                var key = $(this).data('field-key');
                var name = $(this).data('field-name');
                var check = true;

                $(".move-row").each(function () {
                    if(key == $(this).data("field-key")) {
                        checkCnt++;
                        check = false;
                    }
                });


                if (check == true) {
                    var fieldName = name;
                    $(".js-field-select tbody").append("<tr class='move-row' data-field-key='"+key+"'  data-field-name='"+name+"' ><td style='padding:10px;'>"+fieldName+"<input type='hidden' name='countryAccessBlocking[]' value='"+key+"'/></td></tr>");

                    $(".js-field-default tr[data-field-key='" + key + "']").removeClass('default_select');
                    $(".js-field-default tr[data-field-key='" + key + "']").css('background', '#ffffff').addClass("select-item");
                }

            });

            if(checkCnt > 0 ) {
                alert("중복된 항목은 추가 되지 않습니다.");
            }

            return false;

        });

        $(".js-move-right").click(function(e){

            if($(".js-field-select tr.warning").length == 0 ) {
                alert("삭제할 항목을 선택해주세요.");
                return false;
            }

            $(".js-field-select tr.warning").each(function () {
                var key = $(this).data('field-key');
                $(".js-field-select tr[data-field-key='" + key + "']").remove();
                $(".js-field-default tr[data-field-key='" + key + "']").removeClass("select-item");
                $(".js-field-default tr[data-field-key='" + key + "']").removeClass("select-item");
            });

            $(".js-field-select").css("height","");

            return false;

        });
    });

    function changeIpAdminSecurity() {
        if ($('input:radio[name="ipAdminSecurity"]:checked').val() == 'y') {
            $('.ipAdmin').show();
        } else if ($('input:radio[name="ipAdminSecurity"]:checked').val() == 'n') {
            $('input:text[name^="ipAdmin"]').val('');
            $('ul.ipAdmin li').remove();
            $('.ipAdmin').hide();
        }
    }

    function changeIpFrontSecurity() {
        if ($('input:radio[name="ipFrontSecurity"]:checked').val() == 'y') {
            $('.ipFront').show();
        } else if ($('input:radio[name="ipFrontSecurity"]:checked').val() == 'n') {
            $('input:text[name^="ipFront"]').val('');
            $('ul.ipFront li').remove();
            $('.ipFront').hide();
        }
    }

    function changeIpSftpSecurity() {
        if ($('input:radio[name="ipSftpSecurity"]:checked').val() == 'y') {
            $('.ipSftp').show();
        } else if ($('input:radio[name="ipSftpSecurity"]:checked').val() == 'n') {
            $('input:text[name^="ipSftp"]').val('');
            $('ul.ipSftp li').remove();
            $('.ipSftp').hide();
        }
    }

    function changeSecurity() {
        if ($('input:checkbox[name="emailSecurityFl"]').is(':checked') == false && $('input:checkbox[name="smsSecurityFl"]').is(':checked') == false) {
            $('.js-security').prop('disabled', true);
        } else {
            $('.js-security').prop('disabled', false);
        }
    }

    function changeSessionLimit() {
        if ($('input:radio[name="sessionLimitUseFl"]:checked').val() == 'n') {
            $('select[name="sessionLimitTime"]').prop('disabled', true);
        } else {
            $('select[name="sessionLimitTime"]').prop('disabled', false);
        }
    }

    function ipAdminAdd() {
        var addHtml = '';
        addHtml += '<li class="form-inline">';
        addHtml += '	<input type="text" name="ipAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '    <span class="js-bandWidth display-none">';
        addHtml += '    ~';
        addHtml += '    <input type="text" name="ipAdminBandWidth[]" value="" class="form-control width5p number js-bandWidthText" maxlength="3"/>';
        addHtml += '    </span>';
        addHtml += '    <input type="checkbox" name="ipAdminBandWidthFl[]" value="y" />대역 지정';
        addHtml += '	<button class="btn btn-sm btn-white btn-icon-minus js-admin-del">삭제</button>';
        addHtml += '</li>';
        $('ul.ipAdmin').append(addHtml);
        $('.js-admin-del').on('click', function (e) {
            $(this).closest('li').remove();
        });
    }

    function ipFrontAdd() {
        var addHtml = '';
        addHtml += '<li class="form-inline">';
        addHtml += '	<input type="text" name="ipFront[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipFront[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipFront[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipFront[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '    <span class="js-bandWidth display-none">';
        addHtml += '    ~';
        addHtml += '    <input type="text" name="ipFrontBandWidth[]" value="" class="form-control width5p number js-bandWidthText" maxlength="3"/>';
        addHtml += '    </span>';
        addHtml += '    <input type="checkbox" name="ipFrontBandWidthFl[]" value="y" />대역 지정';
        addHtml += '	<button class="btn btn-sm btn-white btn-icon-minus js-front-del">삭제</button>';
        addHtml += '</li>';
        $('ul.ipFront').append(addHtml);
        $('.js-front-del').on('click', function (e) {
            $(this).closest('li').remove();
        });
    }

    function ipSftpAdd() {
        var addHtml = '';
        addHtml += '<li class="form-inline">';
        addHtml += '	<input type="text" name="ipSftp[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipSftp[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipSftp[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipSftp[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<button class="btn btn-sm btn-white btn-icon-minus js-sftp-del">삭제</button>';
        addHtml += '</li>';
        $('ul.ipSftp').append(addHtml);
        $('.js-sftp-del').on('click', function (e) {
            $(this).closest('li').remove();
        });
    }

    function ipExcelAdd() {
        var addHtml = '';
        addHtml += '<li class="form-inline">';
        addHtml += '	<input type="text" name="ipExcel[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipExcel[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipExcel[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipExcel[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '    <span class="js-bandWidth display-none">';
        addHtml += '    ~';
        addHtml += '    <input type="text" name="ipExcelBandWidth[]" value="" class="form-control width5p number js-bandWidthText" maxlength="3"/>';
        addHtml += '    </span>';
        addHtml += '    <input type="checkbox" name="ipExcelBandWidthFl[]" value="y" />대역 지정';
        addHtml += '	<button class="btn btn-sm btn-white btn-icon-minus js-excel-del">삭제</button>';
        addHtml += '</li>';
        $('ul.ipExcel').append(addHtml);
        $('.js-excel-del').on('click', function (e) {
            $(this).closest('li').remove();
        });
    }

    function ipLogintryAdd() {
        var addHtml = '';
        addHtml += '<li class="form-inline">';
        addHtml += '	<input type="text" name="ipLoginTryAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipLoginTryAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipLoginTryAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '	<input type="text" name="ipLoginTryAdmin[]" value="" class="form-control width5p number" maxlength="3"/>';
        addHtml += '    <span class="js-bandWidth display-none">';
        addHtml += '    ~';
        addHtml += '    <input type="text" name="ipLoginTryAdminBandWidth[]" value="" class="form-control width5p number js-bandWidthText" maxlength="3"/>';
        addHtml += '    </span>';
        addHtml += '    <input type="checkbox" name="ipLoginTryAdminBandWidthFl[]" value="y" />대역 지정';
        addHtml += '	<button class="btn btn-sm btn-white btn-icon-minus js-logintry-del">삭제</button>';
        addHtml += '</li>';
        $('ul.ipLoginTryAdmin').append(addHtml);
        $('.js-logintry-del').on('click', function (e) {
            $(this).closest('li').remove();
        });
    }

    function announceSkinPatch(type){
        var message = "메시지가 존재하지 않습니다.";
        switch (type) {
            case "memberCertificationValidationFl" :
                message = "스킨패치 없이 해당 설정을 ‘사용함’으로 변경하는 경우,<br>";
                message += "<span class='text-red'>쇼핑몰 회원이 비밀번호 변경 시 유효하지 않은 요청으로 에러가 발생</span>하게 됩니다.<br>";
                message += "반드시 관련 스킨패치를 진행 한 후 ‘사용함’으로 변경하시기 바랍니다.<br>";
                break;
            case "authCellPhoneFl" :
                message = "스킨패치 없이 해당 설정을 ‘사용함’으로 변경하는 경우,<br>";
                message += "<span class='text-red'>휴대폰 본인인증 시 유효하지 않은 요청으로 인증이 불가</span>하게 됩니다.<br>";
                message += "반드시 관련 스킨패치를 진행 한 후 ‘사용함’으로 변경하시기 바랍니다.<br>";
                break;
        }
        dialog_alert(message, '경고');
    }
    //-->
</script>
