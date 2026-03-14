/**
 * 운영자 권한 설정 레이어 호출
 */
function layer_manage_permission() {
    var loadChk	= $('#managePermissionForm').length;
    var params = {};
    if ($('form#frmManager').length > 0) {
        params['mode'] = $(this).data('mode')
        params['sno'] = $('form#frmManager input:hidden[name="sno"]').val();
    } else if ($('form#frmScm').length > 0) {
        if ($(this).data('mode') == 'modifyScmModify') {
            params['mode'] = 'modify';
            params['sno'] = $('form#frmScm input:hidden[name="superManagerSno"]').val();
        } else {
            params['mode'] = 'register';
            params['sno'] = '';
        }
    }
    params['scmNo'] = get_scmno();
    params['scmFl'] = get_scmfl();
    params['isSuper'] = $(this).data('issuper');
    params['reCall'] = $(this).data('recall');
    params['isAccessControlReason'] = $('form#frmManager input:hidden[name="isAccessControlReason"]').val();

    var existingPermission = { permissionFl : '', permissionMenu : {}, writeEnabledMenu : {}, functionAuth : {} };
    var existingPermNames = ['permissionFl', 'permission_1', 'permission_2', 'permission_3', 'writeEnabledMenu', 'functionAuth'];
    $.each(existingPermNames, function (index, tName) {
        switch ( tName ) {
            case 'permissionFl':
                existingPermission[tName] = $("input[name='" + tName + "']").val();
                break;
            case 'permission_1':
                if( $("input[name^='" + tName + "[']").length > 0 ){
                    existingPermission.permissionMenu[tName] = [];
                    $("input[name^='" + tName + "[']").each(function () {
                        existingPermission.permissionMenu[tName].push( $(this).val() );
                    });
                }
                break;
            case 'permission_2':
            case 'permission_3':
                if( $("input[name^='" + tName + "[']").length > 0 ){
                    existingPermission.permissionMenu[tName] = {};
                    $("input[name^='" + tName + "[']").each(function () {
                        if (typeof existingPermission.permissionMenu[tName][ $(this).data("item") ] === 'undefined') {
                            existingPermission.permissionMenu[tName][ $(this).data("item") ] = [];
                        }
                        existingPermission.permissionMenu[tName][ $(this).data("item") ].push( $(this).val() );
                    });
                }
                break;
            case 'functionAuth':
                if( $("input[name^='" + tName + "[']").length > 0 ){
                    existingPermission[tName][tName] = {};
                    $("input[name^='" + tName + "[']").each(function () {
                        existingPermission[tName][tName][ $(this).data("item") ] = $(this).val();
                    });
                }
                break;
            default:
                $("input[name^='" + tName + "[']").each(function () {
                    if (typeof existingPermission[tName][ $(this).data("item") ] === 'undefined') {
                        existingPermission[tName][ $(this).data("item") ] = [];
                    }
                    existingPermission[tName][ $(this).data("item") ].push( $(this).val() );
                });
        }
    });
    params['existingPermission'] = JSON.stringify(existingPermission);

    $.post('../share/layer_manage_permission.php', params, function (data) {
        if (loadChk == 0) {
            data = '<div id="managePermissionForm">'+data+'</div>';
        }
        var layerForm = data;

        BootstrapDialog.show({
            title:'메뉴 권한 설정',
            size: BootstrapDialog.SIZE_WIDE,
            message: $(layerForm),
            closable: true
        });
    });
}

/**
 * 운영자 권한 적용
 * @param isSuper
 * @param isProvider
 */
