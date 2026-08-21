<?php
/**
 * 站点增强模块 —— 合并 Back To Top Button + Maintenance + 特色图片 三个小功能
 *
 * 每个子功能独立开关，互不耦合。
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Site_Enhance extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'site-enhance';

    /** @var string SMTP 测试失败时的错误信息 */
    private $_smtp_test_error = '';

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
        return __( '站点增强', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( '回到顶部按钮、维护模式、特色图片管理，每个功能独立开关。', 'dreamanual-toolkit' );
    }

    /**
     * 获取设置页 URL
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-se' );
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        // 管理菜单 & 资源
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_drea_se_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_drea_se_get_settings', [ $this, 'ajax_get_settings' ] );

        // ─── 回到顶部 ───
        if ( $this->get_option( 'btt_enabled', false ) ) {
            add_action( 'wp_footer', [ $this, 'render_back_to_top' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_btt_assets' ] );
        }

        // ─── 维护模式 ───
        if ( $this->get_option( 'maintenance_enabled', false ) ) {
            add_action( 'template_redirect', [ $this, 'maintenance_mode' ] );
        }

        // ─── 特色图片筛选器 ───
        if ( $this->get_option( 'feat_img_enabled', false ) ) {
            add_action( 'restrict_manage_posts', [ $this, 'add_feat_img_filter' ] );
            add_filter( 'parse_query', [ $this, 'parse_feat_img_filter' ] );
        }

        // ─── 特色图片列 ───
        if ( $this->get_option( 'feat_img_col_enabled', false ) ) {
            add_filter( 'manage_posts_columns', [ $this, 'add_feat_img_column' ] );
            add_action( 'manage_posts_custom_column', [ $this, 'render_feat_img_column' ], 10, 2 );
        }

        // ─── 默认特色图片 ───
        if ( $this->get_option( 'default_feat_img_enabled', false ) && $this->get_option( 'default_feat_img_id', 0 ) ) {
            add_filter( 'post_thumbnail_html', [ $this, 'default_featured_image' ], 10, 5 );
        }

        // ─── 摘要快速编辑 ───
        if ( $this->get_option( 'quickedit_excerpt_enabled', false ) ) {
            add_filter( 'manage_posts_columns', [ $this, 'add_excerpt_data_column' ] );
            add_action( 'manage_posts_custom_column', [ $this, 'render_excerpt_data_column' ], 10, 2 );
            add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_excerpt_box' ], 10, 2 );
            add_action( 'admin_head-edit.php', [ $this, 'quick_edit_excerpt_script' ] );
        }

        // ─── SMTP 发信 ───
        if ( $this->get_option( 'smtp_enabled', false ) ) {
            add_action( 'phpmailer_init', [ $this, 'configure_smtp' ] );
            add_filter( 'wp_mail_from', [ $this, 'smtp_mail_from' ] );
            add_filter( 'wp_mail_from_name', [ $this, 'smtp_mail_from_name' ] );
        }

        // ─── SMTP 测试发信 AJAX ───
        add_action( 'wp_ajax_drea_se_smtp_test', [ $this, 'ajax_smtp_test' ] );
    }

    /**
     * {@inheritdoc}
     */
    public function on_activate(): void {
        // 迁移旧 BTT 插件设置
        $old_btt = get_option( 'back_to_top_settings' );
        if ( false !== $old_btt && false === get_option( 'drea_site_enhance_btt_enabled' ) ) {
            update_option( 'drea_site_enhance_btt_enabled', true );
            if ( isset( $old_btt['color'] ) ) {
                update_option( 'drea_site_enhance_btt_color', sanitize_hex_color( $old_btt['color'] ) );
            }
            if ( isset( $old_btt['position'] ) ) {
                update_option( 'drea_site_enhance_btt_position', sanitize_text_field( $old_btt['position'] ) );
            }
        }

        // 迁移旧维护模式设置
        $old_maint = get_option( 'maintenance_options' );
        if ( false !== $old_maint && false === get_option( 'drea_site_enhance_maintenance_enabled' ) ) {
            // 仅在旧插件明确启用时才迁移为 enabled
            $was_active = ! empty( $old_maint['state'] );
            update_option( 'drea_site_enhance_maintenance_enabled', $was_active );
            if ( $was_active && isset( $old_maint['description'] ) ) {
                update_option( 'drea_site_enhance_maintenance_msg', sanitize_textarea_field( $old_maint['description'] ) );
            }
        }

        // 默认值
        if ( false === get_option( 'drea_site_enhance_btt_color' ) ) {
            update_option( 'drea_site_enhance_btt_color', '#2271b1' );
        }
        if ( false === get_option( 'drea_site_enhance_btt_icon_color' ) ) {
            update_option( 'drea_site_enhance_btt_icon_color', '#ffffff' );
        }
        if ( false === get_option( 'drea_site_enhance_btt_position' ) ) {
            update_option( 'drea_site_enhance_btt_position', 'right-bottom' );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        $options = [
            'drea_site_enhance_btt_enabled',
            'drea_site_enhance_btt_color',
            'drea_site_enhance_btt_icon_color',
            'drea_site_enhance_btt_position',
            'drea_site_enhance_maintenance_enabled',
            'drea_site_enhance_maintenance_msg',
            'drea_site_enhance_feat_img_enabled',
            'drea_site_enhance_feat_img_col_enabled',
            'drea_site_enhance_default_feat_img_enabled',
            'drea_site_enhance_default_feat_img_id',
            'drea_site_enhance_quickedit_excerpt_enabled',
            'drea_site_enhance_smtp_enabled',
            'drea_site_enhance_smtp_host',
            'drea_site_enhance_smtp_port',
            'drea_site_enhance_smtp_encryption',
            'drea_site_enhance_smtp_user',
            'drea_site_enhance_smtp_pass',
            'drea_site_enhance_smtp_from_name',
            'drea_site_enhance_smtp_from_email',
        ];
        foreach ( $options as $opt ) {
            delete_option( $opt );
        }
    }

    // ─── 管理菜单 ─────────────────────────────────────

    /**
     * 注册子菜单
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'dreamanual-toolkit',
            __( '站点增强', 'dreamanual-toolkit' ),
            __( '站点增强', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-se',
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
        $module_url  = DREA_URL . 'modules/site-enhance';
        $module_path = DREA_PATH . 'modules/site-enhance';

        // 文章列表页：特色图片列或筛选器启用时加载样式
        if ( 'edit.php' === $hook && ( $this->get_option( 'feat_img_enabled', false ) || $this->get_option( 'feat_img_col_enabled', false ) ) ) {
            wp_enqueue_style(
                'drea-se-admin',
                $module_url . '/assets/css/admin.css',
                [ 'drea-toolkit-common' ],
                filemtime( $module_path . '/assets/css/admin.css' )
            );
            return;
        }

        if ( false === strpos( $hook, 'drea-se' ) ) return;

        wp_enqueue_media();

        wp_enqueue_style(
            'drea-se-admin',
            $module_url . '/assets/css/admin.css',
            [ 'drea-toolkit-common' ],
            filemtime( $module_path . '/assets/css/admin.css' )
        );

        wp_enqueue_script(
            'drea-se-admin',
            $module_url . '/assets/js/admin.js',
            [ 'drea-toolkit-common' ],
            filemtime( $module_path . '/assets/js/admin.js' ),
            true
        );

        wp_localize_script( 'drea-se-admin', 'dreaSe', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_se_nonce' ),
            'i18n'    => [
                'saved'               => __( '设置已保存。', 'dreamanual-toolkit' ),
                'failed'              => __( '保存失败，请重试。', 'dreamanual-toolkit' ),
                'error'               => __( '操作失败，请稍后重试。', 'dreamanual-toolkit' ),
                'smtpTestNoTo'        => __( '请输入收件邮箱地址。', 'dreamanual-toolkit' ),
                'smtpTestSuccess'     => __( '测试邮件已发送，请检查收件箱。', 'dreamanual-toolkit' ),
                'smtpTestFail'        => __( '发送失败，请检查 SMTP 设置。', 'dreamanual-toolkit' ),
                'smtpHostRequired'    => __( '已启用 SMTP 发信，请先填写 SMTP 主机地址。', 'dreamanual-toolkit' ),
                'smtpUserRequired'    => __( '已启用 SMTP 发信，请先填写用户名。', 'dreamanual-toolkit' ),
                'smtpPortRange'       => __( 'SMTP 端口号需在 1-65535 之间。', 'dreamanual-toolkit' ),
                'smtpNotEnabled'      => __( '请先启用 SMTP 发信并保存设置后再测试。', 'dreamanual-toolkit' ),
                'maintenanceConfirm'  => __( '开启维护模式后，访客将看到维护页面，无法正常访问网站。是否确认开启？', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    // ─── AJAX ─────────────────────────────────────────

    /**
     * AJAX: 保存设置
     */
    public function ajax_save_settings(): void {
        check_ajax_referer( 'drea_se_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $btt_enabled        = isset( $_POST['btt_enabled'] ) ? boolval( $_POST['btt_enabled'] ) : false;
        $btt_color          = isset( $_POST['btt_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['btt_color'] ) ) : '#2271b1';
        $btt_icon_color     = isset( $_POST['btt_icon_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['btt_icon_color'] ) ) : '#ffffff';
        $btt_position       = isset( $_POST['btt_position'] ) ? sanitize_text_field( wp_unslash( $_POST['btt_position'] ) ) : 'right-bottom';
        $maintenance_enabled = isset( $_POST['maintenance_enabled'] ) ? boolval( $_POST['maintenance_enabled'] ) : false;
        $maintenance_msg    = isset( $_POST['maintenance_msg'] ) ? sanitize_textarea_field( wp_unslash( $_POST['maintenance_msg'] ) ) : '';
        $feat_img_enabled      = isset( $_POST['feat_img_enabled'] ) ? boolval( $_POST['feat_img_enabled'] ) : false;
        $feat_img_col_enabled  = isset( $_POST['feat_img_col_enabled'] ) ? boolval( $_POST['feat_img_col_enabled'] ) : false;
        $default_feat_img_enabled = isset( $_POST['default_feat_img_enabled'] ) ? boolval( $_POST['default_feat_img_enabled'] ) : false;
        $default_feat_img_id   = isset( $_POST['default_feat_img_id'] ) ? absint( $_POST['default_feat_img_id'] ) : 0;
        $quickedit_excerpt_enabled = isset( $_POST['quickedit_excerpt_enabled'] ) ? boolval( $_POST['quickedit_excerpt_enabled'] ) : false;

        // SMTP 设置
        $smtp_enabled    = isset( $_POST['smtp_enabled'] ) ? boolval( $_POST['smtp_enabled'] ) : false;
        $smtp_host       = isset( $_POST['smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_host'] ) ) : '';
        $smtp_port       = isset( $_POST['smtp_port'] ) ? absint( $_POST['smtp_port'] ) : 465;
        $smtp_encryption = isset( $_POST['smtp_encryption'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_encryption'] ) ) : 'ssl';
        $smtp_user       = isset( $_POST['smtp_user'] ) ? sanitize_email( wp_unslash( $_POST['smtp_user'] ) ) : '';
        // 密码不可用通用 sanitize（会破坏特殊字符），但必须 wp_unslash 处理再加密存储
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- 密码需保留原始字符用于 AES 加密，sanitize 会破坏特殊字符
        $smtp_pass       = isset( $_POST['smtp_pass'] ) ? (string) wp_unslash( $_POST['smtp_pass'] ) : ''; // 后续加密
        $smtp_from_name  = isset( $_POST['smtp_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_from_name'] ) ) : '';
        $smtp_from_email = isset( $_POST['smtp_from_email'] ) ? sanitize_email( wp_unslash( $_POST['smtp_from_email'] ) ) : '';

        // SMTP 完整性校验 (F-04)
        if ( $smtp_enabled ) {
            if ( '' === $smtp_host ) {
                wp_send_json_error( [ 'message' => __( '已启用 SMTP 发信，请先填写 SMTP 主机地址。', 'dreamanual-toolkit' ) ] );
            }
            if ( '' === $smtp_user ) {
                wp_send_json_error( [ 'message' => __( '已启用 SMTP 发信，请先填写用户名。', 'dreamanual-toolkit' ) ] );
            }
        }

        // 端口范围校验 (F-12)
        $smtp_port = max( 1, min( 65535, $smtp_port ) );

        // 保存所有设置 (F-07: 检查返回值)
        $settings_to_save = [
            'drea_site_enhance_btt_enabled'               => $btt_enabled,
            'drea_site_enhance_btt_color'                 => $btt_color ?: '#2271b1',
            'drea_site_enhance_btt_icon_color'            => $btt_icon_color ?: '#ffffff',
            'drea_site_enhance_btt_position'              => $btt_position,
            'drea_site_enhance_maintenance_enabled'       => $maintenance_enabled,
            'drea_site_enhance_maintenance_msg'           => $maintenance_msg,
            'drea_site_enhance_feat_img_enabled'          => $feat_img_enabled,
            'drea_site_enhance_feat_img_col_enabled'      => $feat_img_col_enabled,
            'drea_site_enhance_default_feat_img_enabled'  => $default_feat_img_enabled,
            'drea_site_enhance_default_feat_img_id'       => $default_feat_img_id,
            'drea_site_enhance_quickedit_excerpt_enabled' => $quickedit_excerpt_enabled,
            'drea_site_enhance_smtp_enabled'              => $smtp_enabled,
            'drea_site_enhance_smtp_host'                 => $smtp_host,
            'drea_site_enhance_smtp_port'                 => $smtp_port,
            'drea_site_enhance_smtp_encryption'           => $smtp_encryption,
            'drea_site_enhance_smtp_user'                 => $smtp_user,
            'drea_site_enhance_smtp_from_name'            => $smtp_from_name,
            'drea_site_enhance_smtp_from_email'           => $smtp_from_email,
        ];

        $save_failed = false;
        foreach ( $settings_to_save as $key => $value ) {
            $result = update_option( $key, $value );
            // update_option 返回 false 可能是值未变或真正失败，二次验证
            if ( false === $result && get_option( $key ) != $value ) {
                $save_failed = true;
            }
        }

        // SMTP 密码：非空则加密存储
        if ( '' !== $smtp_pass && '\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2\xe2\x80\xa2' !== $smtp_pass ) {
            update_option( 'drea_site_enhance_smtp_pass', AI_Client::encrypt( $smtp_pass ) );
        }

        if ( $save_failed ) {
            wp_send_json_error( [ 'message' => __( '保存失败，请重试。', 'dreamanual-toolkit' ) ] );
        }

        wp_send_json_success( [ 'message' => __( '设置已保存。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 获取设置
     */
    public function ajax_get_settings(): void {
        check_ajax_referer( 'drea_se_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        wp_send_json_success( [
            'btt_enabled'         => (bool) $this->get_option( 'btt_enabled', false ),
            'btt_color'           => $this->get_option( 'btt_color', '#2271b1' ),
            'btt_icon_color'      => $this->get_option( 'btt_icon_color', '#ffffff' ),
            'btt_position'        => $this->get_option( 'btt_position', 'right-bottom' ),
            'maintenance_enabled' => (bool) $this->get_option( 'maintenance_enabled', false ),
            'maintenance_msg'     => $this->get_option( 'maintenance_msg', '' ),
            'feat_img_enabled'    => (bool) $this->get_option( 'feat_img_enabled', false ),
            'feat_img_col_enabled' => (bool) $this->get_option( 'feat_img_col_enabled', false ),
            'default_feat_img_enabled' => (bool) $this->get_option( 'default_feat_img_enabled', false ),
            'default_feat_img_id' => (int) $this->get_option( 'default_feat_img_id', 0 ),
            'quickedit_excerpt_enabled' => (bool) $this->get_option( 'quickedit_excerpt_enabled', false ),
            'smtp_enabled'        => (bool) $this->get_option( 'smtp_enabled', false ),
            'smtp_host'           => $this->get_option( 'smtp_host', '' ),
            'smtp_port'           => (int) $this->get_option( 'smtp_port', 465 ),
            'smtp_encryption'     => $this->get_option( 'smtp_encryption', 'ssl' ),
            'smtp_user'           => $this->get_option( 'smtp_user', '' ),
            'smtp_from_name'      => $this->get_option( 'smtp_from_name', '' ),
            'smtp_from_email'     => $this->get_option( 'smtp_from_email', '' ),
        ] );
    }

    // ─── 回到顶部 ─────────────────────────────────────

    /**
     * 前端输出回到顶部 HTML
     */
    public function render_back_to_top(): void {
        $bg_color   = $this->get_option( 'btt_color', '#2271b1' );
        $icon_color = $this->get_option( 'btt_icon_color', '#ffffff' );

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $svg is static safe SVG markup
        echo '<button id="drea-btt" style="background:' . esc_attr( $bg_color ) . ';color:' . esc_attr( $icon_color ) . ';" aria-label="' . esc_attr__( '回到顶部', 'dreamanual-toolkit' ) . '">' . $svg . '</button>';

        // JS 禁用时提供降级锚链接 (F-26)
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $svg is static safe SVG markup
        echo '<noscript><a href="#top" style="position:fixed;right:24px;bottom:24px;z-index:9999;width:44px;height:44px;border:none;border-radius:50%;background:' . esc_attr( $bg_color ) . ';color:' . esc_attr( $icon_color ) . ';text-decoration:none;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2);">' . $svg . '</a></noscript>';
    }

    /**
     * 加载回到顶部前端 JS
     */
    public function enqueue_btt_assets(): void {
        $module_url  = DREA_URL . 'modules/site-enhance';
        $module_path = DREA_PATH . 'modules/site-enhance';

        wp_enqueue_style(
            'drea-btt',
            $module_url . '/assets/css/btt.css',
            [],
            filemtime( $module_path . '/assets/css/btt.css' )
        );

        $js = <<<'JS'
(function(){
    var btn=document.getElementById('drea-btt');
    if(!btn)return;
    var visible=false;
    window.addEventListener('scroll',function(){
        var shouldShow=window.pageYOffset>400;
        if(shouldShow!==visible){
            visible=shouldShow;
            btn.classList.toggle('is-visible',visible);
        }
    },{passive:true});
    btn.addEventListener('click',function(){
        window.scrollTo({top:0,behavior:'smooth'});
    });
})();
JS;
        wp_register_script( 'drea-btt', false, [], DREA_VERSION, true );
        wp_enqueue_script( 'drea-btt' );
        wp_add_inline_script( 'drea-btt', $js );
    }

    // ─── 维护模式 ─────────────────────────────────────

    /**
     * 维护模式拦截
     */
    public function maintenance_mode(): void {
        // 管理员和 AJAX 请求不受影响
        if ( current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
            return;
        }

        $msg  = $this->get_option( 'maintenance_msg', '' );
        $site_name = get_bloginfo( 'name' );
        $default_msg = __( '网站正在维护，请稍后访问。', 'dreamanual-toolkit' );

        // 维护页为独立 503 页面，样式通过 WordPress 样式 API 输出（Plugin Check 合规）
        $body_classes    = implode( ' ', get_body_class( [ 'drea-maintenance' ] ) );

        $css = '.drea-maintenance{display:flex;justify-content:center;align-items:center;min-height:100vh;padding:24px;}'
            . '.drea-maintenance__card{text-align:center;max-width:480px;width:100%;}'
            . '.drea-maintenance__title{font-size:1.75rem;font-weight:700;margin-bottom:.5rem;}'
            . '.drea-maintenance__divider{width:48px;height:2px;margin:0 auto 1rem;}'
            . '.drea-maintenance__msg{font-size:1rem;line-height:1.75;margin-bottom:1.5rem;}'
            . '.drea-maintenance__footer{font-size:.875rem;opacity:.6;}';

        // 503 页面在 wp_head 之前 exit，需注册后手动打印该样式句柄
        wp_register_style( 'drea-maintenance', false, [], DREA_VERSION );
        wp_add_inline_style( 'drea-maintenance', $css );
        wp_enqueue_style( 'drea-maintenance' );
        ob_start();
        wp_print_styles( [ 'drea-maintenance' ] );
        $style_tags = ob_get_clean();

        $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__( '维护中', 'dreamanual-toolkit' ) . ' — ' . esc_html( $site_name ) . '</title>';
        $html .= $style_tags;
        $html .= '</head><body class="' . esc_attr( $body_classes ) . '">';
        $html .= '<div class="drea-maintenance__card">';
        $html .= '<div class="drea-maintenance__title">' . esc_html__( '维护中', 'dreamanual-toolkit' ) . '</div>';
        $html .= '<div class="drea-maintenance__divider"></div>';
        $html .= '<div class="drea-maintenance__msg">' . esc_html( $msg ?: $default_msg ) . '</div>';
        $html .= '<div class="drea-maintenance__footer">' . esc_html( $site_name ) . '</div>';
        $html .= '</div></body></html>';

        status_header( 503 );
        nocache_headers();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $html is built with esc_html() above
        echo $html;
        exit;
    }

    // ─── 特色图片筛选 ─────────────────────────────────

    /**
     * 添加特色图片筛选器
     */
    public function add_feat_img_filter(): void {
        global $typenow;
        if ( 'post' !== $typenow ) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 文章列表只读筛选参数，不修改数据
        $current = isset( $_GET['drea_feat_img'] ) ? sanitize_text_field( wp_unslash( $_GET['drea_feat_img'] ) ) : '';
        echo '<select name="drea_feat_img">';
        echo '<option value="">' . esc_html__( '所有特色图片', 'dreamanual-toolkit' ) . '</option>';
        echo '<option value="missing"' . selected( $current, 'missing', false ) . '>' . esc_html__( '缺失特色图', 'dreamanual-toolkit' ) . '</option>';
        echo '<option value="has"' . selected( $current, 'has', false ) . '>' . esc_html__( '有特色图', 'dreamanual-toolkit' ) . '</option>';
        echo '</select>';
    }

    /**
     * 解析特色图片筛选
     */
    public function parse_feat_img_filter( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 文章列表只读筛选参数，不修改数据
        $filter = isset( $_GET['drea_feat_img'] ) ? sanitize_text_field( wp_unslash( $_GET['drea_feat_img'] ) ) : '';
        if ( ! $filter ) return;

        if ( 'missing' === $filter ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 后台列表筛选，数据量受分页限制
            $query->query_vars['meta_query'] = [
                [
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        } elseif ( 'has' === $filter ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 后台列表筛选，受分页限制
            $query->query_vars['meta_key']     = '_thumbnail_id';
            $query->query_vars['meta_compare'] = 'EXISTS';
        }
    }

    // ─── 特色图片列 ────────────────────────────────

    /**
     * 在文章列表中添加特色图片列（紧跟复选框之后）
     *
     * @param string[] $columns 已有列。
     * @return string[]
     */
    public function add_feat_img_column( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'cb' === $key ) {
                $new['drea_feat_img'] = '';
            }
        }
        return $new;
    }

    /**
     * 渲染特色图片列：有图显示缩略图，无图显示虚线占位框
     *
     * @param string $column   列名。
     * @param int    $post_id  文章 ID。
     */
    public function render_feat_img_column( string $column, int $post_id ): void {
        if ( 'drea_feat_img' !== $column ) return;

        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            echo wp_get_attachment_image( $thumb_id, [ 50, 50 ], false, [
                'class' => 'drea-se-feat-img__thumb',
            ] );
        } else {
            echo '<span class="drea-se-feat-img__missing" title="' . esc_attr__( '未设置特色图片', 'dreamanual-toolkit' ) . '"></span>';
        }
    }

    // ─── 默认特色图片 ────────────────────────────────

    /**
     * 前台拦截：当文章无特色图片时，用默认图片替换
     *
     * @param string       $html         输出 HTML。
     * @param int          $post_id      文章 ID。
     * @param int          $thumb_id     缩略图 ID。
     * @param string|int[] $size         图片尺寸。
     * @param string|array $attr         属性。
     * @return string
     */
    public function default_featured_image( string $html, int $post_id, int $thumb_id, $size, $attr ): string {
        if ( $thumb_id ) {
            return $html;
        }

        $default_id = (int) $this->get_option( 'default_feat_img_id', 0 );
        if ( ! $default_id ) {
            return $html;
        }

        // 检查附件是否存在 (F-25)
        $attachment = get_post( $default_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return $html;
        }

        return wp_get_attachment_image( $default_id, $size, false, $attr );
    }

    // ─── 摘要快速编辑 ────────────────────────────────

    /**
     * 添加隐藏的摘要数据列（用于 JS 读取摘要内容）
     *
     * @param string[] $columns 已有列。
     * @return string[]
     */
    public function add_excerpt_data_column( array $columns ): array {
        $columns['drea_excerpt'] = ''; // 空表头，CSS 隐藏
        return $columns;
    }

    /**
     * 渲染隐藏的摘要数据（data 属性供 JS 读取）
     *
     * @param string $column   列名。
     * @param int    $post_id  文章 ID。
     */
    public function render_excerpt_data_column( string $column, int $post_id ): void {
        if ( 'drea_excerpt' !== $column ) return;
        $excerpt = get_the_excerpt( $post_id );
        echo '<span class="drea-quickedit-excerpt-data" data-excerpt="' . esc_attr( $excerpt ) . '"></span>';
    }

    /**
     * 在快速编辑面板中添加摘要字段
     *
     * @param string $column_name 列名。
     * @param string $post_type   文章类型。
     */
    public function quick_edit_excerpt_box( string $column_name, string $post_type ): void {
        if ( 'drea_excerpt' !== $column_name ) return;
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php esc_html_e( '摘要', 'dreamanual-toolkit' ); ?></span>
                    <textarea cols="22" rows="3" name="excerpt" class="drea-quickedit-excerpt-field"></textarea>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * 输出快速编辑摘要的内联 JS（在 edit.php 页面头部）
     */
    public function quick_edit_excerpt_script(): void {
        $module_url  = DREA_URL . 'modules/site-enhance';
        $module_path = DREA_PATH . 'modules/site-enhance';

        wp_enqueue_style(
            'drea-quickedit-excerpt',
            $module_url . '/assets/css/quickedit-excerpt.css',
            [],
            filemtime( $module_path . '/assets/css/quickedit-excerpt.css' )
        );

        wp_enqueue_script(
            'drea-quickedit-excerpt',
            $module_url . '/assets/js/quickedit-excerpt.js',
            [ 'jquery' ],
            filemtime( $module_path . '/assets/js/quickedit-excerpt.js' ),
            true
        );
    }

    // ─── SMTP 发信 ─────────────────────────────────────

    /**
     * 配置 PHPMailer 使用 SMTP
     *
     * @param \PHPMailer $phpmailer PHPMailer 实例。
     */
    public function configure_smtp( $phpmailer ): void {
        $host       = $this->get_option( 'smtp_host', '' );
        $port       = (int) $this->get_option( 'smtp_port', 465 );
        $encryption = $this->get_option( 'smtp_encryption', 'ssl' );
        $user       = $this->get_option( 'smtp_user', '' );
        $pass_enc   = get_option( 'drea_site_enhance_smtp_pass', '' );
        $pass       = $pass_enc ? AI_Client::decrypt( $pass_enc ) : '';

        if ( ! $host || ! $user ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->Port       = $port;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $user;
        $phpmailer->Password   = $pass;

        if ( 'ssl' === $encryption ) {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ( 'tls' === $encryption ) {
            $phpmailer->SMTPSecure = 'tls';
        }

        $phpmailer->SMTPAutoTLS = false;
    }

    /**
     * 强制发件人邮箱
     *
     * @param string $email 默认邮箱。
     * @return string
     */
    public function smtp_mail_from( string $email ): string {
        $from = $this->get_option( 'smtp_from_email', '' );
        return $from ?: $email;
    }

    /**
     * 强制发件人名称
     *
     * @param string $name 默认名称。
     * @return string
     */
    public function smtp_mail_from_name( string $name ): string {
        $from_name = $this->get_option( 'smtp_from_name', '' );
        return $from_name ?: $name;
    }

    /**
     * AJAX: 测试 SMTP 发信
     */
    public function ajax_smtp_test(): void {
        check_ajax_referer( 'drea_se_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $to      = sanitize_email( wp_unslash( $_POST['to'] ?? '' ) );
        $subject = __( 'DM工具箱 SMTP 测试', 'dreamanual-toolkit' );
        $body    = __( '这是一封测试邮件，由 Dreamanual Toolkit SMTP 功能发送。', 'dreamanual-toolkit' );

        if ( ! $to ) {
            wp_send_json_error( [ 'message' => __( '请输入收件邮箱地址。', 'dreamanual-toolkit' ) ] );
        }

        // 捕获 PHPMailer 异常以返回具体错误信息
        add_action( 'phpmailer_init', [ $this, 'smtp_test_enable_exceptions' ] );
        add_action( 'wp_mail_failed', [ $this, 'capture_mail_error' ] );

        $this->_smtp_test_error = '';
        $result = wp_mail( $to, $subject, $body );

        remove_action( 'phpmailer_init', [ $this, 'smtp_test_enable_exceptions' ] );
        remove_action( 'wp_mail_failed', [ $this, 'capture_mail_error' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => __( '测试邮件已发送，请检查收件箱。', 'dreamanual-toolkit' ) ] );
        } else {
            $error_detail = $this->_smtp_test_error;
            if ( $error_detail ) {
                $friendly = $this->translate_smtp_error( $error_detail );
                wp_send_json_error( [ 'message' => $friendly ] );
            } else {
                wp_send_json_error( [ 'message' => __( '发送失败，请检查 SMTP 主机、端口、用户名和密码是否正确。', 'dreamanual-toolkit' ) ] );
            }
        }
    }

    /**
     * 将 PHPMailer/SMTP 原始英文错误翻译为用户友好的中文提示
     *
     * @param string $error 原始错误信息。
     * @return string 友好的中文错误提示。
     */
    private function translate_smtp_error( string $error ): string {
        $error_lower = strtolower( $error );

        // 连接类错误
        if ( false !== strpos( $error_lower, 'connect() failed' )
            || false !== strpos( $error_lower, 'connection refused' )
            || false !== strpos( $error_lower, 'connection timed out' )
            || false !== strpos( $error_lower, 'network is unreachable' )
        ) {
            return __( '无法连接到 SMTP 服务器，请检查主机地址和端口是否正确，以及服务器是否允许该端口连接。', 'dreamanual-toolkit' );
        }

        // 认证类错误
        if ( false !== strpos( $error_lower, 'could not authenticate' )
            || false !== strpos( $error_lower, 'authentication failed' )
            || false !== strpos( $error_lower, '535' )
            || false !== strpos( $error_lower, '530' )
            || false !== strpos( $error_lower, 'invalid credentials' )
        ) {
            return __( '认证失败，请检查 SMTP 用户名和密码是否正确。', 'dreamanual-toolkit' );
        }

        // 加密/SSL/TLS 类错误
        if ( false !== strpos( $error_lower, 'ssl' )
            || false !== strpos( $error_lower, 'tls' )
            || false !== strpos( $error_lower, 'certificate' )
            || false !== strpos( $error_lower, 'encryption' )
        ) {
            return __( '加密连接失败，请检查加密方式（SSL/TLS/无加密）设置是否正确。', 'dreamanual-toolkit' );
        }

        // 发件人地址被拒绝
        if ( false !== strpos( $error_lower, 'sender address rejected' )
            || false !== strpos( $error_lower, 'sender not allowed' )
            || ( false !== strpos( $error_lower, 'from' ) && false !== strpos( $error_lower, 'rejected' ) )
        ) {
            return __( '发件人地址被拒绝，请检查发件人邮箱设置是否与 SMTP 账号匹配。', 'dreamanual-toolkit' );
        }

        // 收件人地址被拒绝
        if ( false !== strpos( $error_lower, 'recipient address rejected' )
            || false !== strpos( $error_lower, 'user unknown' )
            || false !== strpos( $error_lower, 'no such user' )
        ) {
            return __( '收件人地址被拒绝，请检查收件邮箱是否正确。', 'dreamanual-toolkit' );
        }

        // 发送频率限制
        if ( false !== strpos( $error_lower, 'rate limit' )
            || false !== strpos( $error_lower, 'too many' )
            || false !== strpos( $error_lower, 'exceed' )
        ) {
            return __( '发送频率超限，请稍后重试。', 'dreamanual-toolkit' );
        }

        // 邮箱容量满
        if ( false !== strpos( $error_lower, 'quota' )
            || false !== strpos( $error_lower, 'mailbox full' )
            || false !== strpos( $error_lower, 'insufficient' )
        ) {
            return __( '邮箱空间不足或超出配额。', 'dreamanual-toolkit' );
        }

        // 兜底：友好中文 + 简略原始错误
        /* translators: %s: original SMTP error message */
        return sprintf( __( '发送失败，请检查 SMTP 设置是否正确。原始错误：%s', 'dreamanual-toolkit' ), $error );
    }

    /**
     * 启用 PHPMailer 异常模式（测试发信用）
     *
     * @param \PHPMailer $phpmailer PHPMailer 实例。
     */
    public function smtp_test_enable_exceptions( $phpmailer ): void {
        $phpmailer->SMTPDebug = 0; // 不输出调试信息，只捕获异常
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API
        $phpmailer->exceptions = true;
    }

    /**
     * 捕获 wp_mail_failed 错误信息
     *
     * @param \WP_Error $error 邮件错误对象。
     */
    public function capture_mail_error( \WP_Error $error ): void {
        $this->_smtp_test_error = $error->get_error_message();
    }
}

// 注册模块
Core::get_instance()->register_module( new Site_Enhance() );
