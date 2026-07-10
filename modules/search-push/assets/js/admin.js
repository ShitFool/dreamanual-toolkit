/**
 * 搜索推送模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    if (typeof dreaSp === 'undefined') return;

    var i18n    = dreaSp.i18n;
    var ajaxUrl = dreaSp.ajaxUrl;
    var nonce   = dreaSp.nonce;

    function $(sel) { return document.querySelector(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'info';
        var container = $('#drea-sp-toast-container');
        if (!container) return;
        var icons = { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' };
        var toast = document.createElement('div');
        toast.className = 'drea-sp-toast drea-sp-toast--' + type;
        toast.innerHTML = '<span style="flex-shrink:0;">' + (icons[type] || icons.info) + '</span>' +
            '<span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);
        toast.offsetHeight;
        toast.classList.add('drea-sp-toast--show');
        setTimeout(function () {
            toast.classList.remove('drea-sp-toast--show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    function saveSettings() {
        var btn = $('#drea-sp-save-btn');
        if (!btn) return;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_sp_save_settings');
        formData.append('nonce', nonce);
        formData.append('baidu_enabled', $('#baidu-enabled').checked ? 1 : 0);
        formData.append('baidu_token', ($('#baidu-token').value || '').trim());
        formData.append('baidu_site', ($('#baidu-site').value || '').trim());
        formData.append('bing_enabled', $('#bing-enabled').checked ? 1 : 0);
        formData.append('bing_key', ($('#bing-key').value || '').trim());
        formData.append('indexnow_enabled', $('#indexnow-enabled').checked ? 1 : 0);
        formData.append('indexnow_key', ($('#indexnow-key').value || '').trim());

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

    function toggleSectionBody(checkbox, bodyId) {
        var body = document.getElementById(bodyId);
        if (!body) return;
        if (checkbox.checked) {
            body.classList.remove('drea-sp-section__body--collapsed');
        } else {
            body.classList.add('drea-sp-section__body--collapsed');
        }
        var section = body.closest('.drea-sp-section');
        if (section) {
            var header = section.querySelector('.drea-sp-section__header');
            if (header) {
                header.style.borderBottom = checkbox.checked ? '1px solid #f0f0f1' : 'none';
            }
        }
    }

    function testPush(engine) {
        var btn = document.querySelector('[data-engine="' + engine + '"]');
        if (!btn) return;
        btn.disabled = true;
        var statusEl = document.getElementById(engine + '-test-status');
        if (statusEl) statusEl.textContent = '';

        var formData = new FormData();
        formData.append('action', 'drea_sp_test_push');
        formData.append('nonce', nonce);
        formData.append('engine', engine);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.testOk, 'success');
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.testFail, 'error');
                }
                btn.disabled = false;
            })
            .catch(function () {
                showToast(i18n.error, 'error');
                btn.disabled = false;
            });
    }

    function init() {
        var saveBtn = $('#drea-sp-save-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveSettings);

        // 开关联动
        var toggles = [
            { checkbox: '#baidu-enabled', body: 'baidu-settings' },
            { checkbox: '#bing-enabled', body: 'bing-settings' },
            { checkbox: '#indexnow-enabled', body: 'indexnow-settings' },
        ];

        toggles.forEach(function (t) {
            var cb = $(t.checkbox);
            if (cb) {
                toggleSectionBody(cb, t.body);
                cb.addEventListener('change', function () {
                    toggleSectionBody(cb, t.body);
                });
            }
        });

        // 测试推送
        document.querySelectorAll('[data-engine]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                testPush(btn.dataset.engine);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
