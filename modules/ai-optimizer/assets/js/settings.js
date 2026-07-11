/**
 * AI 优化模块 — 设置页脚本 (Vanilla JS, no jQuery)
 */
(function () {
    'use strict';

    if (typeof dreaAi === 'undefined') return;

    var i18n    = dreaAi.i18n;
    var ajaxUrl = dreaAi.ajaxUrl;
    var nonce   = dreaAi.nonce;

    var modelMap = {
        kimi: [
            { value: 'kimi-k2.6', label: 'kimi-k2.6' },
            { value: 'moonshot-v1-8k', label: 'moonshot-v1-8k' },
            { value: 'moonshot-v1-32k', label: 'moonshot-v1-32k' },
            { value: 'moonshot-v1-128k', label: 'moonshot-v1-128k' }
        ],
        openai: [
            { value: 'gpt-4o-mini', label: 'gpt-4o-mini' },
            { value: 'gpt-4o', label: 'gpt-4o' },
            { value: 'gpt-3.5-turbo', label: 'gpt-3.5-turbo' }
        ],
        claude: [
            { value: 'claude-3-haiku-20240307', label: 'claude-3-haiku' },
            { value: 'claude-3-sonnet-20240229', label: 'claude-3-sonnet' },
            { value: 'claude-3-opus-20240229', label: 'claude-3-opus' }
        ],
        deepseek: [
            { value: 'deepseek-chat', label: 'DeepSeek-V3' },
            { value: 'deepseek-reasoner', label: 'DeepSeek-R1' }
        ]
    };

    function $(sel) { return document.querySelector(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type) {
        type = type || 'info';
        DreaToast.show(message, type, 'drea-ai-toast-container');
    }

    function updateModelOptions() {
        var provider = $('#ai-provider').value;
        var models = modelMap[provider] || [];
        var modelSelect = $('#ai-model');
        var currentModel = modelSelect.value;

        modelSelect.innerHTML = '';
        models.forEach(function (m) {
            var opt = document.createElement('option');
            opt.value = m.value;
            opt.textContent = m.label;
            modelSelect.appendChild(opt);
        });

        var hasCurrent = models.some(function (m) { return m.value === currentModel; });
        modelSelect.value = hasCurrent ? currentModel : (models.length > 0 ? models[0].value : '');
    }

    function loadSettings() {
        var formData = new FormData();
        formData.append('action', 'drea_ai_get_settings');
        formData.append('nonce', nonce);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) return;
                var d = res.data;
                $('#ai-provider').value = d.provider;
                updateModelOptions();

                var models = modelMap[d.provider] || [];
                var hasModel = models.some(function (m) { return m.value === d.model; });
                $('#ai-model').value = hasModel ? d.model : (models.length > 0 ? models[0].value : '');

                if (d.api_key) $('#ai-api-key').value = d.api_key;
                if (d.opt_tags !== undefined) $('#opt-tags').checked = d.opt_tags;
                if (d.opt_slug !== undefined) $('#opt-slug').checked = d.opt_slug;
                if (d.opt_excerpt !== undefined) $('#opt-excerpt').checked = d.opt_excerpt;
                if (d.tag_limit) $('#tag-limit').value = d.tag_limit;
                if (d.excerpt_length) $('#excerpt-length').value = d.excerpt_length;
                if (d.excerpt_prompt !== undefined) $('#excerpt-prompt').value = d.excerpt_prompt;
            });
    }

    function saveSettings() {
        var btn = $('#save-settings-btn');
        var spinner = btn.parentElement.querySelector('.spinner');
        btn.disabled = true;
        if (spinner) spinner.style.visibility = 'visible';

        var formData = new FormData();
        formData.append('action', 'drea_ai_save_settings');
        formData.append('nonce', nonce);
        formData.append('provider', $('#ai-provider').value);
        formData.append('model', $('#ai-model').value);
        formData.append('api_key', ($('#ai-api-key').value || '').trim());
        formData.append('opt_tags', $('#opt-tags').checked ? 1 : 0);
        formData.append('opt_slug', $('#opt-slug').checked ? 1 : 0);
        formData.append('opt_excerpt', $('#opt-excerpt').checked ? 1 : 0);
        formData.append('tag_limit', parseInt($('#tag-limit').value) || 5);
        formData.append('excerpt_length', parseInt($('#excerpt-length').value) || 100);
        formData.append('excerpt_prompt', ($('#excerpt-prompt').value || '').trim());

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showToast(i18n.settingsSaved, 'success');
                } else {
                    showToast(i18n.saveFailed + (res.data || i18n.unknownError), 'error');
                }
                btn.disabled = false;
                if (spinner) spinner.style.visibility = 'hidden';
            })
            .catch(function () {
                showToast(i18n.networkError, 'error');
                btn.disabled = false;
                if (spinner) spinner.style.visibility = 'hidden';
            });
    }

    function init() {
        var provider = $('#ai-provider');
        if (provider) provider.addEventListener('change', updateModelOptions);

        var saveBtn = $('#save-settings-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveSettings);

        // 生成选项折叠/展开
        var toggle = $('#generation-options-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var panel = $('#generation-options-panel');
                var icon = toggle.querySelector('.dashicons');
                if (!panel) return;
                var isHidden = panel.style.display === 'none';
                panel.style.display = isHidden ? '' : 'none';
                if (icon) {
                    icon.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
                }
                var hint = toggle.querySelector('span:last-child');
                if (hint) {
                    hint.textContent = isHidden ? '（点击收起）' : '（点击展开）';
                }
            });
        }

        loadSettings();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
