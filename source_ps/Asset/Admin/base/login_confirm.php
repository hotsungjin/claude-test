<!-- //@formatter:off -->
<form id="frmLogin" name="frmLogin" action="login_ps.php" method="post">
    <input type="hidden" name="code" value="<?php echo $code; ?>"/>
    <input type="hidden" name="state" value="<?php echo $state; ?>"/>
    <input type="hidden" name="error" value="<?php echo $error; ?>"/>
</form>
<!-- //@formatter:on -->

<script type="text/javascript">
    $(document).ready(function () {
        var params = {
            'code': $('input[name=code]').val(),
            'state': $('input[name=state]').val(),
            'error': $('input[name=error]').val(),
        };
        $.get('../oauth/commerce/token.php', params, function (data) {
            if (data.includes("auth_login")) {
                BootstrapDialog.show({
                    title: '로그인 보안 인증',
                    message: $(data),
                    closable: false
                });
            } else if (data.includes("쇼핑몰 제공용량")) {
                BootstrapDialog.show({
                    message: $(data),
                    closable: false
                });
            } else {
                document.write(data);
            }
        });
    });
</script>
