<?php
/**
 * 站点增强 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$drea_settings = [
    'btt_enabled'         => (bool) get_option( 'drea_site_enhance_btt_enabled', false ),
    'btt_color'           => get_option( 'drea_site_enhance_btt_color', '#2271b1' ),
    'btt_icon_color'      => get_option( 'drea_site_enhance_btt_icon_color', '#ffffff' ),
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
    'avatar_fallback_enabled' => (bool) get_option( 'drea_site_enhance_avatar_fallback_enabled', false ),
    'avatar_fallback_url' => get_option( 'drea_site_enhance_avatar_fallback_url', '' ),
    'avatar_mirror'       => get_option( 'drea_site_enhance_avatar_mirror', 'cn.cravatar.com' ),
    'avatar_replace_gravatar' => (bool) get_option( 'drea_site_enhance_avatar_replace_gravatar', true ),
];

$drea_smtp_has_pass = (bool) get_option( 'drea_site_enhance_smtp_pass', '' );

$drea_default_feat_img_url = $drea_settings['default_feat_img_id'] ? wp_get_attachment_url( $drea_settings['default_feat_img_id'] ) : '';
$drea_avatar_fallback_url  = $drea_settings['avatar_fallback_url'];

/**
 * 输出 section body 的 collapsed 类
 *
 * @param bool $enabled 是否启用。
 */
