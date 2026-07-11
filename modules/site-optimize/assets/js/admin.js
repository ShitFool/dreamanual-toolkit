/**
 * 站点优化模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    var i18n, ajaxUrl, nonce;

    function showToast(message, type) {
        // 动态创建 toast 容器（此页面模板未内嵌容器）
        var container = document.querySelector('.drea-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'drea-toast-container';
            document.body.appendChild(container);
        }
        DreaToast.show(message, type, container.id || undefined);
        if (!container.id) container.id = 'drea-so-toast-container';
    }

    function saveSettings() {
        var btn = document.getElementById('drea-so-save-btn');
        if (!btn) return;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_so_save_settings');
        formData.append('nonce', nonce);

        var toggles = document.querySelectorAll('.drea-toggle__input[data-key]');
        toggles.forEach(function (cb) {
            formData.append(cb.dataset.key, cb.checked ? 1 : 0);
        });

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.saved, 'success');
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.failed, 'error');
                }
                btn.disabled = false;
            })
            .catch(function () {
                showToast(i18n.error, 'error');
                btn.disabled = false;
            });
    }

    function init() {
        if (typeof dreaSo === 'undefined') return;
        i18n    = dreaSo.i18n;
        ajaxUrl = dreaSo.ajaxUrl;
        nonce   = dreaSo.nonce;

        var saveBtn = document.getElementById('drea-so-save-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveSettings);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
