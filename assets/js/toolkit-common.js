/**
 * Dreamanual Toolkit - 公共管理脚本
 */

(function () {
    'use strict';

    if (typeof dreaToolkit === 'undefined') {
        return;
    }

    var ajaxUrl = dreaToolkit.ajaxUrl;
    var nonce   = dreaToolkit.nonce;
    var i18n    = dreaToolkit.i18n;

    /**
     * 模块开关切换
     */
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('drea-toggle__input')) {
            return;
        }

        var checkbox  = e.target;
        var moduleId  = checkbox.dataset.moduleId;
        var actionType = checkbox.checked ? 'activate' : 'deactivate';
        var card      = checkbox.closest('.drea-module-card');

        // 禁用交互
        checkbox.disabled = true;

        var formData = new FormData();
        formData.append('action', 'drea_toggle_module');
        formData.append('nonce', nonce);
        formData.append('module_id', moduleId);
        formData.append('action_type', actionType);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                // 更新卡片状态
                var isActive = data.data.active;
                card.classList.toggle('drea-module-card--active', isActive);
                var statusEl = card.querySelector('.drea-module-card__status');
                if (statusEl) {
                    statusEl.textContent = isActive ? i18n.activated : i18n.deactivated;
                }
            } else {
                // 回滚开关
                checkbox.checked = !checkbox.checked;
                alert(data.data && data.data.message ? data.data.message : i18n.error);
            }
        })
        .catch(function () {
            checkbox.checked = !checkbox.checked;
            alert(i18n.error);
        })
        .finally(function () {
            checkbox.disabled = false;
        });
    });
})();
