<?php
/**
 * AI Comment Guard - Settings Manager
 *
 * @package AI_Comment_Guard
 * @subpackage Admin\Settings
 * @since 1.0.0
 */

namespace AI_Comment_Guard\Admin\Settings;

use AI_Comment_Guard\Utils\Config;
use AI_Comment_Guard\AI\AIManager;

/**
 * Settings Manager
 *
 * @since 1.0.0
 */
class SettingsManager {
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * @var string Settings group name
     */
    private $settings_group = 'ai_comment_guard_settings_group';
    
    /**
     * @var string Settings option name
     */
    private $option_name = 'ai_comment_guard_settings';
    
    /**
     * Constructor
     *
     * @param Config $config Configuration manager
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Initialize settings
     *
     * @return void
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Register settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            $this->settings_group,
            $this->option_name,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_defaults()
            ]
        );
        
        $this->add_settings_sections();
        $this->add_settings_fields();
    }
    
    /**
     * Add settings sections
     *
     * @return void
     */
    private function add_settings_sections() {
        // AI Provider Section
        add_settings_section(
            'ai_provider_section',
            __('AI Provider Configuration', 'ai-comment-guard'),
            [$this, 'render_provider_section'],
            'ai-comment-guard'
        );
        
        // Processing Settings Section
        add_settings_section(
            'processing_section',
            __('Processing Settings', 'ai-comment-guard'),
            [$this, 'render_processing_section'],
            'ai-comment-guard'
        );
        
        // Logging Section
        add_settings_section(
            'logging_section',
            __('Logging Settings', 'ai-comment-guard'),
            [$this, 'render_logging_section'],
            'ai-comment-guard'
        );
        
        // Prompt Customization Section
        add_settings_section(
            'prompt_section',
            __('AI Prompt Customization', 'ai-comment-guard'),
            [$this, 'render_prompt_section'],
            'ai-comment-guard-prompt'
        );
    }
    
    /**
     * Add settings fields
     *
     * @return void
     */
    private function add_settings_fields() {
        // Provider fields
        add_settings_field(
            'ai_provider',
            esc_html__('AI Provider', 'ai-comment-guard'),
            [$this, 'render_provider_field'],
            'ai-comment-guard',
            'ai_provider_section'
        );
        
        add_settings_field(
            'ai_provider_token',
            esc_html__('API Token', 'ai-comment-guard'),
            [$this, 'render_token_field'],
            'ai-comment-guard',
            'ai_provider_section'
        );
        
        // Processing fields
        add_settings_field(
            'auto_process',
            esc_html__('Auto Process Comments', 'ai-comment-guard'),
            [$this, 'render_auto_process_field'],
            'ai-comment-guard',
            'processing_section'
        );
        
        add_settings_field(
            'spam_threshold',
            esc_html__('Spam Threshold', 'ai-comment-guard'),
            [$this, 'render_spam_threshold_field'],
            'ai-comment-guard',
            'processing_section'
        );
        
        add_settings_field(
            'approval_threshold',
            esc_html__('Approval Threshold', 'ai-comment-guard'),
            [$this, 'render_approval_threshold_field'],
            'ai-comment-guard',
            'processing_section'
        );
        
        add_settings_field(
            'log_enabled',
            esc_html__('Enable logging', 'ai-comment-guard'),
            [$this, 'render_log_enabled_field'],
            'ai-comment-guard',
            'logging_section'
        );
        
        add_settings_field(
            'log_retention_days',
            esc_html__('Log retention (days)', 'ai-comment-guard'),
            [$this, 'render_log_retention_field'],
            'ai-comment-guard',
            'logging_section'
        );
        
        // Prompt fields
        add_settings_field(
            'custom_system_message',
            esc_html__('System Message', 'ai-comment-guard'),
            [$this, 'render_system_message_field'],
            'ai-comment-guard-prompt',
            'prompt_section'
        );
        
    }
    
    /**
     * Render provider section
     *
     * @return void
     */
    public function render_provider_section() {
        echo '<p>' . esc_html__('Select your AI provider and enter your API credentials.', 'ai-comment-guard') . '</p>';
    }
    
    /**
     * Render processing section
     *
     * @return void
     */
    public function render_processing_section() {
        echo '<p>' . esc_html__('Configure how comments are processed and thresholds for automatic actions.', 'ai-comment-guard') . '</p>';
    }
    
    /**
     * Render logging section
     *
     * @return void
     */
    public function render_logging_section() {
        echo '<p>' . esc_html__('Configure logging and data retention settings.', 'ai-comment-guard') . '</p>';
    }
    
    /**
     * Render prompt section
     *
     * @return void
     */
    public function render_prompt_section() {
        echo '<p>' . esc_html__('Customize the prompt sent to the AI for comment analysis.', 'ai-comment-guard') . '</p>';
    }
    
