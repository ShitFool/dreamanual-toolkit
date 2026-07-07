/**
 * 站点增强模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    if (typeof dreaSe === 'undefined') return;

    var i18n    = dreaSe.i18n;
    var ajaxUrl = dreaSe.ajaxUrl;
    var nonce   = dreaSe.nonce;

    function $(sel) { return document.querySelector(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'info';
        var container = $('#drea-se-toast-container');
        if (!container) return;
        var icons = { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' };
        var toast = document.createElement('div');
        toast.className = 'drea-se-toast drea-se-toast--' + type;
        toast.innerHTML = '<span style="flex-shrink:0;">' + (icons[type] || icons.info) + '</span>' +
            '<span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);
        toast.offsetHeight;
        toast.classList.add('drea-se-toast--show');
        setTimeout(function () {
            toast.classList.remove('drea-se-toast--show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    function saveSettings() {
        var btn = $('#drea-se-save-btn');
        if (!btn) return;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_se_save_settings');
        formData.append('nonce', nonce);
        formData.append('btt_enabled', $('#btt-enabled').checked ? 1 : 0);
        formData.append('btt_color', ($('#btt-color').value || '#2271b1'));
        formData.append('btt_position', $('#btt-position').value);
        formData.append('maintenance_enabled', $('#maintenance-enabled').checked ? 1 : 0);
        formData.append('maintenance_msg', ($('#maintenance-msg').value || '').trim());
        formData.append('feat_img_enabled', $('#feat-img-enabled').checked ? 1 : 0);
        formData.append('feat_img_col_enabled', $('#feat-img-col-enabled').checked ? 1 : 0);
        formData.append('default_feat_img_enabled', $('#default-feat-img-enabled').checked ? 1 : 0);
        var defaultImgId = $('#default-feat-img-id');
        formData.append('default_feat_img_id', defaultImgId ? defaultImgId.value : 0);
        formData.append('quickedit_excerpt_enabled', $('#quickedit-excerpt-enabled').checked ? 1 : 0);

        // SMTP 设置
        formData.append('smtp_enabled', $('#smtp-enabled').checked ? 1 : 0);
        formData.append('smtp_host', ($('#smtp-host').value || '').trim());
        formData.append('smtp_port', ($('#smtp-port').value || 465));
        formData.append('smtp_encryption', $('#smtp-encryption').value);
        formData.append('smtp_user', ($('#smtp-user').value || '').trim());
        formData.append('smtp_pass', ($('#smtp-pass').value || ''));
        formData.append('smtp_from_name', ($('#smtp-from-name').value || '').trim());
        formData.append('smtp_from_email', ($('#smtp-from-email').value || '').trim());

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
            body.classList.remove('drea-se-section__body--collapsed');
        } else {
            body.classList.add('drea-se-section__body--collapsed');
        }
        // 更新 header 底部边框
        var section = body.closest('.drea-se-section');
        if (section) {
            var header = section.querySelector('.drea-se-section__header');
            if (header) {
                if (checkbox.checked) {
                    header.style.borderBottom = '1px solid #f0f0f1';
                } else {
                    header.style.borderBottom = 'none';
                }
            }
        }
    }

    function init() {
        var saveBtn = $('#drea-se-save-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveSettings);

        // 子功能开关联动
        var toggles = [
            { checkbox: '#btt-enabled', body: 'btt-settings' },
            { checkbox: '#maintenance-enabled', body: 'maintenance-settings' },
            { checkbox: '#feat-img-enabled', body: 'feat-img-settings' },
            { checkbox: '#feat-img-col-enabled', body: 'feat-img-col-settings' },
            { checkbox: '#default-feat-img-enabled', body: 'default-feat-img-settings' },
            { checkbox: '#quickedit-excerpt-enabled', body: 'quickedit-excerpt-settings' },
            { checkbox: '#smtp-enabled', body: 'smtp-settings' },
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

        // 无开关的 section（如默认特色图片）始终显示 header 边框
        document.querySelectorAll('.drea-se-section__body:not(.drea-se-section__body--collapsed)').forEach(function (body) {
            var section = body.closest('.drea-se-section');
            if (section) {
                var header = section.querySelector('.drea-se-section__header');
                if (header && !header.style.borderBottom) {
                    header.style.borderBottom = '1px solid #f0f0f1';
                }
            }
        });

        // 默认特色图片 — 媒体库选择器
        var selectBtn = $('#default-feat-img-select');
        var removeBtn = $('#default-feat-img-remove');
        var imgIdInput = $('#default-feat-img-id');
        var previewDiv = $('#default-feat-img-preview');

        if (selectBtn && typeof wp !== 'undefined' && wp.media) {
            selectBtn.addEventListener('click', function () {
                var frame = wp.media({
                    title: '选择默认特色图片',
                    button: { text: '设为默认' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    imgIdInput.value = attachment.id;
                    previewDiv.innerHTML = '<img src="' + escapeHtml(attachment.url) + '" style="max-width:300px;max-height:150px;border:1px solid #dcdcde;border-radius:4px;">';
                    removeBtn.style.display = '';
                });
                frame.open();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                imgIdInput.value = 0;
                previewDiv.innerHTML = '<span style="color:#999;">未设置</span>';
                removeBtn.style.display = 'none';
            });
        }

        // SMTP 测试发信
        var smtpTestBtn = $('#smtp-test-btn');
        if (smtpTestBtn) {
            smtpTestBtn.addEventListener('click', function () {
                var to = ($('#smtp-test-to').value || '').trim();
                if (!to) {
                    showToast(i18n.smtpTestNoTo, 'error');
                    return;
                }
                smtpTestBtn.disabled = true;
                var statusEl = $('#smtp-test-status');
                if (statusEl) statusEl.textContent = '';

                var formData = new FormData();
                formData.append('action', 'drea_se_smtp_test');
                formData.append('nonce', nonce);
                formData.append('to', to);

                fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            showToast(i18n.smtpTestSuccess, 'success');
                        } else {
                            showToast(res.data && res.data.message ? res.data.message : i18n.smtpTestFail, 'error');
                        }
                        smtpTestBtn.disabled = false;
                    })
                    .catch(function () {
                        showToast(i18n.error, 'error');
                        smtpTestBtn.disabled = false;
                    });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
