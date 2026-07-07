<?php
/**
 * 模块基类 —— 所有模块必须继承此类
 *
 * @package Dreamanual_Toolkit
 */

namespace DREA;

defined( 'ABSPATH' ) || exit;

abstract class Module_Base {

    /**
     * 模块唯一 ID（如 'ai-optimizer'）
     *
     * @return string
     */
    abstract public function get_id(): string;

    /**
     * 模块显示名（如 'AI 优化'）
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * 模块描述
     *
     * @return string
     */
    abstract public function get_description(): string;

    /**
     * 模块版本号
     *
     * @return string
     */
    public function get_version(): string {
        return DREA_VERSION;
    }

    /**
     * 模块主文件路径（相对于插件根目录）
     *
     * @return string
     */
    public function get_path(): string {
        return 'modules/' . $this->get_id() . '/' . $this->get_id() . '.php';
    }

    /**
     * 注册 WordPress hooks —— 模块启用时由 Core 调用
     *
     * @return void
     */
    abstract public function register_hooks(): void;

    /**
     * 模块启用时执行 —— 初始化默认配置、创建数据表等
     *
     * @return void
     */
    public function on_activate(): void {
        // 默认空实现，子类按需覆盖
    }

    /**
     * 模块停用时执行 —— 清理 hook、可选保留数据
     *
     * @return void
     */
    public function on_deactivate(): void {
        // 默认空实现，子类按需覆盖
    }

    /**
     * 模块卸载时执行 —— 删除该模块所有 option 和数据表
     *
     * @return void
     */
    public function uninstall(): void {
        // 默认空实现，子类按需覆盖
    }

    /**
     * 获取模块 option 值的快捷方法
     *
     * @param string $key     option 名（不含前缀）。
     * @param mixed  $default 默认值。
     * @return mixed
     */
    protected function get_option( string $key, $default = false ) {
        return get_option( 'drea_' . str_replace( '-', '_', $this->get_id() ) . '_' . $key, $default );
    }

    /**
     * 更新模块 option 的快捷方法
     *
     * @param string $key   option 名（不含前缀）。
     * @param mixed  $value 值。
     * @return bool
     */
    protected function update_option( string $key, $value ): bool {
        return update_option( 'drea_' . str_replace( '-', '_', $this->get_id() ) . '_' . $key, $value );
    }

    /**
     * 删除模块 option 的快捷方法
     *
     * @param string $key option 名（不含前缀）。
     * @return bool
     */
    protected function delete_option( string $key ): bool {
        return delete_option( 'drea_' . str_replace( '-', '_', $this->get_id() ) . '_' . $key );
    }

    /**
     * 模块是否已启用
     *
     * @return bool
     */
    public function is_active(): bool {
        return Core::get_instance()->is_module_active( $this->get_id() );
    }
}
