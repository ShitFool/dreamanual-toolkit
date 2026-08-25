<?php
/**
 * 搜索推送 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$drea_settings = [
    'baidu_enabled' => (bool) get_option( 'drea_search_push_baidu_enabled', false ),
    'baidu_token'   => get_option( 'drea_search_push_baidu_token', '' ),
    'baidu_site'    => get_option( 'drea_search_push_baidu_site', '' ),
    'bing_enabled'  => (bool) get_option( 'drea_search_push_bing_enabled', false ),
    'bing_key'      => get_option( 'drea_search_push_bing_key', '' ),
];

/**
 * 输出 section body 的 collapsed 类
 */
function drea_sp_body_class( bool $enabled ): void {
    echo $enabled ? '' : ' drea-section__body--collapsed';
}
?>
<div class="wrap drea-wrap drea-sp-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__('Search Push', 'dreamanual-toolkit' ); ?>
    </h1>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-sp-toast-container"></div>

    <!-- 百度推送 -->
    <div class="drea-section<?php echo $drea_settings['baidu_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Baidu Push', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Auto-push links to Baidu Indexing on post publish', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="baidu-enabled" <?php checked( $drea_settings['baidu_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_sp_body_class( $drea_settings['baidu_enabled'] ); ?>" id="baidu-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Site Domain', 'dreamanual-toolkit' ); ?>
                    <small><?php echo esc_html__('Site domain verified on Baidu Search Resource Platform. If different from current site domain, fill in manually.', 'dreamanual-toolkit' ); ?></small>
                </div>
                <div class="drea-settings-row__action">
                    <input type="text" id="baidu-site" value="<?php echo esc_attr( $drea_settings['baidu_site'] ); ?>" class="drea-form-group__input" placeholder="<?php echo esc_attr__('e.g. www.example.com', 'dreamanual-toolkit' ); ?>" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Push Token', 'dreamanual-toolkit' ); ?>
                    <small><?php echo esc_html__('Baidu Search Resource Platform →普通收录 → API access → token parameter value.', 'dreamanual-toolkit' ); ?></small>
                </div>
                <div class="drea-settings-row__action">
                    <input type="text" id="baidu-token" value="<?php echo esc_attr( $drea_settings['baidu_token'] ); ?>" class="drea-form-group__input" placeholder="<?php echo esc_attr__('Obtain from Baidu Search Resource Platform', 'dreamanual-toolkit' ); ?>" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Test Push', 'dreamanual-toolkit' ); ?>
                </div>
                <div class="drea-settings-row__action">
                    <button type="button" class="drea-btn drea-btn--secondary" id="baidu-test-btn" data-engine="baidu"><?php echo esc_html__('Test Push', 'dreamanual-toolkit' ); ?></button>
                    <span class="drea-sp-test-status" id="baidu-test-status"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bing 推送 -->
    <div class="drea-section<?php echo $drea_settings['bing_enabled'] ? '' : ' drea-section--collapsed'; ?>">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html__('Bing Push', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-section__desc"><?php echo esc_html__('Auto-push links to Bing Webmaster Tools on post publish', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-toggle">
                <input type="checkbox" id="bing-enabled" <?php checked( $drea_settings['bing_enabled'] ); ?>>
                <span class="drea-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-section__body<?php drea_sp_body_class( $drea_settings['bing_enabled'] ); ?>" id="bing-settings">
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    API Key
                    <small><?php echo esc_html__('Bing Webmaster Tools → Settings → API Key.', 'dreamanual-toolkit' ); ?></small>
                </div>
                <div class="drea-settings-row__action">
                    <input type="text" id="bing-key" value="<?php echo esc_attr( $drea_settings['bing_key'] ); ?>" class="drea-form-group__input" placeholder="<?php echo esc_attr__('Obtain from Bing Webmaster Tools', 'dreamanual-toolkit' ); ?>" style="max-width:300px;">
                </div>
            </div>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Test Push', 'dreamanual-toolkit' ); ?>
                </div>
                <div class="drea-settings-row__action">
                    <button type="button" class="drea-btn drea-btn--secondary" id="bing-test-btn" data-engine="bing"><?php echo esc_html__('Test Push', 'dreamanual-toolkit' ); ?></button>
                    <span class="drea-sp-test-status" id="bing-test-status"></span>
                </div>
            </div>
        </div>
    </div>

    <p class="submit">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-sp-save-btn" disabled><?php echo esc_html__('Save Settings', 'dreamanual-toolkit' ); ?></button>
    </p>
</div>
