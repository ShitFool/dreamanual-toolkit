<?php
/**
 * AI 优化 — 设置页模板
 *
 * @package Dreamanual_Toolkit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap drea-wrap drea-ai-wrap">
    <h1 class="drea-wrap__title">
        <?php echo esc_html__( 'AI 优化 — 设置', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__( '配置 AI 提供商、模型和生成选项。保存后使用批量处理页或文章编辑器。', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast 容器 -->
    <div class="drea-toast-container" id="drea-ai-toast-container"></div>

    <div class="drea-ai-settings-panel">
        <h2><?php echo esc_html__( 'AI 配置', 'dreamanual-toolkit' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php echo esc_html__( 'AI 提供商', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <select id="ai-provider">
                        <option value="deepseek"><?php echo esc_html__( 'DeepSeek（推荐）', 'dreamanual-toolkit' ); ?></option>
                        <option value="kimi"><?php echo esc_html__( 'Kimi（月之暗面）', 'dreamanual-toolkit' ); ?></option>
                        <option value="openai"><?php echo esc_html__( 'OpenAI', 'dreamanual-toolkit' ); ?></option>
                        <option value="claude"><?php echo esc_html__( 'Claude（Anthropic）', 'dreamanual-toolkit' ); ?></option>
                    </select>
                    <p class="description">
                        <strong class="drea-text-success"><?php echo esc_html__( '推荐 DeepSeek —— 性价比高，审核宽松。如被拦截可切换。', 'dreamanual-toolkit' ); ?></strong>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'AI 模型', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <select id="ai-model"></select>
                    <p class="description"><?php echo esc_html__( '选择 AI 模型。如被审核拦截可切换。', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>API Key</th>
                <td>
                    <input type="password" id="ai-api-key" class="regular-text" placeholder="<?php echo esc_attr__( '输入 API Key', 'dreamanual-toolkit' ); ?>">
                    <p class="description"><?php echo esc_html__( 'API Key 将加密存储于数据库。', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
        </table>

        <h2 id="generation-options-toggle">
            <span class="dashicons dashicons-arrow-right"></span>
            <?php echo esc_html__( '生成选项', 'dreamanual-toolkit' ); ?>
            <span class="drea-gen-toggle-hint"><?php echo esc_html__( '（点击展开）', 'dreamanual-toolkit' ); ?></span>
        </h2>
        <div id="generation-options-panel" style="display:none;">
        <table class="form-table">
            <tr>
                <th><?php echo esc_html__( '默认开关', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-tags" checked> <?php echo esc_html__( '优化标签', 'dreamanual-toolkit' ); ?>
                    </label>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-slug" checked> <?php echo esc_html__( '优化 Slug', 'dreamanual-toolkit' ); ?>
                    </label>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-excerpt"> <?php echo esc_html__( '优化摘要', 'dreamanual-toolkit' ); ?>
                    </label>
                    <p class="description"><?php echo esc_html__( '选择 AI 优化的项目。建议一次只开一个，降低被审核拦截的风险。', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( '标签数量上限', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <input type="number" id="tag-limit" class="small-text" value="5" min="1" max="20" step="1">
                    <p class="description"><?php echo esc_html__( 'AI 生成的标签最大数量。默认 5，范围 1-20。', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( '摘要长度', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <input type="number" id="excerpt-length" class="small-text" value="100" min="50" max="500" step="10">
                    <span class="drea-unit-text"><?php echo esc_html__( '字', 'dreamanual-toolkit' ); ?></span>
                    <p class="description"><?php echo esc_html__( '控制摘要长度。默认 100，范围 50-500。如有自定义提示词则以提示词为准。', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( '摘要提示词', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <textarea id="excerpt-prompt" rows="8" class="drea-monospace-textarea" placeholder="<?php echo esc_attr( \DREA\AI_Optimizer::DEFAULT_EXCERPT_PROMPT ); ?>"></textarea>
                    <p class="description">
                        <?php echo esc_html__( '自定义摘要生成提示词，留空则使用默认（见输入框灰字）。支持的占位符:', 'dreamanual-toolkit' ); ?>
                        <code>{title}</code> <?php echo esc_html__( '标题', 'dreamanual-toolkit' ); ?>、
                        <code>{content}</code> <?php echo esc_html__( '内容', 'dreamanual-toolkit' ); ?>、
                        <code>{excerpt_length}</code> <?php echo esc_html__( '摘要长度', 'dreamanual-toolkit' ); ?>、
                        <code>{current_tags}</code> <?php echo esc_html__( '当前标签', 'dreamanual-toolkit' ); ?>、
                        <code>{existing_tags}</code> <?php echo esc_html__( '已有标签', 'dreamanual-toolkit' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        </div><!-- #generation-options-panel -->

        <p class="submit">
            <button type="button" class="drea-btn drea-btn--primary" id="save-settings-btn" disabled><?php echo esc_html__( '保存设置', 'dreamanual-toolkit' ); ?></button>
            <span class="spinner"></span>
        </p>
    </div>
</div>
