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
        <?php echo esc_html__('AI Optimizer — Settings', 'dreamanual-toolkit' ); ?>
    </h1>
    <p class="description"><?php echo esc_html__('Configure AI provider, model, and generation options. After saving, use the batch process page or post editor.', 'dreamanual-toolkit' ); ?></p>

    <!-- Toast 容器 -->
    <div class="drea-toast-container" id="drea-ai-toast-container"></div>

    <div class="drea-ai-settings-panel">
        <h2><?php echo esc_html__('AI Configuration', 'dreamanual-toolkit' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php echo esc_html__('AI Provider', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <select id="ai-provider">
                        <option value="deepseek"><?php echo esc_html__('DeepSeek (Recommended)', 'dreamanual-toolkit' ); ?></option>
                        <option value="kimi"><?php echo esc_html__('Kimi (Moonshot)', 'dreamanual-toolkit' ); ?></option>
                        <option value="openai"><?php echo esc_html__( 'OpenAI', 'dreamanual-toolkit' ); ?></option>
                        <option value="claude"><?php echo esc_html__('Claude (Anthropic)', 'dreamanual-toolkit' ); ?></option>
                    </select>
                    <p class="description">
                        <strong class="drea-text-success"><?php echo esc_html__('DeepSeek recommended — cost-effective with lenient content review. Switch if blocked.', 'dreamanual-toolkit' ); ?></strong>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('AI Model', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <select id="ai-model"></select>
                    <p class="description"><?php echo esc_html__('Select AI model. Switch if blocked by content review.', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>API Key</th>
                <td>
                    <input type="password" id="ai-api-key" class="regular-text" placeholder="<?php echo esc_attr__('Enter API Key', 'dreamanual-toolkit' ); ?>">
                    <p class="description"><?php echo esc_html__('API Key will be encrypted and stored in the database.', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
        </table>

        <h2 id="generation-options-toggle">
            <span class="dashicons dashicons-arrow-right"></span>
            <?php echo esc_html__('Generation Options', 'dreamanual-toolkit' ); ?>
            <span class="drea-gen-toggle-hint"><?php echo esc_html__('(click to expand)', 'dreamanual-toolkit' ); ?></span>
        </h2>
        <div id="generation-options-panel" style="display:none;">
        <table class="form-table">
            <tr>
                <th><?php echo esc_html__('Default Toggles', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-tags" checked> <?php echo esc_html__('Optimize Tags', 'dreamanual-toolkit' ); ?>
                    </label>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-slug" checked> <?php echo esc_html__('Optimize Slug', 'dreamanual-toolkit' ); ?>
                    </label>
                    <label class="drea-cb-label">
                        <input type="checkbox" id="opt-excerpt"> <?php echo esc_html__('Optimize Excerpt', 'dreamanual-toolkit' ); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('Select items for AI optimization. Recommended to enable only one at a time to reduce the risk of being blocked.', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Tag Count Limit', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <input type="number" id="tag-limit" class="small-text" value="5" min="1" max="20" step="1">
                    <p class="description"><?php echo esc_html__('Maximum number of tags AI generates. Default 5, range 1-20.', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Excerpt Length', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <input type="number" id="excerpt-length" class="small-text" value="100" min="50" max="500" step="10">
                    <span class="drea-unit-text"><?php echo esc_html__('chars', 'dreamanual-toolkit' ); ?></span>
                    <p class="description"><?php echo esc_html__('Control excerpt length. Default 100, range 50-500. If custom prompt is set, it takes precedence.', 'dreamanual-toolkit' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Excerpt Prompt', 'dreamanual-toolkit' ); ?></th>
                <td>
                    <textarea id="excerpt-prompt" rows="8" class="drea-monospace-textarea" placeholder="<?php echo esc_attr( \DREA\AI_Optimizer::DEFAULT_EXCERPT_PROMPT ); ?>"></textarea>
                    <p class="description">
                        <?php echo esc_html__('Custom excerpt generation prompt. Leave empty to use default (shown as placeholder text). Supported placeholders:', 'dreamanual-toolkit' ); ?>
                        <code>{title}</code> <?php echo esc_html__('Title', 'dreamanual-toolkit' ); ?>、
                        <code>{content}</code> <?php echo esc_html__('Content', 'dreamanual-toolkit' ); ?>、
                        <code>{excerpt_length}</code> <?php echo esc_html__('Excerpt Length', 'dreamanual-toolkit' ); ?>、
                        <code>{current_tags}</code> <?php echo esc_html__('Current Tags', 'dreamanual-toolkit' ); ?>、
                        <code>{existing_tags}</code> <?php echo esc_html__('Existing Tags', 'dreamanual-toolkit' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        </div><!-- #generation-options-panel -->

        <p class="submit">
            <button type="button" class="drea-btn drea-btn--primary" id="save-settings-btn" disabled><?php echo esc_html__('Save Settings', 'dreamanual-toolkit' ); ?></button>
            <span class="spinner"></span>
        </p>
    </div>
</div>
