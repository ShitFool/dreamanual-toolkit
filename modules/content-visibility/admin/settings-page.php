<?php
/**
 * 内容可见性 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$drea_categories = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );
$drea_all_roles  = wp_roles()->roles;
$drea_rules      = $this->get_rules(); // 模板在实例方法 render_settings_page() 内 include，$this 可用
$drea_channels   = \DREA\Content_Visibility::CHANNELS;

$drea_channel_labels = [
    'frontend' => __('Frontend Pages', 'dreamanual-toolkit' ),
    'rss'      => __('RSS Feed', 'dreamanual-toolkit' ),
    'rest_api' => __( 'REST API', 'dreamanual-toolkit' ),
    'search'   => __('Site Search', 'dreamanual-toolkit' ),
    'sitemap'  => __('Sitemap', 'dreamanual-toolkit' ),
];
?>
<div class="wrap drea-wrap drea-cv-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__('Content Visibility', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__('Configure display channels and visible roles per category. Uncategorized posts display normally on all channels.', 'dreamanual-toolkit' ); ?></p>

    <!-- 使用说明 -->
    <div class="drea-cv-guide">
        <h3><?php echo esc_html__('Instructions', 'dreamanual-toolkit' ); ?></h3>
        <ol>
            <li><?php echo esc_html__('Channel column checked = category content shown on that channel; unchecked = hidden. All checked by default (show everywhere).', 'dreamanual-toolkit' ); ?></li>
            <li><?php echo esc_html__('"Visible After Hiding" determines which logged-in roles can still see content when a channel is hidden. If none selected, only administrators can see it.', 'dreamanual-toolkit' ); ?></li>
            <li><?php echo esc_html__('All channels checked (default) = no processing for this category.', 'dreamanual-toolkit' ); ?></li>
        </ol>
        <p><strong><?php echo esc_html__('Examples:', 'dreamanual-toolkit' ); ?></strong><?php echo esc_html__('"Diary" category: uncheck all channels + set visible role to "Administrator" = hidden site-wide, only administrators can see (direct links also return 404).', 'dreamanual-toolkit' ); ?></p>
        <p><strong><?php echo esc_html__('Examples:', 'dreamanual-toolkit' ); ?></strong><?php echo esc_html__('"Mini-Program Picks" category: check only REST API, uncheck others + set visible role to "Administrator" = hidden on frontend and direct link returns 404, but mini-program can still read via API.', 'dreamanual-toolkit' ); ?></p>
    </div>

    <!-- Toast -->
    <div class="drea-toast-container" id="drea-cv-toast-container"></div>

    <div class="drea-cv-rules-panel">
        <div class="drea-cv-rules-header">
            <h2><?php echo esc_html__('Category Visibility Rules', 'dreamanual-toolkit' ); ?></h2>
            <button type="button" class="drea-btn drea-btn--primary" id="drea-cv-save-btn" disabled><?php echo esc_html__('Save Rules', 'dreamanual-toolkit' ); ?></button>
        </div>

        <table class="wp-list-table widefat fixed striped drea-cv-rules-table">
            <thead>
                <tr>
                    <th class="drea-cv-col-cat"><?php echo esc_html__('Category', 'dreamanual-toolkit' ); ?></th>
                    <?php foreach ( $drea_channels as $drea_ch ) : ?>
                        <th class="drea-cv-col-channel"><?php echo esc_html( $drea_channel_labels[ $drea_ch ] ?? $drea_ch ); ?></th>
                    <?php endforeach; ?>
                    <th class="drea-cv-col-roles"><?php echo esc_html__('Visible After Hiding', 'dreamanual-toolkit' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $drea_categories ) ) : ?>
                    <tr>
                        <td colspan="<?php echo esc_attr( count( $drea_channels ) + 2 ); ?>" class="drea-cv-empty">
                            <?php echo esc_html__('No categories yet. Please create categories in "Posts → Categories" before configuring visibility rules.', 'dreamanual-toolkit' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                <?php foreach ( $drea_categories as $drea_cat ) :
                    $drea_rule        = $drea_rules[ $drea_cat->term_id ] ?? null;
                    $drea_hidden_chs  = $drea_rule ? ( $drea_rule['channels'] ?? [] ) : [];
                    $drea_ro_active   = $drea_rule ? ( $drea_rule['roles'] ?? [] ) : [];
                ?>
                <tr data-cat-id="<?php echo esc_attr( $drea_cat->term_id ); ?>">
                    <td><strong><?php echo esc_html( $drea_cat->name ); ?></strong> <span class="drea-cv-cat-count">(<?php echo intval( $drea_cat->count ); ?>)</span></td>
                    <?php foreach ( $drea_channels as $drea_ch ) : ?>
                        <td class="drea-cv-cell-center">
                            <input type="checkbox"
                                   class="drea-cv-channel"
                                   data-cat-id="<?php echo esc_attr( $drea_cat->term_id ); ?>"
                                   data-channel="<?php echo esc_attr( $drea_ch ); ?>"
                                   <?php checked( ! in_array( $drea_ch, $drea_hidden_chs, true ) ); ?> />
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <select class="drea-cv-roles"
                                data-cat-id="<?php echo esc_attr( $drea_cat->term_id ); ?>"
                                multiple
                                style="width:100%;min-height:60px;">
                            <?php foreach ( $drea_all_roles as $drea_role_name => $drea_role_info ) : ?>
                                <option value="<?php echo esc_attr( $drea_role_name ); ?>"
                                        <?php echo in_array( $drea_role_name, $drea_ro_active, true ) ? 'selected' : ''; ?>>
                                    <?php echo esc_html( translate_user_role( $drea_role_info['name'] ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 文章级隐藏说明 -->
    <div class="drea-cv-posts-info">
        <h2><?php echo esc_html__('Post-Level Hide', 'dreamanual-toolkit' ); ?></h2>
        <p><?php echo esc_html__('On the post list page, quickly hide individual posts via the "Visibility" column or row actions. Hidden posts won\'t appear in lists, and direct links return 404.', 'dreamanual-toolkit' ); ?></p>
    </div>
</div>
