<?php
/**
 * 模块管理页面模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;

/** @var \DREA\Core $core */
$core    = \DREA\Core::get_instance();
$modules = $core->get_modules();
?>
<div class="wrap drea-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <span class="drea-wrap__version">v<?php echo esc_html( DREA_VERSION ); ?></span>
    </h1>

    <?php if ( empty( $modules ) ) : ?>
        <div class="drea-notice drea-notice--info">
            <p><?php esc_html_e( '未发现可用模块。', 'dreamanual-toolkit' ); ?></p>
        </div>
    <?php else : ?>
        <div class="drea-modules">
            <?php foreach ( $modules as $module ) :
                $is_active = $core->is_module_active( $module->get_id() );
                $toggle_url = add_query_arg( [
                    'action'      => 'drea_toggle_module',
                    'module_id'   => $module->get_id(),
                    'action_type' => $is_active ? 'deactivate' : 'activate',
                    'nonce'       => wp_create_nonce( 'drea_toolkit_nonce' ),
                ], admin_url( 'admin-ajax.php' ) );
            ?>
                <div class="drea-module-card<?php echo $is_active ? ' drea-module-card--active' : ''; ?>"
                     data-module-id="<?php echo esc_attr( $module->get_id() ); ?>">
                    <div class="drea-module-card__header">
                        <h2 class="drea-module-card__name"><?php echo esc_html( $module->get_name() ); ?></h2>
                        <label class="drea-toggle">
                            <input type="checkbox"
                                   class="drea-toggle__input"
                                   data-module-id="<?php echo esc_attr( $module->get_id() ); ?>"
                                   <?php checked( $is_active ); ?> />
                            <span class="drea-toggle__slider"></span>
                        </label>
                    </div>
                    <p class="drea-module-card__desc"><?php echo esc_html( $module->get_description() ); ?></p>
                    <div class="drea-module-card__footer">
                        <span class="drea-module-card__status">
                            <?php echo $is_active
                                ? esc_html__( '已启用', 'dreamanual-toolkit' )
                                : esc_html__( '未启用', 'dreamanual-toolkit' ); ?>
                        </span>
                        <?php if ( $is_active && method_exists( $module, 'get_settings_url' ) ) : ?>
                            <a href="<?php echo esc_url( $module->get_settings_url() ); ?>"
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
