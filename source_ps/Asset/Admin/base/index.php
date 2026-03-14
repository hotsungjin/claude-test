<script type="text/javascript">
    var isVisibleMemo = '<?=$setting['memo']['self']['isVisible']?>';
    var viewAuth = '<?=$setting['memo']['self']['viewAuth']?>';
    var main_setting = <?= json_encode($setting);?>;
</script>
<link type="text/css" href="<?= PATH_ADMIN_GD_SHARE ?>script/slider/slick/slick.css" rel="stylesheet"/>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/slider/slick/slick.js"></script>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/main.js" charset="utf-8"></script>

<!-- 쇼핑몰 서비스 정보 내용 시작 -->
<?php include($serviceInfo); ?>
<!-- 쇼핑몰 서비스 정보 내용 끝 -->

<div class="main-layout main-content reform">
    <div class="content-main">
        <div class="row">
            <div class="col-xs-8">
                <!-- 주문 관리 내용 시작 -->
                <?php include($serviceOrder); ?>
                <!-- 주문 관리 내용 끝 -->
            </div>
            <div class="col-xs-4">
                <!-- 문의 / 답변 관리 내용 시작 -->
                <?php include($serviceBoard); ?>
                <!-- 문의 / 답변 관리 내용 끝 -->
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6">
                <!-- 공지사항 / godo:플러스샵 -->
                <div class="main-section">
                    <div class="table-title">
                        <span class="">공지사항</span>
                        <div class="pull-right">
                            <div id="panel_link_noticeLink" class="pull-right more-view"></div>
                        </div>
                    </div>
                    <div class="board-list-newest content list-unstyled reform" id="panel_board_noticeAPI">
                    </div>
                </div>
            </div>
            <div class="col-xs-6">
                <!-- 패치&업그레이드 -->
                <div class="main-section">
                    <div class="table-title">
                        <span class="">업데이트</span>
                        <div class="pull-right">
                            <div id="panel_link_patchLink" class="pull-right more-view"></div>
                        </div>
                    </div>
                    <ol class="board-list-newest content list-unstyled reform" id="panel_board_patchAPI">
                    </ol>
                </div>
            </div>
        </div>

        <!-- 관리자 메인 배너 시작 -->
        <div class="main-section">
            <div id="panel_banner_mainTop" class="content-main-banner"></div>
        </div>
        <!-- 관리자 메인 배너 끝 -->

        <!-- 중앙 현황판 내용 시작 -->
        <?php include($servicePresentation); ?>
        <!-- 중앙 현황판 내용 끝 -->

        <!-- 주요 일정 내용 시작 -->
        <?php include($serviceCalendar); ?>
        <!-- 주요 일정 내용 끝 -->

        <!-- 관리자 메인 하단 배너 시작 -->
        <div id="panel_banner_mainBottom" class="main-section"></div>
        <!-- 관리자 메인 하단 배너 끝 -->
    </div>

    <div class="content-sub">
        <!-- 관리자 메모 내용 시작 -->
        <?php include($serviceMemo); ?>
        <!-- 관리자 메모 내용 끝 -->

        <!-- 관리자 메인 게시판 시작 -->
        <?php if (gd_is_provider() === false) { ?>
            <!-- 관리자 메인 중간 배너 시작 -->
            <div id="panel_banner_mainMiddle" class="main-section"></div>
            <!-- 관리자 메인 중간 배너 끝 -->
        <?php } ?>

        <!-- 더 좋아지는 고도몰 -->
        <ul class="nav nav-tabs mgt35 display-none">
            <li role="presentation" class="active">
                <a href="#better" aria-controls="panel-better" role="tab" data-toggle="tab">더 좋아지는 고도몰</a>
            </li>
        </ul>
        <div class="tab-content sub-sector sub-sector-no-margin display-none">
            <div role="tabpanel" class="tab-pane fade in active" id="better">
                <div id="panel_link_betterLink" class="pull-right more-view"></div>
                <div id="panel_board_betterAPI"></div>
            </div>
        </div>

        <!-- 운영 필수 서비스 현황 내용 시작 -->
        <?php include($serviceState); ?>
        <!-- 운영 필수 서비스 현황 내용 끝 -->

        <!-- 그로우 상점 1:1문의 시작 -->
        <?php include($growData);?>
        <!-- 그로우 상점 1:1문의 끝 -->

        <?php if(gd_is_provider() === false) { ?>
        <div class="main-section">
            <div class="table-title">
                고객센터
                <a class="btn btn-white btn-sm <?php if ($manager['isSuper'] == 'y') { ?>btn_grow<?php } else {?>btn_err<?php }?>" style="margin-left:5px;">1:1 문의</a>
                <div id="panel_link_customerLink" class="pull-right"></div>
            </div>
            <!-- gorw 상점 유무에 따라 고객센터 번호 호출 -->
            <?php if ($growSetData['shopType'] == 'grow'){ ?>
                <div class="cs-box">
                <span class="call"><p><?= $growSetData['telNo']; ?></p></span>
                    <table><tbody>
                        <tr><td>평일</td><td>09:00 ~ 18:00 (점심 12:30~13:30)</td></tr>
                        <tr><td></td><td>(토/일/공휴일 휴무)</td></tr>
                        </tbody></table>
                </div>
            <?php } else { ?>
                <div id="panel_customer_customerAPI" class="cs-box"></div>
            <?php }  ?>
        </div>
        <?php } ?>
        <!-- 관리자 메인 게시판 끝 -->

    </div>

    <!-- 관리자 메인 측면 배너 시작 -->
    <?php if (gd_is_provider() === false) { ?>
        <div id="panel_banner_mainSide" class="sub-sector banner-float">
        </div>
    <?php } ?>
    <!-- 관리자 메인 측면 배너 끝 -->
</div>
