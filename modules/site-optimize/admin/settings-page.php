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
<div class="wrap drea-so-wrap">
    <h1><?php echo esc_html__( '站点优化', 'dreamanual-toolkit' ); ?></h1>

    <?php foreach ( $groups as $group_id => $group ) : ?>
    <div class="drea-so-section">
        <div class="drea-so-section__header">
            <h2><?php echo esc_html( $group['label'] ); ?></h2>
        </div>
        <div class="drea-so-section__body">
            <table class="form-table">
                <?php foreach ( $group['features'] as $key => $label ) :
                    $enabled = (bool) get_option( 'drea_site_optimize_' . $key, $defaults[ $key ] );
                ?>
                <tr>
                    <th><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label class="drea-so-toggle">
                            <input type="checkbox"
                                   id="drea-so-<?php echo esc_attr( $key ); ?>"
                                   name="<?php echo esc_attr( $key ); ?>"
                                   data-key="<?php echo esc_attr( $key ); ?>"
                                   <?php checked( $enabled ); ?>>
                            <span class="drea-so-toggle__slider"></span>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <p class="submit">
        <button type="button" class="button button-primary" id="drea-so-save-btn"><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
        <span class="drea-so-save-hint"><?php echo esc_html__( '部分设置保存后需刷新页面生效', 'dreamanual-toolkit' ); ?></span>
    </p>
</div>
