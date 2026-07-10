<?php
/**
 * 搜索推送模块 — 文章发布/更新时自动推送到百度、Bing、IndexNow
 *
 * 每个搜索引擎独立开关，互不耦合。
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

class Search_Push extends Module_Base {

    /** @var string 模块 ID */
    const MODULE_ID = 'search-push';

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
        return __( '搜索推送', 'dreamanual-toolkit' );
    }

    /**
     * {@inheritdoc}
     */
    public function get_description(): string {
        return __( '文章发布/更新时自动推送链接到百度、Bing、IndexNow，提升搜索引擎收录效率。', 'dreamanual-toolkit' );
    }

    /**
     * 获取设置页 URL
     */
    public function get_settings_url(): string {
        return admin_url( 'admin.php?page=drea-sp' );
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {
        // 管理菜单 & 资源
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_drea_sp_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_drea_sp_get_settings', [ $this, 'ajax_get_settings' ] );
        add_action( 'wp_ajax_drea_sp_test_push', [ $this, 'ajax_test_push' ] );

        // ─── IndexNow key 验证路由 ───
        if ( $this->get_option( 'indexnow_enabled', false ) ) {
            add_action( 'parse_request', [ $this, 'indexnow_key_route' ] );
        }

        // ─── 发布/更新文章时推送 ───
        $any_enabled = (
            $this->get_option( 'baidu_enabled', false ) ||
            $this->get_option( 'bing_enabled', false ) ||
            $this->get_option( 'indexnow_enabled', false )
        );
        if ( $any_enabled ) {
            add_action( 'transition_post_status', [ $this, 'on_post_status_change' ], 10, 3 );
            add_action( 'drea_sp_delayed_push', [ $this, 'do_delayed_push' ] );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function on_activate(): void {
        // 迁移旧 baidu-submit-link 插件设置
        $old = get_option( 'bsl_option' );
        if ( is_array( $old ) && false === get_option( 'drea_search_push_baidu_enabled' ) ) {
            // 百度
            $baidu_token = isset( $old['token'] ) ? $old['token'] : '';
            if ( ! empty( $baidu_token ) && ! empty( $old['in_bd_active'] ) ) {
                update_option( 'drea_search_push_baidu_enabled', true );
                // token 可能是完整 URL 或纯 token
                if ( 0 === strpos( $baidu_token, 'http' ) ) {
                    // 提取 token 参数
                    $parts = wp_parse_url( $baidu_token );
                    if ( ! empty( $parts['query'] ) ) {
                        wp_parse_str( $parts['query'], $q );
                        if ( ! empty( $q['token'] ) ) {
                            update_option( 'drea_search_push_baidu_token', sanitize_text_field( $q['token'] ) );
                        }
                    }
                } else {
                    update_option( 'drea_search_push_baidu_token', sanitize_text_field( $baidu_token ) );
                }
            }

            // Bing
            if ( ! empty( $old['bing_key'] ) && ( ! empty( $old['bing_auto'] ) || ! empty( $old['bing_manual'] ) ) ) {
                update_option( 'drea_search_push_bing_enabled', true );
                update_option( 'drea_search_push_bing_key', sanitize_text_field( $old['bing_key'] ) );
            }

            // IndexNow
            if ( ! empty( $old['indexnow_key'] ) ) {
                update_option( 'drea_search_push_indexnow_enabled', ! empty( $old['indexnow'] ) );
                update_option( 'drea_search_push_indexnow_key', sanitize_text_field( $old['indexnow_key'] ) );
            }
        }

        // 自动生成 IndexNow key（如果未设置）
        if ( ! get_option( 'drea_search_push_indexnow_key' ) ) {
            update_option( 'drea_search_push_indexnow_key', md5( AUTH_KEY . home_url() ) );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        $options = [
            'drea_search_push_baidu_enabled',
            'drea_search_push_baidu_token',
            'drea_search_push_bing_enabled',
            'drea_search_push_bing_key',
            'drea_search_push_indexnow_enabled',
            'drea_search_push_indexnow_key',
        ];
        foreach ( $options as $opt ) {
            delete_option( $opt );
        }
    }

    // ─── 管理菜单 ─────────────────────────────────────

    public function add_admin_menu(): void {
        add_submenu_page(
            'dreamanual-toolkit',
            __( '搜索推送', 'dreamanual-toolkit' ),
            __( '搜索推送', 'dreamanual-toolkit' ),
            'manage_options',
            'drea-sp',
            [ $this, 'render_page' ]
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '您没有权限访问此页面。', 'dreamanual-toolkit' ) );
        }
        include __DIR__ . '/admin/settings-page.php';
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( false === strpos( $hook, 'drea-sp' ) ) return;

        $module_url  = DREA_URL . 'modules/search-push';
        $module_path = DREA_PATH . 'modules/search-push';

        wp_enqueue_style(
            'drea-sp-admin',
            $module_url . '/assets/css/admin.css',
            [],
            filemtime( $module_path . '/assets/css/admin.css' )
        );

        wp_enqueue_script(
            'drea-sp-admin',
            $module_url . '/assets/js/admin.js',
            [],
            filemtime( $module_path . '/assets/js/admin.js' ),
            true
        );

        wp_localize_script( 'drea-sp-admin', 'dreaSp', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'drea_sp_nonce' ),
            'i18n'    => [
                'saved'    => __( '设置已保存。', 'dreamanual-toolkit' ),
                'failed'   => __( '保存失败，请重试。', 'dreamanual-toolkit' ),
                'error'    => __( '操作失败。', 'dreamanual-toolkit' ),
                'testOk'   => __( '推送成功。', 'dreamanual-toolkit' ),
                'testFail' => __( '推送失败，请检查设置。', 'dreamanual-toolkit' ),
            ],
        ] );
    }

    // ─── AJAX ─────────────────────────────────────────

    public function ajax_save_settings(): void {
        check_ajax_referer( 'drea_sp_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $baidu_enabled    = isset( $_POST['baidu_enabled'] ) ? boolval( $_POST['baidu_enabled'] ) : false;
        $baidu_token      = isset( $_POST['baidu_token'] ) ? sanitize_text_field( wp_unslash( $_POST['baidu_token'] ) ) : '';
        $bing_enabled     = isset( $_POST['bing_enabled'] ) ? boolval( $_POST['bing_enabled'] ) : false;
        $bing_key         = isset( $_POST['bing_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bing_key'] ) ) : '';
        $indexnow_enabled = isset( $_POST['indexnow_enabled'] ) ? boolval( $_POST['indexnow_enabled'] ) : false;
        $indexnow_key     = isset( $_POST['indexnow_key'] ) ? sanitize_text_field( wp_unslash( $_POST['indexnow_key'] ) ) : '';

        update_option( 'drea_search_push_baidu_enabled', $baidu_enabled );
        update_option( 'drea_search_push_baidu_token', $baidu_token );
        update_option( 'drea_search_push_bing_enabled', $bing_enabled );
        update_option( 'drea_search_push_bing_key', $bing_key );
        update_option( 'drea_search_push_indexnow_enabled', $indexnow_enabled );
        update_option( 'drea_search_push_indexnow_key', $indexnow_key );

        wp_send_json_success( [ 'message' => __( '设置已保存。', 'dreamanual-toolkit' ) ] );
    }

    public function ajax_get_settings(): void {
        check_ajax_referer( 'drea_sp_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        wp_send_json_success( [
            'baidu_enabled'    => (bool) $this->get_option( 'baidu_enabled', false ),
            'baidu_token'      => $this->get_option( 'baidu_token', '' ),
            'bing_enabled'     => (bool) $this->get_option( 'bing_enabled', false ),
            'bing_key'         => $this->get_option( 'bing_key', '' ),
            'indexnow_enabled' => (bool) $this->get_option( 'indexnow_enabled', false ),
            'indexnow_key'     => $this->get_option( 'indexnow_key', '' ),
        ] );
    }

    /**
     * AJAX: 测试推送
     */
    public function ajax_test_push(): void {
        check_ajax_referer( 'drea_sp_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( '权限不足', 'dreamanual-toolkit' ) ] );
        }

        $engine  = isset( $_POST['engine'] ) ? sanitize_text_field( wp_unslash( $_POST['engine'] ) ) : '';
        $test_url = home_url( '/' );

        $result = false;
        switch ( $engine ) {
            case 'baidu':
                $result = $this->push_baidu( [ $test_url ] );
                break;
            case 'bing':
                $result = $this->push_bing( [ $test_url ] );
                break;
            case 'indexnow':
                $result = $this->push_indexnow( [ $test_url ] );
                break;
            default:
                wp_send_json_error( [ 'message' => __( '未知的搜索引擎。', 'dreamanual-toolkit' ) ] );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => __( '推送请求已发送。', 'dreamanual-toolkit' ) ] );
    }

    // ─── 推送触发 ─────────────────────────────────────

    /**
     * 文章状态变更时触发推送
     *
     * @param string   $new_status 新状态。
     * @param string   $old_status 旧状态。
     * @param \WP_Post $post       文章对象。
     */
    public function on_post_status_change( string $new_status, string $old_status, \WP_Post $post ): void {
        // 仅处理 post 类型
        if ( 'post' !== $post->post_type ) return;

        // 仅在首次发布或从非 publish 变为 publish 时推送
        if ( 'publish' !== $new_status ) return;
        if ( 'publish' === $old_status && 'publish' === $new_status ) return;

        // 延迟 30 秒推送，避免阻塞发布流程
        wp_schedule_single_event( time() + 30, 'drea_sp_delayed_push', [ $post->ID ] );
    }

    /**
     * 延迟推送回调
     *
     * @param int $post_id 文章 ID。
     */
    public function do_delayed_push( int $post_id ): void {
        $post = get_post( $post_id );
        if ( ! $post || 'publish' !== $post->post_status ) return;

        $url = get_permalink( $post_id );
        if ( ! $url ) return;

        $urls = [ $url ];

        if ( $this->get_option( 'baidu_enabled', false ) ) {
            $this->push_baidu( $urls );
        }
        if ( $this->get_option( 'bing_enabled', false ) ) {
            $this->push_bing( $urls );
        }
        if ( $this->get_option( 'indexnow_enabled', false ) ) {
            $this->push_indexnow( $urls );
        }
    }

    // ─── 百度推送 ─────────────────────────────────────

    /**
     * 推送 URL 到百度普通收录
     *
     * @param string[] $urls URL 列表。
     * @return true|\WP_Error
     */
    protected function push_baidu( array $urls ) {
        $token    = $this->get_option( 'baidu_token', '' );
        $hostname = wp_parse_url( home_url(), PHP_URL_HOST );

        if ( ! $token || ! $hostname ) {
            return new \WP_Error( 'drea_sp_baidu', __( '百度推送 Token 未配置。', 'dreamanual-toolkit' ) );
        }

        $api_url = 'http://data.zz.baidu.com/urls?site=' . urlencode( $hostname ) . '&token=' . urlencode( $token );

        $response = wp_remote_post( $api_url, [
            'body'    => implode( "\n", $urls ),
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'drea_sp_baidu', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            $msg = isset( $body['message'] ) ? $body['message'] : __( '未知错误', 'dreamanual-toolkit' );
            return new \WP_Error( 'drea_sp_baidu', sprintf( __( '百度推送失败 (%d): %s', 'dreamanual-toolkit' ), $code, $msg ) );
        }

        return true;
    }

    // ─── Bing 推送 ─────────────────────────────────────

    /**
     * 推送 URL 到 Bing
     *
     * @param string[] $urls URL 列表。
     * @return true|\WP_Error
     */
    protected function push_bing( array $urls ) {
        $key = $this->get_option( 'bing_key', '' );
        if ( ! $key ) {
            return new \WP_Error( 'drea_sp_bing', __( 'Bing API Key 未配置。', 'dreamanual-toolkit' ) );
        }

        $site_url = home_url( '/' );
        $is_batch = count( $urls ) > 1;

        $api_url = $is_batch
            ? 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' . urlencode( $key )
            : 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl?apikey=' . urlencode( $key );

        $body = $is_batch
            ? wp_json_encode( [ 'siteUrl' => $site_url, 'urlList' => $urls ] )
            : wp_json_encode( [ 'siteUrl' => $site_url, 'url' => reset( $urls ) ] );

        $response = wp_remote_post( $api_url, [
            'headers' => [ 'Content-Type' => 'text/json; charset=utf-8' ],
            'body'    => $body,
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'drea_sp_bing', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $res  = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $res['ErrorCode'] ) && 0 !== (int) $res['ErrorCode'] ) {
            return new \WP_Error( 'drea_sp_bing', sprintf( __( 'Bing 推送失败: ErrorCode %d', 'dreamanual-toolkit' ), (int) $res['ErrorCode'] ) );
        }

        if ( $code >= 400 ) {
            return new \WP_Error( 'drea_sp_bing', sprintf( __( 'Bing 推送失败 (HTTP %d)', 'dreamanual-toolkit' ), $code ) );
        }

        return true;
    }

    // ─── IndexNow 推送 ─────────────────────────────────

    /**
     * 推送 URL 到 IndexNow（通过 Bing 端点）
     *
     * @param string[] $urls URL 列表。
     * @return true|\WP_Error
     */
    protected function push_indexnow( array $urls ) {
        $key = $this->get_option( 'indexnow_key', '' );
        if ( ! $key ) {
            return new \WP_Error( 'drea_sp_indexnow', __( 'IndexNow Key 未配置。', 'dreamanual-toolkit' ) );
        }

        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        $body = wp_json_encode( [
            'host'    => $host,
            'key'     => $key,
            'urlList' => $urls,
        ] );

        // 使用 Bing IndexNow 端点（一次推送通知多个搜索引擎）
        $api_url = 'https://www.bing.com/indexnow';

        $response = wp_remote_post( $api_url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => $body,
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'drea_sp_indexnow', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );

        // 200 = 成功, 202 = key 验证中
        if ( 200 === $code || 202 === $code ) {
            return true;
        }

        $messages = [
            400 => __( '请求格式无效', 'dreamanual-toolkit' ),
            403 => __( 'Key 无效', 'dreamanual-toolkit' ),
            422 => __( 'URL 不属于此站点', 'dreamanual-toolkit' ),
            429 => __( '请求过于频繁', 'dreamanual-toolkit' ),
        ];

        $msg = isset( $messages[ $code ] ) ? $messages[ $code ] : sprintf( __( 'HTTP %d', 'dreamanual-toolkit' ), $code );
        return new \WP_Error( 'drea_sp_indexnow', sprintf( __( 'IndexNow 推送失败: %s', 'dreamanual-toolkit' ), $msg ) );
    }

    /**
     * IndexNow key 验证路由
     * 搜索引擎请求 {key}.txt 时返回 key 值
     *
     * @param \WP $wp WP 对象。
     */
    public function indexnow_key_route( \WP $wp ): void {
        $key = $this->get_option( 'indexnow_key', '' );
        if ( ! $key || ! isset( $wp->request ) ) return;

        $expected = $key . '.txt';
        if ( $wp->request === $expected || trailingslashit( $wp->request ) === $expected ) {
            header( 'Content-Type: text/plain' );
            header( 'X-Robots-Tag: noindex' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- key is alphanumeric hash
            echo $key;
            exit;
        }
    }
}

// 注册模块
Core::get_instance()->register_module( new Search_Push() );
