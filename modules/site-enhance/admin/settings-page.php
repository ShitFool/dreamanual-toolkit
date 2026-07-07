<?php
/**
 * 站点增强 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$settings = [
    'btt_enabled'         => (bool) get_option( 'drea_site_enhance_btt_enabled', false ),
    'btt_color'           => get_option( 'drea_site_enhance_btt_color', '#2271b1' ),
    'btt_position'        => get_option( 'drea_site_enhance_btt_position', 'right-bottom' ),
    'maintenance_enabled' => (bool) get_option( 'drea_site_enhance_maintenance_enabled', false ),
    'maintenance_msg'     => get_option( 'drea_site_enhance_maintenance_msg', '' ),
    'feat_img_enabled'    => (bool) get_option( 'drea_site_enhance_feat_img_enabled', false ),
    'feat_img_col_enabled' => (bool) get_option( 'drea_site_enhance_feat_img_col_enabled', false ),
    'default_feat_img_enabled' => (bool) get_option( 'drea_site_enhance_default_feat_img_enabled', false ),
    'default_feat_img_id' => (int) get_option( 'drea_site_enhance_default_feat_img_id', 0 ),
    'quickedit_excerpt_enabled' => (bool) get_option( 'drea_site_enhance_quickedit_excerpt_enabled', false ),
    'smtp_enabled'        => (bool) get_option( 'drea_site_enhance_smtp_enabled', false ),
    'smtp_host'           => get_option( 'drea_site_enhance_smtp_host', '' ),
    'smtp_port'           => (int) get_option( 'drea_site_enhance_smtp_port', 465 ),
    'smtp_encryption'     => get_option( 'drea_site_enhance_smtp_encryption', 'ssl' ),
    'smtp_user'           => get_option( 'drea_site_enhance_smtp_user', '' ),
    'smtp_from_name'      => get_option( 'drea_site_enhance_smtp_from_name', '' ),
    'smtp_from_email'     => get_option( 'drea_site_enhance_smtp_from_email', '' ),
];

$smtp_has_pass = (bool) get_option( 'drea_site_enhance_smtp_pass', '' );

$default_feat_img_url = $settings['default_feat_img_id'] ? wp_get_attachment_url( $settings['default_feat_img_id'] ) : '';

/**
 * 输出 section body 的 collapsed 类
 *
 * @param bool $enabled 是否启用。
 */