function set_parent_manage_permission(isSuper, isProvider) {
    if ($('select[id^="permission_"]').length == 0) {
        alert('정상적으로 서비스가 제공되지 않아 적용할 수 없습니다.');
        return;
    }

    var hiddenData = '';

    // 권한 범위
    var permissionFl = $('.permission-flag input:radio[name="permissionFl"]:checked').val();
    hiddenData += '<input type="hidden" name="permissionFl" value="' + permissionFl + '"/>';

    // 메뉴 권한
    if (permissionFl == 'l') {
        var permissionMenuArr = {};
        var writeEnabledMenuArr = {};
        $('select[id^="permission_"] option[value="readonly"]:selected, select[id^="permission_"] option[value="writable"]:selected').each(function () {
            var menuCode = $(this).parent().attr('id').split('_');
            if (menuCode[1] == 'godo00778') { // 본사 - [모바일앱 서비스] 경우 하위 메뉴 정의
                menuCode[2] = 'godo00780';
                menuCode[3] = 'godo00781';
            }
            if (menuCode[1] == 'godo00801') { // 본사 - [샵링커 서비스] 경우 하위 메뉴 정의
                menuCode[2] = 'godo00802';
                menuCode[3] = 'godo00803';
            }

            // 권한설정(1차)
            if (typeof menuCode[1] !== 'undefined' && !(menuCode[1] in permissionMenuArr))  permissionMenuArr[ menuCode[1] ] = {};

            // 권한설정(2차)
            if (typeof menuCode[2] !== 'undefined' && !(menuCode[2] in permissionMenuArr[ menuCode[1] ])) permissionMenuArr[ menuCode[1] ][ menuCode[2] ] = [];

            // 권한설정(3차)
            if (typeof menuCode[3] !== 'undefined' && -1 === $.inArray(menuCode[3], permissionMenuArr[ menuCode[1] ][ menuCode[2] ])) permissionMenuArr[ menuCode[1] ][ menuCode[2] ].push(menuCode[3]);

            // 읽기+쓰기
            if ($(this).val() == 'writable') {
                // 권한설정(2차)
                if (typeof menuCode[2] !== 'undefined' && !(menuCode[2] in writeEnabledMenuArr))  writeEnabledMenuArr[ menuCode[2] ] = [];

                // 권한설정(3차)
                if (typeof menuCode[3] !== 'undefined' && -1 === $.inArray(menuCode[3], writeEnabledMenuArr[ menuCode[2] ])) writeEnabledMenuArr[ menuCode[2] ].push(menuCode[3]);
            }
        });

        var permissionMenuTagArr = {'top' : '', 'mid' : '', 'last' : ''};
        $.each(permissionMenuArr, function (top_code, top_sub) {
            permissionMenuTagArr['top'] += '<input type="hidden" name="permission_1[]" value="' + top_code + '"/>';

            $.each(top_sub, function (mid_code, mid_sub) {
                permissionMenuTagArr['mid'] += '<input type="hidden" name="permission_2[' + top_code + '][]" data-item="' + top_code + '" value="' + mid_code + '"/>';

                $.each(mid_sub, function (index, last_code) {
                    permissionMenuTagArr['last'] += '<input type="hidden" name="permission_3[' + mid_code + '][]" data-item="' + mid_code + '" value="' + last_code + '"/>';
                });
            });
        });
        hiddenData += permissionMenuTagArr['top'] + permissionMenuTagArr['mid'] + permissionMenuTagArr['last'];

        $.each(writeEnabledMenuArr, function (mid_code, mid_sub) {
            $.each(mid_sub, function (index, last_code) {
                hiddenData += '<input type="hidden" name="writeEnabledMenu[' + mid_code + '][]" data-item="' + mid_code + '" value="' + last_code + '"/>';
            });
        });
    }

    // 기능 권한
    if (isSuper == 'y' && isProvider) {
        $('#menuList input:checkbox[name^="functionAuth["]:checked').each(function () {
            hiddenData += '<input type="hidden" name="functionAuth[' + $(this).data('value') + ']" data-item="' + $(this).data('value') + '" value="y"/>';
        });
    } else {
        $('#menuList input:checkbox[name^="functionAuth["]:checked:not(:disabled)').each(function () {
            hiddenData += '<input type="hidden" name="functionAuth[' + $(this).data('value') + ']" data-item="' + $(this).data('value') + '" value="y"/>';
        });
    }

    // 권한 변경사유
    var accessControlReason = $('.access-control-reason input:text[name="accessControlReason"]').val();
    hiddenData += '<input type="hidden" name="accessControlReason" value="' + escapeHtml(accessControlReason) + '"/>';

    $('#permission_data').html(hiddenData);
    $('.manage-permission-btn').data('recall', 'true');

    layer_close();
}

var entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#x60;',
    '=': '&#x3D;'
};

function escapeHtml (string) {
    return String(string).replace(/[&<>"'`=\/]/g, function (s) {
        return entityMap[s];
    });
}

/**
 * 권한 범위 선택
 *
 * @param object self
 * @param string modeStr 출력 여부 (show or hide)
 */
function permission_toggle(self, modeStr) {
    if ($(self).attr('value') == 's') { // 전체권한 체크
        if (sessionStorage.getItem('isChangedPermission') == 'true') {
            BootstrapDialog.show({
                title: '정보',
                message: '수정 중인 권한설정이 있습니다.<br>새로운 권한보기 시 수정 중인 내용은 저장되지 않습니다.',
                buttons: [{
                    label: '수정 중인 권한보기',
                    hotkey: 32,
                    size: BootstrapDialog.SIZE_LARGE,
                    action: function (dialog) {
                        $('.permission-flag input:radio[name="permissionFl"][value="l"]').prop("checked", true);
                        dialog.close();
                    }
                }, {
                    label: '새로운 권한보기',
                    cssClass: 'btn-white',
                    size: BootstrapDialog.SIZE_LARGE,
                    action: function (dialog) {
                        dialog.actionType = true;
                        permission_display_toggle(modeStr);
                        sessionStorage.removeItem('isChangedPermission');
                        dialog.close();
                    }
                }
                ],
                onhide: function (dialog) {
                    if (dialog.actionType != true) {
                        $('.permission-flag input:radio[name="permissionFl"][value="l"]').prop("checked", true);
                    }
                }
            });
        } else {
            permission_display_toggle(modeStr);
        }
    } else { // 선택권한 체크
        permission_display_toggle(modeStr);
    }

    // 권한설정 변경여부 기록
    sessionStorage.setItem('isChangedUnitPermission', 'true');
}

/**
 * 권한 범위별 출력 여부
 *
 * @param string modeStr 출력 여부 (show or hide or bringShow)
 */
