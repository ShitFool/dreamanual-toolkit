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
        <?php echo esc_html__('Role Manager', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__('Manage WordPress user roles and capabilities. Click a role to view details and edit capabilities.', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-rm-toast-container"></div>

    <!-- 操作栏 -->
    <div class="drea-rm-actions">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-add-btn"><?php echo esc_html__('Add Role', 'dreamanual-toolkit' ); ?></button>
    </div>

    <!-- 角色列表 -->
    <div class="drea-rm-roles-panel">
        <table class="wp-list-table widefat fixed striped drea-rm-roles-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Role', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__('Key', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__('Users', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__('Capabilities', 'dreamanual-toolkit' ); ?></th>
                    <th><?php echo esc_html__('Actions', 'dreamanual-toolkit' ); ?></th>
                </tr>
            </thead>
            <tbody id="drea-rm-roles-tbody">
                <tr><td colspan="5" class="drea-text-center-muted"><?php echo esc_html__('Loading...', 'dreamanual-toolkit' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <!-- 添加/复制角色对话框 -->
    <div class="drea-rm-dialog-overlay" id="drea-rm-dialog-overlay" style="display:none;">
        <div class="drea-rm-dialog">
            <h2 id="drea-rm-dialog-title"><?php echo esc_html__('Add Role', 'dreamanual-toolkit' ); ?></h2>
            <div class="drea-rm-dialog-body">
                <div class="drea-rm-form-group">
                    <label for="drea-rm-dialog-name"><?php echo esc_html__('Role Name', 'dreamanual-toolkit' ); ?></label>
                    <input type="text" id="drea-rm-dialog-name" class="regular-text" placeholder="<?php echo esc_attr__('e.g. Editorial Assistant', 'dreamanual-toolkit' ); ?>">
                </div>
                <div class="drea-rm-form-group">
                    <label for="drea-rm-dialog-slug"><?php echo esc_html__('Role Key', 'dreamanual-toolkit' ); ?></label>
                    <input type="text" id="drea-rm-dialog-slug" class="regular-text" placeholder="<?php echo esc_attr__('e.g. editorial_assistant', 'dreamanual-toolkit' ); ?>">
                    <p class="description"><?php echo esc_html__('Use lowercase letters and underscores. Cannot be modified after creation.', 'dreamanual-toolkit' ); ?></p>
                </div>
                <input type="hidden" id="drea-rm-dialog-source-role" value="">
            </div>
            <div class="drea-rm-dialog-footer">
                <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-dialog-confirm"><?php echo esc_html__('Confirm', 'dreamanual-toolkit' ); ?></button>
                <button type="button" class="drea-btn drea-btn--secondary" id="drea-rm-dialog-cancel"><?php echo esc_html__('Cancel', 'dreamanual-toolkit' ); ?></button>
            </div>
        </div>
    </div>

    <!-- 角色详情/能力编辑面板 -->
    <div class="drea-rm-detail-panel" id="drea-rm-detail-panel" style="display:none;">
        <div class="drea-rm-detail-header">
            <h2 id="drea-rm-detail-title"></h2>
            <button type="button" class="drea-btn drea-btn--secondary" id="drea-rm-detail-close"><?php echo esc_html__('Close', 'dreamanual-toolkit' ); ?></button>
        </div>
        <div class="drea-rm-detail-body">
            <h3 class="drea-rm-caps-heading"><?php echo esc_html__('Capability Matrix', 'dreamanual-toolkit' ); ?></h3>
            <p class="description drea-rm-caps-desc"><?php echo esc_html__('Check the capabilities this role should have.', 'dreamanual-toolkit' ); ?></p>
            <div id="drea-rm-caps-grid" class="drea-rm-caps-grid"></div>
            <p class="submit">
                <button type="button" class="drea-btn drea-btn--primary" id="drea-rm-save-caps"><?php echo esc_html__('Save Capabilities', 'dreamanual-toolkit' ); ?></button>
            </p>
        </div>
    </div>
</div>
