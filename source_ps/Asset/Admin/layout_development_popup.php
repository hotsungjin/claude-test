<?php
/**
 * 기본레이아웃
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 * @author    Shin Donggyu <artherot@godo.co.kr>
 */

use Framework\Utility\GodoUtils;

include UserFilePath::adminSkin('head.php');
?>
<style type="text/css">
    .page-header h3 label {
        background: #444444 none;
        border-color: #fa2828;
        padding: 7px;
        border-radius: 10px;
        position: relative;
    }

    .page-header h3 a {
        text-shadow: none;
        color: #FFFFFF;
        font-weight: bold;
        text-decoration: none;
    }

    .page-header h3 a .badge {
        background-color: #FA2828;
        color: #000000;
        font-size: 12px;
        font-weight: bold;
    }

    .devs-version .label-version {
        background-color: #444444;
        color: #ffffff;
        margin-left: 15px;
        padding: 15px;
        border-radius: 15px;
    }

    .devs-version .summary * {
        display: inline-block;
        font-weight: bold;
    }

    .devs-version .summary span {
        background: #000000 none;
        border-color: #fa2828;
        margin-left: 10px;
        padding: 10px;
        text-shadow: none;
        color: #FFFFFF;
    }

    .devs-version .summary p {
        vertical-align: middle;
        margin-left: 20px;
    }

    .devs-version ul li {
        margin-left: 20px;
        margin-top: 5px;
        margin-bottom: 5px;
        list-style-type: square;
    }
</style>
<body class="<?php echo $adminBodyClass; ?> layout-basic-popup">
<!-- //@formatter:off -->
    <div id="container-wrap" class="container-fluid">
        <div id="container" class="row">
            <div id="header" class="col-xs-12">
                <div class="page-header form-inline">
                    <h3><?php echo reset($naviMenu->location); if (GodoUtils::isVersionControl()) { ?>
                        <label class="label"><a href="./version_control.php" id="btn-update-check">솔루션 버전 안내</a></label>
                    <?php } ?></h3>
                    <div class="gnb">
                        <ul class="list-inline">
                            <li>
                                <a href="<?php echo URI_HOME ?>main/index.php?__gd5_work_preview=always" target="_blank" class="btn btn-sm">작업쇼핑몰</a>
                            </li>
                            <li>
                                <a href="<?php echo URI_ADMIN ?>base/index.php?__gd5_work_preview=always" target="_blank" class="btn btn-sm">작업관리자</a>
                            </li>
                            <li>
                                <a href="<?php echo URI_HOME ?>main/index.php?__gd5_work_preview=clear" target="_blank" class="btn btn-sm">운영쇼핑몰</a>
                            </li>
                            <li class="no-bar">
                                <a href="<?php echo URI_ADMIN ?>base/index.php?__gd5_work_preview=clear" target="_blank" class="btn btn-sm">운영관리자</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div id="panel_popupPanel"></div>
                <div id="panel_popupCos_modal"></div>
                <div id="panel_popupCos_layer"></div>
            </div>

            <div id="content-wrap">
                <div id="menu">
                    <?php include($layoutMenu); ?>
                    <ul class="list-unstyled menu-banner">
                        <li class="development-ftp"><a href="https://www.nhn-commerce.com/mygodo/main.gd" target="_blank"><img src="<?=PATH_ADMIN_GD_SHARE?>img/development/banner(170x70)_ftp.png" alt="커머스(상세관리)"></a></li>
                        <li><a href="https://www.nhn-commerce.com/echost/power/customize-apply.gd" target="_blank"><img src="<?=PATH_ADMIN_GD_SHARE?>img/development/banner(170x70)_service.png" alt="튜닝센터"></a></li>
                        <li><a href="https://www.nhn-commerce.com/echost/power/add/convenience/openapi-intro.gd" target="_blank"><img src="<?=PATH_ADMIN_GD_SHARE?>img/development/banner(170x70)_api.png" alt="외부연동 (Open API)"></a></li>
                    </ul>
                </div>
                <div id="content" class="row">
                    <div class="col-xs-12">
                        <?php include($layoutContent); ?>
                        <?php include($layoutHelp); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="gnbTopAnchor">
        <a href="#top"><img src="<?=PATH_ADMIN_GD_SHARE?>img/btn_gnb_top.png"></a>
    </div>

    <iframe name="ifrmProcess" src="/blank.php" width="100%" height="200" class="<?=App::isDevelopment() === true ? 'display-block' : 'display-none'?>"></iframe>
<!-- //@formatter:on -->
<script type="text/javascript">
    $(function () {
        adminPanelApiAjax('<?php echo $manualData['menuCode'];?>', '<?php echo $manualData['menuKey'];?>', '<?php echo $manualData['menuFile'];?>');
        <?= gd_isset($menuAccessAuth); ?>

        // 부운영자 FTP/DB 접속시 이용불가
        $('.development-ftp').click(function() {
            var isSuper = '<?= gd_isset($isSuper) ?>';
            if (isSuper !== 'y') {
                BootstrapDialog.show({
                    title: '안내',
                    message: 'FTP, DB 정보는 쇼핑몰 최고 운영자에게 한하여 확인 가능하오니,<br/>자세한 사항은 쇼핑몰 최고 운영자에게 문의하시길 바랍니다.',
                    closable: true
                });
                return false;
            }
        });

        // 탑버튼 클릭
        $(document).on("click", "a[href=#top]", function (e) {
            $('html body').animate({scrollTop: 0}, 'fast');
        });

        // 스크롤 최하단시 탑아이콘 출력 (실제 컨텐츠 $('#content > .col-xs-12').height())
        $(window).scroll(function () {
            if ($(window).height() < $(document).height()) {
                if ($(window).scrollTop() >= 1) {
                    $("#gnbTopAnchor").slideDown(150);
                } else {
                    $("#gnbTopAnchor").slideUp(100);
                }
            }
        });
    });
</script>
</body>
</html>
