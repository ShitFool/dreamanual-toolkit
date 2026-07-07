<?php
/**
 * 内容可见性模块 —— per-category × per-channel × per-role 粒度控制
 *
 * 核心场景:
 * - "日记"分类: frontend/rss/rest_api/search/sitemap 全部隐藏，仅 Administrator 可见
 * - "小程序精选"分类: frontend/rss/search/sitemap 隐藏，REST API 保持开放
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Content_Visibility extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'content-visibility';

    /** @var array 可用的隐藏渠道 */
    const CHANNELS = [ 'frontend', 'rss', 'rest_api', 'search', 'sitemap' ];

    /** @var string option 名 */
    const RULES_OPTION = 'drea_content_visibility_rules';

    /** @var string 文章隐藏 meta key */
    const POST_HIDDEN_META = '_drea_content_visibility_hidden';

    /** @var array|null 缓存的规则 */
    private $rules_cache = null;

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
        return __( '内容可见性', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( '按分类/文章控制内容可见性，支持渠道和角色粒度配置。', 'dreamanual-toolkit' );
    }

    /**
     * 获取模块设置页 URL
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-cv-settings' );
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        // 前端 / RSS / 搜索 — pre_get_posts
        add_action( 'pre_get_posts', [ $this, 'filter_query' ] );

        // 单篇文章直链 404 拦截
        add_action( 'template_redirect', [ $this, 'block_single_post' ] );

        // REST API
        add_filter( 'rest_post_query', [ $this, 'filter_rest_query' ], 10, 2 );

        // WP Sitemap
        add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'filter_sitemap_posts' ] );
        add_filter( 'wp_sitemaps_taxonomies_query_args', [ $this, 'filter_sitemap_taxonomies' ] );

        // 管理菜单
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_drea_cv_save_rules', [ $this, 'ajax_save_rules' ] );
        add_action( 'wp_ajax_drea_cv_get_rules', [ $this, 'ajax_get_rules' ] );
        add_action( 'wp_ajax_drea_cv_toggle_post', [ $this, 'ajax_toggle_post' ] );

        // 文章列表: 可见性筛选器 + 快速操作
        add_action( 'restrict_manage_posts', [ $this, 'add_post_filter' ] );
        add_filter( 'parse_query', [ $this, 'parse_post_filter' ] );
        add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );
        add_filter( 'manage_post_posts_columns', [ $this, 'add_column' ] );
        add_action( 'manage_post_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
    }

    /**
     * {@inheritdoc}
     */
    public function on_activate(): void {
        // 迁移旧 Category Excluder 插件数据
        $old_cats = get_option( 'category_excluder_excluded_cats' );
        if ( false !== $old_cats && false === get_option( self::RULES_OPTION ) ) {
            $old_cats = is_array( $old_cats ) ? $old_cats : explode( ',', $old_cats );
            $rules    = [];
            foreach ( $old_cats as $cat_id ) {
                $cat_id = intval( $cat_id );
                if ( $cat_id ) {
                    $rules[ $cat_id ] = [
                        'channels' => [ 'frontend', 'rss', 'search', 'sitemap' ],
                        'roles'    => [ 'administrator' ],
                    ];
                }
            }
            update_option( self::RULES_OPTION, $rules );
        }

        // 迁移旧"隐藏文章"插件数据
        $old_hidden = get_option( 'hide_posts_hidden_ids' );
        if ( false !== $old_hidden && is_array( $old_hidden ) ) {
            foreach ( $old_hidden as $post_id ) {
                update_post_meta( intval( $post_id ), self::POST_HIDDEN_META, 1 );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        delete_option( self::RULES_OPTION );
        // 清理 post meta
        global $wpdb;
        $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => self::POST_HIDDEN_META ] );
    }

    // ─── 规则读取 ──────────────────────────────────────

    /**
     * 获取所有分类可见性规则
     *
     * @return array [cat_id => ['channels' => [...], 'roles' => [...]]]
     */
    public function get_rules(): array {
        if ( null === $this->rules_cache ) {
            $rules            = get_option( self::RULES_OPTION, [] );
            $this->rules_cache = is_array( $rules ) ? $rules : [];
        }
        return $this->rules_cache;
    }

    /**
     * 获取当前用户可绕过的分类列表
     * 仅返回在指定渠道中、当前用户角色被允许的分类 ID
     *
     * @param string $channel 渠道名。
     * @return int[] 需要排除的分类 ID（当前用户不可绕过的）
     */
    private function get_excluded_cats_for_channel( string $channel ): array {
        $rules      = $this->get_rules();
        $user_roles = is_user_logged_in() ? wp_get_current_user()->roles : [];
        $excluded   = [];

        foreach ( $rules as $cat_id => $rule ) {
            $channels = $rule['channels'] ?? [];
            $roles    = $rule['roles'] ?? [];

            // 该分类在此渠道有隐藏规则
            if ( ! in_array( $channel, $channels, true ) ) {
                continue;
            }

            // 当前用户角色是否在允许列表中
            $bypass = ! empty( array_intersect( $user_roles, $roles ) );
            if ( ! $bypass ) {
                $excluded[] = (int) $cat_id;
            }
        }

        return $excluded;
    }

    /**
     * 获取所有渠道中需排除的分类（用于 posts_where / pre_get_posts 统一排除）
     *
     * @param string[] $channels 渠道列表。
     * @return int[]
     */
    private function get_excluded_cats_for_channels( array $channels ): array {
        $all_excluded = [];
        foreach ( $channels as $channel ) {
            $all_excluded = array_merge( $all_excluded, $this->get_excluded_cats_for_channel( $channel ) );
        }
        return array_unique( $all_excluded );
    }

    // ─── Hook 实现 ─────────────────────────────────────

    /**
     * pre_get_posts —— 前端 / RSS / 搜索
     */
    public function filter_query( \WP_Query $query ): void {
        // 仅前端查询，不影响管理后台
        if ( is_admin() ) {
            return;
        }

        // 不影响主查询之外的查询（如小工具）
        if ( ! $query->is_main_query() && ! apply_filters( 'drea_cv_filter_all_queries', false ) ) {
            return;
        }

        $channels = [];
        if ( $query->is_feed() ) {
            $channels[] = 'rss';
        } elseif ( $query->is_search() ) {
            $channels[] = 'search';
        } else {
            $channels[] = 'frontend';
        }

        $excluded_cats = $this->get_excluded_cats_for_channels( $channels );
        if ( ! empty( $excluded_cats ) ) {
            $current = $query->get( 'category__not_in', [] );
            $query->set( 'category__not_in', array_merge( (array) $current, $excluded_cats ) );
        }

        // 文章级隐藏：排除 _drea_content_visibility_hidden
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            $hidden_ids = $this->get_hidden_post_ids();
            if ( ! empty( $hidden_ids ) ) {
                $current = $query->get( 'post__not_in', [] );
                $query->set( 'post__not_in', array_merge( (array) $current, $hidden_ids ) );
            }
        }
    }

    /**
     * template_redirect —— 拦截单篇文章直链访问
     * 当文章属于 frontend 渠道隐藏的分类，或文章被标记为隐藏时，返回 404
     */
    public function block_single_post(): void {
        if ( is_admin() ) {
            return;
        }

        if ( ! is_singular( 'post' ) ) {
            return;
        }

        // 管理员始终可访问
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }

        // 文章级隐藏
        if ( ! current_user_can( 'manage_options' ) ) {
            $hidden_ids = $this->get_hidden_post_ids();
            if ( in_array( $post_id, $hidden_ids, true ) ) {
                $this->return_404();
                return;
            }
        }

        // 分类级隐藏：检查该文章所属分类是否在 frontend 渠道被隐藏
        $excluded_cats = $this->get_excluded_cats_for_channel( 'frontend' );
        if ( ! empty( $excluded_cats ) ) {
            $post_cats = wp_get_post_categories( $post_id, [ 'fields' => 'ids' ] );
            if ( ! empty( array_intersect( $post_cats, $excluded_cats ) ) ) {
                $this->return_404();
                return;
            }
        }
    }

    /**
     * 返回 404 页面并终止请求
     */
    private function return_404(): void {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        include get_404_template();
        exit;
    }

    /**
     * REST API 查询过滤
     *
     * @param array           $args   查询参数。
     * @param \WP_REST_Request $request 请求对象。
     * @return array
     */
    public function filter_rest_query( array $args, \WP_REST_Request $request ): array {
        $excluded_cats = $this->get_excluded_cats_for_channel( 'rest_api' );
        if ( ! empty( $excluded_cats ) ) {
            $existing = $args['category__not_in'] ?? [];
            $args['category__not_in'] = array_merge( (array) $existing, $excluded_cats );
        }

        // 文章级隐藏
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            $hidden_ids = $this->get_hidden_post_ids();
            if ( ! empty( $hidden_ids ) ) {
                $existing = $args['post__not_in'] ?? [];
                $args['post__not_in'] = array_merge( (array) $existing, $hidden_ids );
            }
        }

        return $args;
    }

    /**
     * WP Sitemap 文章过滤
     */
    public function filter_sitemap_posts( array $args ): array {
        $excluded_cats = $this->get_excluded_cats_for_channel( 'sitemap' );
        if ( ! empty( $excluded_cats ) ) {
            $existing = $args['category__not_in'] ?? [];
            $args['category__not_in'] = array_merge( (array) $existing, $excluded_cats );
        }

        // 文章级隐藏
        $hidden_ids = $this->get_hidden_post_ids();
        if ( ! empty( $hidden_ids ) ) {
            $existing = $args['post__not_in'] ?? [];
            $args['post__not_in'] = array_merge( (array) $existing, $hidden_ids );
        }

        return $args;
    }

    /**
     * WP Sitemap 分类过滤
     */
    public function filter_sitemap_taxonomies( array $args ): array {
        $excluded_cats = $this->get_excluded_cats_for_channel( 'sitemap' );
        if ( ! empty( $excluded_cats ) ) {
            $existing = $args['exclude'] ?? [];
            $args['exclude'] = array_merge( (array) $existing, $excluded_cats );
        }
        return $args;
    }

    /**
     * 获取所有隐藏文章 ID
     *
     * @return int[]
     */
    private function get_hidden_post_ids(): array {
        $cache_key = 'drea_content_visibility_hidden_ids';
        $cached    = wp_cache_get( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = '1'",
            self::POST_HIDDEN_META
        ) );

        $ids = array_map( 'intval', $ids );
        wp_cache_set( $cache_key, $ids, '', 60 );
        return $ids;
    }

    // ─── 管理菜单 ─────────────────────────────────────

    /**
     * 注册子菜单
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'dreamanual-toolkit',
            __( '内容可见性', 'dreamanual-toolkit' ),
            __( '内容可见性', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-cv-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * 渲染设置页
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }
        include __DIR__ . '/admin/settings-page.php';
    }

    /**
     * 加载管理资源
     */
    public function enqueue_admin_assets( string $hook ): void {
        // 仅在内容可见性设置页
        if ( false === strpos( $hook, 'drea-cv' ) ) {
            // 文章列表页也加载（用于行内操作）
            $screen = get_current_screen();
            if ( ! $screen || 'edit-post' !== $screen->id ) {
                return;
            }
        }

        $module_url  = DREA_URL . 'modules/content-visibility';
        $module_path = DREA_PATH . 'modules/content-visibility';

        wp_enqueue_style(
            'drea-cv-admin',
            $module_url . '/assets/css/admin.css',
            [],
            filemtime( $module_path . '/assets/css/admin.css' )
        );

        wp_enqueue_script(
            'drea-cv-admin',
            $module_url . '/assets/js/admin.js',
            [],
            filemtime( $module_path . '/assets/js/admin.js' ),
            true
        );

        wp_localize_script( 'drea-cv-admin', 'dreaCv', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_cv_nonce' ),
            'i18n'    => [
                'saved'  => __( '规则已保存。', 'dreamanual-toolkit' ),
                'failed' => __( '保存失败，请重试。', 'dreamanual-toolkit' ),
                'error'  => __( '操作失败。', 'dreamanual-toolkit' ),
                'toggleError' => __( '切换可见性失败，请重试。', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    // ─── AJAX ─────────────────────────────────────────

    /**
     * AJAX: 保存可见性规则
     */
    public function ajax_save_rules(): void {
        check_ajax_referer( 'drea_cv_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $raw = isset( $_POST['rules'] ) ? sanitize_text_field( wp_unslash( $_POST['rules'] ) ) : '[]';
        $rules = json_decode( $raw, true );

        if ( ! is_array( $rules ) ) {
            wp_send_json_error( [ 'message' => __( '规则数据格式无效', 'dreamanual-toolkit' ) ] );
        }

        // 清洗规则
        $clean = [];
        foreach ( $rules as $cat_id => $rule ) {
            $cat_id = intval( $cat_id );
            if ( ! $cat_id ) continue;

            $channels = $rule['channels'] ?? [];
            $channels = array_values( array_intersect( $channels, self::CHANNELS ) );

            $roles = $rule['roles'] ?? [];
            $all_roles = array_keys( wp_roles()->roles );
            $roles = array_values( array_intersect( $roles, $all_roles ) );

            if ( ! empty( $channels ) ) {
                $clean[ $cat_id ] = [
                    'channels' => $channels,
                    'roles'    => $roles,
                ];
            }
        }

        update_option( self::RULES_OPTION, $clean );
        $this->rules_cache = $clean; // 更新缓存

        wp_cache_delete( 'drea_content_visibility_hidden_ids' );

        // 清除 WP Super Cache 页面缓存，确保新规则立即生效
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        wp_send_json_success( [ 'message' => __( '规则已保存。', 'dreamanual-toolkit' ) ] );
    }

    /**
     * AJAX: 获取可见性规则
     */
    public function ajax_get_rules(): void {
        check_ajax_referer( 'drea_cv_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        wp_send_json_success( [ 'rules' => $this->get_rules() ] );
    }

    /**
     * AJAX: 切换文章隐藏状态
     */
    public function ajax_toggle_post(): void {
        check_ajax_referer( 'drea_cv_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $hidden  = isset( $_POST['hidden'] ) ? boolval( $_POST['hidden'] ) : false;

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => __( '无效的文章 ID', 'dreamanual-toolkit' ) ] );
        }

        if ( $hidden ) {
            update_post_meta( $post_id, self::POST_HIDDEN_META, 1 );
        } else {
            delete_post_meta( $post_id, self::POST_HIDDEN_META );
        }

        wp_cache_delete( 'drea_content_visibility_hidden_ids' );

        // 清除 WP Super Cache 页面缓存
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        wp_send_json_success( [
            'post_id' => $post_id,
            'hidden'  => $hidden,
        ] );
    }

    // ─── 文章列表集成 ─────────────────────────────────

    /**
     * 在文章列表添加"可见性"筛选下拉框
     */
    public function add_post_filter(): void {
        global $typenow;
        if ( 'post' !== $typenow ) return;

        $current = isset( $_GET['drea_cv_visibility'] ) ? sanitize_text_field( wp_unslash( $_GET['drea_cv_visibility'] ) ) : '';
        echo '<select name="drea_cv_visibility">';
        echo '<option value="">' . esc_html__( '所有可见性', 'dreamanual-toolkit' ) . '</option>';
        echo '<option value="hidden"' . selected( $current, 'hidden', false ) . '>' . esc_html__( '已隐藏', 'dreamanual-toolkit' ) . '</option>';
        echo '<option value="visible"' . selected( $current, 'visible', false ) . '>' . esc_html__( '可见', 'dreamanual-toolkit' ) . '</option>';
        echo '</select>';
    }

    /**
     * 解析可见性筛选
     */
    public function parse_post_filter( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) return;

        $filter = isset( $_GET['drea_cv_visibility'] ) ? sanitize_text_field( wp_unslash( $_GET['drea_cv_visibility'] ) ) : '';
        if ( ! $filter ) return;

        global $wpdb;
        if ( 'hidden' === $filter ) {
            $query->query_vars['meta_key']     = self::POST_HIDDEN_META;
            $query->query_vars['meta_value']   = '1';
        } elseif ( 'visible' === $filter ) {
            $query->query_vars['meta_query'] = [
                [
                    'key'     => self::POST_HIDDEN_META,
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }
    }

    /**
     * 添加行操作按钮
     */
    public function add_row_action( array $actions, \WP_Post $post ): array {
        if ( ! current_user_can( 'manage_options' ) ) return $actions;

        $is_hidden = get_post_meta( $post->ID, self::POST_HIDDEN_META, true );
        $label     = $is_hidden ? __( '显示', 'dreamanual-toolkit' ) : __( '隐藏', 'dreamanual-toolkit' );

        $actions['drea_cv_toggle'] = '<a href="#" class="drea-cv-toggle-link" data-post-id="' . esc_attr( $post->ID ) . '" data-hidden="' . esc_attr( $is_hidden ? 0 : 1 ) . '">' . esc_html( $label ) . '</a>';
        return $actions;
    }

    /**
     * 添加可见性列
     */
    public function add_column( array $columns ): array {
        $new = [];
        foreach ( $columns as $k => $v ) {
            $new[ $k ] = $v;
            if ( 'title' === $k ) {
                $new['drea_cv'] = __( '可见性', 'dreamanual-toolkit' );
            }
        }
        return $new;
    }

    /**
     * 渲染可见性列
     */
    public function render_column( string $column, int $post_id ): void {
        if ( 'drea_cv' !== $column ) return;
        $is_hidden = get_post_meta( $post_id, self::POST_HIDDEN_META, true );
        echo $is_hidden
            ? '<span class="drea-cv-badge drea-cv-badge--hidden">' . esc_html__( '隐藏', 'dreamanual-toolkit' ) . '</span>'
            : '<span class="drea-cv-badge drea-cv-badge--visible">' . esc_html__( '可见', 'dreamanual-toolkit' ) . '</span>';
    }
}

// 注册模块
Core::get_instance()->register_module( new Content_Visibility() );
