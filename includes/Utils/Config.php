<?php
/**
 * AI Comment Guard - Configuration Manager
 *
 * @package AI_Comment_Guard
 * @subpackage Utils
 * @since 1.0.0
 */

namespace AI_Comment_Guard\Utils;

/**
 * Configuration Manager
 *
 * @since 1.0.0
 */
class Config {
    
    /**
     * @var array Default settings
     */
    private $defaults = [
        'ai_provider' => '',
        'ai_provider_token' => '',
        'auto_process' => true,
        'spam_threshold' => 0.7,
        'approval_threshold' => 0.3,
        'log_enabled' => false,
        'log_retention_days' => 30,
        'custom_system_message' => '',
        'custom_ai_prompt' => ''
    ];
    
    /**
     * @var array Cached settings
     */
    private $settings = null;
    
    /**
     * Get a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key = null, $default = null) {
        if (null === $this->settings) {
            $this->load_settings();
        }
        
        if (null === $key) {
            return $this->settings;
        }
        
        return isset($this->settings[$key]) ? $this->settings[$key] : ($default ?? $this->defaults[$key] ?? null);
    }
    
    /**
     * Set a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @return bool
     */
    public function set($key, $value) {
        if (null === $this->settings) {
            $this->load_settings();
        }
        
        $this->settings[$key] = $value;
        return $this->save_settings();
    }
    
    /**
     * Update multiple settings at once
     *
     * @param array $settings Settings to update
     * @return bool
     */
    public function update(array $settings) {
        if (null === $this->settings) {
            $this->load_settings();
        }
        
        $this->settings = array_merge($this->settings, $settings);
        return $this->save_settings();
    }
    
    /**
     * Set default settings
     *
     * @return void
     */
    public function set_defaults() {
        add_option('ai_comment_guard_settings', $this->defaults);
        $this->settings = $this->defaults;
    }
    
    /**
     * Load settings from database
     *
     * @return void
     */
    private function load_settings() {
        $this->settings = get_option('ai_comment_guard_settings', $this->defaults);
    }
    
    /**
     * Save settings to database
     *
     * @return bool
     */
    private function save_settings() {
        return update_option('ai_comment_guard_settings', $this->settings);
    }
    
    /**
     * Check if a feature is enabled
     *
     * @param string $feature Feature name
     * @return bool
     */
    public function is_enabled($feature) {
        switch ($feature) {
            case 'auto_process':
                return (bool) $this->get('auto_process', true);
            case 'logging':
                return (bool) $this->get('log_enabled', false);
            default:
                return false;
        }
    }
    
    /**
     * Check if AI provider is configured
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->get('ai_provider')) && !empty($this->get('ai_provider_token'));
    }
    
    /**
     * Get threshold value
     *
     * @param string $type Type of threshold (spam|approval)
     * @return float
     */
    public function get_threshold($type) {
        switch ($type) {
            case 'spam':
                return (float) $this->get('spam_threshold', 0.7);
            case 'approval':
                return (float) $this->get('approval_threshold', 0.3);
            default:
                return 0.5;
        }
    }
    
    /**
     * Get AI prompt
     *
     * @param string $type Type of prompt (system|user)
     * @return string
     */
    public function get_prompt($type = 'user') {
        if ($type === 'system') {
            $custom = $this->get('custom_system_message', '');
            return !empty($custom) ? $custom : __('You are an expert comment moderator. Analyze the comment content and determine if it should be approved, marked as spam, or rejected.', 'ai-comment-guard');
        }
        
        return $this->get('custom_ai_prompt', '');
    }
}
