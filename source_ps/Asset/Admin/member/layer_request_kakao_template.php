<form id="frmRequestKakaoTemplate" action="./kakao_alrim_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="mode" value="requestTemplate" />
    <input type="hidden" name="templateCode" value="<?=$templateCode?>" />
    <table class="table table-cols no-title-line">
        <tr>
            <td>
                <div>(선택) 기타 참고사항을 입력해주세요.</div>
                <div class="pull-right form-inline pdb5">
                    <input type="text" name="requestStringCount" id="requestStringCount" value="0" readonly="readonly" class="form-control width-3xs">
                    / 500 Bytes
                </div>
                <div style="text-align:justify;" class="width100p">
                    <textarea name="requestContent" rows="5" class="requestContent form-control width100p" data-close="true" placeholder="입력하신 내용은 템플릿 검수 요청 시 참고사항으로 전달됩니다."></textarea>
                </div>
            </td>
        </tr>
    </table>
    <div class="text-center">
        <button type="submit" class="btn btn-lg btn btn-red">검수요청</button>
        <button type="button" class="btn btn-lg btn-gray js-layer-close">취소</button>
    </div>
</form>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        $('#frmRequestKakaoTemplate').validate({
            submitHandler: function (form) {
                var params = $(form).serializeArray();
                $.ajax({
                    data: params,
                    type: 'POST',
                    url: "./kakao_alrim_ps.php",
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == 'success') {
                            alert('검수요청에 성공하였습니다.');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else if (data.result == 'delete') {
                            BootstrapDialog.alert({
                                type: BootstrapDialog.TYPE_INFO,
                                message: '해당 템플릿은 같은 카카오톡 채널을 사용중인 다른 상점에서 삭제 처리된 템플릿입니다.',
                                closable: false,
                                callback: function (result) {
                                    if (result) {
                                        location.reload();
                                    }
                                }
                            });
                        } else {
                            alert('검수요청에 실패하였습니다. 잠시후 다시 시도해주세요.');
                        }
                        return false;
                    },
                    error: function(data) {
                        alert('검수요청에 실패하였습니다. 잠시후 다시 시도해주세요.');
                        return false;
                    }
                });
            },
            rules: {},
            messages: {}
        });

        /**
         * SMS 내용 길이 체크
         */
        function setContentsLength(contentsNm, countId) {
            var textarea = $('textarea[name=' + contentsNm + ']');
            var contentsText = textarea.val();
            var textLength = contentsText.length;
            if (textLength > 500) {
                if (textarea.data('close')) {
                    textarea.data('close', false);
                    BootstrapDialog.show({
                        message: '최대 500 Byte 까지 가능합니다.',
                        onhidden: function () {
                            var output = contentsText.slice(0, 500);
                            textarea.val(output);
                            textarea.data('close', true);
                            setSendLength();
                        }
                    });
                }
                $('#' + countId).css("color", "#FF0000");
            } else {
                $('#' + countId).css("color", "");
            }
            $('#' + countId).val(textLength);
        }

        function setSendLength() {
            setContentsLength('requestContent', 'requestStringCount');
        }

        // 글자수 체크
        $('textarea[name=requestContent]').keyup(setSendLength).change(setSendLength);

        setSendLength();
    });
    //-->
</script>
