<?php
/**
 * 内容可见性 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$categories = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );
$all_roles  = wp_roles()->roles;
$rules      = $this->get_rules(); // 模板在实例方法 render_settings_page() 内 include，$this 可用
$channels   = \DREA\Content_Visibility::CHANNELS;

$channel_labels = [
    'frontend' => __( '前端页面', 'dreamanual-toolkit' ),
    'rss'      => __( 'RSS 订阅', 'dreamanual-toolkit' ),
    'rest_api' => __( 'REST API', 'dreamanual-toolkit' ),
    'search'   => __( '站内搜索', 'dreamanual-toolkit' ),
    'sitemap'  => __( '站点地图', 'dreamanual-toolkit' ),
];
?>
<div class="wrap drea-wrap drea-cv-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__( '内容可见性', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__( '按分类配置内容显示渠道和可见角色。未配置的分类在所有渠道正常显示。', 'dreamanual-toolkit' ); ?></p>

    <!-- 使用说明 -->
    <div class="drea-cv-guide">
        <h3><?php echo esc_html__( '使用说明', 'dreamanual-toolkit' ); ?></h3>
        <ol>
            <li><?php echo esc_html__( '渠道列勾选 = 该分类内容在此渠道显示；取消勾选 = 在此渠道隐藏。默认全勾选（全部显示）。', 'dreamanual-toolkit' ); ?></li>
            <li><?php echo esc_html__( '「隐藏后仍可见角色」决定：渠道被隐藏时，哪些登录角色仍能看见。不选则仅管理员可见。', 'dreamanual-toolkit' ); ?></li>
            <li><?php echo esc_html__( '所有渠道都勾选（默认状态）= 该分类不做任何处理。', 'dreamanual-toolkit' ); ?></li>
        </ol>
        <p><strong><?php echo esc_html__( '示例：', 'dreamanual-toolkit' ); ?></strong><?php echo esc_html__( '"日记"分类，取消所有渠道勾选 + 可见角色选「管理员」= 全站隐藏，只有管理员能看（包括直链也会 404）。', 'dreamanual-toolkit' ); ?></p>
        <p><strong><?php echo esc_html__( '示例：', 'dreamanual-toolkit' ); ?></strong><?php echo esc_html__( '"小程序精选"分类，只勾选 REST API，其他取消 + 可见角色选「管理员」= 前台不显示且直链 404，但小程序通过 API 仍能读取。', 'dreamanual-toolkit' ); ?></p>
    </div>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-cv-toast-container"></div>

    <div class="drea-cv-rules-panel">
        <div class="drea-cv-rules-header">
            <h2><?php echo esc_html__( '分类可见性规则', 'dreamanual-toolkit' ); ?></h2>
            <button type="button" class="drea-btn drea-btn--primary" id="drea-cv-save-btn"><?php echo esc_html__( '保存规则', 'dreamanual-toolkit' ); ?></button>
        </div>

        <table class="wp-list-table widefat fixed striped drea-cv-rules-table">
            <thead>
                <tr>
                    <th class="drea-cv-col-cat"><?php echo esc_html__( '分类', 'dreamanual-toolkit' ); ?></th>
                    <?php foreach ( $channels as $ch ) : ?>
                        <th class="drea-cv-col-channel"><?php echo esc_html( $channel_labels[ $ch ] ?? $ch ); ?></th>
                    <?php endforeach; ?>
                    <th class="drea-cv-col-roles"><?php echo esc_html__( '隐藏后仍可见角色', 'dreamanual-toolkit' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $categories as $cat ) :
                    $rule        = $rules[ $cat->term_id ] ?? null;
                    $hidden_chs  = $rule ? ( $rule['channels'] ?? [] ) : [];
                    $ro_active   = $rule ? ( $rule['roles'] ?? [] ) : [];
                ?>
                <tr data-cat-id="<?php echo esc_attr( $cat->term_id ); ?>">
                    <td><strong><?php echo esc_html( $cat->name ); ?></strong> <span class="drea-cv-cat-count">(<?php echo intval( $cat->count ); ?>)</span></td>
                    <?php foreach ( $channels as $ch ) : ?>
                        <td class="drea-cv-cell-center">
                            <input type="checkbox"
                                   class="drea-cv-channel"
                                   data-cat-id="<?php echo esc_attr( $cat->term_id ); ?>"
                                   data-channel="<?php echo esc_attr( $ch ); ?>"
                                   <?php checked( ! in_array( $ch, $hidden_chs, true ) ); ?> />
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <select class="drea-cv-roles"
                                data-cat-id="<?php echo esc_attr( $cat->term_id ); ?>"
                                multiple
                                style="width:100%;min-height:60px;">
                            <?php foreach ( $all_roles as $role_name => $role_info ) : ?>
                                <option value="<?php echo esc_attr( $role_name ); ?>"
                                        <?php echo in_array( $role_name, $ro_active, true ) ? 'selected' : ''; ?>>
                                    <?php echo esc_html( translate_user_role( $role_info['name'] ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 文章级隐藏说明 -->
    <div class="drea-cv-posts-info">
        <h2><?php echo esc_html__( '文章级隐藏', 'dreamanual-toolkit' ); ?></h2>
        <p><?php echo esc_html__( '在文章列表页，可通过"可见性"列或行操作快速隐藏单篇文章。隐藏的文章不会出现在列表中，直链访问也会返回 404。', 'dreamanual-toolkit' ); ?></p>
    </div>
</div>