function permission_display_toggle(modeStr) {
    // 기존 운영자 권한 불러오기 버튼
    $('.manage-btn').prop('disabled', modeStr == 'hide' ? true : false);

    // 선택한 메뉴에 권한 일괄적용
    $('select#batch_permission').prop('disabled', modeStr == 'hide' ? true : false);
    $('#set_batch_permission').prop('disabled', modeStr == 'hide' ? true : false);

    // 메뉴 리스트 타이틀 체크박스
    $('#menuList .js-menu-checkall').prop('disabled', modeStr == 'hide' ? true : false);

    // 메뉴 리스트 체크박스
    $('#menuList input:checkbox[name="chk[]"]').prop('disabled', modeStr == 'hide' ? true : false);

    // 권한설정(1차)
    $('#menuList select[name^="permission_1"]').prop('disabled', modeStr == 'hide' ? true : false);

    // 권한설정(2차)
    $('#menuList select[name^="permission_2"]').prop('disabled', modeStr == 'hide' ? true : false);

    // 권한설정(3차)
    $('#menuList select[name^="permission_3"]').prop('disabled', modeStr == 'hide' ? true : false);

    // 추가설정(기능권한) 체크박스
    $('#menuList input:checkbox[name^="functionAuth["]:not([data-disabled-lock="lock"])').prop('disabled', modeStr == 'hide' ? true : false);

    // 전체권한일 때 권한설정 및 추가설정 초기(읽기+쓰기, checked) 상태로 변경
    if (modeStr == 'hide') {
        $('#menuList select[name^="permission_1"] option[data-defaulted="defaulted"]').prop("selected", true);
        $('#menuList select[name^="permission_2"] option[data-defaulted="defaulted"]').prop("selected", true);
        $('#menuList select[name^="permission_3"] option[data-defaulted="defaulted"]').prop("selected", true);
        $('#menuList input:checkbox[name^="functionAuth["]:not([data-disabled-lock="lock"])').prop('checked', true);
        $('#menuList input:checkbox[name$="MaskingUseFl]"]:not([data-disabled-lock="lock"])').prop('checked', false);
    }
    // 선택권한일 때 권한설정 및 추가설정 초기(권한없음, checked) 상태로 변경
    if (modeStr == 'show') {
        $('#menuList select[name^="permission_1"] option[data-defaulted="defaulted"]').prop("selected", false);
        $('#menuList select[name^="permission_2"] option[data-defaulted="defaulted"]').prop("selected", false);
        $('#menuList select[name^="permission_3"] option[data-defaulted="defaulted"]').prop("selected", false);
        $('#menuList input:checkbox[name^="functionAuth["]:not([data-disabled-lock="lock"])').prop('checked', true);
        $('#menuList input:checkbox[name$="MaskingUseFl]"]:not([data-disabled-lock="lock"])').prop('checked', false);
    }
}

/**
 * 운영자선택 레이어 호출
 */
function call_manage_select() {
    if (sessionStorage.getItem('isChangedPermission') == 'true') {
        dialog_confirm('수정 중인 권한설정이 있습니다.<br>새로운 권한보기 시 수정 중인 내용은 저장되지 않습니다.', function (result) {
            if (result) {
                layer_manage_select();
            }
        }, null, {cancelLabel:'수정 중인 권한보기', 'confirmLabel':'새로운 권한보기'});
    } else {
        layer_manage_select();
    }
}

/**
 * 운영자선택 레이어 호출
 */
function layer_manage_select() {
    var addParam = {
        'scmNo': get_scmno(), // 공급사번호
        'scmFl': get_scmfl(), // 공급사앱 사용여부
        'mode' : $('form#frmScm input:hidden[name="mode"]').val(), // 공급사관리 경우 대표운영자 조회하기 위해 mode 전달
        'layerFormID' : 'addManageSearchForm',
        'layerTitle' : '운영자선택',
    };

    var loadChk = $('#' + addParam['layerFormID']).length;
    $.ajax({
        url: '../share/layer_manage.php',
        type: 'get',
        data: addParam,
        async: false,
        success: function (data) {
            if (loadChk == 0) {
                data = '<div id="' + addParam['layerFormID'] + '">' + data + '</div>';
            }
            var layerForm = data;
            BootstrapDialog.show({
                title: addParam['layerTitle'],
                size: get_layer_size(addParam['size']),
                message: $(layerForm),
                closable: true,
                onshow: function (dialog) {
                    var $modal = dialog.$modal;
                    BootstrapDialog.currentId = $modal.attr('id');
                }
            });
        }
    });
}

/**
 * 메뉴 리스트 출력
 * @param fromManageSno
 * @param type
 * @param isSuper
 * @param toSno
 */
function change_menu_list_layout(fromManageSno, type, isSuper, toSno) {
    $("#menuList").addClass('loading');
    var params = '';
    if (toSno != '' && toSno != undefined) params += '&sno=' + toSno; // 기존 운영자 권한 불러오기 할 때 필요
    else if (get_manager_sno() != '') params += '&sno=' + get_manager_sno(); // 대상 운영자 번호, 등록할 때는 빈값
    if (fromManageSno != '') params += '&fromManageSno=' + fromManageSno; // 기존 운영자 권한 불러오기 할 때 필요
    if (get_scmno() != '') params += '&scmNo=' + get_scmno(); // 등록할 때 공급사 번호 필요
    if (get_scmfl() != '') params += '&scmFl=' + get_scmfl(); // 등록할 때 공급사 구분 필요
    if (isSuper != '') params += '&isSuper=' + isSuper; // 등록할 때 대표여부 필요
    params += '&isAccessControlReason=' + $('form#frmManagerPermission input:hidden[name="isAccessControlReason"]').val(); // 권한 변경사유 활성화&비활성화 여부

    $.ajax({
        method: "GET",
        cache: false,
        url: "../policy/manage_ps.php",
        data: "mode=getMenuListLayout&type=" + type + params,
        dataType: 'html'
    }).success(function (data) {
        $("#menuList").html(data);
        $("#menuList").removeClass('loading height400');
        sessionStorage.removeItem('isChangedPermission');
    }).error(function (e) {
        $("#menuList").removeClass('loading height400');
        alert(e.responseText);
    });
}

/**
 * 메뉴 리스트 출력 후 셋팅
 * @param isSuper
 * @param permissionFl
 * @param changeType
 * @param isProvider
 */