function drea_se_body_class( bool $enabled ): void {
    echo $enabled ? '' : ' drea-se-section__body--collapsed';
}
?>
<div class="wrap drea-se-wrap">
    <h1><?php echo esc_html__( '站点增强', 'dreamanual-toolkit' ); ?></h1>

    <!-- Toast -->
    <div class="drea-se-toast-container" id="drea-se-toast-container"></div>

    <!-- 回到顶部 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '回到顶部按钮', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '页面滚动后显示回到顶部按钮', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="btt-enabled" <?php checked( $settings['btt_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['btt_enabled'] ); ?>" id="btt-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( '按钮颜色', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="color" id="btt-color" value="<?php echo esc_attr( $settings['btt_color'] ); ?>" style="width:60px;height:36px;padding:2px;">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '按钮位置', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <select id="btt-position">
                            <option value="right-bottom" <?php selected( $settings['btt_position'], 'right-bottom' ); ?>><?php echo esc_html__( '右下角', 'dreamanual-toolkit' ); ?></option>
                            <option value="left-bottom" <?php selected( $settings['btt_position'], 'left-bottom' ); ?>><?php echo esc_html__( '左下角', 'dreamanual-toolkit' ); ?></option>
                            <option value="right-top" <?php selected( $settings['btt_position'], 'right-top' ); ?>><?php echo esc_html__( '右上角', 'dreamanual-toolkit' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 维护模式 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '维护模式', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '非管理员访问前台时显示 503 维护页面', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="maintenance-enabled" <?php checked( $settings['maintenance_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['maintenance_enabled'] ); ?>" id="maintenance-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( '提示信息', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <textarea id="maintenance-msg" rows="3" class="large-text" placeholder="<?php echo esc_attr__( '网站正在维护，请稍后访问。', 'dreamanual-toolkit' ); ?>"><?php echo esc_textarea( $settings['maintenance_msg'] ); ?></textarea>
                        <p class="description"><?php echo esc_html__( '留空则使用默认提示信息。', 'dreamanual-toolkit' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 特色图片筛选器 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '特色图片筛选器', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '在文章列表添加缺失/有特色图筛选下拉框', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="feat-img-enabled" <?php checked( $settings['feat_img_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['feat_img_enabled'] ); ?>" id="feat-img-settings">
            <p class="description"><?php echo esc_html__( '启用后，在文章列表页添加「缺失特色图/有特色图」筛选下拉框，方便快速找到未设置特色图片的文章。', 'dreamanual-toolkit' ); ?></p>
        </div>
    </div>

    <!-- 特色图片列 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '特色图片列', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '在文章列表复选框后显示特色图缩略图', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="feat-img-col-enabled" <?php checked( $settings['feat_img_col_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['feat_img_col_enabled'] ); ?>" id="feat-img-col-settings">
            <p class="description"><?php echo esc_html__( '启用后，在文章列表的复选框后添加特色图片缩略图列，直观查看每篇文章的特色图片设置情况。', 'dreamanual-toolkit' ); ?></p>
        </div>
    </div>

    <!-- 默认特色图片 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '默认特色图片', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '未设置特色图的文章自动使用此默认图片', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="default-feat-img-enabled" <?php checked( $settings['default_feat_img_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['default_feat_img_enabled'] ); ?>" id="default-feat-img-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( '默认图片', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <div id="default-feat-img-preview" style="margin-bottom:8px;">
                            <?php if ( $default_feat_img_url ) : ?>
                                <img src="<?php echo esc_url( $default_feat_img_url ); ?>" style="max-width:300px;max-height:150px;border:1px solid #dcdcde;border-radius:4px;">
                            <?php else : ?>
                                <span style="color:#999;"><?php echo esc_html__( '未设置', 'dreamanual-toolkit' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="default-feat-img-id" value="<?php echo esc_attr( $settings['default_feat_img_id'] ); ?>">
                        <button type="button" class="button" id="default-feat-img-select"><?php echo esc_html__( '选择图片', 'dreamanual-toolkit' ); ?></button>
                        <button type="button" class="button" id="default-feat-img-remove" style="<?php echo $settings['default_feat_img_id'] ? '' : 'display:none;'; ?>"><?php echo esc_html__( '移除', 'dreamanual-toolkit' ); ?></button>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 摘要快速编辑 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( '摘要快速编辑', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '在文章快速编辑面板中增加摘要编辑框', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="quickedit-excerpt-enabled" <?php checked( $settings['quickedit_excerpt_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['quickedit_excerpt_enabled'] ); ?>" id="quickedit-excerpt-settings">
            <p class="description"><?php echo esc_html__( '启用后，在文章列表的快速编辑面板中增加摘要编辑框，无需进入编辑页面即可修改摘要。', 'dreamanual-toolkit' ); ?></p>
        </div>
    </div>

    <!-- SMTP 发信 -->
    <div class="drea-se-section">
        <div class="drea-se-section__header">
            <div class="drea-se-section__title">
                <h2><?php echo esc_html__( 'SMTP 发信', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-se-section__desc"><?php echo esc_html__( '通过外部 SMTP 服务器发送邮件', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-se-toggle">
                <input type="checkbox" id="smtp-enabled" <?php checked( $settings['smtp_enabled'] ); ?>>
                <span class="drea-se-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-se-section__body<?php drea_se_body_class( $settings['smtp_enabled'] ); ?>" id="smtp-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( 'SMTP 主机', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="smtp-host" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>" class="regular-text" placeholder="smtp.example.com">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '端口', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="number" id="smtp-port" value="<?php echo esc_attr( $settings['smtp_port'] ); ?>" class="small-text" min="1" max="65535" placeholder="465">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '加密方式', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <select id="smtp-encryption">
                            <option value="ssl" <?php selected( $settings['smtp_encryption'], 'ssl' ); ?>><?php echo esc_html__( 'SSL', 'dreamanual-toolkit' ); ?></option>
                            <option value="tls" <?php selected( $settings['smtp_encryption'], 'tls' ); ?>><?php echo esc_html__( 'TLS', 'dreamanual-toolkit' ); ?></option>
                            <option value="none" <?php selected( $settings['smtp_encryption'], 'none' ); ?>><?php echo esc_html__( '无', 'dreamanual-toolkit' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '用户名', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="smtp-user" value="<?php echo esc_attr( $settings['smtp_user'] ); ?>" class="regular-text" placeholder="user@example.com">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '密码', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="password" id="smtp-pass" value="<?php echo $smtp_has_pass ? '••••••••' : ''; ?>" class="regular-text" autocomplete="new-password">
                        <?php if ( $smtp_has_pass ) : ?>
                            <p class="description"><?php echo esc_html__( '已保存密码。留空则保留现有密码。', 'dreamanual-toolkit' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '发件人名称', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="smtp-from-name" value="<?php echo esc_attr( $settings['smtp_from_name'] ); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '发件人邮箱', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="email" id="smtp-from-email" value="<?php echo esc_attr( $settings['smtp_from_email'] ); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '测试发信', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="email" id="smtp-test-to" class="regular-text" placeholder="<?php echo esc_attr__( '收件邮箱地址', 'dreamanual-toolkit' ); ?>">
                        <button type="button" class="button" id="smtp-test-btn"><?php echo esc_html__( '发送测试邮件', 'dreamanual-toolkit' ); ?></button>
                        <span id="smtp-test-status" style="margin-left:8px;"></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p class="submit">
        <button type="button" class="button button-primary" id="drea-se-save-btn"><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
    </p>
</div>
