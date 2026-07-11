<?php
/**
 * 角色管理 — 页面模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap drea-wrap drea-rm-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__( '角色管理', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__( '管理 WordPress 用户角色和能力。点击角色查看详情和编辑能力。', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-rm-toast-container"></div>

    <!-- 操作栏 -->
    <div class="drea-rm-actions">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-add-btn"><?php echo esc_html__( '添加角色', 'dreamanual-toolkit' ); ?></button>
    </div>

    <!-- 角色列表 -->
    <div class="drea-rm-roles-panel">
        <table class="wp-list-table widefat fixed striped drea-rm-roles-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__( '角色', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__( '标识', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__( '用户数', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__( '能力数', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__( '操作', 'dreamanual-toolkit' ); ?></th>
                </tr>
            </thead>
            <tbody id="drea-rm-roles-tbody">
                <tr><td colspan="5" style="text-align:center;color:var(--drea-text-tertiary);"><?php echo esc_html__( '加载中...', 'dreamanual-toolkit' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <!-- 添加/复制角色对话框 -->
    <div class="drea-rm-dialog-overlay" id="drea-rm-dialog-overlay" style="display:none;">
        <div class="drea-rm-dialog">
            <h2 id="drea-rm-dialog-title"><?php echo esc_html__( '添加角色', 'dreamanual-toolkit' ); ?></h2>
            <div class="drea-rm-dialog-body">
                <div class="drea-rm-form-group">
                    <label for="drea-rm-dialog-name"><?php echo esc_html__( '角色名称', 'dreamanual-toolkit' ); ?></label>
                    <input type="text" id="drea-rm-dialog-name" class="regular-text" placeholder="<?php echo esc_attr__( '如: 编辑助理', 'dreamanual-toolkit' ); ?>">
                </div>
                <div class="drea-rm-form-group">
                    <label for="drea-rm-dialog-slug"><?php echo esc_html__( '角色标识', 'dreamanual-toolkit' ); ?></label>
                    <input type="text" id="drea-rm-dialog-slug" class="regular-text" placeholder="<?php echo esc_attr__( '如: editorial_assistant', 'dreamanual-toolkit' ); ?>">
                    <p class="description"><?php echo esc_html__( '使用小写字母和下划线，不可修改。', 'dreamanual-toolkit' ); ?></p>
                </div>
                <input type="hidden" id="drea-rm-dialog-source-role" value="">
            </div>
            <div class="drea-rm-dialog-footer">
                <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-dialog-confirm"><?php echo esc_html__( '确认', 'dreamanual-toolkit' ); ?></button>
                <button type="button" class="drea-btn drea-btn--secondary" id="drea-rm-dialog-cancel"><?php echo esc_html__( '取消', 'dreamanual-toolkit' ); ?></button>
            </div>
        </div>
    </div>

    <!-- 角色详情/能力编辑面板 -->
    <div class="drea-rm-detail-panel" id="drea-rm-detail-panel" style="display:none;">
        <div class="drea-rm-detail-header">
            <h2 id="drea-rm-detail-title"></h2>
            <button type="button" class="drea-btn drea-btn--secondary" id="drea-rm-detail-close"><?php echo esc_html__( '关闭', 'dreamanual-toolkit' ); ?></button>
        </div>
        <div class="drea-rm-detail-body">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:4px;"><?php echo esc_html__( '能力矩阵', 'dreamanual-toolkit' ); ?></h3>
            <p class="description" style="margin-bottom:var(--drea-space-2);"><?php echo esc_html__( '勾选该角色应具备的能力。', 'dreamanual-toolkit' ); ?></p>
            <div id="drea-rm-caps-grid" class="drea-rm-caps-grid"></div>
            <p class="submit">
                <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-save-caps"><?php echo esc_html__( '保存能力', 'dreamanual-toolkit' ); ?></button>
            </p>
        </div>
    </div>
</div>