function set_menu_list_permissionFl(isSuper, permissionFl, changeType, isProvider) {
    var disabled_permissionFl = undefined;
    var disabled_settingItem = undefined;

    if (typeof isManagerPermissionPage !== 'undefined' && isManagerPermissionPage === true) { // 운영자 권한 설정 페이지 인 경우
        // 본사 최고운영자 또는 공급사 ADMIN 대표운영자 수정 경우 권한 범위 및 설정 기능 disabled
        if ((get_scmfl() == 'n' && get_scmno() == '1' && isSuper == 'y') || (get_scmfl() == 'y' && isSuper == 'y' && isProvider)) {
            disabled_permissionFl = disabled_settingItem = true;
        } else {
            disabled_permissionFl = disabled_settingItem = false;
        }
        // 전체권한 경우 설정 기능 disabled
        if(permissionFl == 's') disabled_settingItem = true;
    }

    if ($('.permission-flag input:radio[name="permissionFl"]:checked').val() != permissionFl) { // 권한 범위가 변경된 경우
        if(permissionFl == 's') {
            $('.permission-flag input:radio[name="permissionFl"][value="s"]').prop("checked", true);
            if (disabled_permissionFl !== true) disabled_settingItem = true;
        } else {
            $('.permission-flag input:radio[name="permissionFl"][value="l"]').prop("checked", true);
            if (disabled_permissionFl !== true) disabled_settingItem = false;
        }
    }

    if (typeof disabled_permissionFl !== 'undefined') {
        $('.permission-flag input:radio[name="permissionFl"]').prop("disabled", disabled_permissionFl); // 권한 범위 disabled
        $('.manage-btn').prop("disabled", disabled_permissionFl); // 기존 운영자 권한 불러오기 버튼 disabled
        $('#accessControlReason').prop("disabled", disabled_permissionFl); // 권한 변경 사유 disabled
    }

    if (typeof disabled_settingItem !== 'undefined') {
        $('select#batch_permission').prop('disabled', disabled_settingItem); // 선택한 메뉴에 권한 일괄적용 disabled
        $('#set_batch_permission').prop('disabled', disabled_settingItem); // 선택한 메뉴에 권한 일괄적용 disabled
    }

    if (changeType == 'bring') { // 기존 운영자 권한 불러오기 할 때 권한 범위는 '선택권한' 으로 처리
        $('.permission-flag input:radio[name="permissionFl"][value="l"]').prop("checked", true);
        permission_display_toggle('bringShow');
    }
}

/**
 * 권한 초기화
 */
function permission_initialization() {
    var btnElement = this;
    dialog_confirm('초기화하면 현재 설정된 권한이 이전 상태로 변경됩니다.<br>초기화 하시겠습니까?', function (result) {
        if (result) {
            var isSuper = $(btnElement).data('issuper');
            change_menu_list_layout('', 'init', isSuper);

            // 권한설정 변경여부 초기화
            sessionStorage.removeItem('isChangedPermission');
            sessionStorage.removeItem('isChangedUnitPermission');
        }
    });
}

/**
 * 노출 메뉴 선택 보기
 */
function view_exposure_top_menu() {
    if ($('input[name="exposure_top_menu[]"]').eq(0).is(':checked')) { // 전체 메뉴
        $('.permission-top').show();
        $('tbody[id^="top_sub_"]').addClass('top-menu-on').removeClass('top-menu-off');
    } else if ($('input[name="exposure_top_menu[]"]:checked').length) { // 선택 메뉴
        $('input[name="exposure_top_menu[]"]').each(function () {
            if ($(this).val() == 'all') return;
            var menuCode = $(this).val();
            var labelObj = $('label[data-target-id^="top_sub_' + menuCode + '"]');
            if ($(this).is(':checked')) {
                // 1차 메뉴 활성화
                $(labelObj).parents('tr').show();
                $('tbody[id^=top_sub_' + menuCode + ']').addClass('top-menu-on').removeClass('top-menu-off');
            } else {
                // 1차 메뉴 비활성화
                $(labelObj).parents('tr').hide();
                $('tbody[id^=top_sub_' + menuCode + ']').addClass('top-menu-off').removeClass('top-menu-on');
                // 1차>2차 메뉴 비활성화
                if ($(labelObj).parents('tr').data('sub-display') != 'hide') {
                    $(labelObj).parents('tr').data('sub-display', 'hide');
                    $('tbody[id^=top_sub_' + menuCode + ']').hide();
                    $('.ui-icon', labelObj).addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');
                }
            }
        });
    }
    // 메뉴 리스트 체크박스 갱신
    set_menu_list_checkbox($('.js-menu-checkall').prop("checked"), true);
    // 레이어 Close
    $('.exposure-menu-top-selection').removeClass('open');
}

/**
 * 메뉴 기준으로 보기
 */
function view_menu_depth() {
    if (this.value == 3) { // 3차 메뉴 기준으로 보기
        // 1차>2차 메뉴 활성화
        $('.permission-top').data('sub-display', 'show');
        $('tbody.top-menu-on').show();
        $('.permission-top .menu-roll .ui-icon').addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');

        // 2차>3차 메뉴 활성화
        $('.permission-mid').data('sub-display', 'show');
        $('tr[id^="mid_sub_"]').show();
        $('.permission-mid .menu-roll .ui-icon').addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');
    } else if (this.value == 2) { // 2차 메뉴 기준으로 보기
        // 1차>2차 메뉴 활성화
        $('.permission-top').data('sub-display', 'show');
        $('tbody.top-menu-on').show();
        $('.permission-top .menu-roll .ui-icon').addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');

        // 2차>3차 메뉴 비활성화
        $('.permission-mid').data('sub-display', 'hide');
        $('tr[id^="mid_sub_"]').hide();
        $('.permission-mid .menu-roll .ui-icon').addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');

    } else { // 1차 메뉴 기준으로 보기
        // 1차>2차 메뉴 비활성화
        $('.permission-top').data('sub-display', 'hide');
        $('tbody[id^="top_sub_"]').hide();
        $('.permission-top .menu-roll .ui-icon').addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');

        // 2차>3차 메뉴 비활성화
        $('.permission-mid').data('sub-display', 'hide');
        $('tr[id^="mid_sub_"]').hide();
        $('.permission-mid .menu-roll .ui-icon').addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');
    }
    // 메뉴 리스트 체크박스 갱신
    set_menu_list_checkbox($('.js-menu-checkall').prop("checked"), true);
}

