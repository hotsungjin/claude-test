<?php
include UserFilePath::adminSkin('head.php');
?>
<style>
    .cache #header .page-header .gnb {
        top: 42px;
    }

    .cache #header .page-header .gnb a {
        color: #494949;
    }
</style>
<body class="<?php echo $adminBodyClass; ?> layout-basic-popup">
<!-- //@formatter:off -->
<div id="container-wrap" class="container-fluid">
    <div id="container" class="row">
        <div id="header" class="col-xs-12">
            <div class="page-header form-inline">
                <h3><?php echo reset($naviMenu->location);?></h3>
            </div>
        </div>

        <div id="content-wrap">
            <div id="menu">
                <?php include($layoutMenu); ?>
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
</body>
