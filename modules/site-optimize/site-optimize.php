<?php
/**
 * 站点优化模块 —— WordPress 功能精简与优化
 *
 * 每个优化项独立开关，互不耦合。
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Site_Optimize extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'site-optimize';

    /**
     * {@inheritdoc}
     */
    public function get_id(): string {
        return self::MODULE_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function get_name(): string {
        return __( '站点优化', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( 'WordPress 功能精简：屏蔽 Emoji、关闭修订、精简头部代码等，每项独立开关。', 'dreamanual-toolkit' );
    }

    /**
     * 获取设置页 URL
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-so' );
    }

    /**
     * 获取所有优化项定义（key => 默认值）
     *
     * @return array<string, bool>
     */
    public static function get_features(): array {
        return [
            // 常规功能
            'disable_revisions'       => true,
            'disable_trackback'       => true,
            'disable_xmlrpc'          => true,
            'disable_feed'            => false,
            'disable_admin_email_ver' => true,
            // 转换功能
            'disable_emoji'           => true,
            'disable_text_transform'  => true,
            'disable_capital_p'       => false,
            // 后台功能
            'remove_gdpr_page'        => true,
            'remove_dashboard_news'   => true,
            'remove_help_tabs'        => true,
            'remove_screen_options'   => true,
            // 页面功能
            'remove_wp_version'       => true,
            'remove_toolbar_option'   => true,
            // 嵌入功能
            'disable_auto_embeds'     => true,
            'disable_wp_embed'        => true,
            // 性能优化
            'enable_speculative'      => true,
        ];
    }

    /**
     * 获取分组信息
     *
     * @return array<string, array{label: string, features: array<string, string>}>
     */
    public static function get_groups(): array {
        return [
            'general' => [
                'label'    => __( '常规功能', 'dreamanual-toolkit' ),
                'features' => [
                    'disable_revisions'       => __( '屏蔽文章修订功能，精简文章表数据', 'dreamanual-toolkit' ),
                    'disable_trackback'       => __( '彻底关闭 Trackback，防止垃圾留言', 'dreamanual-toolkit' ),
                    'disable_xmlrpc'          => __( '关闭 XML-RPC 功能，只在后台发布文章', 'dreamanual-toolkit' ),
                    'disable_feed'            => __( '屏蔽站点 Feed，防止文章被快速采集', 'dreamanual-toolkit' ),
                    'disable_admin_email_ver' => __( '屏蔽站点管理员邮箱定期验证功能', 'dreamanual-toolkit' ),
                ],
            ],
            'transform' => [
                'label'    => __( '转换功能', 'dreamanual-toolkit' ),
                'features' => [
                    'disable_emoji'          => __( '屏蔽 Emoji 转换成图片功能，直接使用 Emoji', 'dreamanual-toolkit' ),
                    'disable_text_transform' => __( '屏蔽字符转换成格式化的 HTML 实体功能', 'dreamanual-toolkit' ),
                    'disable_capital_p'      => __( '屏蔽 WordPress 大小写修正，自行决定如何书写', 'dreamanual-toolkit' ),
                ],
            ],
            'admin' => [
                'label'    => __( '后台功能', 'dreamanual-toolkit' ),
                'features' => [
                    'remove_gdpr_page'      => __( '移除为欧洲通用数据保护条例生成的页面', 'dreamanual-toolkit' ),
                    'remove_dashboard_news' => __( '移除仪表盘的「WordPress 活动及新闻」', 'dreamanual-toolkit' ),
                    'remove_help_tabs'      => __( '移除后台界面右上角的「帮助」标签', 'dreamanual-toolkit' ),
                    'remove_screen_options' => __( '移除后台界面右上角的「选项」标签', 'dreamanual-toolkit' ),
                ],
            ],
            'page' => [
                'label'    => __( '页面功能', 'dreamanual-toolkit' ),
                'features' => [
                    'remove_wp_version'     => __( '移除页面头部版本号和服务发现标签代码', 'dreamanual-toolkit' ),
                    'remove_toolbar_option' => __( '移除工具栏和后台个人资料中工具栏相关选项', 'dreamanual-toolkit' ),
                ],
            ],
            'embed' => [
                'label'    => __( '嵌入功能', 'dreamanual-toolkit' ),
                'features' => [
                    'disable_auto_embeds' => __( '禁用 Auto Embeds 功能，加快页面解析速度', 'dreamanual-toolkit' ),
                    'disable_wp_embed'    => __( '屏蔽嵌入其他 WordPress 文章的 Embed 功能', 'dreamanual-toolkit' ),
                ],
            ],
            'performance' => [
                'label'    => __( '性能优化', 'dreamanual-toolkit' ),
                'features' => [
                    'enable_speculative' => __( '启用推测加载，浏览器预渲染链接页面（Chrome 121+）', 'dreamanual-toolkit' ),
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        // 管理菜单 & 资源
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_drea_so_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_drea_so_get_settings', [ $this, 'ajax_get_settings' ] );

        $features = self::get_features();

        foreach ( $features as $key => $default ) {
            if ( ! $this->get_option( $key, $default ) ) {
                continue;
            }

            switch ( $key ) {
                // ─── 常规功能 ───
                case 'disable_revisions':
                    add_filter( 'wp_revisions_to_keep', '__return_zero' );
                    break;

                case 'disable_trackback':
                    add_filter( 'pings_open', '__return_false', 20 );
                    add_action( 'pre_ping', [ $this, 'disable_self_ping' ] );
                    break;

                case 'disable_xmlrpc':
                    add_filter( 'xmlrpc_enabled', '__return_false' );
                    add_filter( 'wp_headers', [ $this, 'remove_xmlrpc_header' ] );
                    break;

                case 'disable_feed':
                    add_action( 'do_feed', [ $this, 'disable_feed_redirect' ], 1 );
                    add_action( 'do_feed_rdf', [ $this, 'disable_feed_redirect' ], 1 );
                    add_action( 'do_feed_rss', [ $this, 'disable_feed_redirect' ], 1 );
                    add_action( 'do_feed_rss2', [ $this, 'disable_feed_redirect' ], 1 );
                    add_action( 'do_feed_atom', [ $this, 'disable_feed_redirect' ], 1 );
                    remove_action( 'wp_head', 'feed_links_extra', 3 );
                    remove_action( 'wp_head', 'feed_links', 2 );
                    break;

                case 'disable_admin_email_ver':
                    remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
                    add_filter( 'admin_email_check_interval', '__return_zero' );
                    break;

                // ─── 转换功能 ───
                case 'disable_emoji':
                    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
                    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
                    remove_action( 'wp_print_styles', 'print_emoji_styles' );
                    remove_action( 'admin_print_styles', 'print_emoji_styles' );
                    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
                    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
                    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
                    add_filter( 'tiny_mce_plugins', [ $this, 'remove_emoji_tinymce' ] );
                    add_filter( 'wp_resource_hints', [ $this, 'remove_emoji_dns' ], 10, 2 );
                    break;

                case 'disable_text_transform':
                    remove_filter( 'the_content', 'wptexturize' );
                    remove_filter( 'the_title', 'wptexturize' );
                    remove_filter( 'the_excerpt', 'wptexturize' );
                    remove_filter( 'comment_text', 'wptexturize' );
                    remove_filter( 'widget_text', 'wptexturize' );
                    remove_filter( 'list_cats', 'wptexturize' );
                    break;

                case 'disable_capital_p':
                    remove_filter( 'the_title', 'capital_P_dangit', 11 );
                    remove_filter( 'the_content', 'capital_P_dangit', 11 );
                    remove_filter( 'comment_text', 'capital_P_dangit', 31 );
                    break;

                // ─── 后台功能 ───
                case 'remove_gdpr_page':
                    add_action( 'admin_menu', [ $this, 'remove_privacy_page' ], 999 );
                    add_action( 'admin_init', [ $this, 'remove_privacy_admin_notices' ] );
                    break;

                case 'remove_dashboard_news':
                    add_action( 'admin_init', [ $this, 'remove_dashboard_widgets' ] );
                    break;

                case 'remove_help_tabs':
                    add_action( 'admin_head', [ $this, 'remove_help_tabs' ], 999 );
                    break;

                case 'remove_screen_options':
                    add_filter( 'screen_options_show_screen', '__return_false' );
                    break;

                // ─── 页面功能 ───
                case 'remove_wp_version':
                    remove_action( 'wp_head', 'wp_generator' );
                    remove_action( 'wp_head', 'rest_output_link_wp_head' );
                    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
                    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
                    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
                    add_filter( 'the_generator', '__return_empty_string' );
                    break;

                case 'remove_toolbar_option':
                    add_action( 'admin_init', [ $this, 'remove_toolbar_option' ] );
                    add_filter( 'show_admin_bar', '__return_false' );
                    break;

                // ─── 嵌入功能 ───
                case 'disable_auto_embeds':
                    remove_action( 'parse_query', 'wp_oembed_parse_query' );
                    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
                    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
                    add_filter( 'embed_oembed_discover', '__return_false' );
                    break;

                case 'disable_wp_embed':
                    add_action( 'wp_enqueue_scripts', [ $this, 'deregister_wp_embed' ], 99 );
                    add_action( 'admin_enqueue_scripts', [ $this, 'deregister_wp_embed' ], 99 );
                    break;

                // ─── 性能优化 ───
                case 'enable_speculative':
                    add_action( 'wp_head', [ $this, 'output_speculation_rules' ], 99 );
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function on_activate(): void {
        // 写入默认值
        foreach ( self::get_features() as $key => $default ) {
            if ( false === get_option( 'drea_site_optimize_' . $key ) ) {
                update_option( 'drea_site_optimize_' . $key, $default );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        foreach ( array_keys( self::get_features() ) as $key ) {
            delete_option( 'drea_site_optimize_' . $key );
        }
    }

    // ─── 管理菜单 ─────────────────────────────────────

    /**
     * 注册子菜单
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'dreamanual-toolkit',
            __( '站点优化', 'dreamanual-toolkit' ),
            __( '站点优化', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-so',
            [ $this, 'render_page' ]
        );
    }

    /**
     * 渲染设置页
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }
        include __DIR__ . '/admin/settings-page.php';
    }

    /**
     * 加载管理资源
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( false === strpos( $hook, 'drea-so' ) ) return;

        $module_url  = DREA_URL . 'modules/site-optimize';
        $module_path = DREA_PATH . 'modules/site-optimize';

        wp_enqueue_style(
            'drea-so-admin',
            $module_url . '/assets/css/admin.css',
            [ 'drea-toolkit-common' ],
            filemtime( $module_path . '/assets/css/admin.css' )
        );

        wp_enqueue_script(
            'drea-so-admin',
            $module_url . '/assets/js/admin.js',
            [ 'drea-toolkit-common' ],
            filemtime( $module_path . '/assets/js/admin.js' ),
            true
        );

        wp_localize_script( 'drea-so-admin', 'dreaSo', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_so_nonce' ),
            'i18n'    => [
                'saved'  => __( '设置已保存。', 'dreamanual-toolkit' ),
                'failed' => __( '保存失败，请重试。', 'dreamanual-toolkit' ),
                'error'  => __( '操作失败。', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    // ─── AJAX ─────────────────────────────────────────

    /**
     * AJAX: 保存设置
     */
    public function ajax_save_settings(): void {
        check_ajax_referer( 'drea_so_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        foreach ( array_keys( self::get_features() ) as $key ) {
            $value = isset( $_POST[ $key ] ) ? boolval( $_POST[ $key ] ) : false;
            update_option( 'drea_site_optimize_' . $key, $value );
        }

        wp_send_json_success( [ 'message' => __( '设置已保存。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 获取设置
     */
    public function ajax_get_settings(): void {
        check_ajax_referer( 'drea_so_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $data = [];
        foreach ( self::get_features() as $key => $default ) {
            $data[ $key ] = (bool) $this->get_option( $key, $default );
        }

        wp_send_json_success( $data );
    }

    // ─── Hook 回调 ────────────────────────────────────

    /**
     * 禁止自我 Pingback
     */
    public function disable_self_ping( array &$links ): void {
        $home = home_url();
        foreach ( $links as $l => $link ) {
            if ( 0 === strpos( $link, $home ) ) {
                unset( $links[ $l ] );
            }
        }
    }

    /**
     * 移除 XML-RPC 响应头
     */
    public function remove_xmlrpc_header( array $headers ): array {
        unset( $headers['X-Pingback'] );
        return $headers;
    }

    /**
     * Feed 重定向到首页
     */
    public function disable_feed_redirect(): void {
        wp_safe_redirect( home_url(), 301 );
        exit;
    }

    /**
     * 移除 TinyMCE Emoji 插件
     */
    public function remove_emoji_tinymce( array $plugins ): array {
        return array_diff( $plugins, [ 'wpemoji' ] );
    }

    /**
     * 移除 Emoji DNS 预解析
     */
    public function remove_emoji_dns( array $urls, string $relation_type ): array {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_svg_url = 'https://s.w.org/images/core/emoji/';
            foreach ( $urls as $key => $url ) {
                if ( strpos( $url, $emoji_svg_url ) === 0 ) {
                    unset( $urls[ $key ] );
                }
            }
        }
        return $urls;
    }

    /**
     * 移除隐私政策页面
     */
    public function remove_privacy_page(): void {
        remove_submenu_page( 'tools.php', 'privacy.php' );
        remove_submenu_page( 'options-general.php', 'options-privacy.php' );
    }

    /**
     * 移除隐私相关后台通知
     */
    public function remove_privacy_admin_notices(): void {
        remove_action( 'admin_notices', [ 'WP_Privacy_Policy_Content', 'notice' ] );
        remove_action( 'admin_notices', [ 'WP_Privacy_Data_Removal_Requests_Table', 'scheduled_delete_notice' ] );
    }

    /**
     * 移除仪表盘小工具
     */
    public function remove_dashboard_widgets(): void {
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    }

    /**
     * 移除帮助标签
     */
    public function remove_help_tabs(): void {
        $screen = get_current_screen();
        if ( $screen ) {
            $screen->remove_help_tabs();
        }
    }

    /**
     * 移除个人资料中的工具栏选项
     */
    public function remove_toolbar_option(): void {
        remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
        remove_action( 'personal_options', 'toolbar_preferences' );
    }

    /**
     * 注销 WP Embed 脚本
     */
    public function deregister_wp_embed(): void {
        wp_deregister_script( 'wp-embed' );
    }

    /**
     * 输出推测加载规则（Speculation Rules API）
     *
     * Chrome 121+ 支持在用户悬停链接时预渲染页面。
     */
    public function output_speculation_rules(): void {
        $rules = [
            'prerender' => [
                [ 'where' => [ 'href_matches' => '/*' ] ],
            ],
        ];
        echo '<script type="speculationrules">' . wp_json_encode( $rules ) . '</script>' . "\n";
    }
}

// 注册模块
Core::get_instance()->register_module( new Site_Optimize() );
