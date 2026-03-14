<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 * 레이어 회원검색
 */
 if (empty($search['keyword']) !== false) $search['keyword'] = '';
?>
<table class="table table-cols no-title-line">
    <colgroup>
        <col class="width-sm"/>
        <col/>
    </colgroup>
    <tr>
        <th>검색어</th>
        <td>
            <div class="form-inline">
                <?= gd_select_box('key', 'key', $combineSearch, null, $search['key']); ?>
                <?= gd_select_box('searchKind', 'searchKind', $searchKindASelectBox, null, gd_isset($search['searchKind']), null, null, 'form-control '); ?>
                <input type="text" id="keyword" name="keyword" value="<?php echo $search['keyword']; ?>" class="form-control width-xl" data-uri="<?php echo URI_ADMIN; ?>"/>
                <input type="hidden" name="mallSno" value="<?php echo $search['mallSno']; ?>" />
                <input type="hidden" name="loadPageType" value="<?php echo $search['loadPageType']; ?>" />
                <input type="button" value="검색" class="btn btn-hf btn-black" id="btnMemberSearch"/>
            </div>
        </td>
    </tr>
</table>

<?php if ($orderWritePageFl) { ?>
    <div class="mgb15">
        <span class="notice-info">
            휴면 전환 예정일이 7일 이내인 회원의 배경은 회색으로 표시됩니다.
        </span>
    </div>
<?php } ?>

