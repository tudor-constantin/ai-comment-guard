<?php
/**
 * AI Comment Guard - Autoloader
 *
 * @package AICOG
 * @subpackage Core
 * @since 1.0.0
 */

namespace AICOG\Core;

/**
 * Class Autoloader
 * 
 * Handles automatic loading of classes following PSR-4 standard
 *
 * @since 1.0.0
 */
class Autoloader {
    
    /**
     * @var string The plugin namespace
     */
    private $namespace = 'AICOG';
    
    /**
     * @var string The plugin base directory
     */
    private $base_dir;
    
    /**
     * Constructor
     *
     * @param string $base_dir The plugin base directory
     */
    public function __construct($base_dir) {
        $this->base_dir = $base_dir;
    }
    
    /**
     * Register the autoloader
     *
     * @return void
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    /**
     * Autoload classes
     *
     * @param string $class The fully-qualified class name
     * @return void
     */
    private function autoload($class) {
        // Check if the class uses our namespace
        $prefix = $this->namespace . '\\';
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $this->base_dir . '/includes/' . str_replace('\\', '/', $relative_class) . '.php';
        
        // Require the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
