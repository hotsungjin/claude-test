<style type="text/css">
    .blackout {
        width:900px;
        margin:12% auto 0;
        padding:210px 0 90px;
        background:url('<?=PATH_ADMIN_GD_SHARE?>img/icon_error.png') no-repeat center 69px;
        text-align:center;
    }

    .blackout > strong {
        color:#222222;
        font-size:22px;
    }

    .blackout > strong > em {
        font-style:normal;
        color:#EC1C23;
    }

    * {
        font-family:Malgun Gothic, "맑은 고딕", AppleGothic, Dotum, "돋움", sans-serif;
    }

    .blackout p {
        padding:15px 0 0;
        color:#222222;
        font-size:15px;
        line-height:1.5;
        color:#666666;
    }

    /*20160510 윤태건 404error*/
    * {
        margin:0;
        padding:0;
    }

    body {
        -webkit-text-size-adjust:none;
    }

    .c-point {
        color:#EC1C23;
    }

    .submitbtn .skinbtn {
        width:130px;
    }

    .skinbtn {
        display:inline-block;
        *display:inline;
        *zoom:1;
        height:40px;
        padding:0 5px;
        text-align:center;
        vertical-align:top;
        box-sizing:border-box;
        cursor:pointer;
        font-size:14px;
        border:1px solid #B1B1B1;
        background:#FFFFFF;
        color:#777777;
    }

    .btn-m2 {
        height:44px;
    }

    .submitbtn {
        margin:20px 0 0;
    }

    @media (max-width:768px) {
        .blackout {
            width:auto;
            padding:190px 0 90px;
            /*background-size: 97px 84px;*/
        }

        .blackout > strong {
            font-size:20px;
        }

        .blackout p {
            padding:10px 0 0;
            font-size:14px;
            line-height:18px;
            letter-spacing:-0.05em;
        }

        .submitbtn {
            margin:20px 0 0;
        }
    }

    .check {
        padding: 15px 20px 15px 50px;
        background: url('<?=PATH_ADMIN_GD_SHARE?>img/ico_notice2.png') no-repeat 370px 50%;
    }

    .border-top {
        width: 100%;
        padding: 30px 10px 10px 10px;
        border-top: 2px solid #888888;
    }
    .check-font {
        font-size:17px;
    }
</style>
<script type="text/javascript">
    $(function () {
        $('#btnGoBack').click(function (e) {
            window.history.back();
        });
    });
</script>
<div class="blackout">
    <strong>해당 기능은 <em>'최신 버전의 소스가 적용되어 있어야 정상적으로 사용이 가능한 기능'</em> 입니다.</strong>
    <p>
        '적용중인 개발 소스 또는 튜닝된 파일'을 확인하여 주시기 바랍니다.<br>
        적용중인 소스와 최신 원본소스를 비교하여 삭제기능 소스를 추가로 적용해주셔야 합니다.<br><br>
    </p>
    <div class="check"><strong class="check-font">확인해주세요!</strong></div><br>
    <p>
    고도몰5 개발가이드를 준수하지 않고 튜닝(개발소스 수정)한 상태로 사용하여 발생하는 이슈와<br>
    해당 기능을 사용하지 않음으로써 발생하는 법적 이슈에 대한 책임은 상점에게 있습니다.<br><br>
    </p>
    <p class="border-top">
        * 개인정보보호법상 보존기간 경과하거나, 수집 시 이용목적이 달성된 개인정보는 지체 없이 파기해야 합니다.<br>
        주문에 포함된 주문자정보의 경우, "전자상거래 등에서의 소비자보호에 관한 법률"에 따라 5년 간 보관할 수 있습니다.<br>
        해당 법령에 따른 기간 경과 시 즉시 파기하시어 불이익이 없으시도록 운영관리하시기 바랍니다.<br>
        (단, 다른 법령에 의해 보존이 필요한 경우는 예외)<br>
    </p>
</div>
