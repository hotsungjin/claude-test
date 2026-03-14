<div class="page-header js-affix">
    <h3><?php echo end($naviMenu->location); ?></h3>
</div>
<div class="manager_div">
    에이스카운터 관리자페이지가 <a href="" class="btn btn-xs btn-black js-ace-manager">새창</a>으로 연결됩니다.<br />
    팝업 차단 설정이 되어있다면 설정 해제 후 아래 새로고침 버튼을 누르거나 화면을 다시 열어주세요.<br />
    <button name="link" id="link" class="btn btn-lg btn-black js-reload">새로고침</button>
</div>
<form name="loginForm" id="loginForm" method="post">
    <input type="hidden" name="godoSno" value="<?=$godoSno?>" class="loginCheck" />
</form>
<style rel="stylesheet" type="text/css">
    .manager_div { text-align:center; height:500px; padding-top:200px; line-height:25px; }
</style>
<script type="text/javascript">
    $(function () {
        // 새로고침
        $('.js-reload').click(function () {
            location.reload();
        });
    });
</script>
