/**
 * 搜索推送模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    if (typeof dreaSp === 'undefined') return;

    var i18n    = dreaSp.i18n;
    var ajaxUrl = dreaSp.ajaxUrl;
    var nonce   = dreaSp.nonce;
    var dirty   = false; // 跟踪未保存修改 (F-11) —— 同时用于 DreaFormDirty 控制
    var dirtyCtrl = null;

    function $(sel) { return document.querySelector(sel); }

    function showToast(message, type) {
        DreaToast.show(message, type, 'drea-sp-toast-container');
    }

    function saveSettings() {
        var btn = $('#drea-sp-save-btn');
        if (!btn) return;

        var baiduEnabled = $('#baidu-enabled').checked;
        var baiduToken = ($('#baidu-token').value || '').trim();
        var baiduSite = ($('#baidu-site').value || '').trim();
        var bingEnabled = $('#bing-enabled').checked;
        var bingKey = ($('#bing-key').value || '').trim();

        // 前端配置完整性校验
        if (baiduEnabled && !baiduToken) {
            showToast(i18n.baiduTokenRequired, 'error');
            return;
        }
        if (baiduEnabled && !baiduSite) {
            showToast(i18n.baiduSiteRequired, 'error');
            return;
        }
        if (bingEnabled && !bingKey) {
            showToast(i18n.bingKeyRequired, 'error');
            return;
        }

        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_sp_save_settings');
        formData.append('nonce', nonce);
        formData.append('baidu_enabled', baiduEnabled ? 1 : 0);
        formData.append('baidu_token', baiduToken);
        formData.append('baidu_site', baiduSite);
        formData.append('bing_enabled', bingEnabled ? 1 : 0);
        formData.append('bing_key', bingKey);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        console.error('[DREA SP] saveSettings JSON parse error, raw:', text.substring(0, 500));
                        throw e;
                    }
                });
            })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.saved, 'success');
                    dirty = false;
                    if (dirtyCtrl) dirtyCtrl.markClean();
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.failed, 'error');
                    if (dirtyCtrl) dirtyCtrl.markDirty();
                }
            })
            .catch(function () {
                showToast(i18n.error, 'error');
                if (dirtyCtrl) dirtyCtrl.markDirty();
            });
    }

    function testPush(engine) {
        var btn = document.querySelector('[data-engine="' + engine + '"]');
        if (!btn) return;
        // 提示先保存设置 (F-11)
        if (dirty) {
            if (!confirm(i18n.testUnsaved)) return;
        }
        btn.disabled = true;
        var statusEl = document.getElementById(engine + '-test-status');
        if (statusEl) statusEl.textContent = '';

        var formData = new FormData();
        formData.append('action', 'drea_sp_test_push');
        formData.append('nonce', nonce);
        formData.append('engine', engine);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        console.error('[DREA SP] testPush JSON parse error, raw:', text.substring(0, 500));
                        throw e;
                    }
                });
            })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.testOk, 'success');
                    var statusEl = document.getElementById(engine + '-test-status');
                    if (statusEl) statusEl.textContent = i18n.testOk + ' (' + new Date().toLocaleTimeString() + ')';
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

        // 跟踪未保存修改 (F-11) —— 使用 DreaFormDirty 统一管理按钮状态
        var spInputs = document.querySelectorAll('#baidu-enabled, #baidu-token, #baidu-site, #bing-enabled, #bing-key');
        dirtyCtrl = DreaFormDirty.watch(spInputs, saveBtn);
        // DreaFormDirty.watch 的监听器同步 dirty 变量（用于 testPush 的未保存提示）
        spInputs.forEach(function (input) {
            input.addEventListener('change', function () { dirty = true; });
            if (input.type === 'text' || input.type === 'password') {
                input.addEventListener('input', function () { dirty = true; });
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
