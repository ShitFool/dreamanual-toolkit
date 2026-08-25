<?php
/**
 * AI 优化 — 批量处理页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap drea-wrap drea-ai-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__('AI Optimizer — Batch Process', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__('Select posts to auto-generate tags, slug, and excerpt. Please configure API Key in settings first.', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast 容器 -->
    <div class="drea-toast-container" id="drea-ai-toast-container"></div>

    <!-- 进度面板 -->
    <div class="drea-ai-progress-panel" style="display:none;">
        <div class="drea-ai-progress-bar">
            <div class="drea-ai-progress-fill" style="width:0%"></div>
        </div>
        <p class="drea-ai-progress-text"><?php echo esc_html__('Processing...', 'dreamanual-toolkit' ); ?></p>
    </div>

    <!-- 待应用面板 -->
    <div class="drea-ai-apply-panel" style="display:none;">
        <div class="drea-ai-apply-header">
            <h3><?php echo esc_html__('Pending Changes', 'dreamanual-toolkit' ); ?> (<span id="pending-count">0</span>)</h3>
            <button type="button" class="drea-btn drea-btn--primary" id="apply-all-btn"><?php echo esc_html__('Apply All Changes', 'dreamanual-toolkit' ); ?></button>
            <span class="spinner"></span>
        </div>
        <div class="drea-ai-apply-table-wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Post Title', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Current Tags', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('New Tags', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Current Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('New Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Current Excerpt', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('New Excerpt', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Actions', 'dreamanual-toolkit' ); ?></th>
                    </tr>
                </thead>
                <tbody id="drea-ai-apply-tbody"></tbody>
            </table>
        </div>
    </div>

    <!-- 文章列表 -->
    <div class="drea-ai-posts-panel">
        <div class="drea-ai-posts-header">
            <h2><?php echo esc_html__('Post List', 'dreamanual-toolkit' ); ?></h2>
            <div class="drea-ai-posts-actions">
                <label class="drea-cb-label">
                    <?php echo esc_html__('Category Filter:', 'dreamanual-toolkit' ); ?>
                    <select id="category-filter">
                        <option value=""><?php echo esc_html__('All', 'dreamanual-toolkit' ); ?></option>
                        <?php
                        $drea_categories = get_categories( [ 'hide_empty' => false ] );
                        foreach ( $drea_categories as $cat ) {
                            echo '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
                        }
                        ?>
                    </select>
                </label>
                <label class="drea-cb-label"><input type="checkbox" id="drea-toggle-tags" checked> <?php echo esc_html__('Tags', 'dreamanual-toolkit' ); ?></label>
                <label class="drea-cb-label"><input type="checkbox" id="drea-toggle-slug" checked> <?php echo esc_html__('Slug', 'dreamanual-toolkit' ); ?></label>
                <label class="drea-cb-label"><input type="checkbox" id="drea-toggle-excerpt"> <?php echo esc_html__('Excerpt', 'dreamanual-toolkit' ); ?></label>
                <span class="drea-ai-separator">|</span>
                <label class="drea-cb-label"><input type="checkbox" id="select-all"> <?php echo esc_html__('Select All', 'dreamanual-toolkit' ); ?></label>
                <button type="button" class="drea-btn drea-btn--secondary" id="generate-selected-btn" disabled><?php echo esc_html__('Generate AI Suggestions', 'dreamanual-toolkit' ); ?></button>
                <span class="spinner"></span>
            </div>
        </div>
        <div class="drea-ai-pagination drea-ai-pagination-top" id="drea-ai-pagination-top"></div>
        <div class="drea-ai-posts-table-wrap">
            <table class="wp-list-table widefat fixed striped drea-ai-posts-table">
                <thead>
                    <tr>
                        <th class="column-cb"><input type="checkbox" id="select-all-header"></th>
                        <th><?php echo esc_html__('Title', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Current Tags', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Current Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('AI Tags', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( 'AI Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('AI Excerpt', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__('Actions', 'dreamanual-toolkit' ); ?></th>
                    </tr>
                </thead>
                <tbody id="drea-ai-posts-tbody"></tbody>
            </table>
        </div>
        <div class="drea-ai-pagination" id="drea-ai-pagination"></div>
    </div>
</div>
