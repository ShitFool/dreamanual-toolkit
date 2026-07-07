<?php
/**
 * Dreamanual Toolkit 全局卸载脚本
 *
 * 当用户在 WordPress 中选择"删除"插件时触发。
 * 逐个调用各模块的 uninstall() 方法清理数据，
 * 最后删除核心 option。
 *
 * @package Dreamanual_Toolkit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
defined( 'ABSPATH' ) || exit;

// 加载核心文件以获取模块定义
require_once plugin_dir_path( __FILE__ ) . 'includes/class-module.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-core.php';

// 扫描并加载所有模块
$drea_modules_dir = plugin_dir_path( __FILE__ ) . 'modules/';
if ( is_dir( $drea_modules_dir ) ) {
    $drea_module_dirs = glob( $drea_modules_dir . '*', GLOB_ONLYDIR );
    foreach ( $drea_module_dirs as $drea_dir ) {
        $drea_module_id   = basename( $drea_dir );
        $drea_module_file = $drea_dir . '/' . $drea_module_id . '.php';
        if ( file_exists( $drea_module_file ) ) {
            require_once $drea_module_file;
        }
    }
}

// 获取所有已注册模块并执行卸载
$drea_core    = \DREA\Core::get_instance();
$drea_modules = $drea_core->get_modules();

foreach ( $drea_modules as $drea_module ) {
    $drea_module->uninstall();
}

// 删除核心 option
delete_option( 'drea_active_modules' );
