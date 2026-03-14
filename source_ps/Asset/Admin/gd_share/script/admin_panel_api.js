/**
 * 관리자 패널 호출
 * @param string menuCode 현재 폴더명
 * @param string menuKey 현재 화일 키
 * @param string menuFile 현재 화일 명
 */
function adminPanelApiAjax(menuCode, menuKey, menuFile) {
    var params = {
        menuCode: menuCode,
        menuKey: menuKey,
        menuFile: menuFile,
    };
    $.ajax({
        method: 'POST',
        cache: false,
        url: '/share/admin_panel_api.php',
        data: params,
        async: true,
        dataType: 'json',
        success: function (data) {
            if (data === null || typeof data == 'undefined') {
                return;
            }
            // 쇼핑몰 관리 비밀번호 변경안내 팝업 노출 시 화면 뒤에 팝업이 노출되지 않게 처리
            if ($('.bootstrap-dialog-title').text().trim() === "쇼핑몰 관리 비밀번호 변경안내") {
                return;
            }
            $.each(data, function (index, value) {
                switch (index) {
                    case 'banner' :
                        $.each(value, function (idx, val) {
                            var panel = $("#panel_" + index + '_' + val.panelCode);
                            if (panel) {
                                panel.css({
                                    width: val.panelData.width + 'px',
                                    height: val.panelData.height + 'px'
                                });
                                panel.append(val.panelData.posts[0].postBodyText);
                            }
                        });
                        break;

                    case 'board' :
                        $.each(value, function (idx, val) {
                            if (val.panelData.indexOf('Client error') === -1) {
                                var panel = $("#panel_" + index + '_' + val.panelCode);
                                if (!panel.length) return; // panel이 없으면 종료

                                switch (val.panelCode) {
                                    case 'noticeAPI':
                                    case 'patchAPI':
                                        var html = '';
                                        $.each(val.panelData, function (idx, item) {
                                            html += `
                                                <li>
                                                    <a href="${item.url}" target="_blank">
                                                        ${item.title} 
                                                        ${item.isNewPost ? `<img src="${val.gdSharePath}img/icon_new.png" alt="NEW" class="img-fix">` : ''}
                                                    </a>
                                                    <span>${item.publishedAt}</span>
                                                </li>`;
                                        });
                                        panel.html(html);
                                        break;

                                    default:
                                        panel.html(add_new_mark(val.gdSharePath, val.panelData));
                                        break;
                                }
                            }
                        });
                        break;

                    case 'link' :
                        $.each(value, function (idx, val) {
                            var html = '';
                            var panel = $("#panel_" + index + '_' + val.panelCode);
                            if (panel) {
                                html = '<a href="' + val.panelData + '" target="_blank" class="btn btn-sm btn-link">더보기</a>';
                                panel.html(html);
                            }
                        });
                        break;

                    case 'customer' :
                        $.each(value, function (idx, val) {
                            var html = '';
                            var panel = $("#panel_" + index + '_' + val.panelCode);
                            if (panel) {
                                html = '<span class="call">' + val.panelData.tel + '</span>' +
                                    '<table>' +
                                    '<tr><td>평일</td><td>' + val.panelData.text1 + '</td></tr>' +
                                    '<tr><td></td><td>' + val.panelData.text2 + '</td></tr>' +
                                    '</table>';
                                panel.html(html);
                            }
                        });
                        break;

                    case 'popup' :
                        $.each(value, function (idx, val) {
                            if ($("#panel_" + val.panelCode)) {
                                $("#panel_" + val.panelCode).html(val.panelData);
                            }
                        });

                        // 팝업 열지 않기
                        $.each($.cookie(), function (idx, val) {
                            var prefix = idx.split('_');
                            if (prefix[0] == 'adminPanel') {
                                var popupId = idx.replace('adminPanel_', '');
                                if ($('#' + popupId)) {
                                    $('#' + popupId).hide();
                                }
                            }
                        });
                        break;

                    case 'popupCos' :
                        $.each(value, function (idx, val) {
                            var panelCode = val.panelCode;
                            var panelData = val.panelData;
                            var panelSelector = "#panel_" + index + "_" + panelCode;
                            var panel = $(panelSelector);

                            if (panel.length && panelData.posts.length > 0) {
                                // 패널 style sheet 로드
                                panel.html('<style>' + panelPopupStyles(panelSelector, panelCode, panelData) + '</style>');

                                // 팝업 생성
                                addAdminPopupPanel(panel, panelCode, panelData);
                            }
                        });

                        // 팝업 열지 않기
                        $.each($.cookie(), function (idx, val) {
                            var prefix = idx.split('_');
                            if (prefix[0] == 'adminPanel') {
                                var popupId = idx.replace('adminPanel_', '');
                                if ($('#' + popupId)) {
                                    $('#' + popupId).hide();
                                }
                            }
                        });
                        break;

                    case 'kakaoAlrim' :
                        $.each(value, function (idx, val) {
                            var panelCode = val.panelCode;
                            var panelData = val.panelData;
                            var panelSelector = "#panel_" + index + "_" + panelCode;
                            var panel = $(panelSelector);

                            if (panel.length) {
                                // 패널 style sheet 로드
                                panel.html('<style>' + panelPopupStyles(panelSelector, panelCode, panelData) + '</style>');

                                // 팝업 생성
                                addAdminPopupPanel(panel, panelCode, panelData);
                            }
                        });

                        // 팝업 열지 않기
                        $.each($.cookie(), function (idx, val) {
                            var prefix = idx.split('_');
                            if (prefix[0] == 'adminPanel') {
                                var popupId = idx.replace('adminPanel_', '');
                                if ($('#' + popupId)) {
                                    $('#' + popupId).hide();
                                }
                            }
                        });
                        break;
                }
            });
        },
        error: function (data, text) {
            //alert('error : ' + text);
        }
    });

    if (params['menuCode'] == 'base' && params['menuFile'] == 'index') {
        $.ajax({
            method: 'POST',
            cache: false,
            url: '/share/layer_super_admin_commerce_login.php',
            data: params,
            async: true,
            dataType: 'json',
            success: function (data) {
                if (data === null || typeof data == 'undefined') {
                    return;
                }
                $("#panel_login_notice_panel").append(data.result);
            },
            error: function (e) {
                console.error('Failed to get layer_super_admin_commerce_login : ', e);
            }
        });
        $.ajax({
            method: 'POST',
            cache: false,
            url: '/share/layer_super_admin_security.php',
            data: params,
            async: true,
            dataType: 'text',
            success: function (data) {
                if (data === null || typeof data == 'undefined') {
                    return;
                }
                $("#panel_noticePanel").append(data);
            },
            error: function (data, text) {
                //alert('error : ' + text);
            }
        });

        if (!$.cookie('adminPanel_popup-notice-inspect-result')) {
            $.ajax({
                method: 'GET',
                cache: false,
                url: '/share/layer_inspect_result.php',
                data: params,
                async: true,
                dataType: 'text',
                success: function (data) {
                    if (data === null || typeof data == 'undefined') {
                        return;
                    }
                    $("#panel_inspect_notice_panel").append(data);
                }
            });
        }

        if (!$.cookie('adminPanel_popupNotice-pop_ssl_endDate')) {
            $.ajax({
                method: 'GET',
                cache: false,
                url: '/share/layer_ssl_end_date.php',
                async: true,
                dataType: 'text',
                success: function (data) {
                    if (data === null) {
                        return;
                    }
                    $("#panel_ssl_noticePanel").append(data);
                }
            });
        }
    }
}

