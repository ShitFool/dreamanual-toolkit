<?php
/**
 * 站点优化 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

$drea_groups = \DREA\Site_Optimize::get_groups();
$drea_defaults = \DREA\Site_Optimize::get_features();

// 广告拦截规则：读取用户自定义；为空时回退默认规则集
$drea_adblock_rules_raw = get_option( 'drea_site_optimize_adblock_rules', [] );
if ( ! is_array( $drea_adblock_rules_raw ) || empty( $drea_adblock_rules_raw ) ) {
    $drea_adblock_rules_raw = \DREA\Site_Optimize::get_default_adblock_rules();
}
$drea_adblock_rules_text = implode( "\n", $drea_adblock_rules_raw );
?>
<div class="wrap drea-wrap drea-so-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__('Site Optimization', 'dreamanual-toolkit' ); ?>
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
                               class="drea-toggle__input"
                               id="drea-so-<?php echo esc_attr( $drea_key ); ?>"
                               name="<?php echo esc_attr( $drea_key ); ?>"
                               data-key="<?php echo esc_attr( $drea_key ); ?>"
                               <?php checked( $drea_enabled ); ?>>
                        <span class="drea-toggle__slider"></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ( 'adblock' === $drea_group_id ) : ?>
            <div class="drea-settings-row drea-settings-row--textarea">
                <div class="drea-settings-row__label">
                    <?php echo esc_html__('Block Rules (one CSS selector per line)', 'dreamanual-toolkit' ); ?>
                    <small>
                        <?php echo esc_html__('Default rules are pre-filled. Empty rules fall back to defaults.', 'dreamanual-toolkit' ); ?>
                    </small>
                </div>
                <div class="drea-settings-row__action" style="width:100%;max-width:520px;">
                    <textarea id="drea-so-adblock-rules"
                              class="drea-textarea drea-adblock-rules"
                              name="adblock_rules"
                              rows="8"
                              spellcheck="false"
                              placeholder="<?php echo esc_attr__('.example-upsell{display:none;}', 'dreamanual-toolkit' ); ?>"><?php echo esc_textarea( $drea_adblock_rules_text ); ?></textarea>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <p class="submit">
        <button type="button" class="drea-btn drea-btn--primary" id="drea-so-save-btn" disabled><?php echo esc_html__('Save Settings', 'dreamanual-toolkit' ); ?></button>
        <span class="drea-so-save-hint"><?php echo esc_html__('Some settings require page refresh to take effect', 'dreamanual-toolkit' ); ?></span>
    </p>
</div>