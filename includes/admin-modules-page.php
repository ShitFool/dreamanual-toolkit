<?php
/**
 * 模块管理页面模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

/** @var \DREA\Core $drea_core */
$drea_core    = \DREA\Core::get_instance();
$drea_modules = $drea_core->get_modules();
?>
<div class="wrap drea-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <span class="drea-wrap__version">v<?php echo esc_html( DREA_VERSION ); ?></span>
    </h1>

    <?php if ( empty( $drea_modules ) ) : ?>
        <div class="drea-notice drea-notice--info">
            <p><?php esc_html_e( '未发现可用模块。', 'dreamanual-toolkit' ); ?></p>
        </div>
    <?php else : ?>
        <div class="drea-modules">
            <?php foreach ( $drea_modules as $drea_module ) :
                $drea_is_active = $drea_core->is_module_active( $drea_module->get_id() );
            ?>
                <div class="drea-module-card<?php echo $drea_is_active ? ' drea-module-card--active' : ''; ?>"
                     data-module-id="<?php echo esc_attr( $drea_module->get_id() ); ?>">
                    <div class="drea-module-card__header">
                        <h2 class="drea-module-card__name"><?php echo esc_html( $drea_module->get_name() ); ?></h2>
                        <label class="drea-toggle">
                            <input type="checkbox"
                                   class="drea-toggle__input"
                                   data-module-id="<?php echo esc_attr( $drea_module->get_id() ); ?>"
                                   <?php checked( $drea_is_active ); ?> />
                            <span class="drea-toggle__slider"></span>
                        </label>
                    </div>
                    <p class="drea-module-card__desc"><?php echo esc_html( $drea_module->get_description() ); ?></p>
                    <div class="drea-module-card__footer">
                        <span class="drea-module-card__status">
                            <?php echo $drea_is_active
                                ? esc_html__( '已启用', 'dreamanual-toolkit' )
                                : esc_html__( '未启用', 'dreamanual-toolkit' ); ?>
                        </span>
                        <?php if ( $drea_is_active && method_exists( $drea_module, 'get_settings_url' ) ) : ?>
                            <a href="<?php echo esc_url( $drea_module->get_settings_url() ); ?>"
                               class="drea-module-card__link">
                                <?php esc_html_e( '设置', 'dreamanual-toolkit' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
