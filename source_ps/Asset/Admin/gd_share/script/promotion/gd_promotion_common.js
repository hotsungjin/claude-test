/**
 * 유효성 검사 함수 호출 및 실패 메시지 alert
 *
 * @param validateFunction
 * @returns {boolean}
 */
function validateAndAlert(validateFunction) {
    const { result, message } = validateFunction();
    if (!result) {
        alert(message);
        return false;
    }
    return true;
}

/**
 * 쿠폰 사용 가능 상품범위 설정 - 입력값 유효성 검증
 *
 * @returns {{result: boolean, message: string}}
 */
function validateCouponApplyTypeValue() {
    let result = true;
    let message = '';
    let couponApplyType = $('[name="couponApplyProductType"]:checked').val();

    const conditions = [
        { type: 'provider', selector: 'input[name="couponApplyProvider[]"]', message: '쿠폰 사용 가능한 특정 공급사를 선택해주세요.' },
        { type: 'category', selector: 'input[name="couponApplyCategory[]"]', message: '쿠폰 사용 가능한 특정 카테고리를 선택해주세요.' },
        { type: 'brand', selector: 'input[name="couponApplyBrand[]"]', message: '쿠폰 사용 가능한 특정 브랜드를 선택해주세요.' },
        { type: 'goods', selector: 'input[name="couponApplyGoods[]"]', message: '쿠폰 사용 가능한 특정 상품을 선택해주세요.' }
    ];

    for (const condition of conditions) {
        if (couponApplyType === condition.type && !$(condition.selector).length) {
            result = false;
            message = condition.message;
            break;
        }
    }

    return { result: result, message: message };
}

/**
 * 쿠폰 사용 제외 설정 - 입력값 유효성 검증
 */
function validateCouponExceptTypeValue() {
    let result = true;
    let message = '';

    const conditions = [
        { type: 'provider', name: 'couponExceptProviderType', selector: 'input[name="couponExceptProvider[]"]', message: '쿠폰 사용 불가능한 특정 공급사를 선택해주세요.' },
        { type: 'category', name: 'couponExceptCategoryType', selector: 'input[name="couponExceptCategory[]"]', message: '쿠폰 사용 불가능한 특정 카테고리를 선택해주세요.' },
        { type: 'brand', name: 'couponExceptBrandType', selector: 'input[name="couponExceptBrand[]"]', message: '쿠폰 사용 불가능한 특정 브랜드를 선택해주세요.' },
        { type: 'goods', name: 'couponExceptGoodsType', selector: 'input[name="couponExceptGoods[]"]', message: '쿠폰 사용 불가능한 특정 상품을 선택해주세요.' },
    ];

    for (const condition of conditions) {
        const checkboxSelector = `input:checkbox[name="${condition.name}"]`;
        if ($(checkboxSelector).prop("checked") === true && !$(condition.selector).length) {
            result = false;
            message = condition.message;
            break;
        }
    }

    return { result: result, message: message };
}
