<?php if ($menu == 'main_info') { ?>
    <link rel="stylesheet" href="https://fe-sdk.cdn-nhncommerce.com/@ncds/ui-admin/1.0/main.min.css" />
    <script type="text/javascript" src="https://fe-sdk.cdn-nhncommerce.com/@ncds/ui-admin/1.0/main.min.js"></script>
    <style>
        body {
            font-family: Malgun Gothic,"맑은 고딕",AppleGothic,Dotum,"돋움","Helvetica Neue", Helvetica, Arial, sans-serif !important;
            font-size: 12px !important;
            line-height: 1.42857143 !important;
            color: #333333 !important;
        }
        #content .col-xs-12 {
            padding: 0;
        }
        .page-header {
            margin-bottom: 0;
        }
        .ncua-modal-backdrop {
            z-index: 3000;
        }
    </style>

    <div style="padding: 0 30px 0 30px;">
        <div class="page-header js-affix">
            <h3><?php echo end($naviMenu->location);?> <small></small></h3>
        </div>
    </div>

    <iframe name="marketingBooster" src="<?=$pageUrl?>" frameborder="0" marginwidth="0" marginheight="0" width="100%" style="height:2500px; display: block;"></iframe>
<?php } else { ?>
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location);?> <small></small></h3>
        <div class="btn-group">
            <a href="https://marketing.godo.co.kr/board/list.gd" target="_blank" class="btn btn-white" style="background: url(<?=PATH_ADMIN_GD_SHARE?>img/marketing_notice.png) no-repeat 5% 70%; padding-left:35px;">마케팅 공지사항</a>
        </div>
    </div>

    <iframe name='introduce' src='/share/iframe_godo_page.php?menu=<?=$menu?>' frameborder='0' marginwidth='0' marginheight='0' width='100%' height='2100'></iframe>
<?php } ?>

<div class="ncua-modal-backdrop" style="display: none;">
    <div class="ncua-modal ncua-modal--xl">
        <header class="ncua-modal__header ncua-modal__header--left ncua-modal__header--close-button">
            <div class="ncua-modal__title"><div class="ncua-modal__title-text">&nbsp;</div></div>
            <button class="ncua-button-close-x ncua-button-close-x--sm ncua-button-close-x--light ncua-modal__close-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="none">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </header>
        <div class="ncua-modal__content">
            <iframe src="" style="width: 100%; height: calc(-212px + 100vh); border: none"></iframe>
        </div>
    </div>
</div>
