<?php
/**
 * AI Comment Guard - Security Helper
 *
 * @package AICOG
 * @subpackage Utils
 * @since 1.2.3
 */

namespace AICOG\Utils;

/**
 * Security Helper - Centralizes common security validations
 *
 * Provides centralized, consistent security validation methods
 * for AJAX requests, admin forms, and configuration sanitization.
 * Follows WordPress security best practices.
 *
 * @since 1.2.3
 */
class SecurityHelper {
    
    /**
     * Validate AJAX request with nonce and capability check
     *
     * @param string $nonce_action Nonce action name
     * @param string $nonce_field Nonce field name
     * @param string $capability Required capability
     * @return bool True if valid, dies on failure
     */
    public static function validate_ajax_request($nonce_action = 'aicog_nonce', $nonce_field = 'nonce', $capability = 'manage_options') {
        check_ajax_referer($nonce_action, $nonce_field);
        
        if (!current_user_can($capability)) {
            wp_die('Unauthorized', 403);
        }
        
        return true;
    }
    
    /**
     * Validate admin form request
     *
     * @param string $nonce_action Nonce action name
     * @param string $capability Required capability
     * @return bool True if valid, false on failure
     */
    public static function validate_admin_request($nonce_action, $capability = 'manage_options') {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $nonce_action)) {
            return false;
        }
        
        return current_user_can($capability);
    }
    
    /**
     * Sanitize and validate configuration input
     *
     * @param string $key Configuration key
     * @param mixed $value Raw value
     * @return mixed Sanitized value or false on failure
     */
    public static function sanitize_config_value($key, $value) {
        switch ($key) {
            case 'ai_provider':
                $allowed_providers = ['openai', 'anthropic', 'openrouter'];
                return in_array($value, $allowed_providers, true) ? $value : false;
                
            case 'spam_threshold':
            case 'approval_threshold':
                $float_val = (float) $value;
                return ($float_val >= 0 && $float_val <= 1) ? $float_val : false;
                
            case 'log_retention_days':
                $int_val = (int) $value;
                return ($int_val >= 0 && $int_val <= 365) ? $int_val : false;
                
            case 'auto_process':
            case 'log_enabled':
            case 'disable_email_notifications':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                
            case 'ai_provider_token':
                return !empty($value) ? sanitize_text_field($value) : false;
                
            case 'custom_system_message':
                return sanitize_textarea_field($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
}