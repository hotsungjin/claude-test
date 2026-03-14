<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2022, NHN COMMERCE Corp.
 */

?>

<script async defer crossorigin="anonymous" src="https://connect.facebook.net/ko_KR/sdk.js"></script>

<script type="text/javascript">
    $(window).on("load", function() {
        $('.fb-lwi-ads-<?php echo $mode; ?>').attr(
            'data-fbe-redirect-uri',
            window.location.origin + '/marketing/facebook_ads_config.php');
    });

    window.fbAsyncInit = function() {
        FB.init({
            appId: atob('NzM3NzA3NzMwMzYwNzkz'),
            autoLogAppEvents: true,
            xfbml: true,
            version: 'v12.0'
        });
    }
</script>

<div class="fb-lwi-ads-<?php echo $mode; ?>"
     data-fbe-extras="{'business_config':{'business':{'name':'<?php echo $shopName; ?>'}},
                        'setup':{'external_business_id':'<?php echo $shopNo; ?>-godo5',
                                'timezone':'Asia/Seoul',
                                'currency':'KRW',
                                'business_vertical':'ECOMMERCE'},
                        'repeat':false}"
     data-fbe-scopes="manage_business_extension,ads_management,business_management"
     data-fbe-redirect-uri="">
</div>