function drea_se_body_class( bool $enabled ): void {
    echo $enabled ? '' : ' drea-section__body--collapsed';
}
?>
<div class="wrap drea-wrap drea-se-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__('Site Enhancement', 'dreamanual-toolkit' ); ?>
    </h1>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-se-toast-container"></div>

    <!-- 回到顶部 -->
    <div class="drea-section<?php echo $drea_settings['btt_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Back to Top Button', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Show back-to-top button after page scroll', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="btt-enabled" <?php checked( $drea_settings['btt_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['btt_enabled'] ); ?>" id="btt-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Position', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <select id="btt-position">
                        <option value="right-bottom" <?php selected( $drea_settings['btt_position'], 'right-bottom' ); ?>><?php echo esc_html__('Bottom Right', 'dreamanual-toolkit' ); ?></option>
                        <option value="left-bottom" <?php selected( $drea_settings['btt_position'], 'left-bottom' ); ?>><?php echo esc_html__('Bottom Left', 'dreamanual-toolkit' ); ?></option>
                        <option value="right-top" <?php selected( $drea_settings['btt_position'], 'right-top' ); ?>><?php echo esc_html__('Top Right', 'dreamanual-toolkit' ); ?></option>
                        <option value="left-top" <?php selected( $drea_settings['btt_position'], 'left-top' ); ?>><?php echo esc_html__('Top Left', 'dreamanual-toolkit' ); ?></option>
                    </select>
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Background Color', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="color" id="btt-color" value="<?php echo esc_attr( $drea_settings['btt_color'] ); ?>" class="drea-color-input">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Icon Color', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="color" id="btt-icon-color" value="<?php echo esc_attr( $drea_settings['btt_icon_color'] ); ?>" class="drea-color-input">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Preview', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <div class="drea-btt-preview">
                        <button type="button" id="drea-btt-preview-btn" class="drea-btt-preview__btn" style="background:<?php echo esc_attr( $drea_settings['btt_color'] ); ?>;color:<?php echo esc_attr( $drea_settings['btt_icon_color'] ); ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 维护模式 -->
    <div class="drea-section<?php echo $drea_settings['maintenance_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Maintenance Mode', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Show 503 maintenance page to non-admin visitors on frontend', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="maintenance-enabled" <?php checked( $drea_settings['maintenance_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['maintenance_enabled'] ); ?>" id="maintenance-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Notice Message', 'dreamanual-toolkit' ); ?>
                    <small><?php echo esc_html__('Leave empty to use default notice.', 'dreamanual-toolkit' ); ?></small>
                </div>
                <div class="drea-settings-row__action" style="width:100%;max-width:400px;">
                    <textarea id="maintenance-msg" rows="3" placeholder="<?php echo esc_attr__('Site is under maintenance, please visit later.', 'dreamanual-toolkit' ); ?>"><?php echo esc_textarea( $drea_settings['maintenance_msg'] ); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- 特色图片筛选器 -->
    <div class="drea-section<?php echo $drea_settings['feat_img_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Featured Image Filter', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Add missing/has featured image filter dropdown to post list', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="feat-img-enabled" <?php checked( $drea_settings['feat_img_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['feat_img_enabled'] ); ?>" id="feat-img-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('When enabled, adds a "Missing/Has Featured Image" filter dropdown on the post list page, making it easy to find posts without featured images.', 'dreamanual-toolkit' ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 特色图片列 -->
    <div class="drea-section<?php echo $drea_settings['feat_img_col_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Featured Image Column', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Show featured image thumbnail after checkbox in post list', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="feat-img-col-enabled" <?php checked( $drea_settings['feat_img_col_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['feat_img_col_enabled'] ); ?>" id="feat-img-col-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('When enabled, adds a featured image thumbnail column after the checkbox in the post list, visually showing each post\'s featured image status.', 'dreamanual-toolkit' ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 默认特色图片 -->
    <div class="drea-section<?php echo $drea_settings['default_feat_img_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Default Featured Image', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Posts without featured image automatically use this default image', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="default-feat-img-enabled" <?php checked( $drea_settings['default_feat_img_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['default_feat_img_enabled'] ); ?>" id="default-feat-img-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Default Image', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <div id="default-feat-img-preview" class="drea-se-img-preview">
                        <?php if ( $drea_default_feat_img_url ) : ?>
                            <img src="<?php echo esc_url( $drea_default_feat_img_url ); ?>">
                        <?php else : ?>
                            <span><?php echo esc_html__('Not Set', 'dreamanual-toolkit' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="default-feat-img-id" value="<?php echo esc_attr( $drea_settings['default_feat_img_id'] ); ?>">
                    <button type="button" class="drea-btn drea-btn--secondary" id="default-feat-img-select"><?php echo esc_html__('Select Image', 'dreamanual-toolkit' ); ?></button>
                    <button type="button" class="drea-btn drea-btn--secondary" id="default-feat-img-remove" style="<?php echo $drea_settings['default_feat_img_id'] ? '' : 'display:none;'; ?>"><?php echo esc_html__('Remove', 'dreamanual-toolkit' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- 摘要快速编辑 -->
    <div class="drea-section<?php echo $drea_settings['quickedit_excerpt_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Excerpt Quick Edit', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Add excerpt edit box in post quick-edit panel', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="quickedit-excerpt-enabled" <?php checked( $drea_settings['quickedit_excerpt_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['quickedit_excerpt_enabled'] ); ?>" id="quickedit-excerpt-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('When enabled, adds an excerpt edit box in the quick-edit panel of the post list, allowing excerpt editing without entering the full edit page.', 'dreamanual-toolkit' ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP 发信 -->
    <div class="drea-section<?php echo $drea_settings['smtp_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('SMTP Mail', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Send email via external SMTP server', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="smtp-enabled" <?php checked( $drea_settings['smtp_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['smtp_enabled'] ); ?>" id="smtp-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('SMTP Host', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="text" id="smtp-host" value="<?php echo esc_attr( $drea_settings['smtp_host'] ); ?>" class="drea-form-group__input" placeholder="smtp.example.com" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Port', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="number" id="smtp-port" value="<?php echo esc_attr( $drea_settings['smtp_port'] ); ?>" class="drea-form-group__input" min="1" max="65535" placeholder="465" style="width:100px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Encryption', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <select id="smtp-encryption">
                        <option value="ssl" <?php selected( $drea_settings['smtp_encryption'], 'ssl' ); ?>><?php echo esc_html__( 'SSL', 'dreamanual-toolkit' ); ?></option>
                        <option value="tls" <?php selected( $drea_settings['smtp_encryption'], 'tls' ); ?>><?php echo esc_html__( 'TLS', 'dreamanual-toolkit' ); ?></option>
                        <option value="none" <?php selected( $drea_settings['smtp_encryption'], 'none' ); ?>><?php echo esc_html__('None', 'dreamanual-toolkit' ); ?></option>
                    </select>
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Username', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="text" id="smtp-user" value="<?php echo esc_attr( $drea_settings['smtp_user'] ); ?>" class="drea-form-group__input" placeholder="user@example.com" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Password', 'dreamanual-toolkit' ); ?>
                    <?php if ( $drea_smtp_has_pass ) : ?>
                        <small><?php echo esc_html__('Password saved. Leave empty to keep current password.', 'dreamanual-toolkit' ); ?></small>
                    <?php endif; ?>
                </div>
                <div class="drea-settings-row__action">
                    <input type="password" id="smtp-pass" value="<?php echo $drea_smtp_has_pass ? '••••••••' : ''; ?>" class="drea-form-group__input" autocomplete="new-password" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('From Name', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="text" id="smtp-from-name" value="<?php echo esc_attr( $drea_settings['smtp_from_name'] ); ?>" class="drea-form-group__input" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('From Email', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <input type="email" id="smtp-from-email" value="<?php echo esc_attr( $drea_settings['smtp_from_email'] ); ?>" class="drea-form-group__input" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Test Mail', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <div class="drea-input-group">
                        <input type="email" id="smtp-test-to" class="drea-form-group__input drea-input-group__input" placeholder="<?php echo esc_attr__('Recipient Email', 'dreamanual-toolkit' ); ?>">
                        <button type="button" class="drea-btn drea-btn--secondary drea-input-group__btn" id="smtp-test-btn"><?php echo esc_html__('Send Test Email', 'dreamanual-toolkit' ); ?></button>
                    </div>
                    <span id="smtp-test-status"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 评论头像优化 -->
    <div class="drea-section<?php echo $drea_settings['avatar_fallback_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Comment Avatar', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Replace Gravatar with mirror source and custom default avatar for commenters', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="avatar-fallback-enabled" <?php checked( $drea_settings['avatar_fallback_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_se_body_class( $drea_settings['avatar_fallback_enabled'] ); ?>" id="avatar-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Mirror Source', 'dreamanual-toolkit' ); ?>
                    <small><?php echo esc_html__('Replace gravatar.com domain with this mirror (no http:// prefix)', 'dreamanual-toolkit' ); ?></small>
                </div>
                <div class="drea-settings-row__action">
                    <input type="text" id="avatar-mirror" value="<?php echo esc_attr( $drea_settings['avatar_mirror'] ); ?>" class="drea-form-group__input" placeholder="cn.cravatar.com" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Replace Gravatar', 'dreamanual-toolkit' ); ?>
                </div>
                <div class="drea-settings-row__action">
                    <label class="drea-toggle">
                        <input type="checkbox" id="avatar-replace-gravatar" <?php checked( $drea_settings['avatar_replace_gravatar'] ); ?>>
                        <span class="drea-toggle__slider"></span>
                    </label>
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html__('Default Avatar', 'dreamanual-toolkit' ); ?></div>
                <div class="drea-settings-row__action">
                    <div id="avatar-fallback-preview" class="drea-se-img-preview">
                        <?php if ( $drea_avatar_fallback_url ) : ?>
                            <img src="<?php echo esc_url( $drea_avatar_fallback_url ); ?>">
                        <?php else : ?>
                            <span><?php echo esc_html__('Not Set', 'dreamanual-toolkit' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="avatar-fallback-url" value="<?php echo esc_attr( $drea_settings['avatar_fallback_url'] ); ?>">
                    <button type="button" class="drea-btn drea-btn--secondary" id="avatar-fallback-select"><?php echo esc_html__('Select Image', 'dreamanual-toolkit' ); ?></button>
                    <button type="button" class="drea-btn drea-btn--secondary" id="avatar-fallback-remove" style="<?php echo $drea_settings['avatar_fallback_url'] ? '' : 'display:none;'; ?>"><?php echo esc_html__('Remove', 'dreamanual-toolkit' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <p class="submit">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-se-save-btn" disabled><?php echo esc_html__('Save Settings', 'dreamanual-toolkit' ); ?></button>
        <span class="drea-so-save-hint"><?php echo esc_html__('Some settings require page refresh to take effect', 'dreamanual-toolkit' ); ?></span>
    </p>
</div>