/**
 * 관리자 패널 팝업창 Cookie 생성
 * @param string name 팝업창 이름 (코드_창종류)
 * @param int expireDay 쿠키 기간
 * @param object elemnt elemnt
 * @return
 */
function adminPanelCookie(name, expireDay, elemnt) {
    if (expireDay == '') {
        expireDay = 7;
    }

    var cookieName = 'adminPanel_' + name.replace('#', '');

    $.cookie(cookieName, 'true', {expires: expireDay, path: '/'});
    setTimeout("$(name).hide()");

    return;
}

function edu_panel(panel_data) {
    var result = [];
    var li_by_data = $(panel_data).find('li:lt(2)');
    try {
        result.push('<div class="edunews-items">');
        result.push('<ul>');
        $.each(li_by_data, function (idx, item) {
            var a_by_item = $(item).find('a');
            var img_by_item = $(item).find('img');
            result.push('<li><a href="' + a_by_item.attr('href') + '" target="_blank">');
            result.push('<div class="edunews-head">');
            if (img_by_item.length > 0) {
                result.push('<img src="' + img_by_item.attr('src') + '">');
            }
            result.push('</div>');
            result.push('<div class="edunews-body">');
            result.push('<div class="edunews-title">' + $(item).find('.edunews-title').text() + '</div>');
            result.push('<div class="edunews-date">' + $(item).find('.edunews-date').text() + '</div>');
            result.push('</div>');
            result.push('</a></li>');
        });
        result.push('</ul>');
        result.push('</div>');
    } catch (e) {
        return panel_data;
    }
    return result.join('');
}

