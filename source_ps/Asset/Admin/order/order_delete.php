<div class="page-header js-affix" xmlns="http://www.w3.org/1999/html">
    <h3><?php echo end($naviMenu->location); ?> <small>5년 이상 경과한 주문서를 삭제, 관리할 수 있습니다.</small></h3>
</div>
<style>
    #frmOrderDelete .search {
        float: right;
        display: inline-block;
    }
</style>
<form id="frmOrderDelete" action="" method="get" target="">
    <input type="hidden" name="mode" value=""/>
    <input type="hidden" name="searchFl" value="y">
    <input type="hidden" name="applyPath" value="<?= gd_php_self() ?>">
    <input type="hidden" name="reFlag" value="">

    <div class="panel pd10">
        <p><b style="font-size: 15px;">주문 내역 삭제란?</b></p>
        <p>
            전자상거래 등에서의 소비자 보호 법률에 따라, 주문 발생 후 거래 기록에 대해 자신의 정보처리시스템을 통하여 처리한 범위 내에서 최대 5년 까지</br>
            개인정보를 보유할 수 있으며, 목적 달성 및 보유기간이 지난 개인정보에 대해서는 지체없이 개인정보를 복구﹒재생할 수 없도록 파기 해야 합니다.</br>
            해당 법령을 준수하지 않을 경우 2년 이하의 징역 또는 2천만원 이하의 벌금에 처할 수 있으므로 개인정보 보호 의무에 따라 5년 이상된 주문</br>
            내역에 대해 지속적인 운영 관리 하실 것을 권장합니다.
            <a href="https://www.law.go.kr/법령/전자상거래등에서의소비자보호에관한법률시행령" target="_blank">내용확인></a></br>
            <span style="color: #fa2828;">삭제한 주문 내역에 대해서는 복원 및 복구가 절대 불가하며, 삭제 후 엑셀다운로드 또한 불가하므로 삭제 시 유의하시기 바랍니다.</span>
        </p>
    </div>

    <div class="table-title gd-help-manual">삭제 대상 검색</div>

    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <?php if ($gGlobal['isUse'] === true) { ?>
                <tr>
                    <th>상점</th>
                    <td>
                        <?php
                        foreach ($gGlobal['useMallList'] as $val) {
                            ?>
                            <label class="radio-inline">
                                <input type="radio" name="mallFl"
                                       value="<?= $val['sno'] ?>" <?= gd_isset($checked['mallFl'][$val['sno']]); ?>/><span
                                        class="flag flag-16 flag-<?= $val['domainFl'] ?>"></span> <?= $val['mallName'] ?>
                            </label>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
            <?php if (gd_use_provider() === true) { ?>
                <tr>
                    <th>예외 공급사</th>
                    <td>
                        <div class="form-inline">
                            <label>
                                <button type="button" class="btn btn-sm btn-gray"
                                        onclick="layer_register('scm','checkbox')">공급사 선택
                                </button>
                            </label>
                            <div id="scmLayer"
                                 class="selected-btn-group <?= $search['scmFl'] == 'y' && !empty($search['scmNo']) ? 'active' : '' ?>">
                                <h5>선택된 공급사 : </h5>
                                <?php if ($search['scmFl'] == 'y') {
                                    foreach ($search['scmNo'] as $k => $v) { ?>
                                        <span id="info_scm_<?= $v ?>" class="btn-group btn-group-xs">
                                <input type="hidden" name="scmNo[]" value="<?= $v ?>"/>
                                <input type="hidden" name="scmNoNm[]" value="<?= $search['scmNoNm'][$k] ?>"/>
                                <span class="btn"><?= $search['scmNoNm'][$k] ?></span>
                                <button type="button" class="btn btn-icon-delete" data-toggle="delete"
                                        data-target="#info_scm_<?= $v ?>">삭제</button>
                                </span>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <p class="notice-info">삭제 주문 내역 생성 요청 후 24시간 이내 삭제 대상 내역이 생성됩니다.</p>
        <p class="notice-info">주문삭제대상 검색은 '주문번호'를 기준으로 진행되므로 삭제 대상에서 제외한 공급사의 주문도 삭제될 수 있습니다.</p>
    </div>
    <div class="table-btn">
        <button id="deleteOrderSearchBtn" type="button" class="btn btn-ml btn-black js-order-delete-search">삭제 주문 내역
            생성
        </button>
    </div>
</form>

<form id="frmList" name="frmList" action="" method="get">
    <input type="hidden" name="mode"/>
    <input type="hidden" name="sno" value=""/>
    <input type="hidden" name="cnt" value=""/>

    <div class="table-title gd-help-manual">삭제 대상 내역
        <span class="notice-danger">생성된 삭제 주문 내역은 24시간 내에만 삭제가 가능합니다</span>
    </div>
    <table class="table table-rows">
        <thead>
        <tr>
            <th class="width3p">번호</th>
            <th class="width8p">삭제주문건수</th>
            <th class="width10p">생성일시</th>
            <th class="width8p">생성자</th>
            <th class="width7p">삭제일시</th>
            <th class="width8p">처리자</th>
            <th class="width6p">처리상태</th>
            <th class="width12p">처리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (empty($getData) === false && is_array($getData)) {
            foreach ($getData as $data) {
                // 처리 상태
                if ($data['status'] == 'n') {
                    $status = '-';
                }
                if ($data['status'] == 'g') {
                    $statusFl = true;
                    $dtFl = false;
                    $status = '생성중';
                    if ($data['regEndDt']) {
                        $regDt = $data['regDt'];
                    }
                }
                if ($data['status'] == 'f') {
                    $statusFl = false;
                    $dtFl = false;
                    $status = '생성실패';
                    $regDt = '-';
                }
                if ($data['status'] == 'c') {
                    $status = '생성완료';

                    // 삭제일시
                    $limitDate = date('Y-m-d H:i:s', strtotime($data['regEndDt'] . ' + 1 days'));
                    if ($data['regEndDt'] > $limitDate) { // 24시간 경과 시,
                        $dtFl = false;
                        $delDt = '-';
                    } else {
                        $dtFl = true;
                        $delDt = '~' . $limitDate . ' 까지';
                    }

                    if ($data['regEndDt']) {
                        $regDt = $data['regEndDt'];
                    }
                }
                if ($data['status'] == 'd') {
                    $statusFl = true;
                    $dtFl = false;
                    $status = '삭제예정';
                    $regDt = $data['modDt'];
                    $delDt = $data['modDt'];
                }
                if ($data['status'] == 'df') {
                    $statusFl = false;
                    $dtFl = false;
                    $status = '삭제실패';
                    $regDt = $data['regDt'];
                    if ($data['delDt']) {
                        $delDt = $data['delDt'];
                    }
                }
                if ($data['status'] == 'dc') {
                    $statusFl = false;
                    $dtFl = false;
                    $status = '삭제완료';
                    $regDt = $data['regDt'];
                    if ($data['delDt']) {
                        $delDt = $data['delDt'];
                    }
                }
                if ($data['status'] == 'l') {
                    $statusFl = false;
                    $dtFl = false;
                    $status = '기간만료';
                    $delDt = '-';
                    $regDt = $data['regEndDt'];
                }

                ?>
                <tr class="text-center">
                    <td><?= number_format($page->idx--); ?></td>
                    <?php
                    if ($data['status'] == 'g' || $data['status'] == 'f') {
                        ?>
                        <td>-</td>
                    <?php } else { ?>
                        <td><?= $data['deleteCnt']; ?> 건</td>
                    <?php } ?>
                    <td><?= $regDt; ?></td>
                    <td><?= $data['creator']; ?><br/>(<?= $data['creatorIp']; ?>)</td>
                    <?php
                    if ($data['status'] == 'g' || $data['status'] == 'f') {
                        ?>
                        <td>-</td>
                    <?php } else { ?>
                        <td <?php if ($dtFl == true) { ?>style="color:#fa2828;"<?php } ?>><?= $delDt; ?></td>
                    <?php }
                    if ($data['status'] == 'd' || $data['status'] == 'df' || $data['status'] == 'dc') {
                        ?>
                        <td><?= $data['deleter']; ?><br/>(<?= $data['deleterIp']; ?>)</td>
                    <?php } else { ?>
                        <td>-</td>
                    <?php } ?>
                    <td <?php if ($statusFl == true) { ?>style="color:#fa2828;"<?php } ?>><?= $status; ?></td>
                    <?php
                    if ($data['status'] == 'c') {
                        ?>
                        <td>
                            <button type="button" class="btn btn-white js-order-delete" data-sno="<?= $data['sno'] ?>"
                                    data-cnt="<?= $data['deleteCnt']; ?>" data-dt="<?= $data['regEndDt']; ?>">주문삭제
                            </button>
                            <button type="button" class="btn btn-white btn-icon-excel js-order-excel"
                                    data-sno="<?= $data['sno'] ?>">엑셀다운로드
                            </button>
                        </td>
                    <?php } else { ?>
                        <td>-</td>
                    <?php } ?>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="8" class="no-data">
                    생성된 삭제 주문 내역이 없습니다.
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</form>

<div class="center"><?php echo $page->getPage(); ?></div>

<script type="text/javascript">

    $(document).ready(function () {

        // 삭제 주문 내역 생성
        $('#deleteOrderSearchBtn').click(function () {
            $('#frmOrderDelete input[name="mode"]').val('lapse_order_delete_chk');
            var parameter = $('#frmOrderDelete').serialize();

            $.ajax({
                method: 'post',
                cache: false,
                url: './order_ps.php',
                data: parameter,
                success: function (data) {
                    var arrData = $.parseJSON(data);
                    if (arrData.cnt > 0) {
                        $.each(arrData, function (key, val) {
                            if (val.status == "g") {
                                // 생성중
                                dialog_confirm('삭제 주문 내역이 이미 생성중입니다.<br\>' +
                                    '새로운 삭제 내역을 생성하시겠습니까?<br\>', function (result) {
                                    if (result) {
                                        $('#frmOrderDelete input[name="mode"]').val('');
                                        before_lapse_order_delete(val.sno, val.status);
                                    }
                                }, '경고', {cancelLabel: '취소', 'confirmLabel': '확인'});
                            }
                            if (val.status == "c" || val.status == "d") {
                                // 생성완료
                                dialog_confirm('삭제 예정인 주문 내역이 있습니다.<br\>' +
                                    '새로운 삭제 내역을 생성하시겠습니까?<br\>' +
                                    '(신규 생성 신청 시, 이전에 생성한 내역은 무조건 삭제 됩니다.)', function (result) {
                                    if (result) {
                                        $('#frmOrderDelete input[name="mode"]').val('');
                                        before_lapse_order_delete(val.sno, val.status);
                                    }
                                }, '경고', {cancelLabel: '취소', 'confirmLabel': '확인'});
                            }
                        });
                    } else {
                        lapse_order_delete_list_create();
                    }
                }
            });
        });

        // 주문 삭제
        $('.js-order-delete').click(function () {
            //var no = $(this).attr('data-sno');
            var cnt = $(this).attr('data-cnt');
            $('#frmList input[name="sno"]').val($(this).attr('data-sno'));
            $('#frmList input[name="cnt"]').val($(this).attr('data-cnt'));
            dialog_confirm('<div style="color:#fa2828">삭제 시 삭제한 주문 내역은 완전 삭제되어 복원되지 않으며,<br\>' +
                '삭제 주문 내역에 대한 엑셀다운로드 또한 불가합니다.</div><br\><br\>' +
                '주문내역 ' + cnt + '건을 삭제 하시겠습니까?', function (result) {
                // 보안인증
                if (result) {
                    var params = {
                        mode: 'lapse_order_delete',
                        url: './order_ps.php',
                        data: $('#frmList').serializeArray()
                    };
                    $.get('../share/layer_godo_sms.php', params, function (data) {
                        BootstrapDialog.show({
                            message: $(data),
                            closable: false
                        });
                    });
                }
            }, '주문 내역 삭제', {cancelLabel: '취소', 'confirmLabel': '삭제'});
        });

        // 엑셀다운로드
        $('.js-order-excel').click(function () {
            var sno = $(this).attr('data-sno');
            var params = {
                data: {sno: sno},
            };
            $.ajax({
                url: '../share/layer_order_excel.php',
                type: 'post',
                data: params,
                async: false,
                success: function (data) {
                    var layerForm = data;
                    var configure = {
                        title: '엑셀 다운로드',
                        size: BootstrapDialog.SIZE_NORMAL,
                        message: $(layerForm),
                        closable: true
                    };
                    BootstrapDialog.show(configure);
                }
            });
        });

        // 공급사
        $('.btn-icon-delete').click(function () {
            $('#scmLayer').removeClass('active');
        });
    });

    /**
     * 이전 삭제 주문 내역 생성건 삭제
     */
    function before_lapse_order_delete(sno, status) {
        $.ajax({
            method: 'get',
            cache: false,
            url: './order_ps.php',
            data: "mode=before_lapse_order_delete&sno=" + sno + "&status=" + status + "&reFlag=y",
            success: function () {
                lapse_order_delete_list_create();
            }
        });
    }

    /**
     * 삭제 주문 내역 체크
     */
    function lapse_order_delete_list_create() {
        $('#frmOrderDelete input[name="mode"]').val('lapse_order_delete_search');
        var parameters = $('#frmOrderDelete').serialize().replace(/%5B%5D/, '');

        $.get('./order_ps.php', {
            'mode': 'lapse_order_delete_search',
            'data': parameters
        }, function (result) {
            var resultData = JSON.parse(result);
            if (resultData.cnt > 0) {
                dialog_confirm('삭제 주문 내역은<span style="color:#fa2828"> 생성시간부터 24시간 이내 생성</span>됩니다.<br\>' +
                    '삭제 대상 내역에서 확인 후 삭제해주세요.', function (result) {
                    if (result) {
                        $('#frmOrderDelete input[name="mode"]').val('');
                        $('#frmOrderDelete input[name="reFlag"]').val('n');
                        lapse_order_delete_save(resultData);
                    }
                }, '정보', {cancelLabel: '취소', 'confirmLabel': '확인'});
            } else {
                alert("삭제 가능한 주문내역이 존재하지 않습니다.\n검색 조건을 다시 확인하여 주시기 바랍니다");
                setTimeout(function () {
                    location.reload();
                }, 5000);
            }
            return false;
        });
    }

    /**
     * 삭제 주문 내역 저장
     */
    function lapse_order_delete_save(data) {
        $.post('./order_ps.php', {
            'mode': 'lapse_order_delete_save',
            'data': data
        }, function (res) {
            if (res) {
                location.reload();
            }
        });
    }

    /**
     *  연결하기 Ajax layer
     */
    function layer_register(typeStr, mode, isDisabled) {
        var layerFormID = 'addPresentForm';
        typeStrId = typeStr.substr(0, 1).toUpperCase() + typeStr.substr(1);

        if (typeStr == 'scm') {
            $('input:radio[name=scmFl]:input[value=y]').prop("checked", true);
            var mode = 'simple';
            var layerTitle = '예외 공급사 선택';
        }

        var addParam = {
            "mode": mode,
            "layerFormID": layerFormID,
            "layerTitle": layerTitle,
        };

        if (!_.isUndefined(isDisabled) && isDisabled == true) {
            addParam.disabled = 'disabled';
        }

        layer_add_info(typeStr, addParam);
    }
</script>