<?php
/**
 * 角色管理模块 —— 迁移自"用户角色编辑"插件
 *
 * 提供角色和能力的可视化管理：查看角色能力矩阵、新建/复制/删除角色、
 * 编辑角色能力、用户角色快速变更。
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Role_Manager extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'role-manager';

    /** @var array 禁止删除的角色 */
    const PROTECTED_ROLES = [ 'administrator' ];

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
        return __( '角色管理', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( '可视化管理 WordPress 用户角色和能力，支持角色复制和批量编辑。', 'dreamanual-toolkit' );
    }

    /**
     * 获取设置页 URL
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-rm' );
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_drea_rm_get_roles', [ $this, 'ajax_get_roles' ] );
        add_action( 'wp_ajax_drea_rm_get_role', [ $this, 'ajax_get_role' ] );
        add_action( 'wp_ajax_drea_rm_add_role', [ $this, 'ajax_add_role' ] );
        add_action( 'wp_ajax_drea_rm_copy_role', [ $this, 'ajax_copy_role' ] );
        add_action( 'wp_ajax_drea_rm_delete_role', [ $this, 'ajax_delete_role' ] );
        add_action( 'wp_ajax_drea_rm_update_role', [ $this, 'ajax_update_role' ] );
        add_action( 'wp_ajax_drea_rm_get_users_by_role', [ $this, 'ajax_get_users_by_role' ] );
        add_action( 'wp_ajax_drea_rm_change_user_role', [ $this, 'ajax_change_user_role' ] );
    }

    /**
     * {@inheritdoc}
     * WP 原生角色存储在 wp_options 中，无需迁移
     */
    public function on_activate(): void {
        // 角色数据由 WP 原生管理，无需额外初始化
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        // 不删除角色数据——角色属于 WP 核心，不应随插件卸载而清除
    }

    // ─── 管理菜单 ─────────────────────────────────────

    /**
     * 注册子菜单
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'dreamanual-toolkit',
            __( '角色管理', 'dreamanual-toolkit' ),
            __( '角色管理', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-rm',
            [ $this, 'render_page' ]
        );
    }

    /**
     * 渲染页面
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }
        include __DIR__ . '/admin/roles-page.php';
    }

    /**
     * 加载管理资源
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( false === strpos( $hook, 'drea-rm' ) ) return;

        $module_url  = DREA_URL . 'modules/role-manager';
        $module_path = DREA_PATH . 'modules/role-manager';

        wp_enqueue_style(
            'drea-rm-admin',
            $module_url . '/assets/css/admin.css',
            [],
            filemtime( $module_path . '/assets/css/admin.css' )
        );

        wp_enqueue_script(
            'drea-rm-admin',
            $module_url . '/assets/js/admin.js',
            [],
            filemtime( $module_path . '/assets/js/admin.js' ),
            true
        );

        wp_localize_script( 'drea-rm-admin', 'dreaRm', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_rm_nonce' ),
            'i18n'    => [
                /* translators: %s: Role name */
                'confirmDelete'       => __( '确定要删除角色"%s"吗？此操作不可撤销。', 'dreamanual-toolkit' ),
                'cannotDelete'        => __( '无法删除内置角色。', 'dreamanual-toolkit' ),
                'roleAdded'           => __( '角色已添加。', 'dreamanual-toolkit' ),
                'roleCopied'          => __( '角色已复制。', 'dreamanual-toolkit' ),
                'roleDeleted'         => __( '角色已删除。', 'dreamanual-toolkit' ),
                'roleUpdated'         => __( '角色能力已更新。', 'dreamanual-toolkit' ),
                'userRoleChanged'     => __( '用户角色已更改。', 'dreamanual-toolkit' ),
                'failed'              => __( '操作失败，请重试。', 'dreamanual-toolkit' ),
                'error'               => __( '请求失败。', 'dreamanual-toolkit' ),
                'noRoles'             => __( '暂无角色', 'dreamanual-toolkit' ),
                'builtIn'             => __( '内置', 'dreamanual-toolkit' ),
                'edit'                => __( '编辑', 'dreamanual-toolkit' ),
                'copy'                => __( '复制', 'dreamanual-toolkit' ),
                'delete'              => __( '删除', 'dreamanual-toolkit' ),
                'fillRequired'        => __( '请填写角色名称和标识', 'dreamanual-toolkit' ),
                'addRole'             => __( '添加角色', 'dreamanual-toolkit' ),
                'copyRole'            => __( '复制角色', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    // ─── AJAX 处理器 ──────────────────────────────────

    /**
     * AJAX: 获取所有角色
     */
    public function ajax_get_roles(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $wp_roles = wp_roles();
        $roles    = [];

        foreach ( $wp_roles->roles as $name => $info ) {
            $user_count = count( get_users( [ 'role' => $name, 'fields' => 'ID' ] ) );
            $roles[]    = [
                'name'        => $name,
                'display_name' => translate_user_role( $info['name'] ),
                'capabilities' => array_keys( $info['capabilities'] ),
                'user_count'  => $user_count,
                'is_protected' => in_array( $name, self::PROTECTED_ROLES, true ),
            ];
        }

        wp_send_json_success( [ 'roles' => $roles ] );
    }

    /**
     * AJAX: 获取单个角色详情
     */
    public function ajax_get_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $role_name = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );
        $role      = get_role( $role_name );

        if ( ! $role ) {
            wp_send_json_error( [ 'message' => __( '角色不存在', 'dreamanual-toolkit' ) ] );
        }

        wp_send_json_success( [
            'name'         => $role_name,
            'display_name' => translate_user_role( wp_roles()->roles[ $role_name ]['name'] ),
            'capabilities' => array_keys( $role->capabilities ),
        ] );
    }

    /**
     * AJAX: 添加新角色
     */
    public function ajax_add_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $display_name = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
        $role_slug    = sanitize_key( $_POST['role_slug'] ?? '' );

        if ( ! $display_name || ! $role_slug ) {
            wp_send_json_error( [ 'message' => __( '角色名称和标识不能为空', 'dreamanual-toolkit' ) ] );
        }

        if ( get_role( $role_slug ) ) {
            wp_send_json_error( [ 'message' => __( '角色标识已存在', 'dreamanual-toolkit' ) ] );
        }

        add_role( $role_slug, $display_name, [ 'read' => true ] );

        wp_send_json_success( [ 'message' => __( '角色已添加。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 复制角色
     */
    public function ajax_copy_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $source  = sanitize_text_field( wp_unslash( $_POST['source_role'] ?? '' ) );
        $new_slug = sanitize_key( $_POST['new_role_slug'] ?? '' );
        $new_name = sanitize_text_field( wp_unslash( $_POST['new_role_name'] ?? '' ) );

        $source_role = get_role( $source );
        if ( ! $source_role ) {
            wp_send_json_error( [ 'message' => __( '源角色不存在', 'dreamanual-toolkit' ) ] );
        }

        if ( get_role( $new_slug ) ) {
            wp_send_json_error( [ 'message' => __( '角色标识已存在', 'dreamanual-toolkit' ) ] );
        }

        add_role( $new_slug, $new_name, $source_role->capabilities );

        wp_send_json_success( [ 'message' => __( '角色已复制。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 删除角色
     */
    public function ajax_delete_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $role_name = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );

        if ( in_array( $role_name, self::PROTECTED_ROLES, true ) ) {
            wp_send_json_error( [ 'message' => __( '无法删除内置保护角色', 'dreamanual-toolkit' ) ] );
        }

        remove_role( $role_name );

        wp_send_json_success( [ 'message' => __( '角色已删除。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 更新角色能力
     */
    public function ajax_update_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $role_name = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );
        $caps_raw  = isset( $_POST['capabilities'] ) ? sanitize_text_field( wp_unslash( $_POST['capabilities'] ) ) : '[]';
        $caps      = json_decode( $caps_raw, true );

        if ( ! is_array( $caps ) ) {
            wp_send_json_error( [ 'message' => __( '能力数据格式无效', 'dreamanual-toolkit' ) ] );
        }

        $role = get_role( $role_name );
        if ( ! $role ) {
            wp_send_json_error( [ 'message' => __( '角色不存在', 'dreamanual-toolkit' ) ] );
        }

        // 先移除所有能力
        foreach ( $role->capabilities as $cap => $val ) {
            $role->remove_cap( $cap );
        }

        // 添加新能力
        foreach ( $caps as $cap ) {
            $role->add_cap( sanitize_text_field( $cap ) );
        }

        wp_send_json_success( [ 'message' => __( '角色能力已更新。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 获取角色下的用户列表
     */
    public function ajax_get_users_by_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $role_name = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );
        $users     = get_users( [ 'role' => $role_name ] );

        $result = [];
        foreach ( $users as $user ) {
            $result[] = [
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'user_email'   => $user->user_email,
                'roles'        => $user->roles,
            ];
        }

        wp_send_json_success( [ 'users' => $result ] );
    }

    /**
     * AJAX: 更改用户角色
     */
    public function ajax_change_user_role(): void {
        check_ajax_referer( 'drea_rm_nonce', 'nonce' );
        if ( ! current_user_can( 'promote_users' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $user_id   = intval( $_POST['user_id'] ?? 0 );
        $new_role  = sanitize_text_field( wp_unslash( $_POST['new_role'] ?? '' ) );

        if ( ! $user_id || ! $new_role ) {
            wp_send_json_error( [ 'message' => __( '参数无效', 'dreamanual-toolkit' ) ] );
        }

        // 不允许修改自己的角色（防止锁死）
        if ( $user_id === get_current_user_id() ) {
            wp_send_json_error( [ 'message' => __( '不能修改自己的角色', 'dreamanual-toolkit' ) ] );
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( '用户不存在', 'dreamanual-toolkit' ) ] );
        }

        $user->set_role( $new_role );

        wp_send_json_success( [ 'message' => __( '用户角色已更改。', 'dreamanual-toolkit' ) ] );
    }
}

// 注册模块
Core::get_instance()->register_module( new Role_Manager() );
