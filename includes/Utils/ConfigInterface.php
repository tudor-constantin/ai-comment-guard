<?php
/**
 * AI Comment Guard - Configuration Interface
 *
 * @package AICOG
 * @subpackage Utils
 * @since 1.2.3
 */

namespace AICOG\Utils;

/**
 * Configuration Interface
 *
 * @since 1.2.3
 */
interface ConfigInterface {
    
    /**
     * Get a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key = null, $default = null);
    
    /**
     * Set a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @return bool Success status
     */
    public function set($key, $value);
    
    /**
     * Check if a feature is enabled
     *
     * @param string $feature Feature name
     * @return bool
     */
    public function is_enabled($feature);
    
    /**
     * Check if AI provider is configured
     *
     * @return bool
     */
    public function is_configured();
    
    /**
     * Get threshold value
     *
     * @param string $type Type of threshold
     * @return float
     */
    public function get_threshold($type);
    
    /**
     * Clear configuration cache
     *
     * @return void
     */
    public function clear_cache();
}