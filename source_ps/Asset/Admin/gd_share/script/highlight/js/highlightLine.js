/**
 *  highlight 처리 후 tr 요소에 대해 class 부여
 */
function addLineNumberClass(lines) {
    var lineNumber = '',
        classNm = '';
    $(lines).each(function() {
        lineNumber = $(this).find('td').first().data('line-number');
        classNm = 'hljs-ln-line line-number-' + lineNumber;
        $(this).addClass(classNm);
    });
}
