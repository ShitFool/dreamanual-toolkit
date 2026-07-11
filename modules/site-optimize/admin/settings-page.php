<?php
/**
 * 站点优化 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$groups = \DREA\Site_Optimize::get_groups();
$defaults = \DREA\Site_Optimize::get_features();
?>
<div class="wrap drea-wrap drea-so-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__( '站点优化', 'dreamanual-toolkit' ); ?>
    </h1>

    <?php foreach ( $groups as $group_id => $group ) : ?>
    <div class="drea-section">
        <div class="drea-section__header">
            <h2><?php echo esc_html( $group['label'] ); ?></h2>
        </div>
        <div class="drea-section__body">
            <?php foreach ( $group['features'] as $key => $label ) :
                $enabled = (bool) get_option( 'drea_site_optimize_' . $key, $defaults[ $key ] );
            ?>
            <div class="drea-settings-row">
                <div class="drea-settings-row__label"><?php echo esc_html( $label ); ?></div>
                <div class="drea-settings-row__action">
                    <label class="drea-toggle">
                        <input type="checkbox"
                               id="drea-so-<?php echo esc_attr( $key ); ?>"
                               name="<?php echo esc_attr( $key ); ?>"
                               data-key="<?php echo esc_attr( $key ); ?>"
                               <?php checked( $enabled ); ?>>
                        <span class="drea-toggle__slider"></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <p class="submit">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-so-save-btn"><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
        <span class="drea-so-save-hint"><?php echo esc_html__( '部分设置保存后需刷新页面生效', 'dreamanual-toolkit' ); ?></span>
    </p>
</div>
