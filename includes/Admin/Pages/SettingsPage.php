<?php
/**
 * AI Comment Guard - Settings Page
 *
 * @package AICOG
 * @subpackage Admin\Pages
 * @since 1.0.0
 */

namespace AICOG\Admin\Pages;

use AICOG\Utils\Config;
use AICOG\Admin\Settings\SettingsManager;

/**
 * Settings Page
 *
 * @since 1.0.0
 */
class SettingsPage {
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * @var SettingsManager Settings manager
     */
    private $settings_manager;
    
    /**
     * Constructor
     *
     * @param Config $config Configuration manager
     * @param SettingsManager $settings_manager Settings manager
     */
    public function __construct(Config $config, SettingsManager $settings_manager) {
        $this->config = $config;
        $this->settings_manager = $settings_manager;
    }
    
    /**
     * Render settings tab
     *
     * @return void
     */
    public function render_settings_tab() {
        $has_config = $this->config->is_configured();
        
        if (!$has_config) {
            $this->render_setup_notice();
        } else {
            $this->render_success_notice();
        }
        
        ?>
        <form method="post" action="options.php" id="ai-comment-guard-form">
            <?php
            settings_fields('aicog_settings_group');
            do_settings_sections('ai-comment-guard');
            ?>
            
            <?php $this->render_test_section(); ?>
            
            <div class="ai-comment-guard-save-section">
                <?php $this->render_save_warnings(); ?>
                <?php submit_button(__('Save Settings', 'ai-comment-guard'), 'primary', 'submit', true, ['id' => 'save-config-btn']); ?>
            </div>
        </form>
        <?php
    }
    
    /**
     * Render prompt tab
     *
     * @return void
     */
    public function render_prompt_tab() {
        ?>
        <div class="ai-comment-guard-prompt-tab">
            <div class="notice notice-info">
                <p><?php esc_html_e('Customize how the AI analyzes comments by modifying the prompt below.', 'ai-comment-guard'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('aicog_settings_group');
                do_settings_sections('ai-comment-guard-prompt');
                submit_button(__('Save Prompt Settings', 'ai-comment-guard'));
                ?>
            </form>
            
            <div class="ai-comment-guard-prompt-help">
                <h3><?php esc_html_e('Prompt Guidelines', 'ai-comment-guard'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Use clear, specific instructions for the AI', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('Include criteria for spam, rejection, and approval', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('Request JSON format response for reliable parsing', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('Test your prompt with sample comments before deploying', 'ai-comment-guard'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render setup notice
     *
     * @return void
     */
    private function render_setup_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <h3><?php esc_html_e('Setup Required', 'ai-comment-guard'); ?></h3>
                <p><?php esc_html_e('Configure your AI provider to start protecting your comments.', 'ai-comment-guard'); ?></p>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render success notice
     *
     * @return void
     */
    private function render_success_notice() {
        ?>
        <div class="notice notice-success">
            <p>
                <h3><?php esc_html_e('AI Provider Configured', 'ai-comment-guard'); ?></h3>
                <p><?php esc_html_e('Your AI provider is configured and ready to analyze comments.', 'ai-comment-guard'); ?></p>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render test section
     *
     * @return void
     */
    private function render_test_section() {
        ?>
        <div class="ai-comment-guard-test-section" id="test-connection-section">
            <h3><?php esc_html_e('Test Connection', 'ai-comment-guard'); ?></h3>
            <p><?php esc_html_e('Test your AI provider connection before saving the configuration.', 'ai-comment-guard'); ?></p>
            
            <button type="button" id="test-ai-connection" class="button button-secondary">
                <?php esc_html_e('Test AI Connection', 'ai-comment-guard'); ?>
            </button>
            
            <div id="test-result" class="ai-comment-guard-test-result" style="display: none;"></div>
        </div>
        <?php
    }
    
    /**
     * Render save warnings
     *
     * @return void
     */
    private function render_save_warnings() {
        ?>
        <div class="save-warning" id="save-warning" style="display: none;">
            <p>
                <strong><?php esc_html_e('⚠ Warning:', 'ai-comment-guard'); ?></strong>
                <?php esc_html_e('Test the connection before saving the configuration.', 'ai-comment-guard'); ?>
            </p>
        </div>
        
        <div class="connection-required-warning" id="connection-required-warning" style="display: none;">
            <p>
                <strong><?php esc_html_e('⚠ Connection Required:', 'ai-comment-guard'); ?></strong>
                <?php esc_html_e('You must test and verify the connection before saving.', 'ai-comment-guard'); ?>
            </p>
        </div>
        <?php
    }
}