function add_new_mark(gd_share_path, panel_data) {
    var li_by_mark = $(panel_data);
    var start = moment().subtract(7, 'days').format('YYYY-MM-DD');
    var end = moment().add(1, 'days').format('YYYY-MM-DD');
    $.each(li_by_mark, function (idx, item) {
        $.each($(item).find('li'), function (idx2, item2) {
            var date = $(item2).find('span').text();
            //console.log(start, end, date);
            var a_tag = $(item2).find('a');
            if (a_tag.text().length > 30) {
                a_tag.text(a_tag.text().str_cut(50));
            }
            if (moment(date).isBetween(start, end)) {
                a_tag.append(' <img src="' + gd_share_path + 'img/icon_new.png" alert="NEW" class="img-fix">');
            }
        });
    });
    return li_by_mark.html();
}

function panelPopupStyles(panel, panelCode, panelData) {
    var actionBoxHeight = 52; // 52은 action박스 고정 높이
    var zIndex = panelCode === 'modal' ? 1003 : 1000; // panelCode에 따라 z-index 설정

    // 위치 조합 확인 및 값 설정
    var isTopLeft = panelData.positionTop && panelData.positionLeft;
    var isBottomRight = !isTopLeft && panelData.positionBottom && panelData.positionRight;

    // transform 값 설정
    var transformX = isTopLeft ? removeUnderscore(panelData.positionLeft) :
        isBottomRight ? removeUnderscore(panelData.positionRight) : '0';

    var transformY = isTopLeft ? removeUnderscore(panelData.positionTop) :
        isBottomRight ? removeUnderscore(panelData.positionBottom) : '0';

    // transform 문자열 생성 (isTopLeft일 때만 -transformX)
    var transformStr = isTopLeft ?
        `translate(-${transformX}, -${transformY})` :
        `translate(${transformX}, ${transformY})`;

    var styles = `
        ${panel} .popup_container {
            -webkit-font-smoothing: antialiased;
            position: fixed;
            display: inline-block;
            width: ${panelData.width}px;
            height: ${panelData.height + actionBoxHeight}px;
            
            ${isTopLeft ? `top: ${removeUnderscore(panelData.positionTop)};` : ''}
            ${isTopLeft ? `left: ${removeUnderscore(panelData.positionLeft)};` : ''}
            ${isBottomRight ? `bottom: ${removeUnderscore(panelData.positionBottom)};` : ''}
            ${isBottomRight ? `right: ${removeUnderscore(panelData.positionRight)};` : ''}

            transform: ${transformStr};
            background-color: rgb(255, 255, 255);
            border-radius: 12px;
            box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 8px;
            overflow: hidden;
            z-index: ${zIndex};
            font-family: Noto Sans KR, Roboto, sans-serif;
        }
        
        ${panel} .popup_slider {
            height: ${panelData.height}px;
        }
        
        ${panel} .slick-slide {
            height: ${panelData.height}px;
        }
        
        ${panel} .slick-slide a{
           outline: none;
        }
        
        ${panel} .slick-slide p{
            font-size: 16px;
            line-height: 1.5;
            margin: 0;
        }

        ${panel} .controls {
            display: flex;
            padding: 0 16px;
            height: ${actionBoxHeight}px;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #0C111D;
        }
        
        ${panel} .gray-text {
            color: #98A2B3;
        }
        
        ${panel} .slide-pagination
        {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
        }
        
        ${panel} .slide-count {
            display: flex;
            gap: 2px;
        }
        
        ${panel} .slide-pagination-arrow {
            display: inline-block;
            cursor: pointer;
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
        }

        ${panel} .actionBox {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        ${panel} input[type="checkbox"] {
            left: 18px;
            top: 2px;
        }

        ${panel} .button {
            cursor: pointer;
            width: 53px;
            height: 36px;
            border: 1px solid #D0D5DD;
            background: #FFF;
            border-radius: 8px;
            font-weight: 700;
        }
        
        ${panel} label {
            color: #344054;
            font-weight: 500;
        }
    `;

    return styles;
}