/**
 * 리스트 메뉴별 Show/Hide
 */
function view_menu_roll() {
    var targetId = $(this).data('target-id');
    var display = $(this).parents('tr').data('sub-display');
    if (targetId.substring(0, 8) == 'top_sub_') { // 1차 메뉴
        if (display == 'hide') {
            // 1차>2차 메뉴 활성화
            $(this).parents('tr').data('sub-display', 'show');
            $('tbody[id^=' + targetId + ']').show();
            $('.ui-icon', this).addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');
        } else {
            // 1차>2차 메뉴 비활성화
            $(this).parents('tr').data('sub-display', 'hide');
            $('tbody[id^=' + targetId + ']').hide();
            $('.ui-icon', this).addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');
        }
    }
    else { // 2차 메뉴
        if (display == 'hide') {
            // 2차>3차 메뉴 활성화
            $(this).parents('tr').data('sub-display', 'show');
            $('tr[id^="' + targetId + '_"]').show();
            $('.ui-icon', this).addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');
        } else {
            // 2차>3차 메뉴 비활성화
            $(this).parents('tr').data('sub-display', 'hide');
            $('tr[id^="' + targetId + '_"]').hide();
            $('.ui-icon', this).addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');
        }
    }
}

/**
 * 선택한 메뉴에 권한 일괄적용
 */
function set_batch_permission() {
    if ($('select#batch_permission').val() == 'none') {
        alert('일괄 적용할 권한을 선택해주세요.');
    } else if ($('input[name="chk[]"]:checked').length == 0) {
        alert('일괄 적용할 메뉴를 선택해주세요.');
    } else {
        var batchPermission = $('select#batch_permission').val();

        // 선택된 일부 메뉴에 허용되지 않은 권한을 선택하여 일괄적용 시 alert 노출
        // 일괄적용 선택된 권한은 선택된 메뉴 중 적용이 가능한 메뉴의 '권한설정' 셀렉트박스에 적용되고, 적용 불가한 메뉴는 적용되지 않음
        var isDisabled = false;
        $('input[name="chk[]"]:checked').each(function () {
            var targetId = $(this).data('target-id');
            if ($('select[id^="' + targetId + '"] option[value="' + batchPermission + '"][data-disabled-lock="lock"]').length > 0) {
                isDisabled = true;
                return false;
            }
        });
        if (isDisabled === true) {
            dialog_alert("해당 권한을 적용할 수 없는 메뉴가 선택되었습니다.", '경고', {
                callback: function () { change_batch_permission(); }
            });
        } else {
            change_batch_permission();
        }

        // 권한설정 변경여부 기록
        if ($('input[name="chk[]"]:checked').length) {
            sessionStorage.setItem('isChangedPermission', 'true');
            sessionStorage.setItem('isChangedUnitPermission', 'true');
        }
    }
}

/**
 * 선택한 메뉴에 권한 일괄적용 변경
 */
function change_batch_permission() {
    var batchPermission = $('select#batch_permission').val();
    $('input[name="chk[]"]:checked').each(function () {
        var targetId = $(this).data('target-id');
        // 자신 + 하위 메뉴 동일한 권한 선택
        $('select[id^="' + targetId + '"] option[value="' + batchPermission + '"]:not([data-disabled-lock="lock"])').prop("selected", true);
        // 부모 메뉴 "개별설정" 선택
        var lastChangeSelect = $('select[id^="' + targetId + '"] option[value="' + batchPermission + '"]:not([data-disabled-lock="lock"]):last');
        if ($(lastChangeSelect).length > 0) {
            var lastTargetId = $(lastChangeSelect).parent('select').attr('id');
            var parentId = get_parent_menu_id(lastTargetId);
            var permission = $(lastChangeSelect).parent('select').val();
            if ($('select[id="' + parentId + '"]').length > 0) {
                permission = set_parent_menu_permission(permission, parentId);

                // 최상위 메뉴 "개별설정" 선택
                var parentId = get_parent_menu_id(parentId);
                if ($('select[id="' + parentId + '"]').length > 0) set_parent_menu_permission(permission, parentId);
            }
        }
    });
}

/**
 * 권한설정(1차)
  */
function set_menu_permission_top() {
    var targetId = $(this).attr('id');
    var permission = $(this).val();

    // 하위 메뉴 동일한 권한 선택
    $('select[id^="' + targetId + '"]').val(permission).prop("selected", true);

    // 권한설정 변경여부 기록
    sessionStorage.setItem('isChangedPermission', 'true');
    sessionStorage.setItem('isChangedUnitPermission', 'true');
}

/**
 * 권한설정(2차)
 */
function set_menu_permission_mid() {
    var targetId = $(this).attr('id');
    var permission = $(this).val();

    // 하위 메뉴 동일한 권한 선택
    $('select[id^="' + targetId + '"]').val(permission).prop("selected", true);

    // 부모(1차) 메뉴 "개별설정" 선택
    var parentId = get_parent_menu_id($(this).attr('id'));
    set_parent_menu_permission(permission, parentId);

    // 권한설정 변경여부 기록
    sessionStorage.setItem('isChangedPermission', 'true');
    sessionStorage.setItem('isChangedUnitPermission', 'true');
}

