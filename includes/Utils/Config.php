<?php
/**
 * AI Comment Guard - Configuration Manager
 *
 * @package AICOG
 * @subpackage Utils
 * @since 1.0.0
 */

namespace AICOG\Utils;

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
        'custom_system_message' => ''
    ];
    
    /**
     * @var array Cached settings
     */
    private $settings = null;
    
    /**
     * @var string Encryption key for sensitive data
     */
    private $encryption_key = null;
    
    /**
     * @var string Cache key for transients
     */
    private $cache_key = 'aicog_config_cache';
    
    /**
     * @var int Cache expiration time in seconds (1 hour)
     */
    private $cache_expiration = 3600;
    
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
            // Return all settings, but decrypt sensitive fields for internal use
            $settings = $this->settings;
            foreach ($settings as $setting_key => $setting_value) {
                if ($this->is_sensitive_field($setting_key) && !empty($setting_value)) {
                    $settings[$setting_key] = $this->decrypt_data($setting_value);
                }
            }
            return $settings;
        }
        
        $value = isset($this->settings[$key]) ? $this->settings[$key] : ($default ?? $this->defaults[$key] ?? null);
        
        // Decrypt sensitive data when requested
        if ($this->is_sensitive_field($key) && !empty($value)) {
            $value = $this->decrypt_data($value);
        }
        
        return $value;
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
        
        // Encrypt sensitive data before storing
        if ($this->is_sensitive_field($key) && !empty($value)) {
            $value = $this->encrypt_data($value);
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
        
        // Encrypt sensitive fields before merging
        foreach ($settings as $key => $value) {
            if ($this->is_sensitive_field($key) && !empty($value)) {
                $settings[$key] = $this->encrypt_data($value);
            }
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
        add_option('aicog_settings', $this->defaults);
        $this->settings = $this->defaults;
        
        // Update cache with defaults
        set_transient($this->cache_key, $this->settings, $this->cache_expiration);
    }
    
    /**
     * Load settings from database with caching
     *
     * @return void
     */
    private function load_settings() {
        // Try to get from cache first
        $cached_settings = get_transient($this->cache_key);
        
        if ($cached_settings !== false) {
            $this->settings = $cached_settings;
            return;
        }
        
        // Load from database if not cached
        $this->settings = get_option('aicog_settings', $this->defaults);
        
        // Cache the settings
        set_transient($this->cache_key, $this->settings, $this->cache_expiration);
    }
    
    /**
     * Save settings to database and update cache
     *
     * @return bool
     */
    private function save_settings() {
        $result = update_option('aicog_settings', $this->settings);
        
        if ($result) {
            // Update cache with new settings
            set_transient($this->cache_key, $this->settings, $this->cache_expiration);
        } else {
            // If save failed, invalidate cache to force reload from DB
            $this->clear_cache();
        }
        
        return $result;
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
     * @param string $type Type of prompt (system)
     * @return string
     */
    public function get_prompt($type = 'system') {
        if ($type === 'system') {
            $custom = $this->get('custom_system_message', '');
            return !empty($custom) ? $custom : __('You are an expert comment moderator. Analyze the comment content and determine if it should be approved, marked as spam, or rejected.', 'ai-comment-guard');
        }
        
        return '';
    }
    
    /**
     * Get encryption key for sensitive data
     *
     * @return string
     */
    private function get_encryption_key() {
        if (null === $this->encryption_key) {
            // Use WordPress salts/keys for encryption
            $this->encryption_key = wp_hash('aicog_encryption_key_' . SECURE_AUTH_KEY . AUTH_KEY);
        }
        return $this->encryption_key;
    }
    
    /**
     * Encrypt sensitive data
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    private function encrypt_data($data) {
        if (empty($data)) {
            return $data;
        }
        
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        // Combine IV and encrypted data, then base64 encode
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     *
     * @param string $encrypted_data Encrypted data
     * @return string Decrypted data
     */
    private function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return $encrypted_data;
        }
        
        // Check if data is already decrypted (for backward compatibility)
        $decoded = base64_decode($encrypted_data, true);
        if ($decoded === false) {
            // Not base64 encoded, assume it's plain text (old format)
            return $encrypted_data;
        }
        
        $key = $this->get_encryption_key();
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        // If decryption fails, return original data (backward compatibility)
        return $decrypted !== false ? $decrypted : $encrypted_data;
    }
    
    /**
     * Check if a field contains sensitive data that should be encrypted
     *
     * @param string $key Field key
     * @return bool
     */
    private function is_sensitive_field($key) {
        $sensitive_fields = ['ai_provider_token'];
        return in_array($key, $sensitive_fields, true);
    }
    
    /**
     * Get a masked version of sensitive data for display purposes
     *
     * @param string $key The configuration key
     * @return string Masked value or empty string
     */
    public function get_masked($key) {
        if (!$this->is_sensitive_field($key)) {
            return $this->get($key);
        }
        
        $value = $this->get($key);
        if (empty($value)) {
            return '';
        }
        
        // Return a masked version - show first 4 and last 4 characters
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }
        
        return substr($value, 0, 4) . str_repeat('•', $length - 8) . substr($value, -4);
    }
    
    /**
     * Check if a sensitive field has a value (without revealing it)
     *
     * @param string $key The configuration key
     * @return bool
     */
    public function has_sensitive_value($key) {
        if (!$this->is_sensitive_field($key)) {
            return !empty($this->get($key));
        }
        
        if (null === $this->settings) {
            $this->load_settings();
        }
        
        return !empty($this->settings[$key]);
    }
    
    /**
     * Clear configuration cache
     *
     * @return void
     */
    public function clear_cache() {
        delete_transient($this->cache_key);
        $this->settings = null; // Force reload from database
    }
    
    /**
     * Warm up the cache by loading settings
     *
     * @return void
     */
    public function warm_cache() {
        $this->load_settings();
    }
}
