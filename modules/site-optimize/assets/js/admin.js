/**
 * 站点优化模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    var i18n, ajaxUrl, nonce;

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'info';
        var icons = { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' };
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:40px;right:20px;z-index:999999;display:flex;align-items:center;gap:10px;padding:12px 16px;background:#fff;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);border-left:4px solid #2271b1;font-size:13px;line-height:1.5;opacity:0;transform:translateX(100px);transition:all .3s ease;pointer-events:none;' + (type === 'success' ? 'border-left-color:#008a20;background:#edfaef;' : type === 'error' ? 'border-left-color:#d63638;background:#fcf0f1;' : '');
        toast.innerHTML = '<span style="flex-shrink:0;">' + (icons[type] || icons.info) + '</span>' +
            '<span>' + escapeHtml(message) + '</span>';
        document.body.appendChild(toast);
        void toast.offsetHeight;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
        toast.style.pointerEvents = 'auto';
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100px)';
            toast.style.pointerEvents = 'none';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    function saveSettings() {
        var btn = document.getElementById('drea-so-save-btn');
        if (!btn) return;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_so_save_settings');
        formData.append('nonce', nonce);

        var toggles = document.querySelectorAll('.drea-so-toggle input[type="checkbox"]');
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
