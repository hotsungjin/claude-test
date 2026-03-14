<style>
    table.table-memo td {
        font-size: 12px
    }

    textarea {
        width: 85% !important;
        height: 90px !important;
    }

    button[name=contentsButton] {
        margin-left: 10px;
        height: 90px;
        width: 13%;
    }
</style>
<div class="page-header js-affix">
    <h3><?= end($naviMenu->location); ?>
    </h3>
    <?php ?>
</div>
<div class="table-title gd-help-manual"><?=($isShow == 'y') ? '플러스리뷰 게시글 보기' : '상세보기'; ?></div>
<form name="frmDelete" action="plus_review_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="mode" value="delete">
    <input type="hidden" name="sno" value="<?= $req['sno'] ?>">
    <input type="hidden" name="popupMode" value="<?= $req['popupMode'] ?>">
    <input type="hidden" name="queryString" value="<?= $queryString?>">
    <?php if($isShow != 'y') { ?><input type="hidden" name="goodsNo" value="<?= $data['goodsNo'] ?>"><?php } ?>
</form>

<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>

    <?php if($listType == 'board') { ?>
    <tr>
        <th>작성자</th>
        <td class="width20p"><?= $data['writer'] ?> </td>
        <th class="width-sm">아이피</th>
        <td colspan="3"><?= $data['writerIp'] ?></td>
    </tr>

    <tr>
        <th>작성일</th>
        <td>
            <?=$data['regDt']?>
        </td>
        <th>추천</th>
        <td>
            <?=$data['recommend']?>
        </td>
    </tr>
    <tr>
        <th>승인</th>
        <td colspan="3">
            <?= $data['applyFl'] == 'y' ? '승인':'미승인'?>
        </td>
    </tr>
    <tr>
        <th>상품정보</th>
        <td colspan="3">
            <table id="selectGoodsTbl" class="table table-cols mgt15">
                <colgroup>
                    <col class="width10p"/>
                    <col class="width30p"/>
                    <col class="width60p"/>
                </colgroup>
                <tbody>
                <tr>
                    <td>
                        <a href="<?= URI_HOME ?>goods/goods_view.php?goodsNo=<?= $data['goodsNo'] ?>" target="_blank">
                            <img src="<?= $data['goodsImageSrc']; ?>" width="100"></a></td>
                    <td>
                        <div onclick="goods_register_popup('<?= $data['goodsNo']; ?>' <?php if (gd_is_provider()) {
                            echo ",'1'";
                        } ?>);" class="hand">
                            <b><?= $data['goodsNm'] ?></b></div>
                    </td>
                    <td align="left"><?= gd_currency_display($data['goodsPrice']) ?></td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <?php if ($config['pointFl'] == 'y') { ?>
        <tr>
            <th>평가</th>
            <td colspan="3">
                <span class="rating"><span style="width:<?= $data['goodsPt'] * 20 ?>%;">별</span></span>
            </td>
        </tr>
    <?php } ?>
    <tr>
        <th>파일첨부</th>
        <td colspan="3">
            <?php
            if ($data['uploadedFile']) {
                ?>
                <ul style="padding:0px">
                    <?php foreach ($data['uploadedFile'] as $val) { ?>
                        <li class="mgb5">
                            <a href="/board/download.php?type=plusReview&sno=<?= $data['sno'] ?>&fid=<?= $val['fid'] ?>">
                            <img src="<?=$val['thumSrc']?>" width="100" >
                          <?= $val['uploadFileNm'] ?></a></li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                -
            <?php } ?>
        </td>
    </tr>
        <tr>
            <th>추가정보/옵션</th>
            <td>
                <?php foreach($data['addFormData'] as $key=>$val) {?>
                <div><?=$key?> : <?=$val?></div>
                <?php }?>
                <span style="color:#329cff">
                <?php foreach($data['option'] as $val) {?>
                <?=$val['name']?> : <?=$val['value']?><br>
                <?php }?>
                </span>
            </td>
        </tr>
    <tr>
        <th>내용</th>
        <td colspan="3" style="margin:0px"><?= $data['viewContents'] ?></td>
    </tr>
    <?php if ($config['memoFl'] == 'y' && $isShow == 'y') { ?>
        <tr>
            <th>댓글</th>
            <td colspan="3">

                <table class="table table-rows table-fixed">
                    <tr>
                        <td colspan="4">
                            <form name="frmMemoWrite" action="plus_review_ps.php" method="post" target="ifrmProcess">
                                <div>
                                    <input type="hidden" name="articleSno" value="<?= $req['sno'] ?>">
                                    <input type="hidden" name="mode" value="addMemo">
                                    <textarea class="form-control pull-left" name="memo" required></textarea>
                                    <button type="submit" name="contentsButton" class="btn btn-white pull-left">저장</button>
                                    <div class="clear-both"></div>
                                </div>
                            </form>
                        </td>
                    </tr>
                </table>
                <div class="mgb10">총 댓글 수 : <?= $data['memoCnt'] ?></div>
                <?php if ($data['memoCnt'] > 0) { ?>
                    <table class="table table-memo">
                        <colgroup>
                            <col class="width-sm">
                            <col>
                            <col class="width-sm">
                            <col class="width-sm">
                        </colgroup>
                        <?php foreach ($data['memoList'] as $val) {
                                if ($val['isShow'] == 'y') {
                            ?>
                            <tr>
                                <td align="center">
                                    <?= $val['writer'] ?>
                                </td>
                                <td>
                                    <form name="frmMemo<?= $val['sno'] ?>" action="plus_review_ps.php" method="post" target="ifrmProcess">
                                        <input type="hidden" name="mode" value="modifyMemo">
                                        <input type="hidden" name="articleSno" value="<?= $req['sno'] ?>">
                                        <input type="hidden" name="sno" value="<?= $val['sno'] ?>">
                                        <div class="js-text-memo">
                                            <?= $val['viewMemo'] ?>
                                        </div>
                                        <div class="display-none js-textarea-modify-memo">
                                            <textarea class="form-control pull-left" name="memo" required><?= ($val['memo']) ?></textarea>
                                            <button type="submit" name=contentsButton class="btn btn-white pull-left">저장</button>
                                            <div class="clear-both"></div>
                                        </div>
                                    </form>
                                </td>
                                <td valign="top">
                                    <?= $val['regDt'] ?>
                                </td>
                                <td valign="top">
                                    <button class="btn btn-white js-btn-memo-modify">수정</button>
                                    <button class="btn btn-white js-btn-memo-delete" title="확인" data-message="정말로 삭제하시겠습니까?">삭제</button>
                                </td>
                            </tr>
                        <?php }
                        }?>
                    </table>
                    <?php
                } else { ?>
                    <div class="center">댓글이 없습니다.</div>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
    <?php } else { ?>
        <tr>
            <th>작성자</th>
            <td><?= $data['writerNm']; ?></td>
            <th class="width-md">등록시간</th>
            <td><?= $data['regDt']; ?></td>
        </tr>
        <tr>
            <th>내용</th>
            <td colspan="3" style="margin:0px"><?= $data['memo']; ?></td>
        </tr>
    <?php } ?>
    <?php if($isShow == 'n') { ?>
        <tr>
            <th>신고내용</th>
            <td colspan="3">
                <table class="table table-cols table-fixed">
                    <colgroup>
                        <col class="width-sm">
                        <col>
                    </colgroup>
                    <tr>
                        <th>신고자</th>
                        <td><?=$reportData['memId']?></td>
                    </tr>
                    <tr>
                        <th>신고일</th>
                        <td><?=$reportData['regDt']?></td>
                    </tr>
                    <tr>
                        <th>신고사유</th>
                        <td><textarea disabled><?=gd_htmlspecialchars_stripslashes($reportData['reportMemo'])?></textarea></td>
                    </tr>
                    <tr>
                        <th>개인정보수집동의</th>
                        <td><?=($reportData['checkCollectAgreeFl'] == 'y') ? '동의함' : '동의안함'?></td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
</table>
<div class="text-center">
    <?php if($req['popupMode'] != 'yes') { // CRM 팝업모드가 아닐 경우 ?>
    <button type="button" class="btn btn-white btn-lg js-btn-list">목록</button>
    <?php } ?>
    <?php if($data['channel']!='naverpay' && $isShow == 'y') {?>
    <button type="button" class="btn btn-white btn-lg js-btn-modify">수정</button>
    <?php }?>
    <button type="button" class="btn btn-white btn-lg js-btn-remove">삭제</button>
    <?php if($isShow == 'n'){?>
    <button type="button" class="btn btn-white btn-lg js-btn-report">신고해제</button>
    <?php }?>
</div>
<script>
    function getUrlVars(paramKey) {
        if (typeof paramKey == 'undefined') {
            paramKey = '';
        }
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        if (window.location.href.indexOf('?') < 0) {
            return '';
        }
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            key = hash[0];
            val = hash[1];
            if (paramKey != '') {
                if (key == paramKey) {
                    return val;
                }
            }

            if (key == 'sno' || key == 'mode') {
                continue;
            }

            vars.push(hashes[i]);
        }

        if (paramKey != '') {
            return '';
        }

        return vars.join('&');
    }

    $(document).ready(function () {
        $('.js-btn-list').bind('click', function () {
            location.href = "plus_review_list.php?" + getUrlVars();
        })

        $('.js-btn-modify').bind('click', function () {
            location.href = "plus_review_register.php?mode=modify&sno=<?=$req['sno']?>&popupMode=<?=$req['popupMode']?>&queryString=<?=urlencode($queryString)?>";
        })

        $('.js-btn-remove').bind('click', function () {
            frmDelete.submit();
        })

        $('.js-btn-memo-modify').bind('click', function () {
            var textarea = $(this).closest('tr').find('.js-textarea-modify-memo');
            var memoText = $(this).closest('tr').find('.js-text-memo');
            if (textarea.is(':visible')) {
                memoText.show();
                textarea.hide();
            }
            else {
                memoText.hide();
                textarea.show();
            }
        })

        $('.js-btn-modify').bind('click', function () {
            $(this).closest('form').find('input[name=mode]').val('modifyMemo');
            $(this).closest('form').submit();
        })

        $('.js-btn-memo-delete').bind('click', function () {
            var form = $(this).closest('tr').find('form');
            var mode = $(this).closest('tr').find('input[name=mode]');
            BootstrapDialog.confirm({
                title: $(this).attr('title'),
                message: $(this).data('message'),
                callback: function (result) {
                    if (result) {
                        mode.val('deleteMemo');
                        form.submit();
                    }
                }
            });
        })

        $('.js-btn-report').bind('click', function() {
            var sno = '<?= $req['sno'] ?>';
            var listType = '<?= $listType ?>';
            dialog_confirm('선택한 게시물을 신고해제 하시겠습니까?\r\n이 경우 기존 신고내역은 확인 불가합니다', function (result) {
                if (result) {
                    $.ajax({
                        method: "POST",
                        url: "../board/article_ps.php",
                        data: {mode: 'report', sno: sno, bdId: 'plusReview', listType: listType},
                        dataType: 'text'
                    }).success(function (data) {
                        $('body').append(data);
                        location.href = '../board/plus_review_list.php?isShow=n&listType='+listType;
                    }).error(function (e) {
                        alert(e.responseText);
                    });
                }
            });
        });

    })
</script>