/**
 * 권한설정(3차)
 */
function set_menu_permission_last() {
    var targetId = $(this).attr('id');
    var permission = $(this).val();

    // 부모(2차) 메뉴 "개별설정" 선택
    var parentId = get_parent_menu_id($(this).attr('id'));
    permission = set_parent_menu_permission(permission, parentId);

    // 최상위(1차) 메뉴 "개별설정" 선택
    var parentId = get_parent_menu_id(parentId);
    set_parent_menu_permission(permission, parentId);

    // 권한설정 변경여부 기록
    sessionStorage.setItem('isChangedPermission', 'true');
    sessionStorage.setItem('isChangedUnitPermission', 'true');
}

/**
 * 메뉴 리스트 체크박스 설정
 * @param isCheck 설정여부
 * @param skipChecked 설정제외여부
 */
function set_menu_list_checkbox(isCheck, skipChecked) {
    if (skipChecked === true) { // 노출 메뉴 선택 또는 메뉴 기준으로 보기 일 때는 설정 제외
    } else {
        $('#menuList tbody:visible tr:visible input:checkbox[name="chk[]"]:not(:disabled)').prop('checked', isCheck); // 활성화된 1차,2차,3차 메뉴 내 체크박스 설정
    }
    $('#menuList tbody:hidden input:checkbox[name="chk[]"]:not(:disabled)').prop('checked', false); // 비활성화된 2차 메뉴 영역(3차 포함) 내 체크박스 해제
    $('#menuList tr:hidden input:checkbox[name="chk[]"]:not(:disabled)').prop('checked', false); // 비활성화된 1차 메뉴 및 3차 메뉴 내 체크박스 해제
}

/**
 * 상위 메뉴 ID 리턴
 * @param selfId
 * @returns {string}
 */
function get_parent_menu_id(selfId) {
    var tmp = selfId.split('_');
    tmp.pop();
    return tmp.join('_');
}

/**
 * 동급 메뉴와 달리 개별설정한 경우 상위 메뉴을 "개별설정" 선택
 * @param permission
 * @param parentId
 * @returns {*}
 */
function set_parent_menu_permission(permission, parentId) {
    var isSame = true;
    $('select[id^="' + parentId + '_"]').each(function () {
        var status = $(this).val();
        if (status === null) status = '';
        if (permission != status) {
            isSame = false;
            return;
        }
    });
    if (isSame === true) {
        $('select[id="' + parentId + '"]').val(permission).prop("selected", true);
    } else {
        $('select[id="' + parentId + '"]').val('individual').prop("selected", true);
        permission = 'individual';
    }
    return permission;
}

/**
 * 추가설정(기능권한) 닫기
 */
function close_function_dropdown() {
    $(this).parents('div.function-auth-selection').removeClass('open');
}

/**
 * 추가설정(기능권한) 디버그권한 Disabled 처리
 */
function set_debug_disable() {
    $('#menuList input[name="functionAuth[debugPermissionFl]"]').prop('disabled', (this.checked ? false : true));
    if (this.checked !== true) {
        $('#menuList input[name="functionAuth[debugPermissionFl]"]').prop('checked', false);
    }
}

/**
 * 운영자 권한 설정> 공급사 구분 Default
 */
function init_scmFl() {
    if ($('#frmManagerPermission input[name=scmFl]').length > 1) {
        $('#frmManagerPermission input[name=scmFl][value=n]').prop('checked', true); // 본사 checked
        $('#frmManagerPermission').data('scmFl', 'n'); // 공급사 구분값 보관

        // 운영자 검색> 공급사 구분에서 선택된 공급사 삭제
        $(document).off("click", "[data-toggle=delete]"); // 공급사 삭제 버튼 기존 이벤트 제거
        $(document).on('click', '#frmManagerPermission .btn-icon-delete', function (e) {
            if ($('input[name=scmFl][value=n]').prop('checked') !== true) {
                $('input[name=scmFl][value=n]').prop('checked', true);
                $('input[name="scmFl"]:eq(0)').trigger('click');
            }
        });
    }
}

/**
 * 운영자 권한 설정> 공급사 구분 선택
 * @param scmFl
 */
function scm_toggle(scmFl) {
    if (scmFl == 'n' && scmFl == $('#frmManagerPermission').data('scmFl')) { // '본사' 선택된 상태에서 다시 '본사' 선택한 경우
        return;
    }

    if (sessionStorage.getItem('isChangedUnitPermission') == 'true') {
        BootstrapDialog.show({
            title: '정보',
            message: '수정 중인 권한설정이 있습니다.<br>공급사 구분을 변경할 경우 수정 중인 내용은 저장되지 않습니다.',
            buttons: [{
                label: '취소',
                hotkey: 32,
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    scm_toggle_revert(); // 공급사 구분 선택 원복
                    dialog.close();
                }
            }, {
                label: '확인',
                cssClass: 'btn-white',
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    dialog.actionType = true;
                    call_scm_manager(scmFl);
                    dialog.close();
                }
            }
            ],
            onhide: function (dialog) {
                if (dialog.actionType != true) {
                    scm_toggle_revert(); // 공급사 구분 선택 원복
                }
            }
        });
    } else {
        call_scm_manager(scmFl);
    }
}

/**
 * 운영자 권한 설정> 공급사 구분 선택 원복
 */
function scm_toggle_revert() {
    var scmFl = $('#frmManagerPermission').data('scmFl');
    $('#frmManagerPermission input:radio[name=scmFl]:input[value=' + scmFl + ']').prop("checked", true);
}

/**
 * 운영자 권한 설정> 공급사 구분 선택> 운영자 요청
 * @param scmFl
 */
