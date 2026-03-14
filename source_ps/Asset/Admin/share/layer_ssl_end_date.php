<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2023, NHN COMMERCE Corp.
 */
?>
<style>
    .layer_administrator_add{position:absolute;top:120px;left:40px;border:#FA2828 1px solid;background-color:#FFF}.administrator_add_top{position:relative}.administrator_add_top .btn_administrator_add_close{position:absolute;top:19px;right:19px}.administrator_add_bot{padding:29px 29px 0}.tit{font-size:14px;font-weight:700;color:#333;padding:0 20px}.box_gray{background:#F7F7F7;padding:20px}.setting_step{font-size:13px;color:#666}.setting_step li{margin-top:13px;letter-spacing:-.5px;line-height:1.5em}.setting_step li:first-child{margin-top:0}.txt_infor{padding:0 22px 0 20px;margin:10px 0 30px}.line_red{color:#FA2828;display:inline-block;border-bottom:#FA2828 1px solid;text-decoration:none}.line_red:hover{text-decoration:none;color:#FA2828}.etc_bot_con{margin-bottom:29px}.line_gray_con{color:#FA2828;text-align:center;padding:0 20px}.line_gray_con p{border-top:#DADADA 1px solid;padding-top:20px;letter-spacing:-.5px}.line_gray_con .type2{font-size:13px;text-align:left}.check_form{text-align:center;margin-top:10px}.check_form > *{vertical-align:middle}.check_form input[type="checkbox"]:checked{background-image:url(/admin/gd_share/img/panel/checkbox_red_on.png)}.check_form label{font-size:14px;font-weight:700;margin-left:6px;position:relative;top:1px}.btn_administrator_add{margin-top:15px;overflow:hidden}.btn_administrator_add > *{float:right}.btn_administrator_add > :first-child{float:left}.btn_administrator_add .btn-gray{background-color:#8B8B8B;font-weight:700}.bot_gray_close{width:100%;height:20px;line-height:19px;font-size:11px;color:#7C7C7C;background-color:#E7E7E7;text-align:right;padding-right:15px}.bot_gray_close input[type="checkbox"]{vertical-align:middle}
</style>
<div class="layer_administrator_add" id="popupNotice_ssl_endDate">
    <div class="administrator_add_top">
        <div class="vis_area">
            <img src="/admin/gd_share/img/panel/ssl_pc_top.png" alt="관리자 보안서버 만료일 안내"/>
        </div>
        <a href="#" class="btn_administrator_add_close btn-close"><img src="/admin/gd_share/img/panel/btn_close.png" class="btn-close" onclick="$('#popupNotice_ssl_endDate').hide();" alt="닫기"/></a>
    </div>
    <div class="administrator_add_bot">
        <div class="box_gray">
            <ul class="setting_step">
                <?php foreach ($dataList as $data) { ?>
                    <li <?php if ($data['expirationFl']) { ?> style="color: #fa2828;" <?php } ?>>
                        <?php echo '['.$data['type'].'] ' . $data['sslConfigDomain'] .' : ' . $data['sslConfigEndDate']; ?> </li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="bot_gray_close" style="margin-top:20px;">
        <input type="checkbox" class="check-week-close" title="오늘 하루 열지 않기" onclick="adminPanelCookie('popupNotice-pop_ssl_endDate', 1, this);$('#popupNotice_ssl_endDate').hide();"/>오늘 하루 열지 않기
        <input type="checkbox" class="check-close" onclick="$('#popupNotice_ssl_endDate').hide();" title="닫기">닫기
    </div>
</div>
