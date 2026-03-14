<div class="page-header js-affix">
    <h3><?php echo end($naviMenu->location); ?></h3>
</div>

<div class="table-title gd-help-manual">방문자 검색</div>
<form id="frmSearchStatistics" method="get">
    <input type="hidden" name="searchDevice">
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tbody>
        <tr>
            <th>기간검색</th>
            <td>
                <div class="form-inline">
                    <div class="input-group js-datepicker">
                        <input type="text" class="form-control width-xs" name="searchDate[]" value="<?= $searchDate[0]; ?>"/>
                        <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                    </div>
                    ~
                    <div class="input-group js-datepicker">
                        <input type="text" class="form-control width-xs" name="searchDate[]" value="<?php echo $searchDate[1]; ?>"/>
                        <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                    </div>

                    <div class="btn-group js-dateperiod-statistics" data-toggle="buttons" data-target-name="searchDate[]">
                        <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['1']; ?>">
                            <input type="radio" name="searchPeriod" value="1" <?= $checked['searchPeriod']['1']; ?> >전일
                        </label>
                        <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['7']; ?>">
                            <input type="radio" name="searchPeriod" value="7" <?= $checked['searchPeriod']['7']; ?> >7일
                        </label>
                        <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['15']; ?>">
                            <input type="radio" name="searchPeriod" value="15" <?= $checked['searchPeriod']['15']; ?> >15일
                        </label>
                        <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['30']; ?>">
                            <input type="radio" name="searchPeriod" value="30" <?= $checked['searchPeriod']['30']; ?> >1개월
                        </label>
                        <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['90']; ?>">
                            <input type="radio" name="searchPeriod" value="90" <?= $checked['searchPeriod']['90']; ?> >3개월
                        </label>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th>검색엔진 선택</th>
            <td>
                <select name="searchInflow">
                    <option value="all" <?= $checked['searchInflow']['all']; ?>>전체</option>
                    <option value="naver" <?= $checked['searchInflow']['naver']; ?>>네이버</option>
                    <option value="daum" <?= $checked['searchInflow']['daum']; ?>>다음</option>
                    <option value="google" <?= $checked['searchInflow']['google']; ?>>구글</option>
                    <option value="nate" <?= $checked['searchInflow']['nate']; ?>>네이트</option>
                    <option value="bing" <?= $checked['searchInflow']['bing']; ?>>빙</option>
                    <option value="kakao" <?= $checked['searchInflow']['kakao']; ?>>카카오</option>
                    <option value="etc" <?= $checked['searchInflow']['etc']; ?>>기타</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="table-btn">
        <button type="submit" class="btn btn-lg btn-black">검색</button>
    </div>
</form>

<ul class="nav nav-tabs mgb20">
    <li><a id="visitInflow" class="hand">검색유입 현황</a></li>
    <li class="active"><a id="visitSearchword" class="hand">유입검색어 현황</a></li>
</ul>

<div class="table-action mgt30 mgb0">
    <div class="pull-left pdt5">
        데이터 노출형태 :
        <select name="deviceFl">
            <option value="all" <?= $checked['searchDevice']['all']; ?> >통합</option>
            <option value="pc" <?= $checked['searchDevice']['pc']; ?> >PC쇼핑몰</option>
            <option value="mobile" <?= $checked['searchDevice']['mobile']; ?> >모바일쇼핑몰</option>
        </select>
    </div>
    <div class="pull-right">
        <button type="button" class="btn btn-white btn-icon-excel btn-excel">엑셀 다운로드</button>
    </div>
</div>

<div class="code-html js-excel-data">
    <div id="grid"></div>
</div>

<script>
    <!--
    $(document).ready(function () {
        $('[name="deviceFl"]').change(function (e) {
            $('[name="searchDevice"]').val($('[name="deviceFl"]').val());
            $('#frmSearchStatistics').submit();
        });
        $('#visitInflow').click(function (e) {
            $('#frmSearchStatistics').attr('action', './visit_inflow_v2.php');
            $('#frmSearchStatistics').submit();
        });
        $('#visitSearchword').click(function (e) {
            $('#frmSearchStatistics').attr('action','./visit_search_word_v2.php');
            $('#frmSearchStatistics').submit();
        });
        $('.btn-excel').click(function () {
            grid.setDisplayRowCount('<?=$visitCount?>');
            statistics_excel_download();
            grid.setDisplayRowCount('<?= $rowDisplay; ?>');
        });
    });

    var grid = new tui.Grid({
        el: $('#grid'),
        autoNumbering: true,
        columnFixCount: 1,
        headerHeight: 50,
        displayRowCount: <?= $rowDisplay; ?>,
        minimumColumnWidth: 20,
        columnModelList : [
            {
                "title" : "<b>검색어</b>",
                "columnName" : "searchWord",
                "align" : "center",
                "width" : 100,
                editOption: {
                    type: 'normal'
                }
            },
            {
                "title" : "<b>유입수</b>",
                "columnName" : "searchCount",
                "align" : "center",
                "width" : 100,
                editOption: {
                    type: 'normal'
                },
                "formatter" : function(columnValue){
                    var sValue = String(columnValue) || "0";
                    return sValue.replace(/(\d)(?=(\d{3})+$)/gi, "$1,");
                }
            },
            {
                "title" : "<b>비율</b>",
                "columnName" : "searchPercent",
                "align" : "center",
                "width" : 100,
                editOption: {
                    type: 'normal',
                    "afterContent" : " %"
                }
            }
        ]
    });
    grid.setRowList(<?= $rowList; ?>);
    //-->
</script>
<script type="text/javascript" src="<?=PATH_ADMIN_GD_SHARE?>script/statistics.js"></script>
