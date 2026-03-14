<form id="frmConfig" name="frmConfig" action="naver_script_config_ps.php" method="post"  target ="ifrmProcess" >
    <input type="hidden" name="mode" value="naver_common_inflow_script" />
    <input type="hidden" name="naverCommonInflowScriptVersion">
    <input type="hidden" name="ref" value="<?= $ref ?>"/>
	<div class="page-header js-affix">
		<h3><?php echo end($naviMenu->location);?> </h3>
        <div class="btn-group">
            <input type="button" id="serviceGuide" value="가이드" class="btn btn-white save marketing-guide" data-url="<?=$guideUrl?>" />
            <input type="submit" value="저장" class="btn btn-red save" />
        </div>
	</div>

    <!-- 공통 스크 립트 v2 Start-->
    <div id="v2Set" class="display-none">
        <div class="table-title">
            <?php echo end($naviMenu->location). ' (구 공통유입 스크립트 설정 V2)'?>
        </div>
        <div>
            <table class="table table-cols">
                <colgroup><col class="width-md" /><col/></colgroup>
                <tr>
                    <th>사용 여부</th>
                    <td>
                        <label class="radio-inline" title="네이버 공통스크립트를 사용하시려면 &quot;사용함&quot;을 체크를 하시면 됩니다.!"><input type="radio" name="naverCommonInflowScriptFl" value="y" <?php echo gd_isset($checked['naverCommonInflowScriptFl']['y']);?> />사용함</label>
                        <label class="radio-inline" title="네이버 공통스크립트를 사용하지 않으려면 &quot;사용안함&quot;을 체크를 하시면 됩니다.!"><input type="radio" name="naverCommonInflowScriptFl" value="n" <?php echo gd_isset($checked['naverCommonInflowScriptFl']['n']);?> />사용안함</label>
                    </td>
                </tr>
                <tr class="dependent">
                    <th>네이버 공통 인증키</th>
                    <td>
                        <div class="notice-danger mgb10">
                            한번 입력하신 "네이버공통인증키"는 변경하실 수 없습니다. 최초 입력시 유의하여주시기 바랍니다.<br/>
                            만일 잘못입력하였거나 변경이 필요할 시에는 NHN커머스 고객센터로 문의주시기 바랍니다.
                        </div>
                        <div>
                            <?php if (empty($data['accountId']) === true) {?>
                                <input type="text" name="accountId" value="" class="form-control width-xl" />
                            <?php } else { ?>
                                <span class="eng text-darkblue bold"><?php echo $data['accountId'];?></span>
                                <input type="hidden" name="accountId" value="<?php echo $data['accountId'];?>" class="form-control width-xl" />
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <!-- 공통 스크 립트 v2 End-->

    <!-- 공통 스크 립트 v1 Start-->
    <div id="v1Set" class="display-none">
        <div class="table-title">
            <?php echo end($naviMenu->location);?>
            <span style="float:right;">
            <button type="button" id="latestDiversion" class="btn btn-lg btn-gray"
                    style="float:left; margin-top:-10px; margin-right:8px; color: #000">네이버 공통스크립트 v2 전환하기
            </button>
        </span>
        </div>
        <div>
            <table class="table table-cols">
                <colgroup><col class="width-md" /><col/></colgroup>
                <tr>
                    <th>사용 여부</th>
                    <td>
                        <label class="radio-inline" title="네이버 공통스크립트를 사용하시려면 &quot;사용함&quot;을 체크를 하시면 됩니다.!"><input type="radio" name="naverCommonInflowScriptFl" value="y" <?php echo gd_isset($checked['naverCommonInflowScriptFl']['y']);?> />사용함</label>
                        <label class="radio-inline" title="네이버 공통스크립트를 사용하지 않으려면 &quot;사용안함&quot;을 체크를 하시면 됩니다.!"><input type="radio" name="naverCommonInflowScriptFl" value="n" <?php echo gd_isset($checked['naverCommonInflowScriptFl']['n']);?> />사용안함</label>
                    </td>
                </tr>
                <tr class="dependent">
                    <th>네이버 공통 인증키</th>
                    <td>
                        <div class="notice-danger mgb10">
                            한번 입력하신 "네이버공통인증키"는 변경하실 수 없습니다. 최초 입력시 유의하여주시기 바랍니다.<br/>
                            만일 잘못입력하였거나 변경이 필요할 시에는 NHN커머스 고객센터로 문의주시기 바랍니다.
                        </div>
                        <div>
                            <?php if (empty($data['accountId']) === true) {?>
                                <input type="text" name="accountId" value="" class="form-control width-xl" />
                            <?php } else { ?>
                                <span class="eng text-darkblue bold"><?php echo $data['accountId'];?></span>
                                <input type="hidden" name="accountId" value="<?php echo $data['accountId'];?>" class="form-control width-xl" />
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                <tr class="dependent">
                    <th>White List</th>
                    <td>

                        <div class="notice-danger mgb10">
                            네이버페이 및 마일리지서비스의 유입경로별 혜택은 네이버광고센터에 등록하신 도메인에 한해서만 적용됩니다.<br/>
                            쇼핑몰이 여러개의 도메인으로 운영되는 경우 White List에 해당 도메인들을 추가하여주시기 바랍니다.
                        </div>

                        <div>

                            <table id="addWhiteList">
                                <tr id="addWhiteList0">
                                    <td><input type="text" name="whiteList[0]"  class="form-control width_lLarge"></td>
                                    <td class="center pdl5 bln"><input type="button" class="btn btn-black btn-sm" onclick="add_white_list();" value="추가" /></td>
                                </tr>
                                <?php if(gd_isset($data['whiteList'])) { ?>
                                    <?php foreach($data['whiteList'] as $k => $v) { ?>
                                        <tr id="addWhiteList<?=$k+1?>">
                                            <td class="pdt3"><input type="text" name="whiteList[<?=$k+1?>]"  class="form-control width_lLarge" value="<?=$v?>"></td>
                                            <td class="center pdt3 pdl5 bln"><input type="button" onclick="field_remove('addWhiteList<?=$k+1?>');" class="btn btn-gray btn-sm" value="삭제" /></td>
                                        </tr>
                                    <?php }
                                } ?>
                            </table>

                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <!-- 공통 스크 립트 v1 End-->
</form>

<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/ncds/MarketingGuideModalManager.js"></script>
<script type="text/javascript">
	<!--
    const accountId = '<?=$data['accountId']?>'; // 네어버 공동 인증키
    let naverCommonInflowScriptVersion = '<?=$data['naverCommonInflowScriptVersion']?>'; // 사용 버전 (v1 or v2)
    let naverCommonInflowScriptFl = '<?=$data['naverCommonInflowScriptFl']?>'; // 사용 여부

	$(document).ready(function(){
        // 최초 접속시 전환 페이지 노출 여부
        function setVersion(version) {
            naverCommonInflowScriptVersion = version;
            $("#v1Set").toggleClass('display-none', version === 'v2');
            $("#v2Set").toggleClass('display-none', version === 'v1');
            $('#' + version + 'Set').find('input[name="naverCommonInflowScriptFl"][value=' + naverCommonInflowScriptFl + ']').trigger('click');
        }

        if (naverCommonInflowScriptVersion === 'v2' || !accountId) {
            $("#v1Set").html('');
            setVersion('v2');
        } else {
            setVersion('v1');
        }

        // 스크립트 사용여부 라디오 버튼 이벤트 처리
        $('input[name="naverCommonInflowScriptFl"]').on('change', function() {
            if ($(this).val() === 'y') {
                // 사용 선택 시 항목들 보이기
                $('tr.dependent').show();
            } else {
                // 사용안함 선택 시 항목들 숨기기
                $('tr.dependent').hide();
            }
        });

        // 페이지 로드 시 초기 상태 설정
        $('input[name="naverCommonInflowScriptFl"]:checked').trigger('change');

        // 저장 버튼 클릭
        $("#frmConfig").validate({
            submitHandler: function (form) {
                $('input[name="naverCommonInflowScriptVersion"]').val(naverCommonInflowScriptVersion);
                form.target = 'ifrmProcess';
                form.submit();
                return false;
            }
        });
	});

    // 공통 스크 립트 설정 V2 전환 클릭
    $(function () {
        const v2TransitionNotification = '네이버 공통스크립트를 v2로 전환 하시겠습니까? (전환 시 기존 스크립트는 사용 불가합니다.)' +
            '<br/><span style="color:#fa2828">※ 확인 버튼 클릭 후 상단에 저장 버튼을 통해 저장하셔야 전환이 완료 됩니다.</span>';

        function handleV2Transition(result) {
            if (result) {
                $("#v1Set").html('');
                $("#v2Set").removeClass('display-none')
                    .find('input[name="naverCommonInflowScriptFl"][value="y"]').trigger('click');
                naverCommonInflowScriptVersion = 'v2';
            }
        }

        $('#latestDiversion').click(function () {
            dialog_confirm(v2TransitionNotification, handleV2Transition);
        });
    });

	//인기검색어
	function add_white_list(){
		var fieldID		= 'addWhiteList';
		var fieldNoChk	= $('#'+fieldID).find('tr:last').get(0).id.replace(fieldID,'');
		if (fieldNoChk == '') {
			var fieldNoChk	= 0;
		}
		var fieldNo		= parseInt(fieldNoChk) + 1;
		var addHtml		= '';
		addHtml	+= '<tr id="'+fieldID+fieldNo+'">';
		addHtml	+= '<td class="center pdt3"><input type="text" name="whiteList['+fieldNo+']" value="" class="form-control width_lLarge" /></td>';
		addHtml	+= '<td class="center pdt3 pdl5 bln"><input type="button" class="btn btn-gray btn-sm" onclick="field_remove(\''+fieldID+fieldNo+'\');" value="삭제" /></td>';
		addHtml	+= '</tr>';
		$('#'+fieldID).append(addHtml);
	}

    document.addEventListener('DOMContentLoaded', () => {
        const modal = new GodoMarketing.MarketingGuideModalManager();

        document.querySelectorAll('.marketing-guide').forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                modal.open(url);
            });
        });
    });
	//-->
</script>
