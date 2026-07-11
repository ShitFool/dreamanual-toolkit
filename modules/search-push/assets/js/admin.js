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

    function showToast(message, type) {
        DreaToast.show(message, type, 'drea-sp-toast-container');
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
            { checkbox: '#baidu-enabled', body: 'baidu-settings', section: null },
            { checkbox: '#bing-enabled', body: 'bing-settings', section: null },
        ];

        toggles.forEach(function (t) {
            var cb = $(t.checkbox);
            if (cb) {
                // 初始化折叠状态
                DreaSection.toggle(cb, t.body);
                cb.addEventListener('change', function () {
                    DreaSection.toggle(cb, t.body);
                    // 更新 section --collapsed class
                    var body = document.getElementById(t.body);
                    if (body) {
                        var section = body.closest('.drea-section');
                        if (section) {
                            section.classList.toggle('drea-section--collapsed', !cb.checked);
                        }
                    }
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
