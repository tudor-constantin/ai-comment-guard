<?php
/**
 * AI Comment Guard - Main Plugin Class
 *
 * @package AI_Comment_Guard
 * @subpackage Core
 * @since 1.0.0
 */

namespace AI_Comment_Guard\Core;

use AI_Comment_Guard\Admin\AdminManager;
use AI_Comment_Guard\Comments\CommentProcessor;
use AI_Comment_Guard\Database\DatabaseManager;
use AI_Comment_Guard\Utils\Config;

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
class Plugin {
    
    /**
     * @var Plugin Single instance of the class
     */
    private static $instance = null;
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * @var DatabaseManager Database manager
     */
    private $database;
    
    /**
     * @var AdminManager Admin manager
     */
    private $admin;
    
    /**
     * @var CommentProcessor Comment processor
     */
    private $comment_processor;
    
    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->config = new Config();
        $this->database = new DatabaseManager();
    }
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Initialize components
        $this->init_components();
        
        // Register hooks
        $this->register_hooks();
        
        // Register cleanup hook
        add_action('ai_comment_guard_cleanup', [$this, 'cleanup_old_logs']);
    }
    
    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components() {
        // Initialize admin area if in admin
        if (is_admin()) {
            $this->admin = new AdminManager($this->config, $this->database);
            $this->admin->init();
        }
        
        // Initialize comment processor
        $this->comment_processor = new CommentProcessor($this->config, $this->database);
        $this->comment_processor->init();
    }
    
    /**
     * Register plugin hooks
     *
     * @return void
     */
    private function register_hooks() {
        // Activation/deactivation hooks are now registered in main plugin file
        // Only register uninstall hook here
        register_uninstall_hook(AI_COMMENT_GUARD_PLUGIN_FILE, [__CLASS__, 'uninstall']);
    }
    
    /**
     * Plugin activation
     *
     * @return void
     */
    public function activate() {
        // Create database tables
        $this->database->create_tables();
        
        // Set default options
        $this->config->set_defaults();
        
        // Clear any scheduled hooks
        wp_clear_scheduled_hook('ai_comment_guard_cleanup');
        
        // Schedule cleanup
        wp_schedule_event(time(), 'daily', 'ai_comment_guard_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     *
     * @return void
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('ai_comment_guard_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Cleanup old logs based on retention policy
     *
     * @return void
     */
    public function cleanup_old_logs() {
        // Delete logs older than retention period
        $this->database->clean_old_logs();
    }
    
    /**
     * Plugin uninstall
     *
     * @return void
     */
    public static function uninstall() {
        $database = new DatabaseManager();
        $database->drop_tables();
        
        // Delete options
        delete_option('ai_comment_guard_settings');
        delete_option('ai_comment_guard_version');
        
        // Delete transients
        delete_transient('ai_comment_guard_connection_tested');
    }
    
    /**
     * Get config manager
     *
     * @return Config
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Get database manager
     *
     * @return DatabaseManager
     */
    public function get_database() {
        return $this->database;
    }
}
