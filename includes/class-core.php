<?php
/**
 * 核心调度器 —— 模块注册、开关、设置页框架
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Core {

    /** @var Core|null 单例实例 */
    private static ?Core $instance = null;

    /** @var array<string, Module_Base> 已注册的模块实例 [module_id => instance] */
    private array $modules = [];

    /** @var array<string> 当前启用的模块 ID 列表 */
    private array $active_modules = [];

    /**
     * 获取单例
     */
    public static function get_instance(): Core {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 私有构造 —— 初始化核心逻辑
     *
     * 注意：必须在最开头设置 self::$instance，防止模块文件中的
     * Core::get_instance() 调用触发重入（经典 PHP 单例陷阱）。
     */
    private function __construct() {
        self::$instance = $this; // 防止重入
        $this->register_core_hooks(); // 必须先注册，确保 add_menu_page 在 add_submenu_page 之前执行
        $this->load_active_modules();
    }

    // ─── 模块注册 ────────────────────────────────────────

    /**
     * 注册一个模块
     *
     * @param Module_Base $module 模块实例。
     * @return void
     */
    public function register_module( Module_Base $module ): void {
        $this->modules[ $module->get_id() ] = $module;
    }

    /**
     * 获取所有已注册的模块
     *
     * @return array<string, Module_Base>
     */
    public function get_modules(): array {
        return $this->modules;
    }

    /**
     * 获取指定模块
     *
     * @param string $id 模块 ID。
     * @return Module_Base|null
     */
    public function get_module( string $id ): ?Module_Base {
        return $this->modules[ $id ] ?? null;
    }

    /**
     * 模块是否已启用
     *
     * @param string $id 模块 ID。
     * @return bool
     */
    public function is_module_active( string $id ): bool {
        return in_array( $id, $this->active_modules, true );
    }

    // ─── 启用/停用 ───────────────────────────────────────

    /**
     * 启用一个模块
     *
     * @param string $id 模块 ID。
     * @return bool|WP_Error
     */
    public function activate_module( string $id ) {
        $module = $this->get_module( $id );
        if ( ! $module ) {
            return new \WP_Error( 'drea_unknown_module', __( '未知模块', 'dreamanual-toolkit' ) );
        }

        if ( $this->is_module_active( $id ) ) {
            return true; // 已启用
        }

        // 调用模块的启用钩子
        $module->on_activate();

        // 更新启用列表
        $this->active_modules[] = $id;
        $this->save_active_modules();

        // 注册 hooks
        $module->register_hooks();

        return true;
    }

    /**
     * 停用一个模块
     *
     * @param string $id 模块 ID。
     * @return bool|WP_Error
     */
    public function deactivate_module( string $id ) {
        $module = $this->get_module( $id );
        if ( ! $module ) {
            return new \WP_Error( 'drea_unknown_module', __( '未知模块', 'dreamanual-toolkit' ) );
        }

        if ( ! $this->is_module_active( $id ) ) {
            return true; // 已停用
        }

        // 调用模块的停用钩子
        $module->on_deactivate();

        // 更新启用列表
        $this->active_modules = array_values( array_diff( $this->active_modules, [ $id ] ) );
        $this->save_active_modules();

        return true;
    }

    // ─── 模块加载 ─────────────────────────────────────────

    /**
     * 发现并加载所有模块定义，然后初始化已启用的模块
     *
     * @return void
     */
    private function load_active_modules(): void {
        // 读取已启用的模块列表
        $this->active_modules = get_option( 'drea_active_modules', [] );
        if ( ! is_array( $this->active_modules ) ) {
            $this->active_modules = [];
        }

        // 发现所有模块：扫描 modules/ 目录
        $modules_dir = DREA_PATH . 'modules/';
        if ( ! is_dir( $modules_dir ) ) {
            return;
        }

        $module_dirs = glob( $modules_dir . '*', GLOB_ONLYDIR );
        foreach ( $module_dirs as $dir ) {
            $module_id   = basename( $dir );
            $module_file = $dir . '/' . $module_id . '.php';
            if ( file_exists( $module_file ) ) {
                require_once $module_file;
            }
        }

        // 对已启用的模块注册 hooks
        foreach ( $this->active_modules as $id ) {
            $module = $this->get_module( $id );
            if ( $module ) {
                $module->register_hooks();
            }
        }
    }

    /**
     * 持久化启用模块列表
     *
     * @return void
     */
    private function save_active_modules(): void {
        update_option( 'drea_active_modules', $this->active_modules );
    }

    // ─── 核心 Hooks ──────────────────────────────────────

    /**
     * 注册核心 WordPress hooks（菜单、AJAX、资源）
     *
     * @return void
     */
    private function register_core_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_drea_toggle_module', [ $this, 'ajax_toggle_module' ] );
        add_filter( 'plugin_action_links_' . DREA_BASENAME, [ $this, 'plugin_action_links' ] );
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
    }

    /**
     * 加载国际化翻译
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'dreamanual-toolkit',
            false,
            dirname( DREA_BASENAME ) . '/languages'
        );
    }

    /**
     * 注册管理菜单
     *
     * @return void
     */
    public function add_admin_menu(): void {
        // 顶级菜单：模块管理
        add_menu_page(
            __( 'DM工具箱', 'dreamanual-toolkit' ),
            __( 'DM工具箱', 'dreamanual-toolkit' ),
            'manage_options',
            'dreamanual-toolkit',
            [ $this, 'render_modules_page' ],
            'dashicons-admin-tools',
            80
        );

        // 模块管理子菜单（与顶级菜单同页面，避免重复项）
        add_submenu_page(
            'dreamanual-toolkit',
            __( '模块管理', 'dreamanual-toolkit' ),
            __( '模块管理', 'dreamanual-toolkit' ),
            'manage_options',
            'dreamanual-toolkit',
            [ $this, 'render_modules_page' ]
        );
    }

    /**
     * 渲染模块管理页面
     *
     * @return void
     */
    public function render_modules_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }

        $modules = $this->get_modules();
        include DREA_PATH . 'includes/admin-modules-page.php';
    }

    /**
     * 加载后台公共资源
     *
     * @param string $hook 当前页面 hook。
     * @return void
     */
    public function enqueue_admin_assets( string $hook ): void {
        // 仅在 Toolkit 相关页面加载
        if ( strpos( $hook, 'dreamanual-toolkit' ) === false && strpos( $hook, 'drea-' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'drea-toolkit-common',
            DREA_URL . 'assets/css/toolkit-common.css',
            [],
            filemtime( DREA_PATH . 'assets/css/toolkit-common.css' )
        );

        wp_enqueue_script(
            'drea-toolkit-common',
            DREA_URL . 'assets/js/toolkit-common.js',
            [],
            filemtime( DREA_PATH . 'assets/js/toolkit-common.js' ),
            true
        );

        wp_localize_script( 'drea-toolkit-common', 'dreaToolkit', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_toolkit_nonce' ),
            'i18n'    => [
                'activating'   => __( '启用中…', 'dreamanual-toolkit' ),
                'deactivating' => __( '停用中…', 'dreamanual-toolkit' ),
                'activated'    => __( '已启用', 'dreamanual-toolkit' ),
                'deactivated'  => __( '已停用', 'dreamanual-toolkit' ),
                'error'        => __( '操作失败，请重试', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    /**
     * AJAX：切换模块启用/停用
     *
     * @return void
     */
    public function ajax_toggle_module(): void {
        check_ajax_referer( 'drea_toolkit_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $module_id = sanitize_text_field( wp_unslash( $_POST['module_id'] ?? '' ) );
        $action    = sanitize_text_field( wp_unslash( $_POST['action_type'] ?? '' ) );

        if ( ! $module_id || ! in_array( $action, [ 'activate', 'deactivate' ], true ) ) {
            wp_send_json_error( [ 'message' => __( '参数无效', 'dreamanual-toolkit' ) ] );
        }

        if ( 'activate' === $action ) {
            $result = $this->activate_module( $module_id );
        } else {
            $result = $this->deactivate_module( $module_id );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [
            'module_id' => $module_id,
            'active'    => $this->is_module_active( $module_id ),
        ] );
    }

    /**
     * 插件列表页添加"设置"快捷链接
     *
     * @param array $links 已有链接。
     * @return array
     */
    public function plugin_action_links( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=dreamanual-toolkit' ),
            __( '设置', 'dreamanual-toolkit' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }
}
