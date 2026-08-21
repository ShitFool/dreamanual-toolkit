/**
 * 内容可见性模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    if (typeof dreaCv === 'undefined') return;

    var i18n    = dreaCv.i18n;
    var ajaxUrl = dreaCv.ajaxUrl;
    var nonce   = dreaCv.nonce;

    var dirtyCtrl = null;

    function $(sel) { return document.querySelector(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        DreaToast.show(message, type, 'drea-cv-toast-container');
    }

    /**
     * 从 DOM 收集当前规则
     * UI 逻辑：勾选 = 显示，未勾选 = 隐藏
     * 存储：channels 列表 = 未勾选（隐藏）的渠道
     */
    function collectRules() {
        var rows = document.querySelectorAll('.drea-cv-rules-table tbody tr');
        var rules = {};

        rows.forEach(function (row) {
            var catId = row.dataset.catId;
            if (!catId) return;

            var hiddenChannels = [];
            row.querySelectorAll('.drea-cv-channel').forEach(function (cb) {
                if (!cb.checked) {
                    hiddenChannels.push(cb.dataset.channel);
                }
            });

            var roles = [];
            var select = row.querySelector('.drea-cv-roles');
            if (select) {
                Array.from(select.selectedOptions).forEach(function (opt) {
                    roles.push(opt.value);
                });
            }

            // 只在有渠道被隐藏时才生成规则
            if (hiddenChannels.length > 0) {
                rules[catId] = { channels: hiddenChannels, roles: roles };
            }
        });

        return rules;
    }

    /**
     * 保存规则
     */
    function saveRules() {
        var btn = $('#drea-cv-save-btn');
        if (!btn) return;

        // 校验：有渠道被隐藏但角色为空时警告
        var rules = collectRules();
        var warnCount = 0;
        Object.keys(rules).forEach(function (catId) {
            if (rules[catId].channels.length > 0 && rules[catId].roles.length === 0) {
                warnCount++;
            }
        });
        if (warnCount > 0 && !confirm(i18n.noRolesWarning)) {
            return;
        }
        // dirty-state: 有修改才允许点保存，但防御性检查
        if (btn.disabled) return;
        btn.disabled = true;

        var rules = collectRules();
        var warnCount = 0;
        Object.keys(rules).forEach(function (catId) {
            if (rules[catId].channels.length > 0 && rules[catId].roles.length === 0) {
                warnCount++;
            }
        });
        if (warnCount > 0 && !confirm(i18n.noRolesWarning)) {
            return;
        }

        btn.disabled = true;

        var rules = collectRules();

        var formData = new FormData();
        formData.append('action', 'drea_cv_save_rules');
        formData.append('nonce', nonce);
        formData.append('rules', JSON.stringify(rules));

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        console.error('[DREA CV] saveRules JSON parse error, raw:', text.substring(0, 500));
                        throw e;
                    }
                });
            })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.saved, 'success');
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

    /**
     * 切换文章可见性（行操作）
     */
    function togglePost(link) {
        if (!link) return;
        var postId = link.dataset.postId;
        var hidden = link.dataset.hidden;
        var action = hidden === '1' ? i18n.confirmHide : i18n.confirmShow;
        if (!confirm(action)) return;

        var formData = new FormData();
        formData.append('action', 'drea_cv_toggle_post');
        formData.append('nonce', nonce);
        formData.append('post_id', postId);
        formData.append('hidden', hidden);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        console.error('[DREA CV] togglePost JSON parse error, raw:', text.substring(0, 500));
                        throw e;
                    }
                });
            })
            .then(function (res) {
                if (res.success) {
                    // 刷新列表以反映状态变化
                    window.location.reload();
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.error, 'error');
                }
            })
            .catch(function () {
                showToast(i18n.toggleError, 'error');
            });
    }

    function init() {
        var saveBtn = $('#drea-cv-save-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveRules);

        // dirty-state 跟踪：所有 channel checkbox + roles select
        var cvInputs = document.querySelectorAll('.drea-cv-channel, .drea-cv-roles');
        dirtyCtrl = DreaFormDirty.watch(cvInputs, saveBtn);

        // 行操作：隐藏/显示链接
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.drea-cv-toggle-link');
            if (link) {
                e.preventDefault();
                togglePost(link);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
