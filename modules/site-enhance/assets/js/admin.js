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

    function showToast(message, type, duration) {
        DreaToast.show(message, type, 'drea-se-toast-container', duration);
    }

    var dirtyCtrl = null;

    function saveSettings() {
        var btn = $('#drea-se-save-btn');
        if (!btn) return;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_se_save_settings');
        formData.append('nonce', nonce);
        formData.append('btt_enabled', $('#btt-enabled').checked ? 1 : 0);
        formData.append('btt_color', ($('#btt-color').value || '#2271b1'));
        formData.append('btt_icon_color', ($('#btt-icon-color').value || '#ffffff'));
        var bttPosEl = $('#btt-position');
        formData.append('btt_position', bttPosEl ? bttPosEl.value : 'right-bottom');
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

        // 评论头像优化
        formData.append('avatar_fallback_enabled', $('#avatar-fallback-enabled') ? ($('#avatar-fallback-enabled').checked ? 1 : 0) : 0);
        formData.append('avatar_fallback_url', ($('#avatar-fallback-url').value || ''));
        formData.append('avatar_mirror', ($('#avatar-mirror').value || 'cn.cravatar.com').trim());
        formData.append('avatar_replace_gravatar', $('#avatar-replace-gravatar') ? ($('#avatar-replace-gravatar').checked ? 1 : 0) : 1);

        // SMTP 完整性校验 (F-04)
        if ($('#smtp-enabled').checked) {
            var smtpHost = ($('#smtp-host').value || '').trim();
            var smtpUser = ($('#smtp-user').value || '').trim();
            if (!smtpHost) {
                showToast(i18n.smtpHostRequired, 'error');
                btn.disabled = false;
                return;
            }
            if (!smtpUser) {
                showToast(i18n.smtpUserRequired, 'error');
                btn.disabled = false;
                return;
            }
            // 端口范围校验 (F-12)
            var port = parseInt($('#smtp-port').value, 10) || 465;
            if (port < 1 || port > 65535) {
                showToast(i18n.smtpPortRange, 'error');
                btn.disabled = false;
                return;
            }
        }

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        console.error('[DREA SE] saveSettings JSON parse error, raw:', text.substring(0, 500));
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

    function init() {
        var saveBtn = $('#drea-se-save-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveSettings);

        // dirty-state 跟踪：无修改时按钮禁用，修改后启用，保存成功后禁用
        var seInputs = document.querySelectorAll(
            '#btt-enabled,#btt-color,#btt-icon-color,#btt-position,#maintenance-enabled,#maintenance-msg,' +
            '#feat-img-enabled,#feat-img-col-enabled,#default-feat-img-enabled,#default-feat-img-id,' +
            '#quickedit-excerpt-enabled,#smtp-enabled,#smtp-host,#smtp-port,#smtp-encryption,' +
            '#smtp-user,#smtp-pass,#smtp-from-name,#smtp-from-email,' +
            '#avatar-fallback-enabled,#avatar-fallback-url,#avatar-mirror,#avatar-replace-gravatar'
        );
        dirtyCtrl = DreaFormDirty.watch(seInputs, saveBtn);

        // BTT 实时预览：背景色/图标色变化时更新预览按钮
        var bttColorInput = $('#btt-color');
        var bttIconColorInput = $('#btt-icon-color');
        var bttPreviewBtn = $('#drea-btt-preview-btn');
        if (bttColorInput && bttPreviewBtn) {
            bttColorInput.addEventListener('input', function () {
                bttPreviewBtn.style.background = bttColorInput.value;
            });
        }
        if (bttIconColorInput && bttPreviewBtn) {
            bttIconColorInput.addEventListener('input', function () {
                bttPreviewBtn.style.color = bttIconColorInput.value;
            });
        }

        // 子功能开关联动
        var toggles = [
            { checkbox: '#btt-enabled', body: 'btt-settings' },
            { checkbox: '#maintenance-enabled', body: 'maintenance-settings' },
            { checkbox: '#feat-img-enabled', body: 'feat-img-settings' },
            { checkbox: '#feat-img-col-enabled', body: 'feat-img-col-settings' },
            { checkbox: '#default-feat-img-enabled', body: 'default-feat-img-settings' },
            { checkbox: '#quickedit-excerpt-enabled', body: 'quickedit-excerpt-settings' },
            { checkbox: '#smtp-enabled', body: 'smtp-settings' },
            { checkbox: '#avatar-fallback-enabled', body: 'avatar-settings' },
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

        // 维护模式开启二次确认 (F-15)
        var maintCheckbox = $('#maintenance-enabled');
        if (maintCheckbox) {
            maintCheckbox.addEventListener('change', function () {
                if (maintCheckbox.checked) {
                    if (!confirm(i18n.maintenanceConfirm)) {
                        maintCheckbox.checked = false;
                        DreaSection.toggle(maintCheckbox, 'maintenance-settings');
                        var body = document.getElementById('maintenance-settings');
                        if (body) {
                            var section = body.closest('.drea-section');
                            if (section) section.classList.add('drea-section--collapsed');
                        }
                    }
                }
            });
        }

        // 默认特色图片 — 媒体库选择器
        var selectBtn = $('#default-feat-img-select');
        var removeBtn = $('#default-feat-img-remove');
        var imgIdInput = $('#default-feat-img-id');
        var previewDiv = $('#default-feat-img-preview');

        if (selectBtn && typeof wp !== 'undefined' && wp.media) {
            selectBtn.addEventListener('click', function () {
                var frame = wp.media({
                    title: 'Select Default Featured Image',
                    button: { text: 'Set as Default' },
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
                previewDiv.innerHTML = '<span>Not Set</span>';
                removeBtn.style.display = 'none';
            });
        }

        // SMTP 测试发信
        var smtpTestBtn = $('#smtp-test-btn');

        // 评论头像 — 媒体库选择器
        var avatarSelectBtn = $('#avatar-fallback-select');
        var avatarRemoveBtn = $('#avatar-fallback-remove');
        var avatarUrlInput  = $('#avatar-fallback-url');
        var avatarPreview   = $('#avatar-fallback-preview');

        if (avatarSelectBtn && typeof wp !== 'undefined' && wp.media) {
            avatarSelectBtn.addEventListener('click', function () {
                var frame = wp.media({
                    title: 'Select Default Avatar',
                    button: { text: 'Set as Default Avatar' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    avatarUrlInput.value = attachment.url;
                    avatarPreview.innerHTML = '<img src="' + DreaToast._escapeHtml(attachment.url) + '">';
                    avatarRemoveBtn.style.display = '';
                });
                frame.open();
            });
        }

        if (avatarRemoveBtn) {
            avatarRemoveBtn.addEventListener('click', function () {
                avatarUrlInput.value = '';
                avatarPreview.innerHTML = '<span>Not Set</span>';
                avatarRemoveBtn.style.display = 'none';
            });
        }

        // SMTP 测试发信
        if (smtpTestBtn) {
            smtpTestBtn.addEventListener('click', function () {
                var to = ($('#smtp-test-to').value || '').trim();
                if (!to) {
                    showToast(i18n.smtpTestNoTo, 'error');
                    return;
                }
                // SMTP 未启用时提示 (F-12)
                if (!$('#smtp-enabled').checked) {
                    showToast(i18n.smtpNotEnabled, 'error');
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
                    .then(function (r) {
                        return r.text().then(function (text) {
                            try { return JSON.parse(text); }
                            catch (e) {
                                console.error('[DREA SE] smtpTest JSON parse error, raw:', text.substring(0, 500));
                                throw e;
                            }
                        });
                    })
                    .then(function (res) {
                        if (res.success) {
                            showToast(i18n.smtpTestSuccess, 'success');
                        } else {
                            var errMsg = res.data && res.data.message ? res.data.message : i18n.smtpTestFail;
                            showToast(errMsg, 'error', 6000);
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
