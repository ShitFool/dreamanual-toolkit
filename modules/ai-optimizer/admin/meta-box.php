<?php
/**
 * AI 优化 — Meta Box 模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="drea-ai-meta-box-wrap">
    <div class="drea-ai-meta-section">
        <p class="description"><?php echo esc_html__('AI automatically generates optimization suggestions based on post title and content.', 'dreamanual-toolkit' ); ?></p>
        <button type="button" class="button button-primary drea-ai-meta-generate-btn" id="drea-ai-meta-generate-btn" style="width:100%;">
            <?php echo esc_html__('Generate AI Suggestions', 'dreamanual-toolkit' ); ?>
        </button>
        <span class="spinner" style="float:none;margin:10px auto 0;display:block;visibility:hidden;"></span>
    </div>

    <div class="drea-ai-meta-suggestion" id="drea-ai-meta-suggestion" style="display:none;">
        <div class="drea-ai-meta-field">
            <label><strong><?php echo esc_html__('Tags', 'dreamanual-toolkit' ); ?></strong></label>
            <div class="drea-ai-meta-tags" id="drea-ai-meta-tags"></div>
            <input type="hidden" id="drea-ai-meta-tags-input" name="ai_meta_tags">
        </div>

        <div class="drea-ai-meta-field">
            <label><strong><?php echo esc_html__( 'Slug', 'dreamanual-toolkit' ); ?></strong></label>
            <input type="text" id="drea-ai-meta-slug" class="widefat" readonly>
        </div>

        <div class="drea-ai-meta-field">
            <label><strong><?php echo esc_html__('Excerpt', 'dreamanual-toolkit' ); ?></strong></label>
            <textarea id="drea-ai-meta-excerpt" class="widefat" rows="4" style="font-size:12px;line-height:1.5;"></textarea>
        </div>

        <div class="drea-ai-meta-actions">
            <button type="button" class="button button-primary" id="drea-ai-meta-apply-btn" style="width:100%;"><?php echo esc_html__('Apply Changes', 'dreamanual-toolkit' ); ?></button>
            <button type="button" class="button button-link" id="drea-ai-meta-regenerate-btn" style="width:100%;margin-top:8px;text-align:center;"><?php echo esc_html__('Regenerate', 'dreamanual-toolkit' ); ?></button>
        </div>
    </div>
</div>
