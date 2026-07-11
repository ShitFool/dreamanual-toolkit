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

    function showToast(message, type) {
        DreaToast.show(message, type, 'drea-se-toast-container');
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
                DreaSection.toggle(cb, t.body);
                cb.addEventListener('change', function () {
                    DreaSection.toggle(cb, t.body);
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
                    previewDiv.innerHTML = '<img src="' + DreaToast._escapeHtml(attachment.url) + '">';
                    removeBtn.style.display = '';
                });
                frame.open();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                imgIdInput.value = 0;
                previewDiv.innerHTML = '<span>未设置</span>';
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
