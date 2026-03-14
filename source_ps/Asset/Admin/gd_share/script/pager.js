define(
    'PSModule.pager', function (){
        var pager = {
            paging_bar_id: '',
            drawPaging: function (total, list_size, paging_size, page) {
                block = Math.ceil(page/paging_size);

                block_start = ((block-1) * paging_size) + 1;
                block_end = block_start + paging_size -1;
                total_page = Math.ceil(total/list_size);
                total_block = total_page / paging_size;

                if (block_end>total_page) {
                    block_end = total_page;
                }

                var html = '';
                if (page>0 && page<=total_page) {
                    if (block > 1) {
                        html += pager.drawPagingPart(1, "paging_first", "", "<<");
                        html += pager.drawPagingPart(((block_start-paging_size)), "paging_pre", "", "<");
                    }
                    for (var i = block_start; i <= block_end; i++) {
                        html += pager.drawPagingPart(i, "", "", i, page);

                    }
                    if (block < total_block) {
                        html += pager.drawPagingPart(block_end + 1, "paging_next", "", ">");
                        html += pager.drawPagingPart(total_page, "paging_last", "", ">>");
                    }
                }
                return html;
            },
            drawPagingPart: function (no, id, class_, inner, page) {
                var class_ = 'ps_plusshop_page';
                var active_class = (no==page) ? 'active' : '';
                return '<li class=' + active_class + '><span id=\''+ id + '\' class=\''+ class_ + ' ' + active_class + '\' page=' + no + ' >' + inner + '</span></li>';
            },
            goToPage: function (no) {
                alert('goToPage function 정의 후, 사용 바랍니다.');
            }
        }


        return (function (pager) {
            return {
                drawPaging: pager.drawPaging,
                setPagingBarId: function(name) { pager.paging_bar_id = name; },
                load: function (id, total, list_size, paging_size, current_page, goToPage) {
                    $('.ps_plusshop_list').html(pager.drawPaging(total, list_size, paging_size, current_page));
                    $('.ps_plusshop_page').bind('click', function () {
                        goToPage($(this).attr('page'));
                    });
                },
                goToPage: function (page) { pager.goToPage(page);}
            }
        })(pager);
    }
);
