/**
 * AI 优化模块 — Meta Box 脚本 (Vanilla JS, no jQuery)
 */
(function () {
    'use strict';

    if (typeof dreaAi === 'undefined') return;

    var i18n    = dreaAi.i18n;
    var ajaxUrl = dreaAi.ajaxUrl;
    var nonce   = dreaAi.nonce;

    var suggestion = null;
    var postId = 0;

    function $(sel) { return document.querySelector(sel); }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showSpinner(btn, show) {
        if (!btn) return;
        var spinner = btn.parentElement.querySelector('.spinner');
        if (show) {
            btn.disabled = true;
            if (spinner) spinner.style.visibility = 'visible';
        } else {
            btn.disabled = false;
            if (spinner) spinner.style.visibility = 'hidden';
        }
    }

    function showMessage(message, type) {
        var wrap = document.querySelector('.drea-ai-meta-box-wrap');
        if (!wrap) return;
        var existing = wrap.querySelector('.drea-ai-meta-message');
        if (existing) existing.remove();

        var cssClass = type === 'error' ? 'notice-error' : 'notice-info';
        var msg = document.createElement('div');
        msg.className = 'notice ' + cssClass + ' drea-ai-meta-message';
        msg.style.cssText = 'margin:10px 0;padding:8px 12px;';
        msg.innerHTML = '<p>' + escapeHtml(message) + '</p>';
        wrap.prepend(msg);

        if (type === 'error') {
            setTimeout(function () {
                msg.style.transition = 'opacity .5s';
                msg.style.opacity = '0';
                setTimeout(function () { msg.remove(); }, 500);
            }, 8000);
        }
    }

    function generate() {
        if (!postId) {
            showMessage(i18n.pleaseSaveDraft, 'error');
            return;
        }

        var btn = $('#drea-ai-meta-generate-btn');
        showSpinner(btn, true);
        var suggestionEl = $('#drea-ai-meta-suggestion');
        if (suggestionEl) suggestionEl.style.display = 'none';

        var formData = new FormData();
        formData.append('action', 'drea_ai_generate_single');
        formData.append('nonce', nonce);
        formData.append('post_id', postId);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                showSpinner(btn, false);
                if (res.success) {
                    suggestion = res.data;
                    renderSuggestion();
                } else {
                    showMessage(res.data || i18n.unknownError, 'error');
                }
            })
            .catch(function (err) {
                showSpinner(btn, false);
                showMessage(i18n.networkError, 'error');
            });
    }

    function renderSuggestion() {
        if (!suggestion) return;
        var s = suggestion;

        // Tags
        var tagsEl = $('#drea-ai-meta-tags');
        var tagsInput = $('#drea-ai-meta-tags-input');
        if (s.tags && s.tags.length) {
            tagsEl.innerHTML = s.tags.map(function (t) {
                return '<span class="drea-ai-tag-badge drea-ai-tag-badge--new">' + escapeHtml(t) + '</span>';
            }).join('');
            tagsInput.value = s.tags.join(',');
        } else {
            tagsEl.innerHTML = '<span class="drea-ai-tag-badge">' + i18n.noTagsGenerated + '</span>';
            tagsInput.value = '';
        }

        // Slug
        var slugEl = $('#drea-ai-meta-slug');
        slugEl.value = s.slug || i18n.notGenerated;

        // Excerpt
        var excerptEl = $('#drea-ai-meta-excerpt');
        excerptEl.value = s.excerpt || i18n.notGenerated;

        var suggestionEl = $('#drea-ai-meta-suggestion');
        if (suggestionEl) suggestionEl.style.display = '';
    }

    function apply() {
        if (!suggestion || !postId) return;

        var btn = $('#drea-ai-meta-apply-btn');
        btn.disabled = true;
        btn.textContent = i18n.applying;

        var formData = new FormData();
        formData.append('action', 'drea_ai_apply');
        formData.append('nonce', nonce);
        formData.append('post_id', postId);
        (suggestion.tags || []).forEach(function (t) {
            formData.append('tags[]', t);
        });
        formData.append('slug', suggestion.slug || '');
        var excerptEl = $('#drea-ai-meta-excerpt');
        formData.append('excerpt', excerptEl ? excerptEl.value : '');

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    // 应用成功后刷新页面，确保表单从数据库重新加载
                    // 避免用户点"发布/更新"时旧表单值覆盖 AJAX 写入的数据
                    showMessage(i18n.applied, 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    showMessage(i18n.applyFailed + (res.data || i18n.unknownError), 'error');
                    btn.disabled = false;
                    btn.textContent = i18n.applyChanges;
                }
            })
            .catch(function () {
                showMessage(i18n.applyFailed + i18n.networkError, 'error');
                btn.disabled = false;
                btn.textContent = i18n.applyChanges;
            });
    }

    function init() {
        var postIdInput = document.querySelector('#post_ID');
        postId = postIdInput ? parseInt(postIdInput.value) || 0 : 0;

        var genBtn = $('#drea-ai-meta-generate-btn');
        var regenBtn = $('#drea-ai-meta-regenerate-btn');
        var applyBtn = $('#drea-ai-meta-apply-btn');

        if (genBtn) genBtn.addEventListener('click', generate);
        if (regenBtn) regenBtn.addEventListener('click', generate);
        if (applyBtn) applyBtn.addEventListener('click', apply);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
