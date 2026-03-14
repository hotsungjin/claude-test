<div class="well well-sm">
	선택하신 <?=count($data['statusCheck'])?>건의 주문을 <?=$title?> 승인처리 하시겠습니까?
    <br/>클레임 처리 진행 중 또는 완료된 주문의 '주문상태'는 변경 되지 않습니다.
</div>

<form method="post" name="frmUserHandleAccept" id="frmUserHandleAccept" action="../order/order_ps.php" target="ifrmProcess">
	<input type="hidden" name="mode" value="user_handle_accept" />
    <input type="hidden" name="statusMode" value="<?=$statusMode?>" />
    <input type="hidden" name="duplication" value="true" />
	<?php foreach ($statusCheck as $val) { ?>
	<input type="hidden" name="statusCheck[]" value="<?=$val?>">
	<?php } ?>
	<div class="table-title gd-help-manual"><?=$title?>승인 메모 등록</div>
	<textarea name="adminHandleReason" class="form-control"></textarea>

	<div class="text-center mgt10">
		<button type="button" class="btn btn-lg btn-white js-layer-close">취소</button>
		<button type="submit" class="btn btn-lg btn-black">확인</button>
	</div>
</form>

<script type="text/javascript">
<!--
$(document).ready(function(){
    // 폼 체크 후 전송
    $('#frmUserHandleAccept').validate({
        dialog: false,
        rules: {
            adminHandleReason: 'required',
            duplication: { maxlength:4 }
        },
        messages: {
            adminHandleReason: '<?=$title?>승인 메모를 등록해주세요.',
            duplication: '처리중 입니다. 잠시만 기다려 주세요.'
        },
        submitHandler: function(form) {
            $('input[name=duplication]').val('false');
            form.target = 'ifrmProcess';
            form.submit();
        }
    });
});
//-->
</script>
