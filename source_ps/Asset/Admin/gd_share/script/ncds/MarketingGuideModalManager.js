/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */
(function(global) {
    global.GodoMarketing = global.GodoMarketing || {};

    global.GodoMarketing.MarketingGuideModalManager = class MarketingGuideModalManager {
        constructor() {
            this.injectModalHTML();
            this.modal = document.querySelector('.ncua-modal-backdrop');
            this.closeBtn = this.modal.querySelector('.ncua-modal__close-button');
            this.iframe = document.getElementById('modalIframe');

            this.bindClose();
        }

        injectModalHTML() {
            const modalHTML = `
                <div class="ncua-modal-backdrop" style="display: none;z-index: 3000;">
                    <div class="ncua-modal ncua-modal--xl">
                        <header class="ncua-modal__header ncua-modal__header--left ncua-modal__header--close-button">
                            <div class="ncua-modal__title"><div class="ncua-modal__title-text">&nbsp;</div></div>
                            <button class="ncua-button-close-x ncua-button-close-x--sm ncua-button-close-x--light ncua-modal__close-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="none">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6 6 18M6 6l12 12"></path>
                                </svg>
                            </button>
                        </header>
                        <div class="ncua-modal__content">
                            <iframe id="modalIframe" src="" style="width: 100%; height: calc(-212px + 100vh); border: none"></iframe>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        // ncds css에 따른 고도몰 css 우선순위 재적용
        applyGlobalStyle() {
            const style = document.createElement('style');
            style.textContent = `
            body {
                font-family: Malgun Gothic, "맑은 고딕", AppleGothic, Dotum, "돋움", "Helvetica Neue", Helvetica, Arial, sans-serif !important;
                font-size: 12px !important;
                line-height: 1.42857143 !important;
                color: #333333 !important;
            }
        `;
            document.head.appendChild(style);
        }

        bindClose() {
            this.closeBtn.addEventListener('click', () => this.close());
        }

        async open(url) {
            // @todo 추후 ncds 전역 반영시 아래 두줄 관련 내용 삭제 필요
            this.applyGlobalStyle();
            await this.loadDependencies();

            // CSP 이슈 방지에 따라 부모창이 http 프로토콜인 경우 iframe에서 url 로드하지 않음
            const parentUrl = new URL(window.parent.location.href);

            if(parentUrl.protocol === 'http:'){
                // http인 경우 새 탭으로 노출처리
                window.open(url, '_blank');
            }
            else if(parentUrl.protocol === 'https:'){
                this.iframe.src = url;
                this.modal.style.display = '';
                document.body.style.overflow = 'hidden';
            }
        }

        close() {
            this.modal.style.display = 'none';
            this.iframe.src = '';
            document.body.style.overflow = '';
        }

        loadDependencies() {
            const promises = [];

            // 1. CSS 로드
            const cssHref = "https://fe-sdk.cdn-nhncommerce.com/@ncds/ui-admin/1.0/main.min.css";
            if (!document.querySelector(`link[href="${cssHref}"]`)) {
                promises.push(new Promise((resolve) => {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = cssHref;
                    link.onload = resolve;
                    link.onerror = resolve;
                    document.head.appendChild(link);
                }));
            }

            // 2. JS 로드
            const scriptSrc = "https://fe-sdk.cdn-nhncommerce.com/@ncds/ui-admin/1.0/main.min.js";
            if (!document.querySelector(`script[src="${scriptSrc}"]`)) {
                promises.push(new Promise((resolve) => {
                    const script = document.createElement('script');
                    script.src = scriptSrc;
                    script.onload = resolve;
                    script.onerror = resolve;
                    document.head.appendChild(script);
                }));
            }

            return Promise.all(promises);
        }
    };
})(window);
