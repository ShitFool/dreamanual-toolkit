<?php
/**
 * 站点优化 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$drea_groups = \DREA\Site_Optimize::get_groups();
$drea_defaults = \DREA\Site_Optimize::get_features();
?>
<div class="wrap drea-wrap drea-so-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__( '站点优化', 'dreamanual-toolkit' ); ?>
    </h1>

    <?php foreach ( $drea_groups as $drea_group_id => $drea_group ) : ?>
    <div class="drea-section">
        <div class="drea-section__header">
            <div class="drea-section__title">
                <h2><?php echo esc_html( $drea_group['label'] ); ?></h2>
            </div>
        </div>
        <div class="drea-section__body">
            <?php foreach ( $drea_group['features'] as $drea_key => $drea_label ) :
                $drea_enabled = (bool) get_option( 'drea_site_optimize_' . $drea_key, $drea_defaults[ $drea_key ] );
            ?>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html( $drea_label ); ?></div>
                <div class="drea-settings-row__action">
                    <label class="drea-toggle">
                        <input type="checkbox"
                               id="drea-so-<?php echo esc_attr( $drea_key ); ?>"
                               name="<?php echo esc_attr( $drea_key ); ?>"
                               data-key="<?php echo esc_attr( $drea_key ); ?>"
                               <?php checked( $drea_enabled ); ?>>
                        <span class="drea-toggle__slider"></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <p class="submit">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-so-save-btn" disabled><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
        <span class="drea-so-save-hint"><?php echo esc_html__( '部分设置保存后需刷新页面生效', 'dreamanual-toolkit' ); ?></span>
    </p>
</div>