function addAdminPopupPanel(panel, panelCode, panelData) {
    if (panelData.additionalInput) {
        Object.assign(panelData, parseAdditionalInput(panelData.additionalInput));
    }

    var modalContainer = $('<div class="popup_container"></div>');

    // slider 추가
    var sliderContainer = addAdminPopupPanelSlides(panel, panelData);

    // control 추가
    var controls = addAdminPopupPanelControls(panel, panelCode, panelData);

    modalContainer.append(sliderContainer);
    modalContainer.append(controls);
    panel.append(modalContainer);

    if (panelData.isDimmed) {
        panel.append($('<div class="modal-background"></div>').css({
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                background: 'rgba(0, 0, 0, 0.5)',
                zIndex: '1002',
                display: 'block'
            })
        );
    }

    // Slick 슬라이더 초기화
    sliderContainer.slick({
        infinite: panelData.infinite,
        autoplay: panelData.autoplay,
        autoplaySpeed: panelData.delay,
        prevArrow: panel.find('.slide-pagination-prev'),
        nextArrow: panel.find('.slide-pagination-next')
    });
}

function addAdminPopupPanelSlides(panel, panelData) {
    var sliderContainer = $('<div class="popup_slider"></div>');

    panelData.posts.forEach(function (post) {
        sliderContainer.append(`
            <div class='popup'>
                ${post.postBodyText}
            </div>
        `);
    });

    sliderContainer.on('init reInit afterChange', function (event, slick, currentSlide) {
        var current = (currentSlide || 0) + 1;
        var totalSlides = slick.slideCount;
        var slideInfo = `${current} <span class="gray-text"> / ${totalSlides}</span>`;
        panel.find('.slide-count').html(slideInfo);
    });

    return sliderContainer;
}

function addAdminPopupPanelControls(panel, panelCode, panelData) {
    var controls = $(`
        <div class="controls">
            <div class="slide-pagination">
                <div class="slide-pagination-arrow slide-pagination-prev">
                    <img src="/admin/gd_share/img/btn_chevron_left.png">
                </div>
                <div class="slide-count"></div>
                <div class="slide-pagination-arrow slide-pagination-next">
                    <img src="/admin/gd_share/img/btn_chevron_right.png">
                </div>
            </div>
            <div class="actionBox">
                <div class="checkbox">
                    <input type="checkbox" id="${panelCode}_notShowAgain">
                    <label for="${panelCode}_notShowAgain">${panelData.cookieAliveDay}일간 다시 보지 않기</label>
                </div>
                <button class="button">닫기</button>
            </div>
        </div>
    `);

    // 닫기 버튼에 이벤트 리스너 추가
    controls.find('.button').on('click', function () {
        closeAdminPopupPanel(panel, panelCode, panelData);
    });

    controls.find(`#${panelCode}_notShowAgain, label[for="${panelCode}_notShowAgain"]`).on('click', function () {
        closeAdminPopupPanel(panel, panelCode, panelData);
    });

    return controls;
}

function closeAdminPopupPanel(panel, panelCode, panelData) {
    if (panelData.isDimmed === 'true') {
        $('.modal-background').hide();
    }

    // 체크박스 확인
    var checkboxSelector = panel.selector+` #${panelCode}_notShowAgain`;

    if ($(checkboxSelector).is(':checked')) {
        adminPanelCookie(panel.selector, Number(panelData.cookieAliveDay), this);
    }

    $(panel.selector).hide();
}

function parseAdditionalInput(input) {
    var params = {};

    if (input) {
        input.split('&').forEach(function (part) {
            var item = part.split('=');
            params[item[0]] = item[1];
        });
    }

    // 자동 롤링 설정
    params.autoplay = params.autoplay === 'true';
    // 딤드 처리 여부
    params.isDimmed = params.isDimmed === 'true';
    // 딜레이 설정 (밀리초 단위로 변환)
    params.delay = parseInt(params.delay) || 5000;
    // 무한 롤링 설정
    params.infinite = params.infinite === 'true';

    return params;
}

// 위치값 정제 함수 (ex. 50_% => 50 , %)
function removeUnderscore(value) {
    if (typeof value === 'string') {
        return value.replace("_", "");
    }
    return '0';
}
