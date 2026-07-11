<?php
/**
 * 搜索推送 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$settings = [
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
    echo $enabled ? '' : ' drea-sp-section__body--collapsed';
}
?>
<div class="wrap drea-sp-wrap">
    <h1><?php echo esc_html__( '搜索推送', 'dreamanual-toolkit' ); ?></h1>

    <!-- Toast -->
    <div class="drea-sp-toast-container" id="drea-sp-toast-container"></div>

    <!-- 百度推送 -->
    <div class="drea-sp-section">
        <div class="drea-sp-section__header">
            <div class="drea-sp-section__title">
                <h2><?php echo esc_html__( '百度推送', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-sp-section__desc"><?php echo esc_html__( '文章发布时自动推送链接到百度普通收录', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-sp-toggle">
                <input type="checkbox" id="baidu-enabled" <?php checked( $settings['baidu_enabled'] ); ?>>
                <span class="drea-sp-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-sp-section__body<?php drea_sp_body_class( $settings['baidu_enabled'] ); ?>" id="baidu-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( '站点域名', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="baidu-site" value="<?php echo esc_attr( $settings['baidu_site'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr__( '如 www.example.com', 'dreamanual-toolkit' ); ?>">
                        <p class="description"><?php echo esc_html__( '百度搜索资源平台验证的站点域名。若与当前站点域名不同请手动填写。', 'dreamanual-toolkit' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '推送 Token', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="baidu-token" value="<?php echo esc_attr( $settings['baidu_token'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr__( '在百度搜索资源平台获取', 'dreamanual-toolkit' ); ?>">
                        <p class="description"><?php echo esc_html__( '百度搜索资源平台 → 普通收录 → 接口调用 → token 参数值。', 'dreamanual-toolkit' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '测试推送', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <button type="button" class="button" id="baidu-test-btn" data-engine="baidu"><?php echo esc_html__( '测试推送', 'dreamanual-toolkit' ); ?></button>
                        <span class="drea-sp-test-status" id="baidu-test-status"></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Bing 推送 -->
    <div class="drea-sp-section">
        <div class="drea-sp-section__header">
            <div class="drea-sp-section__title">
                <h2><?php echo esc_html__( 'Bing 推送', 'dreamanual-toolkit' ); ?></h2>
                <span class="drea-sp-section__desc"><?php echo esc_html__( '文章发布时自动推送链接到 Bing 站长平台', 'dreamanual-toolkit' ); ?></span>
            </div>
            <label class="drea-sp-toggle">
                <input type="checkbox" id="bing-enabled" <?php checked( $settings['bing_enabled'] ); ?>>
                <span class="drea-sp-toggle__slider"></span>
            </label>
        </div>
        <div class="drea-sp-section__body<?php drea_sp_body_class( $settings['bing_enabled'] ); ?>" id="bing-settings">
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( 'API Key', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <input type="text" id="bing-key" value="<?php echo esc_attr( $settings['bing_key'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr__( '在 Bing Webmaster Tools 获取', 'dreamanual-toolkit' ); ?>">
                        <p class="description"><?php echo esc_html__( 'Bing Webmaster Tools → Settings → API Key。', 'dreamanual-toolkit' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( '测试推送', 'dreamanual-toolkit' ); ?></th>
                    <td>
                        <button type="button" class="button" id="bing-test-btn" data-engine="bing"><?php echo esc_html__( '测试推送', 'dreamanual-toolkit' ); ?></button>
                        <span class="drea-sp-test-status" id="bing-test-status"></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p class="submit">
        <button type="button" class="button button-primary" id="drea-sp-save-btn"><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
    </p>
</div>
