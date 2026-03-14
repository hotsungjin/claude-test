<script type="text/javascript">
    <!--
    $(document).ready(function () {
        const complied = _.template($('#progressExcel').html());
        $(".modal-content").append(complied());

        $("#frmExcelForm").validate({
            dialog: false,
            rules: {
                password: {
                    required: true,
                    minlength: 10,
                    maxlength: 16,
                    equalTo: "input[name=rePassword]"
                },
                rePassword: {
                    required: true
                },
                excelDownloadReason: {
                    required: true
                }
            },
            messages: {
                password: {
                    required: "비밀번호를 입력해주세요",
                    minlength: '비밀번호는 영문대문자/영문소문자/숫자/특수문자 등 2개 포함, 10~16자 적용을 권장',
                    maxlength: '비밀번호는 영문대문자/영문소문자/숫자/특수문자 중 2개 포함, 10~16자 적용을 권장',
                    equalTo: "동일한 비밀번호를 입력해주세요."
                },
                rePassword: {
                    required: "비밀번호 확인을 입력해주세요",
                }
            },
            submitHandler: function (form) {
                const excelPageNum = $.trim($("input[name='excelPageNum']").val());
                if (excelPageNum === '' || excelPageNum === '0') {
                    dialog_confirm('엑셀파일당 데이터 최대개수 제한이 없어 대용량 다운로드 시 시간이 오래 걸릴 수 있습니다. 다운로드 요청을 진행하시겠습니까?', function (result) {
                        if (result) {
                            form.target = 'ifrmProcess';
                            form.submit();
                            $(".js-progress-excel").show();
                        } else {
                            return false;
                        }
                    }, '확인', {"cancelLabel": '취소', "confirmLabel": '진행'});
                } else {
                    form.target = 'ifrmProcess';
                    form.submit();
                    $(".js-progress-excel").show();
                    return false;
                }
            }
        });

        set_excel_list();

        //영어랑숫자만입력
        $("input.js-type-normal").bind('keyup', function () {
            $(this).val($(this).val().replace(/[^a-z0-9!@#$%^_{}~,.]*/gi, ''));
        });

        // 데이터 최대개수 입력창 (숫자만)
        $("input[name='excelPageNum']").bind('keyup', function () {
            $(this).val($(this).val().replace(/[^0-9]*/gi, ''));
        });

        $('input[maxlength]').maxlength({
            showOnReady: true,
            alwaysShow: true
        });

        // maxlength의 경우 display none으로 되어있으면 정상작동 하지 않는다 따라서 페이지 로딩 후 maxlength가 적용된 후 display none으로 강제 처리 (임시방편 처리)
        setTimeout(function () {
            $('#frmExcelForm').find('input[maxlength]').next('span.bootstrap-maxlength').css({top: '1px', left: '255px'});
        }, 1000);
    });

    /**
     * 주문내역 삭제 다운로드 요청 리스트
     */
    function set_excel_list() {
        const menu = 'order';
        const reasonUseFl = '<?=$reasonUseFl;?>';
        const layerExcelToken = '<?=$layerExcelToken?>';
        const lapseOrderDeleteSno = $('#frmExcelForm input[name=sno]').val();

        $.post('../share/layer_excel_ps.php', {
            'mode': 'searchList',
            'menu': menu,
            'location': 'order_delete',
            'layerExcelToken': layerExcelToken,
            'lapseOrderDeleteSno': lapseOrderDeleteSno
        }, function (data) {
            const getData = $.parseJSON(data);
            var addHtml = "";

            // CSRF 토큰 체크
            if (getData === '잘못된 접근입니다.') {
                alert("잘못된 접근입니다.");
                $('div.bootstrap-dialog-close-button').click();
                return false;
            }

            if (!$.isEmptyObject(getData)) {
                var pageNum = 1;
                $.each(getData, function (key, val) {
                    if (val.state == 'n') var cnt = 1;
                    else var cnt = val.fileName.split('<?=STR_DIVISION?>').length;

                    for (var i = 0; i < cnt; i++) {
                        addHtml += "<tr>";
                        addHtml += "<td class='width-3xs center number'>" + pageNum + "</td>";
                        addHtml += "<td class='center' style='width: 375px'>" + val.downloadFileName + "</td>";
                        addHtml += "<td class='width-3xs center'>" + (i + 1) + "/" + cnt + "</td>";
                        if (val.state = 'y') {
                            addHtml += "<td class='width-xs center'>생성완료</td>";
                            addHtml += (val.managerNm === null) ? "<td class='width-xs center'>고도CS담당</td>" : "<td class='width-xs center'>" + val.managerNm + "(" + val.managerId + ")</td>";
                            addHtml += "<td class='width-xs center'>~" + val.expiryDate + "</td>";
                            addHtml += "<td class='width-xs center'><button type='button' class='btn btn-white btn-xs js-excel-request-download' data-sno='" + val.sno + "' data-key='" + i + "' data-title='" + val.title +"'>다운로드</button></td>";
                        } else {
                            addHtml += "<td class='width-xs center'>생성중</td>";
                            addHtml += "<td class='width-xs center'>" + val.managerNm + "(" + val.managerId + ")</td>";
                            addHtml += "<td class='width-xs center'></td>";
                            addHtml += "<td class='width-xs center'><button type='button' class='btn btn-white btn-xs js-excel-request-download'>다운로드</button></td>";
                        }
                        addHtml += '</tr>';
                        pageNum++;
                    }
                });
            } else {
                addHtml += "<tr><td class='center no-data' colspan='7'>다운로드할 엑셀 양식을 선택 후 요청 버튼을 눌러주세요.</td></tr>";
            }

            $("#tblExcelRequest tbody").html(addHtml);

            if (pageNum > 5) {
                $("#tblExcelRequest thead tr th:last").show();
            } else {
                $("#tblExcelRequest thead tr th:last").hide();
            }

            // 엑셀 다운로드 버튼 클릭시
            $('.js-excel-request-download').on('click', function () {
                if (reasonUseFl === 'y') {
                    // 다운로드 보안 설정 확인
                    var authUseFl = false;
                    $.ajax({
                        url: '../share/layer_excel_ps.php',
                        type: 'post',
                        async: false,
                        data: {'mode':'checkAuthUseFl', 'sno': $(this).data('sno')},
                        success: function (useFl) {
                            if (useFl == 'true') authUseFl = true
                        }
                    });
                }

                if ($(this).data('sno')) {
                    if (authUseFl || reasonUseFl !== 'y') {
                        $("input[name='sno']").val($(this).data('sno'));
                        $("input[name='excelKey']").val($(this).data('key'));
                        $("input[name='excelTitle']").val($(this).data('title'));
                        $("#frmExcelRequest").submit();
                    } else {
                        var complied = _.template($('#downloadReason').html());
                        var message = complied();
                        var target = $(this);
                        BootstrapDialog.show({
                            title: '엑셀 다운로드 사유',
                            size: BootstrapDialog.SIZE_WIDE,
                            message: message,
                            buttons: [{
                                label: '확인',
                                cssClass: 'btn-black',
                                hotkey: 32,
                                size: BootstrapDialog.SIZE_LARGE,
                                action: function (dialog) {
                                    if ($('#excelDownloadReason').val() == '') {
                                        $('#reasonError').removeClass('display-none');
                                        return false;
                                    }
                                    dialog.close();
                                    $("input[name='sno']").val(target.data('sno'));
                                    $("input[name='excelKey']").val(target.data('key'));
                                    $("input[name='excelTitle']").val(target.data('title'));
                                    $("input[name='excelDownloadReason']").val($('#excelDownloadReason').val());
                                    $("#frmExcelRequest").submit();
                                }
                            }]
                        });
                    }
                } else {
                    alert("파일이 생성되지 않았습니다.");
                    return false;
                }
            });

            if (getData.length > 0) {
                $("#tblExcelRequest tbody").css("display", "block");
                $("#tblExcelRequest thead").css("display", "block");
                $("#tblExcelRequest tbody").css("height", "200px");
                $("#tblExcelRequest tbody").css("overflow-y", "auto");
                $("#tblExcelRequest tbody").css("overflow-x", "hidden");
            } else {
                $("#tblExcelRequest tbody").css("display", "");
                $("#tblExcelRequest tbody").css("height", "50px");
            }

            $(".js-progress-excel").hide();
            $("#progressView").text("0%");
            $("#progressViewBg").css("width", "0%");
        });
    }

    function progressExcel(size) {
        if ($.isNumeric(size) == false) {
            size = "100";
        }

        $("#progressView").text(size + "%");
        $("#progressViewBg").css({
            "background-color": "#fa2828",
            "width": size + "%"
        });
    }

    function cancelProgressExcel() {
        dialog_confirm('요청 취소 시 생성 중인 엑셀 다운로드 파일이 모두 삭제됩니다. 진행중인 내용을 취소하고 페이지를 이동하시겠습니까?', function (result) {
            if (result) {
                if ($.browser.msie) {
                    document.execCommand("Stop");
                } else {
                    window.stop(); //works in all browsers but IE
                }

                setTimeout(function () {
                    $(".js-progress-excel").hide();
                    $("#progressView").text("0%");
                    $("#progressViewBg").css("width", "0%");
                }, 10);
            } else {
                return false;
            }
        }, '확인', {"cancelLabel": '취소', "confirmLabel": '확인'});
    }

    function hide_process() {
        $(".js-progress-excel").hide();
    }

    /**
     * 엑셀 다운로드 보안 인증
     */
    function open_excel_auth() {
        const params = {
            mode: 'download',
            url: '../share/layer_excel_ps.php',
            data: $("#frmExcelRequest").serializeArray(),
            managerId: '<?=$managerId?>'
        };
        $.get('../share/layer_excel_auth.php', params, function (data) {
            BootstrapDialog.show({
                title: '엑셀 다운로드 보안 인증',
                message: $(data),
                closable: false,
                onshow: function (dialog) {
                    const $modal = dialog.$modal;
                    BootstrapDialog.currentId = $modal.attr('id');
                }
            });
        });
    }

    //-->
</script>
<script type="text/html" id="progressExcel">
    <div class="js-progress-excel" style="position:absolute;width:100%;height:100%;top:0px;left:0px;background:#000000;z-index:1060;opacity:0.5;display:none;"></div>
    <div class="js-progress-excel" style="width:300px;background:#fff;margin:0 auto;position:absolute;z-index:1070;top:250px;padding:20px;left:270px;text-align:center;display:none;">다운로드할 엑셀파일을 생성 중입니다.<br/> 잠시만 기다려주세요.
        <p style="font-size:22px;" id="progressView">0%</p>
        <div style="width:260px;height:18px;background:#ccc;margin-bottom:10px;">
            <div id="progressViewBg" style="height:100%">&nbsp;</div>
        </div>
        <a onclick="cancelProgressExcel()" style="cursor:pointer">요청 취소</a>
    </div>
</script>

<!-- //@formatter:off -->
<form id="frmExcelForm" name="frmExcelForm" action="../share/layer_excel_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="lapse_order_delete_excel_download" />
    <input type="hidden" name="authFl" value="" />
    <input type="hidden" name="sno" value="<?=$setData['sno']?>" />
    <input type="hidden" name="location" value="order_delete" />
    <input type="hidden" name="whereFl" value="search" />
    <input type="hidden" name="passwordFl" value="y" />
    <input type="hidden" name="downloadFileName" value="<?=$setData['downloadFileName']?>" />
    <input type="hidden" name="layerExcelToken" value="<?=$layerExcelToken?>" >

    <div class="table-title gd-help-manual">엑셀다운로드</div>
    <table class="table table-cols no-title-line">
        <colgroup>
            <col class="width-md"/>
            <col/>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>비밀번호 설정</th>
            <td colspan="3">
                <div class="form-inline pw_marking">
                    <input type="password" name="password" class="form-control width-xl js-type-normal" placeholder="영문/숫자/특수문자 2개 포함, 10~16자"/>
                    <a href="#" class="icon_pw_marking"></a>
                </div>
            </td>
        </tr>
        <tr>
            <th>비밀번호 확인</th>
            <td>
                <div class="form-inline pw_marking">
                    <input type="password" name="rePassword" class="form-control width-xl js-type-normal"/>
                    <a href="#" class="icon_pw_marking"></a>
                </div>
            </td>
        </tr>
    </table>
    <script>
        $(document).ready(function () {
            $('.icon_pw_marking').on('click', function(){
                var $thisInput = $(this).closest('.pw_marking').find('.form-control');

                if($thisInput.attr('type') == 'password'){
                    $thisInput.attr('type','text')
                    $(this).addClass('on');
                }else{
                    $thisInput.attr('type','password')
                    $(this).removeClass('on');
                }
            });
        });
    </script>
    <p class="notice-info">
        개인정보를 개인용 PC에 저장할 시 암호화가 의무이므로 비밀번호 꼭! 입력하셔야 합니다.
    </p>
    <p class="notice-info">
        개인정보의 안정성 확보조치 기준(고시)에 의거하여 개인정보 다운로드 시 사유 확인이 필요합니다.
    </p>
    <div class="table-btn">
        <input type="submit" value="요청" class="btn btn-lg btn-black js-order-download">
    </div>
</form>

<div>
    <form id="frmExcelRequest" name="frmExcelRequest" action="../share/layer_excel_ps.php" method="post" target="ifrmProcess">
        <input type="hidden"  name="mode" value="download">
        <input type="hidden"  name="excelFileName" value="<?=$excelFileName?>">
        <input type="hidden"  name="sno" value="">
        <input type="hidden"  name="excelKey" value="">
        <input type="hidden"  name="excelTitle" value="">
        <input type="hidden"  name="excelDownloadReason" value="">
        <table class="table table-rows" id="tblExcelRequest">
            <thead>
            <tr>
                <th class="width-3xs">번호</th>
                <th class="width-2xl">파일명</th>
                <th class="width-3xs">파일구분</th>
                <th class="width-xs">파일상태</th>
                <th class="width-xs">요청자</th>
                <th class="width-xs">다운로드 기간</th>
                <th class="width-xs">다운로드</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </form>
</div>

<script type="text/html" id="downloadReason">
    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm">
                <col>
            </colgroup>
            <tbody>
            <tr style="border-top: 1px solid #E6E6E6;">
                <th>사유 선택</th>
                <td>
                    <div class="form-inline">
                        <?= gd_select_box('excelDownloadReason', 'excelDownloadReason', $reasonList, null, null, '=사유 선택=', null, 'form-control'); ?>
                        <div id="reasonError" class="text-red display-none">사유 선택은 필수입니다.</div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="well">
        <div class="notice-info">개인정보의 안전성 확보조치 기준(고시)에 의거하여 개인정보를 다운로드한 경우 사유 확인이 필요합니다.</div>
    </div>
</script>

<script type="text/javascript">
    <!--
    <?php if (empty($tooltipData) === false) {?>
    $(document).ready(function () {
        // 툴팁 데이터
        var tooltipData = <?php echo $tooltipData;?>;
        var sectionEle = null;
        $('#frmExcelForm .table.table-cols th').each(function(idx){
            if ($(this).closest('table').siblings('.table-title').length) {
                sectionEle = $(this).closest('table').prevAll('.table-title:first');
            } else if ($(this).closest('table').parent('div').siblings('.table-title').length) {
                sectionEle = $(this).closest('table').parent('div').prevAll('.table-title:first');
            } else {
                sectionEle = $(this).closest('table').parent('div').parent('div').prevAll('.table-title:first');
            }
            if (typeof sectionEle[0] !== "undefined") {
                var sectionTitle = $(sectionEle[0]).html().replace(/\(?<\/?[^*]+>/gi, '').trim().replace(/ /gi, '').replace(/\n/gi, '');
                var titleName = $(this).text().trim().replace(/ /gi, '').replace(/\n/gi, '');
                for (var i in tooltipData) {
                    if (tooltipData[i].title == sectionTitle) {
                        if (tooltipData[i].attribute == titleName) {
                            $(this).append('<button type="button" onclick="tooltip(this)" class="btn btn-xs js-layer-tooltip" title="' + tooltipData[i].content + '" data-placement="right" data-width="' + tooltipData[i].cntWidth + '"><span title="" class="icon-tooltip"></span></button>');
                        }
                    }
                }
            }
        });
        $(document).on('click', '.tooltip.in .tooltip-close', function () {
            $('.js-layer-tooltip[aria-describedby=' + $(this).parent().attr('id') + ']').trigger('click');
        });
        $('button.close').click(function(){
            $('.tooltip.in .tooltip-close').trigger('click');
        });
    });

    function tooltip(e) {
        if ($(e).attr('aria-describedby')) {
            $(e).tooltip('destroy');
        } else {
            var option = {
                trigger: 'click',
                container: '#content',
                html: true,
                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div><button class="tooltip-close">close</button></div>',
            };
            $(e).on('shown.bs.tooltip', function () {
                $(".tooltip.in").css({
                    width: 254,
                    maxWidth: "none",
                });
            });
            $(e).tooltip(option).tooltip('show');
        }
    }
    <?php }?>
    //-->
</script>
<style>
    .js-layer-tooltip {background-color: transparent;}
</style>
<!-- //@formatter:on -->