    /**
     * Render provider field
     *
     * @return void
     */
    public function render_provider_field() {
        $value = $this->config->get('ai_provider', '');
        $providers = AIManager::get_available_providers();
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[ai_provider]" class="regular-text">
            <option value=""><?php esc_html_e('Select provider...', 'ai-comment-guard'); ?></option>
            <?php foreach ($providers as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="ai_provider"><?php esc_html_e('Select your AI provider', 'ai-comment-guard'); ?></label>
        <p class="description"><?php esc_html_e('Select your preferred AI provider', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Render token field
     *
     * @return void
     */
    public function render_token_field() {
        $value = $this->config->get('ai_provider_token', '');
        ?>
        <input type="password" 
               name="<?php echo esc_attr($this->option_name); ?>[ai_provider_token]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description"><?php esc_html_e('Enter your API token for the selected provider.', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Render auto process field
     *
     * @return void
     */
    public function render_auto_process_field() {
        $value = $this->config->get('auto_process', true);
        ?>
        <!-- Hidden field to ensure value is always sent -->
        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[auto_process]" value="0" />
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr($this->option_name); ?>[auto_process]" 
                   value="1" 
                   <?php checked($value, 1); ?> />
            <?php esc_html_e('Automatically process new comments with AI analysis', 'ai-comment-guard'); ?>
        </label>
        <?php
    }
    
    /**
     * Render spam threshold field
     *
     * @return void
     */
    public function render_spam_threshold_field() {
        $value = $this->config->get('spam_threshold', 0.7);
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name); ?>[spam_threshold]" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="1" 
               step="0.1" 
               class="small-text" />
        <p class="description"><?php esc_html_e('Confidence threshold for marking as spam (0.0 - 1.0)', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Render approval threshold field
     *
     * @return void
     */
    public function render_approval_threshold_field() {
        $value = $this->config->get('approval_threshold', 0.3);
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name); ?>[approval_threshold]" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="1" 
               step="0.1" 
               class="small-text" />
        <p class="description"><?php esc_html_e('Confidence threshold for automatic approval (0.0 - 1.0)', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Render log enabled field
     *
     * @return void
     */
    public function render_log_enabled_field() {
        $value = $this->config->get('log_enabled', false);
        ?>
        <!-- Hidden field to ensure value is always sent -->
        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[log_enabled]" value="0" />
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr($this->option_name); ?>[log_enabled]" 
                   value="1" 
                   <?php checked($value, 1); ?> 
                   id="log_enabled_checkbox" />
            <?php esc_html_e('Enable logging', 'ai-comment-guard'); ?>
        </label>
        <p class="description"><?php esc_html_e('When enabled, a "Logs" tab will appear on this page.', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Render log retention field
     *
     * @return void
     */
    public function render_log_retention_field() {
        $value = $this->config->get('log_retention_days', 30);
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name); ?>[log_retention_days]" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="365" 
               class="small-text" />
        <p class="description">
            <?php esc_html_e('Number of days to keep logs. Set to 0 to keep logs indefinitely. Default: 30 days.', 'ai-comment-guard'); ?>
        </p>
        <?php
    }
    
    /**
     * Render system message field
     *
     * @return void
     */
    public function render_system_message_field() {
        $value = $this->config->get('custom_system_message', '');
        $placeholder = __('You are an expert comment moderator. Analyze comments and respond only with valid JSON.', 'ai-comment-guard');
        ?>
        <textarea name="<?php echo esc_attr($this->option_name); ?>[custom_system_message]" 
                  rows="3" 
                  cols="50" 
                  class="large-text"
                  placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('System message that defines AI behavior. Leave empty to use default.', 'ai-comment-guard'); ?></p>
        <?php
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Raw input values
     * @return array Sanitized values
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        $current = $this->config->get();
        
        // Detect if this is a prompt-only submission
        $is_prompt_only = !empty($input['custom_system_message']) && 
                         !isset($input['ai_provider']) && 
                         !isset($input['ai_provider_token']) &&
                         !array_key_exists('auto_process', $input) &&
                         !array_key_exists('log_enabled', $input);
        
        if ($is_prompt_only) {
            // This is from the prompt tab - only process prompt field
            if (isset($input['custom_system_message'])) {
                $sanitized['custom_system_message'] = sanitize_textarea_field($input['custom_system_message']);
            }
        } else {
            // This is from the main settings tab - process all fields
            
            // Sanitize provider
            if (isset($input['ai_provider'])) {
                $sanitized['ai_provider'] = sanitize_text_field($input['ai_provider']);
            }
            
            // Sanitize token
            if (isset($input['ai_provider_token'])) {
                $sanitized['ai_provider_token'] = sanitize_text_field($input['ai_provider_token']);
            }
            
            // Sanitize other fields
            if (array_key_exists('auto_process', $input)) {
                $sanitized['auto_process'] = filter_var($input['auto_process'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            if (array_key_exists('log_enabled', $input)) {
                $sanitized['log_enabled'] = filter_var($input['log_enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            
            if (isset($input['spam_threshold'])) {
                $sanitized['spam_threshold'] = max(0, min(1, floatval($input['spam_threshold'])));
            }
            
            if (isset($input['approval_threshold'])) {
                $sanitized['approval_threshold'] = max(0, min(1, floatval($input['approval_threshold'])));
            }
            
            if (isset($input['log_retention_days'])) {
                $sanitized['log_retention_days'] = max(0, min(365, intval($input['log_retention_days'])));
            }
        }
        
        // Clear connection test flag
        delete_transient('ai_comment_guard_connection_tested');
        
        return array_merge($current, $sanitized);
    }
    
    /**
     * Get default settings
     *
     * @return array
     */
    private function get_defaults() {
        return [
            'ai_provider' => '',
            'ai_provider_token' => '',
            'auto_process' => true,
            'spam_threshold' => 0.7,
            'approval_threshold' => 0.3,
            'log_enabled' => false,
            'log_retention_days' => 30,
            'custom_system_message' => ''
        ];
    }
}