function call_scm_manager(scmFl) {
    if (scmFl == 'n') { // '본사' 선택한 경우 운영자 검색 요청
        if ($('input[name="scmNo"]').length > 0) {
            del_selected_scm(); // 선택된 공급사 삭제
        }
        $('#frmManagerPermission').data('scmFl', 'n'); // 공급사 구분값 보관
        $('#frmManagerPermission select[name="managerSearchKey"] option:eq(0)').prop("selected", true);
        $('#frmManagerPermission input[name="managerSearchKeyword"]').val('');
        call_manager_search(); // 운영자 검색
    } else { // '공급사' 선택한 경우 공급사 선택 요청
        $('#frmManagerPermission input:radio[name=scmFl]:input[value=y]').prop("checked", true);
        call_layer_scm();
    }
}

/**
 * 운영자 권한 설정> 공급사 구분 선택> 선택된 공급사 삭제
 */
function del_selected_scm() {
    var btnIconDelete = '#frmManagerPermission .btn-icon-delete';
    if ($(btnIconDelete).closest('.selected-btn-group').length > 0) {
        if ($(btnIconDelete).closest('.selected-btn-group').find('div.btn-group').length == 1) {
            $(btnIconDelete).closest('.selected-btn-group').removeClass('active').empty();
        }
    }
    var tbodyID = $($(btnIconDelete).data('target')).closest('tbody').prop('id');
    $($(btnIconDelete).data('target')).remove();
    $.each($('#' + tbodyID).find('.number'), function (index) {
        $(this).html(index + 1);
    });
}

/**
 * 운영자 권한 설정> 공급사 선택
 */
function call_layer_scm() {
    var fileCd = 'scm';
    var addParam = {
        "mode": 'radio',
        "callFunc": 'set_scm_select',
        "layerFormID": 'addSearchForm',
        "dataFormID": 'info_' + fileCd,
        "parentFormID": fileCd + 'Layer',
        "dataInputNm": fileCd + 'No',
        "layerTitle": '공급사 선택'
    };
    var loadChk = $('#' + addParam['layerFormID']).length;
    $.ajax({
        url: '../share/layer_' + fileCd + '.php',
        type: 'get',
        data: addParam,
        async: false,
        success: function (data) {
            if (loadChk == 0) {
                data = '<div id="' + addParam['layerFormID'] + '">' + data + '</div>';
            }
            var layerForm = data;
            var configure = {
                title: addParam['layerTitle'],
                size: get_layer_size(addParam['size']),
                message: $(layerForm),
                closable: true,
                onhide: function (dialog) {
                    if ($('#frmManagerPermission').data('scmFl') != $('#frmManagerPermission input:radio[name=scmFl]:checked').val()) {
                        scm_toggle_revert(); // 공급사 구분 선택 원복
                    }
                }
            };
            BootstrapDialog.show(configure);
        }
    });
}

/**
 * 운영자 권한 설정> 공급사 값 세팅
 * @param data
 */
function set_scm_select(data) {
    displayTemplate(data);
    $('#frmManagerPermission').data('scmFl', 'y'); // 공급사 구분값 보관
    $('#frmManagerPermission select[name="managerSearchKey"] option:eq(0)').prop("selected", true);
    $('#frmManagerPermission input[name="managerSearchKeyword"]').val('');
    call_manager_search(); // 운영자 검색
}

/**
 * 운영자 권한 설정> 운영자 검색
 * @param pagelink 페이징 번호
 */
function call_manager_search(pagelink) {
    if (typeof pagelink == 'undefined') {
        pagelink = '';
    }

    if ($('input[name=scmFl]').length) { // 공급사 사용 중이고 공급사 관리모드가 아닌 경우
        if ($('input[name=scmFl]:checked').length != 1) {
            alert('공급사 구분을 선택해주세요.');
            return;
        }
        if ($('input[name=scmFl][value="y"]:checked').length == 1 && $('input:hidden[name="scmNo"]').val() == '') {
            alert('공급사를 선택해주세요.');
            return;
        }
    }

    $("#managerList").addClass('loading');
    var parameters = {
        'mode': 'getManagerListLayout',
        'key': $('select[name="managerSearchKey"]').val(),
        'keyword': $('input[name="managerSearchKeyword"]').val(),
        'scmNo' : get_scmno(),
        'scmFl' : get_scmfl(),
        'pagelink': pagelink,
        'searchKind': $('select[name="searchKind"]').val(),
    };

    $.get('../policy/manage_ps.php', parameters, function (data) {
        $('#managerList').html(data);
        $("#managerList").removeClass('loading height400');

        // 공급사가 변경될 때만 운영자의 메뉴 권한 설정 레이아웃 재구성 호출
        var preScmNo = $('#menuList').data('scmNo');
        var nowScmNo = ($('input:hidden[name="scmNo"]').val() ? $('input:hidden[name="scmNo"]').val() : 1);
        if (preScmNo != nowScmNo || pagelink == '')  init_menu_list_layout(); // 메뉴 리스트 초기화
    });
}

/**
 * 운영자 권한 설정> 메뉴 리스트 초기화
 */
