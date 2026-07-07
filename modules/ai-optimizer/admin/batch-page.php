<?php
/**
 * AI 优化 — 批量处理页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap drea-ai-wrap">
    <h1><?php echo esc_html__( 'AI 优化 — 批量处理', 'dreamanual-toolkit' ); ?> <span style="font-size:12px;color:#666;font-weight:normal;">v<?php echo esc_html( DREA_VERSION ); ?></span></h1>
    <p class="description"><?php echo esc_html__( '选择文章自动生成标签、Slug 和摘要。请先在设置中配置 API Key。', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast 容器 -->
    <div class="drea-ai-toast-container" id="drea-ai-toast-container"></div>

    <!-- 进度面板 -->
    <div class="drea-ai-progress-panel" style="display:none;">
        <div class="drea-ai-progress-bar">
            <div class="drea-ai-progress-fill" style="width:0%"></div>
        </div>
        <p class="drea-ai-progress-text"><?php echo esc_html__( '处理中...', 'dreamanual-toolkit' ); ?></p>
    </div>

    <!-- 待应用面板 -->
    <div class="drea-ai-apply-panel" style="display:none;">
        <div class="drea-ai-apply-header">
            <h3><?php echo esc_html__( '待应用更改', 'dreamanual-toolkit' ); ?> (<span id="pending-count">0</span>)</h3>
            <button type="button" class="button button-primary" id="apply-all-btn"><?php echo esc_html__( '应用所有更改', 'dreamanual-toolkit' ); ?></button>
            <span class="spinner" style="float:none;margin-top:0;"></span>
        </div>
        <div class="drea-ai-apply-table-wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( '文章标题', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '当前标签', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '新标签', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '当前 Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '新 Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '当前摘要', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '新摘要', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '操作', 'dreamanual-toolkit' ); ?></th>
                    </tr>
                </thead>
                <tbody id="drea-ai-apply-tbody"></tbody>
            </table>
        </div>
    </div>

    <!-- 文章列表 -->
    <div class="drea-ai-posts-panel">
        <div class="drea-ai-posts-header">
            <h2><?php echo esc_html__( '文章列表', 'dreamanual-toolkit' ); ?></h2>
            <div class="drea-ai-posts-actions">
                <label style="margin-right:15px;">
                    <?php echo esc_html__( '分类筛选:', 'dreamanual-toolkit' ); ?>
                    <select id="category-filter">
                        <option value=""><?php echo esc_html__( '全部', 'dreamanual-toolkit' ); ?></option>
                        <?php
                        $categories = get_categories( [ 'hide_empty' => false ] );
                        foreach ( $categories as $cat ) {
                            echo '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
                        }
                        ?>
                    </select>
                </label>
                <label style="margin-right:10px;"><input type="checkbox" id="drea-toggle-tags" checked> <?php echo esc_html__( '标签', 'dreamanual-toolkit' ); ?></label>
                <label style="margin-right:10px;"><input type="checkbox" id="drea-toggle-slug" checked> <?php echo esc_html__( '别名', 'dreamanual-toolkit' ); ?></label>
                <label style="margin-right:15px;"><input type="checkbox" id="drea-toggle-excerpt"> <?php echo esc_html__( '摘要', 'dreamanual-toolkit' ); ?></label>
                <span style="color:#ccc;margin-right:10px;">|</span>
                <label style="margin-right:15px;"><input type="checkbox" id="select-all"> <?php echo esc_html__( '全选', 'dreamanual-toolkit' ); ?></label>
                <button type="button" class="button" id="generate-selected-btn" disabled><?php echo esc_html__( '生成 AI 建议', 'dreamanual-toolkit' ); ?></button>
                <span class="spinner" style="float:none;margin-top:0;"></span>
            </div>
        </div>
        <div class="drea-ai-pagination drea-ai-pagination-top" id="drea-ai-pagination-top"></div>
        <div class="drea-ai-posts-table-wrap">
            <table class="wp-list-table widefat fixed striped drea-ai-posts-table">
                <thead>
                    <tr>
                        <th class="column-cb"><input type="checkbox" id="select-all-header"></th>
                        <th><?php echo esc_html__( '标题', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '当前标签', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '当前 Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( 'AI 标签', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( 'AI Slug', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( 'AI 摘要', 'dreamanual-toolkit' ); ?></th>
                        <th><?php echo esc_html__( '操作', 'dreamanual-toolkit' ); ?></th>
                    </tr>
                </thead>
                <tbody id="drea-ai-posts-tbody"></tbody>
            </table>
        </div>
        <div class="drea-ai-pagination" id="drea-ai-pagination"></div>
    </div>
</div>
