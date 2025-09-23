<?php
/**
 * AI Comment Guard - Admin Manager
 *
 * @package AI_Comment_Guard
 * @subpackage Admin
 * @since 1.0.0
 */

namespace AI_Comment_Guard\Admin;

use AI_Comment_Guard\Utils\Config;
use AI_Comment_Guard\Database\DatabaseManager;
use AI_Comment_Guard\Admin\Pages\SettingsPage;
use AI_Comment_Guard\Admin\Pages\LogsPage;
use AI_Comment_Guard\Admin\Settings\SettingsManager;

/**
 * Admin Manager
 *
 * @since 1.0.0
 */
class AdminManager {
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * @var DatabaseManager Database manager
     */
    private $database;
    
    /**
     * @var SettingsManager Settings manager
     */
    private $settings_manager;
    
    /**
     * @var SettingsPage Settings page
     */
    private $settings_page;
    
    /**
     * @var LogsPage Logs page
     */
    private $logs_page;
    
    /**
     * Constructor
     *
     * @param Config $config Configuration manager
     * @param DatabaseManager $database Database manager
     */
    public function __construct(Config $config, DatabaseManager $database) {
        $this->config = $config;
        $this->database = $database;
    }
    
    /**
     * Initialize admin components
     *
     * @return void
     */
    public function init() {
        // Initialize settings manager
        $this->settings_manager = new SettingsManager($this->config);
        $this->settings_manager->init();
        
        // Initialize pages
        $this->settings_page = new SettingsPage($this->config, $this->settings_manager);
        $this->logs_page = new LogsPage($this->database);
        
        // Register hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
        
        // Add plugin action links
        add_filter('plugin_action_links_' . AI_COMMENT_GUARD_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);
    }
    
    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main settings page
        add_options_page(
            __('AI Comment Guard Settings', 'ai-comment-guard'),
            __('AI Comment Guard', 'ai-comment-guard'),
            'manage_options',
            'ai-comment-guard',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     *
     * @return void
     */
    public function render_admin_page() {
        // Get current tab (nonce verification not required for read-only GET parameters in admin context)
        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $log_enabled = $this->config->is_enabled('logging');
        
        // Available tabs
        $tabs = [
            'settings' => __('Settings', 'ai-comment-guard'),
            'prompt' => __('Prompt', 'ai-comment-guard')
        ];
        
        if ($log_enabled) {
            $tabs['logs'] = __('Logs', 'ai-comment-guard');
        }
        
        ?>
        <div class="wrap ai-comment-guard-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->render_header(); ?>
            <?php $this->render_tabs($tabs, $current_tab); ?>
            
            <div class="ai-comment-guard-content">
                <?php
                switch ($current_tab) {
                    case 'prompt':
                        $this->settings_page->render_prompt_tab();
                        break;
                    case 'logs':
                        if ($log_enabled) {
                            $this->logs_page->render();
                        }
                        break;
                    case 'settings':
                    default:
                        $this->settings_page->render_settings_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render page header
     *
     * @return void
     */
    private function render_header() {
        ?>
        <div class="ai-comment-guard-header">
            <h2><?php esc_html_e('AI-Powered Comment Management', 'ai-comment-guard'); ?></h2>
            <p><?php esc_html_e('Configure your custom AI provider to automatically analyze comments and keep your site spam-free.', 'ai-comment-guard'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render tabs navigation
     *
     * @param array $tabs Available tabs
     * @param string $current_tab Current active tab
     * @return void
     */
    private function render_tabs($tabs, $current_tab) {
        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_label): ?>
                <a href="?page=ai-comment-guard&tab=<?php echo esc_attr($tab_key); ?>" 
                   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </h2>
        <?php
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_ai-comment-guard') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'ai-comment-guard-admin',
            AI_COMMENT_GUARD_PLUGIN_URL . 'admin/css/admin.css',
            [],
            AI_COMMENT_GUARD_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'ai-comment-guard-admin',
            AI_COMMENT_GUARD_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            AI_COMMENT_GUARD_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'ai-comment-guard-admin',
            'ai_comment_guard_ajax',
            [
                'nonce' => wp_create_nonce('ai_comment_guard_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'strings' => $this->get_js_strings()
            ]
        );
    }
    
    /**
     * Get JavaScript strings for localization
     *
     * @return array
     */
    private function get_js_strings() {
        return [
            'testing' => __('Testing connection...', 'ai-comment-guard'),
            'test_success' => __('Connection successful!', 'ai-comment-guard'),
            'test_error' => __('Connection failed:', 'ai-comment-guard'),
            'test_button' => __('Test AI Connection', 'ai-comment-guard'),
            'confirm_delete_logs' => __('Are you sure you want to delete all logs?', 'ai-comment-guard'),
            'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'ai-comment-guard'),
            'provider_required' => __('Please select a provider and enter the API token', 'ai-comment-guard'),
            'test_required' => __('Please test the connection before saving', 'ai-comment-guard'),
            'threshold_error' => __('Thresholds must be between 0.0 and 1.0', 'ai-comment-guard'),
            'log_tab_note' => __('Note: After saving, a "Logs" tab will appear on this page.', 'ai-comment-guard'),
            'unsaved_changes_notice' => __('Unsaved changes: Remember to save your settings.', 'ai-comment-guard'),
            'token_validated' => __('🔒 Validated', 'ai-comment-guard'),
            'token_validated_tooltip' => __('Token validated. Change provider to modify.', 'ai-comment-guard')
        ];
    }
    
    /**
     * Register AJAX handlers
     *
     * @return void
     */
    private function register_ajax_handlers() {
        // Test AI connection
        add_action('wp_ajax_test_ai_connection', [$this, 'handle_test_connection']);
        
        // Delete logs
        add_action('wp_ajax_ai_comment_guard_delete_logs', [$this->logs_page, 'handle_delete_logs']);
        
        // Get statistics
        add_action('wp_ajax_ai_comment_guard_get_stats', [$this, 'handle_get_stats']);
    }
    
    /**
     * Handle test connection AJAX request
     *
     * @return void
     */
    public function handle_test_connection() {
        check_ajax_referer('ai_comment_guard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider = isset($_POST['ai_provider']) ? sanitize_text_field(wp_unslash($_POST['ai_provider'])) : '';
        $token = isset($_POST['ai_provider_token']) ? sanitize_text_field(wp_unslash($_POST['ai_provider_token'])) : '';
        
        if (empty($provider) || empty($token)) {
            wp_send_json_error(__('Please select a provider and provide the token', 'ai-comment-guard'));
        }
        
        try {
            $ai_manager = new \AI_Comment_Guard\AI\AIManager($provider, $token);
            
            if ($ai_manager->test_connection()) {
                wp_send_json_success([
                    'message' => __('Connection successful with AI provider', 'ai-comment-guard'),
                    'provider' => $provider
                ]);
            } else {
                wp_send_json_error(__('Connection test failed', 'ai-comment-guard'));
            }
        } catch (\Exception $e) {
            wp_send_json_error(sprintf(
                /* translators: %s is the error message from the connection attempt */
                __('Connection error: %s', 'ai-comment-guard'),
                $e->getMessage()
            ));
        }
    }

    /**
     * Handle get statistics AJAX request
     *
     * @return void
     */
    public function handle_get_stats() {
        check_ajax_referer('ai_comment_guard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        $stats = $this->database->get_statistics($days);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=ai-comment-guard'),
            esc_html__('Settings', 'ai-comment-guard')
        );
        
        // Add settings link at the beginning
        array_unshift($links, $settings_link);
        
        return $links;
    }
}