function init_menu_list_layout() {
    // 권한 범위(전체권한) 초기화
    $('.permission-flag input:radio[name="permissionFl"]').prop("disabled", false);
    $('.permission-flag input:radio[name="permissionFl"][value="s"]').prop("checked", true);

    // 기존 운영자 권한 불러오기 버튼 데이터 초기화
    $('.manage-btn').data('issuper', 'n');

    // 권한 초기화 버튼 데이터 초기화
    $('.permission-initialization-btn').data('issuper', 'n');

    // 메뉴 기준으로 보기(1차) 초기화
    $('#view_menu_depth option[value="1"]').prop("selected", true);

    // 권한설정 변경여부 초기화
    sessionStorage.removeItem('isChangedPermission');
    sessionStorage.removeItem('isChangedUnitPermission');

    // 메뉴 리스트 초기화
    change_menu_list_layout('', 'init', 'n');

    // 운영자 검색시 공급사가 변경될 때만 운영자의 메뉴 권한 설정 레이아웃을 재구성하기 위해 데이터 기록
    $('#menuList').data('scmNo', get_scmno());
}

/**
 * 운영자 권한 설정> 운영자 리스트 체크박스 액션
 */
function act_manage_list_checkbox() {
    // 개별 권한보기 해제
    if (this.checked === false && $(this).parents('tr').hasClass( 'permission-view-on' ) === true) {
        call_unit_permission( $('.btn-view-unit-permission', $(this).parents('tr')) );
    }
}

/**
 * 운영자 권한 설정> 개별 권한보기 호출
 *
 * @param btnElement
 */
function call_unit_permission(btnElement) {
    if (sessionStorage.getItem('isChangedUnitPermission') == 'true') {
        BootstrapDialog.show({
            title: '정보',
            message: '수정 중인 권한설정이 있습니다.<br>새로운 권한보기 시 수정 중인 내용은 저장되지 않습니다.',
            buttons: [{
                label: '수정 중인 권한보기',
                hotkey: 32,
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    if ($(btnElement).parents('tr').hasClass( 'permission-view-on' ) === true) {
                        $('input:checkbox[name="manage_sno[]"]', $(btnElement).parents('tr')).prop("checked", true);
                    }
                    dialog.close();
                }
            }, {
                label: '새로운 권한보기',
                cssClass: 'btn-white',
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    dialog.actionType = true;
                    view_unit_permission(btnElement);
                    dialog.close();
                }
            }
            ],
            onhide: function (dialog) {
                if (dialog.actionType != true) {
                    if ($(btnElement).parents('tr').hasClass( 'permission-view-on' ) === true) {
                        $('input:checkbox[name="manage_sno[]"]', $(btnElement).parents('tr')).prop("checked", true);
                    }
                }
            }
        });
    } else {
         view_unit_permission(btnElement);
    }
}

/**
 * 운영자 권한 설정> 개별 권한보기
 *
 * @param btnElement
 */
function view_unit_permission(btnElement) {
    if ($(btnElement).parents('tr').hasClass( 'permission-view-on' ) === true) { // 원복
        // 선택한 라인 비활성화 표기
        set_view_unit_permission_display_off($(btnElement).parents('tr'));

        // 메뉴 리스트 초기화
        init_menu_list_layout();
    } else { // 보기
        var sno = $(btnElement).parents('tr').data('sno');
        var isSuper = $(btnElement).parents('tr').data('issuper');

        // 기존 운영자 권한 불러오기 버튼 데이터 기록
        $('.manage-btn').data('issuper', isSuper);

        // 권한 초기화 버튼 데이터 기록
        $('.permission-initialization-btn').data('issuper', isSuper);

        // 메뉴 기준으로 보기(1차) 초기화
        $('#view_menu_depth option[value="1"]').prop("selected", true);

        // 권한설정 변경여부 초기화
        sessionStorage.removeItem('isChangedPermission');
        sessionStorage.removeItem('isChangedUnitPermission');

        // 선택한 라인 활성화 표기
        set_view_unit_permission_display_on($(btnElement).parents('tr'));

        // 메뉴 리스트 출력
        change_menu_list_layout('', 'init', isSuper, sno);
    }
}

/**
 * 운영자 권한 설정> 개별 권한보기> 디스플레이 활성화
 * @param trElement
 */
function set_view_unit_permission_display_on(trElement) {
    // 선택한 라인 외 비활성화 표기
    $('#managerList .js-checkall').prop("disabled", true); // 운영자 리스트 선택 체크박스
    $('#managerList input:checkbox[name="manage_sno[]"]:not(:disabled)').prop("disabled", true);
    $('#managerList input:checkbox[name="manage_sno[]"]:checked').prop("checked", false);
    $('#managerList tr.permission-view-on').each(function () {
        $('.btn-view-unit-permission', this).addClass('btn-white').removeClass('btn-black');
        $(this).removeClass('permission-view-on');
    });

    // 선택한 라인 활성화 표기
    $('input:checkbox[name="manage_sno[]"]', trElement).prop("disabled", false).prop("checked", true);
    $('.btn-view-unit-permission', trElement).addClass('btn-black').removeClass('btn-white');
    $(trElement).addClass('permission-view-on');
}

/**
 * 운영자 권한 설정> 개별 권한보기> 디스플레이 비활성화
 * @param trElement
 */
function set_view_unit_permission_display_off(trElement) {
    $('.permission-flag input:radio[name="permissionFl"]').prop("disabled", false);
    $('#managerList .js-checkall').prop("disabled", false); // 운영자 리스트 선택 체크박스
    $('#managerList input:checkbox[name="manage_sno[]"]').prop("disabled", false);
    $('input:checkbox[name="manage_sno[]"]', trElement).prop("checked", false);
    $('.btn-view-unit-permission', trElement).addClass('btn-white').removeClass('btn-black');
    $(trElement).removeClass('permission-view-on');
}

/**
 * 운영자 권한 설정> 저장 후 처리
 */
function save_after_manger() {
    // 권한설정 변경여부 초기화
    sessionStorage.removeItem('isChangedPermission');
    sessionStorage.removeItem('isChangedUnitPermission');
}