<table class="table table-rows">
    <thead>
    <tr>
        <th class="width5p"></th>
        <th class="width5p">번호</th>
        <th class="width10p">상점 구분</th>
        <th class="width10p">아이디/닉네임</th>
        <th class="width10p">이름</th>
        <th class="width20p">등급</th>
        <th class="width20p">이메일</th>
        <th class="width10p">전화번호</th>
        <th class="width20p">휴대폰번호</th>
        <?php if ($orderWritePageFl) { ?>
            <th class="width20p">휴면예정일</th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php
    if (is_array($data) && gd_isset($data)) {
        $i = 0;
        $listHtml = [];
        foreach ($data as $key => $val) {
            $sleepAlertStyle = ($orderWritePageFl && $val['sleepAlertFl']) ? 'style="background-color: #eee;"' : '';
            $sleepAlertData = $orderWritePageFl ? '" data-sleepAlertFl = "' . $val['sleepAlertFl'] : '';
            $listHtml[] = '<tr id="tbl_member_' . $val['memNo'] . '" class="center" ' . $sleepAlertStyle . '>';
            $listHtml[] = '<td class="center"><input type="radio" id="layer_member_' . $val['sno'] . '" name="layer_member" value="' . $val['memNo'] . '" data-memId="'.$val['memId'].'" data-memNm="'.$val['memNm'].'" data-email="'.$val['email'].'" data-phone="'.$val['phone'].'" data-cellPhone="'.$val['cellPhone'].'" data-zonecode="'.$val['zonecode'].'" data-zipcode="'.$val['zipcode'].'" data-address="'.$val['address'].'" data-addressSub="'.$val['addressSub'].'" data-deliveryFree="'.$memberGroupInfo[$val['groupSno']]['deliveryFree']. $sleepAlertData .'" /></td>';
            $listHtml[] = '<td class="center">' . $page->idx-- . '</td>';
            $listHtml[] = '<td class="center"><span class="flag flag-16 flag-' . $gGlobal['mallList'][$val['mallSno']]['domainFl'] . '"></span>' . $gGlobal['mallList'][$val['mallSno']]['mallName'] . '</th>';
            $listHtml[] = '<td class="font-eng">' . $val['memId'];
            if ($val['snsTypeFl'] == 'payco' || $val['snsTypeFl'] == 'facebook' || $val['snsTypeFl'] == 'naver' || $val['snsTypeFl'] == 'kakao' || $val['snsTypeFl'] == 'wonder') {
                $listHtml[] = gd_get_third_party_icon_web_path($val['snsTypeFl']);
            }
            $listHtml[] = '<br/><span class="notice-ref notice-sm">' . $val['nickNm'] . '</span></td>';
            $listHtml[] = '<td class="center">' . $val['memNm'] . '</td>';
            $listHtml[] = '<td class="center">' . $groups[$val['groupSno']] . '</td>';
            $listHtml[] = '<td>' . $val['email'] . '</td>';
            $listHtml[] = '<td>' . $val['phone'] . '</td>';
            $listHtml[] = '<td>' . $val['cellPhone'] . '</td>';
            if ($orderWritePageFl) {
                $listHtml[] = '<td>' . $val['sleepScheduleDt'] . '</td>';
            }
            $listHtml[] = '</tr>';
            $i++;
        }
        echo implode('', $listHtml);
    } elseif ($isSkip) {
        echo '<tr><td class="no-data" colspan="9">검색기능을 이용해주세요.</td></tr>';
    } else {
        echo '<tr><td class="no-data" colspan="9">검색 결과가 없습니다.</td></tr>';
    }
    ?>

    </tbody>
</table>
<div class="center"><?= $page->getPage('#'); ?></div>
<div class="text-center">
    <button type="button" class="btn btn-black" id="btnConfirm">확인</button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $body = $('.modal');
        $body.off('click').on('click', '#btnConfirm', function () {
            var memNo = $(':radio:checked', '.modal-content').val();
            if($("input[name='loadPageType']").val() === "order_write"){
                if (_.isUndefined(memNo)) {
                    alert('선택된 회원번호가 없습니다.');
                } else {
                    const self = $(':radio:checked', '.modal-content');
                    if (self.attr('data-sleepAlertFl')) {
                        BootstrapDialog.show({
                            title: '안내',
                            message: '<p>휴면전환 7일 전인 회원입니다. 수기주문건 마일리지 적립 전 휴면전환 될 경우 마일리지가 지급되지 않습니다. 수기주문 회원정보로 선택하시겠습니까?</p>',
                            buttons: [{
                                label: '취소',
                                action: function(dialog) {
                                    dialog.close();
                                }
                            }, {
                                label: '확인',
                                cssClass: 'btn-black',
                                action: function(dialog) {
                                    setMemberInfo(memNo, self);
                                    dialog.close();
                                }
                            }]
                        });
                    } else {
                        setMemberInfo(memNo, self);
                    }
                }

                return false;
            }

            if (window.location.href.indexOf('crm') > 0) {
                if (_.isUndefined(memNo)) {
                    layer_close();
                } else {
                    location.href = location.pathname + '?popupMode=yes&memNo=' + memNo;
                }
            }
            else{
                if (_.isUndefined(memNo)) {
                    alert('선택된 회원번호가 없습니다.');
                } else {
                    member_crm(memNo);
                    layer_close();
                }
            }
        });

        //검색어 변경 될 때 placeHolder 교체 및 검색 종류 변환 및 검색 종류 변환
        var searchKeyword = $('.modal input[name=keyword]');
        var searchKind = $('.modal #searchKind');
        var arrSearchKey = ['memId', 'memNm', 'nickNm', 'email', 'cellPhone', 'phone', 'ceo', 'fax'];
        var strSearchKey = $('.modal #key').val();

        setKeywordPlaceholder(searchKeyword, searchKind, strSearchKey, arrSearchKey);

        $body.on('change', '#searchKind', function () {
            setKeywordPlaceholder(searchKeyword, searchKind, $('.modal #key').val(), arrSearchKey);
        });

        $body.on('change', '#key', function () {
            setKeywordPlaceholder(searchKeyword, searchKind, $(this).val(), arrSearchKey);
        });
    });

    /**
     * 선택 회원정보 적용
     * @param memNo
     * @param self
     */
    function setMemberInfo (memNo, self) {
        //주문자, 수령자 정보 초기화
        resetOrderInfoCommon();
        //회원 정보 초기화
        resetMemberInfoCommon();
        //회원 장바구니 추가의 기능으로 추가된 상품(쿠폰사용이 되어있는) 의 쿠키 삭제
        resetMemberCartSnoCookie();

        //수기주문 등록페이지 에서의 회원선택
        let data = {
            memNo: memNo,
            memId : self.attr('data-memId'),
            memNm: self.attr('data-memNm'),
            email: self.attr('data-email'),
            phone: self.attr('data-phone'),
            cellPhone: self.attr('data-cellPhone'),
            zonecode: self.attr('data-zonecode'),
            zipcode: self.attr('data-zipcode'),
            address: self.attr('data-address'),
            addressSub: self.attr('data-addressSub'),
            deliveryFree: self.attr('data-deliveryFree')
        }
        insert_address_info(data);

        //주문건 memNo 변경, 수기주문 쿠폰 사용정보 초기화, 회원 정보 가져오기, 결제정보 초기화
        set_member_info(memNo);

        set_goods('y');

        layer_close();
    }
</script>
