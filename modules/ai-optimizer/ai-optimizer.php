<?php
/**
 * AI 优化模块 —— 迁移自 Dreamanual AI Tag Optimizer v1.3.1
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class AI_Optimizer extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'ai-optimizer';

    /**
     * 默认摘要提示词（与 AI_Client::build_prompt 中的默认规则一致）
     */
    const DEFAULT_EXCERPT_PROMPT = "请为这篇文章生成简介。规则如下：\n1. 如果文章是小说/故事类，直接从文中选取最有吸引力的原句作为简介。\n2. 如果是个人评论/随笔类且原文用第一人称写作，简介也请保持第一人称（用\"我\"而非\"作者\"），直接引用原文精彩观点或句子。\n3．其他类型文章提炼核心观点。\n控制在{excerpt_length}字以内，不要硬凑字数。";

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
        return __( 'AI 优化', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( 'AI 自动生成标签、Slug、摘要，支持 DeepSeek / Kimi / OpenAI / Claude。', 'dreamanual-toolkit' );
    }

    /**
     * 获取模块设置页 URL
     *
     * @return string
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-ai-settings' );
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

        // AJAX handlers
        add_action( 'wp_ajax_drea_ai_get_posts', [ $this, 'ajax_get_posts' ] );
        add_action( 'wp_ajax_drea_ai_generate', [ $this, 'ajax_generate' ] );
        add_action( 'wp_ajax_drea_ai_apply', [ $this, 'ajax_apply' ] );
        add_action( 'wp_ajax_drea_ai_get_existing_tags', [ $this, 'ajax_get_existing_tags' ] );
        add_action( 'wp_ajax_drea_ai_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_drea_ai_get_settings', [ $this, 'ajax_get_settings' ] );
        add_action( 'wp_ajax_drea_ai_generate_single', [ $this, 'ajax_generate_single' ] );
    }

    /**
     * {@inheritdoc}
     * 从旧插件 dreaaita_* 迁移 option 数据
     */
    public function on_activate(): void {
        // 旧 → 新 option 映射
        $migrations = [
            'dreaaita_provider'       => 'drea_ai_optimizer_provider',
            'dreaaita_model'          => 'drea_ai_optimizer_model',
            'dreaaita_api_key'        => 'drea_ai_optimizer_api_key',
            'dreaaita_opt_tags'       => 'drea_ai_optimizer_opt_tags',
            'dreaaita_opt_slug'       => 'drea_ai_optimizer_opt_slug',
            'dreaaita_opt_excerpt'    => 'drea_ai_optimizer_opt_excerpt',
            'dreaaita_excerpt_length' => 'drea_ai_optimizer_excerpt_length',
            'dreaaita_excerpt_prompt' => 'drea_ai_optimizer_excerpt_prompt',
        ];

        foreach ( $migrations as $old => $new ) {
            if ( false !== get_option( $old ) && false === get_option( $new ) ) {
                $value = get_option( $old );
                // API Key 需要加密存储
                if ( 'dreaaita_api_key' === $old && ! empty( $value ) ) {
                    $value = AI_Client::encrypt( $value );
                }
                update_option( $new, $value );
            }
        }
    }

    /**
     * {@inheritdoc}
     * 删除该模块的所有 option
     */
    public function uninstall(): void {
        $options = [
            'drea_ai_optimizer_provider',
            'drea_ai_optimizer_model',
            'drea_ai_optimizer_api_key',
            'drea_ai_optimizer_opt_tags',
            'drea_ai_optimizer_opt_slug',
            'drea_ai_optimizer_opt_excerpt',
            'drea_ai_optimizer_tag_limit',
            'drea_ai_optimizer_excerpt_length',
            'drea_ai_optimizer_excerpt_prompt',
        ];
        foreach ( $options as $opt ) {
            delete_option( $opt );
        }
    }

    // ─── 管理菜单 ──────────────────────────────────────

    /**
     * 注册子菜单
     */
    public function add_admin_menu(): void {
        // 批量处理页
        add_submenu_page(
            'dreamanual-toolkit',
            __( 'AI 优化 — 批量处理', 'dreamanual-toolkit' ),
            __( 'AI 优化', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-ai-batch',
            [ $this, 'render_batch_page' ]
        );

        // 设置页
        add_submenu_page(
            'dreamanual-toolkit',
            __( 'AI 优化 — 设置', 'dreamanual-toolkit' ),
            __( 'AI 设置', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-ai-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * 渲染批量处理页
     */
    public function render_batch_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }
        include __DIR__ . '/admin/batch-page.php';
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

    // ─── Meta Box ───────────────────────────────────────

    /**
     * 注册文章编辑器侧边栏 Meta Box
     */
    public function add_meta_box(): void {
        add_meta_box(
            'drea_ai_meta',
            __( 'AI 优化', 'dreamanual-toolkit' ),
            [ $this, 'render_meta_box' ],
            [ 'post', 'page' ],
            'side',
            'high'
        );
    }

    /**
     * 渲染 Meta Box
     *
     * @param \WP_Post $post 当前文章。
     */
    public function render_meta_box( \WP_Post $post ): void {
        include __DIR__ . '/admin/meta-box.php';
    }

    // ─── 资源加载 ───────────────────────────────────────

    /**
     * 加载后台资源
     *
     * @param string $hook 当前页面 hook。
     */
    public function enqueue_admin_assets( string $hook ): void {
        $module_url  = DREA_URL . 'modules/ai-optimizer';
        $module_path = DREA_PATH . 'modules/ai-optimizer';

        // 批量处理页
        if ( false !== strpos( $hook, 'drea-ai-batch' ) ) {
            wp_enqueue_style(
                'drea-ai-admin',
                $module_url . '/assets/css/admin.css',
                [],
                filemtime( $module_path . '/assets/css/admin.css' )
            );
            wp_enqueue_script(
                'drea-ai-batch',
                $module_url . '/assets/js/batch.js',
                [],
                filemtime( $module_path . '/assets/js/batch.js' ),
                true
            );
            $this->localize_script( 'drea-ai-batch', 'batch' );
        }

        // 设置页
        if ( false !== strpos( $hook, 'drea-ai-settings' ) ) {
            wp_enqueue_style(
                'drea-ai-admin',
                $module_url . '/assets/css/admin.css',
                [],
                filemtime( $module_path . '/assets/css/admin.css' )
            );
            wp_enqueue_script(
                'drea-ai-settings',
                $module_url . '/assets/js/settings.js',
                [],
                filemtime( $module_path . '/assets/js/settings.js' ),
                true
            );
            $this->localize_script( 'drea-ai-settings', 'settings' );
        }

        // 文章编辑器 Meta Box
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->id, [ 'post', 'page' ], true ) ) {
            wp_enqueue_style(
                'drea-ai-admin',
                $module_url . '/assets/css/admin.css',
                [],
                filemtime( $module_path . '/assets/css/admin.css' )
            );
            wp_enqueue_script(
                'drea-ai-meta',
                $module_url . '/assets/js/meta-box.js',
                [],
                filemtime( $module_path . '/assets/js/meta-box.js' ),
                true
            );
            $this->localize_script( 'drea-ai-meta', 'meta' );
        }
    }

    /**
     * 本地化脚本数据
     *
     * @param string $handle 脚本 handle。
     * @param string $context 上下文 (batch/settings/meta)。
     */
    private function localize_script( string $handle, string $context ): void {
        $common_i18n = [
            'unknownError' => __( '未知错误', 'dreamanual-toolkit' ),
            'networkError' => __( '网络错误，请检查连接。', 'dreamanual-toolkit' ),
        ];

        $context_i18n = [];

        if ( 'batch' === $context ) {
            $context_i18n = [
                'pleaseConfigureApiKey' => __( '请先在设置中配置 API Key。', 'dreamanual-toolkit' ),
                'failedToLoadSettings'  => __( '加载设置失败，请刷新。', 'dreamanual-toolkit' ),
                'postsLoaded'           => __( ' 篇文章已加载', 'dreamanual-toolkit' ),
                'noPostsInCategory'     => __( '该分类下无文章。', 'dreamanual-toolkit' ),
                'loadFailed'            => __( '加载失败: ', 'dreamanual-toolkit' ),
                'noPosts'               => __( '暂无文章', 'dreamanual-toolkit' ),
                'noTags'                => __( '无标签', 'dreamanual-toolkit' ),
                'notGenerated'          => __( '未生成', 'dreamanual-toolkit' ),
                'generate'              => __( '生成', 'dreamanual-toolkit' ),
                'regenerate'            => __( '重新生成', 'dreamanual-toolkit' ),
                'apply'                 => __( '应用', 'dreamanual-toolkit' ),
                'processing'            => __( '处理中...', 'dreamanual-toolkit' ),
                'done'                  => __( '完成!', 'dreamanual-toolkit' ),
                'generating'            => __( '生成中...', 'dreamanual-toolkit' ),
                'generatedSuccessfully' => __( ' 生成成功', 'dreamanual-toolkit' ),
                'failed'                => __( ' 失败: ', 'dreamanual-toolkit' ),
                'requestTimedOut'       => __( '请求超时（AI 响应超过 35 秒）', 'dreamanual-toolkit' ),
                'continuing'            => __( '，继续...', 'dreamanual-toolkit' ),
                'noPendingChanges'      => __( '无待应用更改。', 'dreamanual-toolkit' ),
                'applyAiSuggestionsTo'  => __( '将 AI 建议应用到 ', 'dreamanual-toolkit' ),
                'postsQuestion'         => __( ' 篇文章？', 'dreamanual-toolkit' ),
                'slugChangeNote'        => __( '注意：更改 Slug 会导致旧 URL 404，请确保有正确的重定向。', 'dreamanual-toolkit' ),
                'excerptOverwriteNote'  => __( '摘要更改将覆盖当前内容。', 'dreamanual-toolkit' ),
                'applying'              => __( '正在应用 ', 'dreamanual-toolkit' ),
                'allChangesApplied'     => __( '所有更改已应用！刷新页面查看更新。', 'dreamanual-toolkit' ),
                'changesApplied'        => __( ' 项更改已应用', 'dreamanual-toolkit' ),
                'applyFailed'           => __( ' 应用失败: ', 'dreamanual-toolkit' ),
                'networkErrorShort'     => __( ' 网络错误', 'dreamanual-toolkit' ),
                'prev'                  => __( '上一页', 'dreamanual-toolkit' ),
                'next'                  => __( '下一页', 'dreamanual-toolkit' ),
                'page'                  => __( '第 ', 'dreamanual-toolkit' ),
                'generateForSelected'   => __( '为选中的 ', 'dreamanual-toolkit' ),
                'selected'              => __( ' 篇生成 AI 建议', 'dreamanual-toolkit' ),
            ];
        } elseif ( 'settings' === $context ) {
            $context_i18n = [
                'settingsSaved' => __( '设置已保存。', 'dreamanual-toolkit' ),
                'saveFailed'    => __( '保存失败: ', 'dreamanual-toolkit' ),
            ];
        } elseif ( 'meta' === $context ) {
            $context_i18n = [
                'pleaseSaveDraft'      => __( '请先保存文章草稿。', 'dreamanual-toolkit' ),
                'generationFailed'     => __( '生成失败: ', 'dreamanual-toolkit' ),
                'requestTimedOut'      => __( '请求超时（AI 响应超过 35 秒）', 'dreamanual-toolkit' ),
                'noTagsGenerated'      => __( '无标签生成', 'dreamanual-toolkit' ),
                'applying'             => __( '应用中...', 'dreamanual-toolkit' ),
                'applied'              => __( '已应用!', 'dreamanual-toolkit' ),
                'applyChanges'         => __( '应用更改', 'dreamanual-toolkit' ),
                'applyFailed'          => __( '应用失败: ', 'dreamanual-toolkit' ),
                'slugUpdatedReload'    => __( 'Slug 已更新，页面将刷新。', 'dreamanual-toolkit' ),
            ];
        }

        wp_localize_script( $handle, 'dreaAi', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_ai_nonce' ),
            'i18n'    => array_merge( $common_i18n, $context_i18n ),
        ] );
    }

    // ─── AJAX 处理器 ────────────────────────────────────

    /**
     * AJAX: 获取文章列表
     */
    public function ajax_get_posts(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $page     = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? max( 1, min( 100, intval( $_POST['per_page'] ) ) ) : 20;
        $category = isset( $_POST['category'] ) ? intval( $_POST['category'] ) : 0;

        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( $category ) {
            $args['cat'] = $category;
        }

        $query = new \WP_Query( $args );
        $posts = [];

        foreach ( $query->posts as $post ) {
            $tags = get_the_tags( $post->ID );
            $posts[] = [
                'id'      => $post->ID,
                'title'   => $post->post_title,
                'slug'    => $post->post_name,
                'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
                'tags'    => $tags && ! is_wp_error( $tags ) ? wp_list_pluck( $tags, 'name' ) : [],
                'date'    => $post->post_date,
            ];
        }

        wp_send_json_success( [
            'posts'       => $posts,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ] );
    }

    /**
     * AJAX: 生成 AI 建议（批量页用，从前端传 API Key）
     */
    public function ajax_generate(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $post_id        = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $provider       = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'deepseek';
        $model          = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
        $api_key        = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $existing_tags  = isset( $_POST['existing_tags'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['existing_tags'] ) ) : [];
        $opt_tags       = isset( $_POST['opt_tags'] ) ? boolval( $_POST['opt_tags'] ) : true;
        $opt_slug       = isset( $_POST['opt_slug'] ) ? boolval( $_POST['opt_slug'] ) : true;
        $opt_excerpt    = isset( $_POST['opt_excerpt'] ) ? boolval( $_POST['opt_excerpt'] ) : false;
        $tag_limit      = isset( $_POST['tag_limit'] ) ? max( 1, min( 20, intval( $_POST['tag_limit'] ) ) ) : 5;
        $excerpt_length = isset( $_POST['excerpt_length'] ) ? max( 50, min( 500, intval( $_POST['excerpt_length'] ) ) ) : 100;
        $excerpt_prompt = isset( $_POST['excerpt_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt_prompt'] ) ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( __( '无效的文章 ID。', 'dreamanual-toolkit' ) );
        }
        if ( ! $api_key ) {
            wp_send_json_error( __( '请输入 API Key。', 'dreamanual-toolkit' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( __( '文章未找到。', 'dreamanual-toolkit' ) );
        }

        $content = wp_strip_all_tags( $post->post_content );
        if ( mb_strlen( $content ) > 3000 ) {
            $content = mb_substr( $content, 0, 3000 ) . '...';
        }

        $current_tags      = get_the_tags( $post_id );
        $current_tag_names = $current_tags && ! is_wp_error( $current_tags ) ? wp_list_pluck( $current_tags, 'name' ) : [];

        $ai     = new AI_Client( $provider, $api_key, $model );
        $result = $ai->generate_tags_and_slug( $post->post_title, $content, $current_tag_names, $existing_tags, $opt_tags, $opt_slug, $opt_excerpt, $excerpt_length, $excerpt_prompt, $tag_limit );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: 生成 AI 建议（Meta Box 用，从设置读取 API Key）
     */
    public function ajax_generate_single(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( __( '无效的文章 ID。', 'dreamanual-toolkit' ) );
        }

        $settings = $this->get_settings_array();
        if ( empty( $settings['api_key'] ) ) {
            wp_send_json_error( __( '请先在"AI 优化 → 设置"中配置 API Key。', 'dreamanual-toolkit' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( __( '文章未找到。', 'dreamanual-toolkit' ) );
        }

        $content = wp_strip_all_tags( $post->post_content );
        if ( mb_strlen( $content ) > 3000 ) {
            $content = mb_substr( $content, 0, 3000 ) . '...';
        }

        $current_tags      = get_the_tags( $post_id );
        $current_tag_names = $current_tags && ! is_wp_error( $current_tags ) ? wp_list_pluck( $current_tags, 'name' ) : [];

        $all_tags = get_terms( [
            'taxonomy'   => 'post_tag',
            'hide_empty' => false,
            'fields'     => 'names',
        ] );
        $existing_tags = $all_tags && ! is_wp_error( $all_tags ) ? $all_tags : [];

        $ai     = new AI_Client( $settings['provider'], $settings['api_key'], $settings['model'] );
        $result = $ai->generate_tags_and_slug(
            $post->post_title, $content, $current_tag_names, $existing_tags,
            $settings['opt_tags'], $settings['opt_slug'], $settings['opt_excerpt'],
            $settings['excerpt_length'], $settings['excerpt_prompt'], $settings['tag_limit']
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: 应用 AI 建议
     */
    public function ajax_apply(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $tags    = isset( $_POST['tags'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['tags'] ) ) : [];
        $slug    = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
        $excerpt = isset( $_POST['excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ) ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( __( '无效的文章 ID。', 'dreamanual-toolkit' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( __( '文章未找到。', 'dreamanual-toolkit' ) );
        }

        $update_data = [ 'ID' => $post_id ];

        if ( ! empty( $tags ) ) {
            wp_set_post_tags( $post_id, $tags );
        }

        if ( ! empty( $slug ) && $slug !== $post->post_name ) {
            $slug = wp_unique_post_slug( $slug, $post_id, $post->post_status, $post->post_type, $post->post_parent );
            $update_data['post_name'] = $slug;
        }

        if ( ! empty( $excerpt ) ) {
            $update_data['post_excerpt'] = $excerpt;
        }

        if ( count( $update_data ) > 1 ) {
            wp_update_post( $update_data );
        }

        wp_send_json_success( [
            'message' => __( '更改已应用。', 'dreamanual-toolkit' ),
            'tags'    => $tags,
            'slug'    => $slug,
            'excerpt' => $excerpt,
        ] );
    }

    /**
     * AJAX: 获取所有已有标签
     */
    public function ajax_get_existing_tags(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $tags = get_terms( [
            'taxonomy'   => 'post_tag',
            'hide_empty' => false,
            'fields'     => 'names',
        ] );

        wp_send_json_success( [
            'tags' => $tags && ! is_wp_error( $tags ) ? $tags : [],
        ] );
    }

    /**
     * AJAX: 保存设置
     */
    public function ajax_save_settings(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        $provider       = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'deepseek';
        $model          = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
        $api_key        = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $opt_tags       = isset( $_POST['opt_tags'] ) ? boolval( $_POST['opt_tags'] ) : true;
        $opt_slug       = isset( $_POST['opt_slug'] ) ? boolval( $_POST['opt_slug'] ) : true;
        $opt_excerpt    = isset( $_POST['opt_excerpt'] ) ? boolval( $_POST['opt_excerpt'] ) : false;
        $tag_limit      = isset( $_POST['tag_limit'] ) ? max( 1, min( 20, intval( $_POST['tag_limit'] ) ) ) : 5;
        $excerpt_length = isset( $_POST['excerpt_length'] ) ? max( 50, min( 500, intval( $_POST['excerpt_length'] ) ) ) : 100;
        $excerpt_prompt = isset( $_POST['excerpt_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt_prompt'] ) ) : '';

        update_option( 'drea_ai_optimizer_provider', $provider );
        update_option( 'drea_ai_optimizer_model', $model );
        update_option( 'drea_ai_optimizer_opt_tags', $opt_tags );
        update_option( 'drea_ai_optimizer_opt_slug', $opt_slug );
        update_option( 'drea_ai_optimizer_opt_excerpt', $opt_excerpt );
        update_option( 'drea_ai_optimizer_tag_limit', $tag_limit );
        update_option( 'drea_ai_optimizer_excerpt_length', $excerpt_length );
        update_option( 'drea_ai_optimizer_excerpt_prompt', $excerpt_prompt );

        // API Key 加密存储
        if ( ! empty( $api_key ) ) {
            update_option( 'drea_ai_optimizer_api_key', AI_Client::encrypt( $api_key ) );
        }

        wp_send_json_success( [
            'message' => __( '设置已保存。', 'dreamanual-toolkit' ),
        ] );
    }

    /**
     * AJAX: 获取设置
     */
    public function ajax_get_settings(): void {
        check_ajax_referer( 'drea_ai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '权限不足。', 'dreamanual-toolkit' ) );
        }

        wp_send_json_success( $this->get_settings_array() );
    }

    // ─── 辅助方法 ──────────────────────────────────────

    /**
     * 获取设置数组（API Key 解密）
     *
     * @return array
     */
    private function get_settings_array(): array {
        $encrypted_key = get_option( 'drea_ai_optimizer_api_key', '' );
        $api_key       = '';

        // 尝试解密；如果是旧版明文 key 则直接使用
        if ( ! empty( $encrypted_key ) ) {
            $decrypted = AI_Client::decrypt( $encrypted_key );
            $api_key   = ! empty( $decrypted ) ? $decrypted : $encrypted_key;
        }

        return [
            'provider'       => get_option( 'drea_ai_optimizer_provider', 'deepseek' ),
            'model'          => get_option( 'drea_ai_optimizer_model', 'deepseek-chat' ),
            'api_key'        => $api_key,
            'opt_tags'       => (bool) get_option( 'drea_ai_optimizer_opt_tags', true ),
            'opt_slug'       => (bool) get_option( 'drea_ai_optimizer_opt_slug', true ),
            'opt_excerpt'    => (bool) get_option( 'drea_ai_optimizer_opt_excerpt', false ),
            'tag_limit'      => (int) get_option( 'drea_ai_optimizer_tag_limit', 5 ),
            'excerpt_length' => (int) get_option( 'drea_ai_optimizer_excerpt_length', 100 ),
            'excerpt_prompt' => get_option( 'drea_ai_optimizer_excerpt_prompt', '' ),
        ];
    }
}

// 注册模块到 Core
Core::get_instance()->register_module( new AI_Optimizer() );
