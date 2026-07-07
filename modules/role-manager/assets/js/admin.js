/**
 * 角色管理模块 — 管理脚本 (Vanilla JS)
 */
(function () {
    'use strict';

    if (typeof dreaRm === 'undefined') return;

    var i18n    = dreaRm.i18n;
    var ajaxUrl = dreaRm.ajaxUrl;
    var nonce   = dreaRm.nonce;

    var currentEditRole = null;
    var wpCapabilities  = [];

    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'info';
        var container = $('#drea-rm-toast-container');
        if (!container) return;
        var icons = { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' };
        var toast = document.createElement('div');
        toast.className = 'drea-rm-toast drea-rm-toast--' + type;
        toast.innerHTML = '<span style="flex-shrink:0;">' + (icons[type] || icons.info) + '</span>' +
            '<span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);
        toast.offsetHeight;
        toast.classList.add('drea-rm-toast--show');
        setTimeout(function () {
            toast.classList.remove('drea-rm-toast--show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    function postAjax(data) {
        data.nonce = nonce;
        var body = new FormData();
        for (var key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach(function (v) { body.append(key + '[]', v); });
            } else {
                body.append(key, data[key]);
            }
        }
        return fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    // ─── 加载角色列表 ───

    function loadRoles() {
        postAjax({ action: 'drea_rm_get_roles' })
            .then(function (res) {
                if (!res.success) { showToast(i18n.failed, 'error'); return; }
                renderRoles(res.data.roles);

                // 收集所有能力用于编辑
                var capsSet = {};
                res.data.roles.forEach(function (r) {
                    r.capabilities.forEach(function (c) { capsSet[c] = true; });
                });
                wpCapabilities = Object.keys(capsSet).sort();
            })
            .catch(function () { showToast(i18n.error, 'error'); });
    }

    function renderRoles(roles) {
        var tbody = $('#drea-rm-roles-tbody');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (roles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#646970;">' + escapeHtml(i18n.noRoles) + '</td></tr>';
            return;
        }

        roles.forEach(function (role) {
            var tr = document.createElement('tr');
            var protectedBadge = role.is_protected
                ? ' <span class="drea-rm-role-badge drea-rm-role-badge--protected">' + escapeHtml(i18n.builtIn) + '</span>'
                : '';
            tr.innerHTML =
                '<td><strong>' + escapeHtml(role.display_name) + '</strong>' + protectedBadge + '</td>' +
                '<td><code>' + escapeHtml(role.name) + '</code></td>' +
                '<td>' + role.user_count + '</td>' +
                '<td>' + role.capabilities.length + '</td>' +
                '<td>' +
                    '<button type="button" class="button button-small drea-rm-edit-btn" data-role="' + escapeHtml(role.name) + '">' + escapeHtml(i18n.edit) + '</button> ' +
                    '<button type="button" class="button button-small drea-rm-copy-btn" data-role="' + escapeHtml(role.name) + '" data-display="' + escapeHtml(role.display_name) + '">' + escapeHtml(i18n.copy) + '</button>' +
                    (!role.is_protected ? ' <button type="button" class="button button-small drea-rm-delete-btn" data-role="' + escapeHtml(role.name) + '" data-display="' + escapeHtml(role.display_name) + '">' + escapeHtml(i18n.delete) + '</button>' : '') +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    // ─── 添加/复制角色对话框 ───

    function showDialog(title, sourceRole) {
        $('#drea-rm-dialog-title').textContent = title;
        $('#drea-rm-dialog-name').value = '';
        $('#drea-rm-dialog-slug').value = '';
        $('#drea-rm-dialog-source-role').value = sourceRole || '';
        $('#drea-rm-dialog-slug').disabled = false;
        $('#drea-rm-dialog-overlay').style.display = '';
    }

    function hideDialog() {
        $('#drea-rm-dialog-overlay').style.display = 'none';
    }

    function confirmDialog() {
        var name = ($('#drea-rm-dialog-name').value || '').trim();
        var slug = ($('#drea-rm-dialog-slug').value || '').trim();
        var source = $('#drea-rm-dialog-source-role').value;

        if (!name || !slug) {
            showToast(i18n.fillRequired, 'error');
            return;
        }

        var action = source ? 'drea_rm_copy_role' : 'drea_rm_add_role';
        var data = { action: action, display_name: name, role_slug: slug };
        if (source) {
            data.source_role = source;
            data.new_role_slug = slug;
            data.new_role_name = name;
        }

        postAjax(data)
            .then(function (res) {
                if (res.success) {
                    showToast(source ? i18n.roleCopied : i18n.roleAdded, 'success');
                    hideDialog();
                    loadRoles();
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.failed, 'error');
                }
            })
            .catch(function () { showToast(i18n.error, 'error'); });
    }

    // ─── 删除角色 ───

    function deleteRole(roleName, displayName) {
        var msg = i18n.confirmDelete.replace('%s', displayName);
        if (!confirm(msg)) return;

        postAjax({ action: 'drea_rm_delete_role', role: roleName })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.roleDeleted, 'success');
                    loadRoles();
                } else {
                    showToast(res.data && res.data.message ? res.data.message : i18n.failed, 'error');
                }
            })
            .catch(function () { showToast(i18n.error, 'error'); });
    }

    // ─── 编辑角色能力 ───

    function editRole(roleName) {
        postAjax({ action: 'drea_rm_get_role', role: roleName })
            .then(function (res) {
                if (!res.success) { showToast(i18n.failed, 'error'); return; }
                currentEditRole = res.data;
                renderDetail(res.data);
            })
            .catch(function () { showToast(i18n.error, 'error'); });
    }

    function renderDetail(roleData) {
        var panel = $('#drea-rm-detail-panel');
        if (!panel) return;

        $('#drea-rm-detail-title').textContent = roleData.display_name + ' (' + roleData.name + ')';
        renderCapsGrid(roleData.capabilities);
        panel.style.display = '';
    }

    function renderCapsGrid(activeCaps) {
        var grid = $('#drea-rm-caps-grid');
        if (!grid) return;
        grid.innerHTML = '';

        // 合并所有已知能力
        var allCaps = wpCapabilities.slice();
        activeCaps.forEach(function (c) {
            if (allCaps.indexOf(c) === -1) allCaps.push(c);
        });
        allCaps.sort();

        allCaps.forEach(function (cap) {
            var label = document.createElement('label');
            label.className = 'drea-rm-cap-item';
            var checked = activeCaps.indexOf(cap) !== -1 ? ' checked' : '';
            label.innerHTML = '<input type="checkbox" class="drea-rm-cap-check" data-cap="' + escapeHtml(cap) + '"' + checked + '> ' + escapeHtml(cap);
            grid.appendChild(label);
        });
    }

    function saveCaps() {
        if (!currentEditRole) return;

        var caps = [];
        $$('.drea-rm-cap-check:checked').forEach(function (cb) {
            caps.push(cb.dataset.cap);
        });

        postAjax({
            action: 'drea_rm_update_role',
            role: currentEditRole.name,
            capabilities: JSON.stringify(caps)
        })
        .then(function (res) {
            if (res.success) {
                showToast(i18n.roleUpdated, 'success');
                loadRoles();
            } else {
                showToast(res.data && res.data.message ? res.data.message : i18n.failed, 'error');
            }
        })
        .catch(function () { showToast(i18n.error, 'error'); });
    }

    function closeDetail() {
        var panel = $('#drea-rm-detail-panel');
        if (panel) panel.style.display = 'none';
        currentEditRole = null;
    }

    // ─── 事件绑定和初始化 ───

    function init() {
        // 添加角色按钮
        var addBtn = $('#drea-rm-add-btn');
        if (addBtn) addBtn.addEventListener('click', function () {
            showDialog(i18n.addRole, '');
        });

        // 对话框按钮
        var dialogConfirm = $('#drea-rm-dialog-confirm');
        var dialogCancel = $('#drea-rm-dialog-cancel');
        if (dialogConfirm) dialogConfirm.addEventListener('click', confirmDialog);
        if (dialogCancel) dialogCancel.addEventListener('click', hideDialog);

        // 点击遮罩关闭
        var overlay = $('#drea-rm-dialog-overlay');
        if (overlay) overlay.addEventListener('click', function (e) {
            if (e.target === overlay) hideDialog();
        });

        // 详情面板
        var saveCapsBtn = $('#drea-rm-save-caps');
        var closeDetailBtn = $('#drea-rm-detail-close');
        if (saveCapsBtn) saveCapsBtn.addEventListener('click', saveCaps);
        if (closeDetailBtn) closeDetailBtn.addEventListener('click', closeDetail);

        // 委托表格行内按钮
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.drea-rm-edit-btn');
            if (btn) { editRole(btn.dataset.role); return; }

            btn = e.target.closest('.drea-rm-copy-btn');
            if (btn) {
                showDialog(i18n.copyRole, btn.dataset.role);
                $('#drea-rm-dialog-name').value = btn.dataset.display + ' (副本)';
                $('#drea-rm-dialog-slug').value = btn.dataset.role + '_copy';
                return;
            }

            btn = e.target.closest('.drea-rm-delete-btn');
            if (btn) { deleteRole(btn.dataset.role, btn.dataset.display); return; }
        });

        loadRoles();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
