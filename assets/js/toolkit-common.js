/**
 * Dreamanual Toolkit - 公共管理脚本
 */

(function () {
    'use strict';

    /* ─── 通用 Toast 工具 ─── */
    window.DreaToast = {
        icons: { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' },

        show: function (message, type, containerId, duration) {
            type = type || 'info';
            duration = duration || 3000;
            var container = containerId
                ? document.getElementById(containerId)
                : document.querySelector('.drea-toast-container, [id$="-toast-container"]');
            if (!container) return;

            var icons = this.icons;
            var toast = document.createElement('div');
            toast.className = 'drea-toast drea-toast--' + type;
            toast.innerHTML = '<span style="flex-shrink:0;">' + (icons[type] || icons.info) + '</span>' +
                '<span>' + this._escapeHtml(message) + '</span>';
            container.appendChild(toast);
            // 触发回流以启动动画
            toast.offsetHeight;
            toast.classList.add('drea-toast--show');
            setTimeout(function () {
                toast.classList.remove('drea-toast--show');
                setTimeout(function () { toast.remove(); }, 300);
            }, duration);
        },

        _escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /* ─── 通用表单 dirty-state 工具 ─── */
    window.DreaFormDirty = {
        /**
         * 监听表单输入，控制保存按钮的 disabled 状态。
         * - 初始状态：按钮禁用（无修改）
         * - 用户修改任意输入：按钮启用
         * - 保存成功后调用 markClean()：按钮再次禁用
         * - 保存失败后调用 markDirty()：按钮保持/恢复启用
         *
         * @param {string|NodeList|Array} inputs  CSS 选择器或元素列表。
         * @param {HTMLElement}           saveBtn 保存按钮元素。
         * @return {{markClean:Function,markDirty:Function}}
         */
        watch: function (inputs, saveBtn) {
            if (!saveBtn) return { markClean: function(){}, markDirty: function(){} };

            // 统一为元素数组
            var els;
            if (typeof inputs === 'string') {
                els = Array.prototype.slice.call(document.querySelectorAll(inputs));
            } else if (inputs && inputs.length !== undefined) {
                els = Array.prototype.slice.call(inputs);
            } else {
                els = [];
            }

            var self = this;

            // 初始禁用
            saveBtn.disabled = true;

            els.forEach(function (el) {
                el.addEventListener('change', function () {
                    self._markDirty(saveBtn);
                });
                if (el.type === 'text' || el.type === 'password' || el.type === 'textarea' ||
                    el.tagName === 'TEXTAREA' || el.type === 'number' || el.type === 'email' ||
                    el.type === 'color' || el.type === 'range') {
                    el.addEventListener('input', function () {
                        self._markDirty(saveBtn);
                    });
                }
            });

            return {
                markClean: function () { self._markClean(saveBtn); },
                markDirty: function () { self._markDirty(saveBtn); }
            };
        },

        _markClean: function (btn) {
            btn.disabled = true;
        },

        _markDirty: function (btn) {
            btn.disabled = false;
        }
    };

    /* ─── 通用 Section 折叠工具 ─── */
    window.DreaSection = {
        toggle: function (checkbox, bodyId) {
            var body = document.getElementById(bodyId);
            if (!body) return;
            if (checkbox.checked) {
                body.classList.remove('drea-section__body--collapsed');
            } else {
                body.classList.add('drea-section__body--collapsed');
            }
            // 同步箭头方向
            this._syncArrow(body);
        },

        /** 点按 header 切换折叠（无开关时） */
        toggleByHeader: function (header) {
            var section = header.closest('.drea-section');
            if (!section) return;
            var body = section.querySelector('.drea-section__body');
            if (!body) return;
            body.classList.toggle('drea-section__body--collapsed');
            this._syncArrow(body);
        },

        _syncArrow: function (body) {
            var section = body.closest('.drea-section');
            if (!section) return;
            var arrow = section.querySelector('.drea-section__arrow');
            if (!arrow) return;
            var collapsed = body.classList.contains('drea-section__body--collapsed');
            // 折叠：箭头朝右；展开：箭头朝下（CSS :not 控制）
            if (collapsed) {
                section.classList.add('drea-section--collapsed');
            } else {
                section.classList.remove('drea-section--collapsed');
            }
        },

        /** 自动为有折叠功能的 section 添加箭头指示器 */
        init: function () {
            document.querySelectorAll('.drea-section__header').forEach(function (header) {
                // 跳过已有箭头的
                if (header.querySelector('.drea-section__arrow')) return;
                // 跳过没有 body 的 section
                var section = header.closest('.drea-section');
                if (!section) return;
                var body = section.querySelector('.drea-section__body');
                if (!body) return;

                var arrow = document.createElement('span');
                arrow.className = 'drea-section__arrow';
                header.insertBefore(arrow, header.firstChild);

                // 如果当前折叠了，同步样式
                if (body.classList.contains('drea-section__body--collapsed')) {
                    section.classList.add('drea-section--collapsed');
                }

                // 点击 header（非 toggle 区域）切换折叠
                header.addEventListener('click', function (e) {
                    // 忽略点击 toggle/checkbox 的操作
                    if (e.target.closest('.drea-toggle')) return;
                    DreaSection.toggleByHeader(header);
                });
            });
        }
    };

    // DOM Ready 初始化 section 箭头
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', DreaSection.init);
    } else {
        DreaSection.init();
    }

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
        if (!moduleId) return; // 非模块开关，忽略

        var actionType = checkbox.checked ? 'activate' : 'deactivate';
        var card      = checkbox.closest('.drea-module-card');

        // 禁用交互 + loading 状态 (F-13)
        checkbox.disabled = true;
        var statusEl = card.querySelector('.drea-module-card__status');
        var originalText = statusEl ? statusEl.textContent : '';
        if (statusEl) {
            statusEl.textContent = checkbox.checked ? i18n.activating : i18n.deactivating;
        }

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
        .then(function (r) {
            return r.text().then(function (text) {
                try { return JSON.parse(text); }
                catch (e) {
                    console.error('[DREA Toolkit] toggle module JSON parse error, raw:', text.substring(0, 500));
                    throw e;
                }
            });
        })
        .then(function (data) {
            if (data.success) {
                // 更新卡片状态
                var isActive = data.data.active;
                card.classList.toggle('drea-module-card--active', isActive);
                if (statusEl) {
                    statusEl.textContent = isActive ? i18n.activated : i18n.deactivated;
                }
            } else {
                // 回滚开关
                checkbox.checked = !checkbox.checked;
                if (statusEl) statusEl.textContent = originalText;
                alert(data.data && data.data.message ? data.data.message : i18n.error);
            }
        })
        .catch(function () {
            checkbox.checked = !checkbox.checked;
            if (statusEl) statusEl.textContent = originalText;
            alert(i18n.error);
        })
        .finally(function () {
            checkbox.disabled = false;
        });
    });
})();
