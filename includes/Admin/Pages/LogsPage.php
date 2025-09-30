<?php
/**
 * AI Comment Guard - Logs Page
 *
 * @package AI_Comment_Guard
 * @subpackage Admin\Pages
 * @since 1.0.0
 */

namespace AI_Comment_Guard\Admin\Pages;

use AI_Comment_Guard\Database\DatabaseManager;

/**
 * Logs Page
 *
 * @since 1.0.0
 */
class LogsPage {
    
    /**
     * @var DatabaseManager Database manager
     */
    private $database;
    
    /**
     * Constructor
     *
     * @param DatabaseManager $database Database manager
     */
    public function __construct(DatabaseManager $database) {
        $this->database = $database;
    }
    
    /**
     * Ensure database table exists
     *
     * @return void
     */
    private function ensure_table_exists() {
        // Create tables if they don't exist
        $this->database->create_tables();
    }
    
    /**
     * Render logs page
     *
     * @return void
     */
    public function render() {
        // Ensure table exists before rendering
        $this->ensure_table_exists();
        
        // Handle actions
        $this->handle_actions();
        
        // Get current filters (nonce verification not required for read-only GET parameters in admin context)
        $current_page = isset($_GET['paged']) ? max(1, intval(wp_unslash($_GET['paged']))) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per_page = 10;
        $action_filter = isset($_GET['action_filter']) ? sanitize_text_field(wp_unslash($_GET['action_filter'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        
        // Get logs
        $result = $this->database->get_logs([
            'page' => $current_page,
            'per_page' => $per_page,
            'action' => $action_filter
        ]);
        
        $logs = $result['logs'];
        $total_pages = $result['pages'];
        $total_items = $result['total'];
        
        // Show statistics
        $this->render_statistics();
        
        ?>
        <div class="ai-comment-guard-logs">
            <div class="tablenav top">
                <?php $this->render_filters($action_filter); ?>
                <?php $this->render_actions(); ?>
            </div>
            
            <?php $this->render_logs_table($logs); ?>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <?php $this->render_pagination($current_page, $total_pages, $total_items); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle actions
     *
     * @return void
     */
    private function handle_actions() {
        if (isset($_POST['delete_logs'])) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'ai_comment_guard_clear_logs')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'ai-comment-guard') . '</p></div>';
                return;
            }
            
            if (!current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('You are not allowed to perform this action.', 'ai-comment-guard') . '</p></div>';
                return;
            }

            $this->database->clear_logs();
            echo '<div class="notice notice-success"><p>' . esc_html__('Logs deleted successfully.', 'ai-comment-guard') . '</p></div>';
        }
    }
    
    /**
     * Render statistics
     *
     * @return void
     */
    private function render_statistics() {
        // Get retention days from config
        $config = new \AI_Comment_Guard\Utils\Config();
        $retention_days = $config->get('log_retention_days', 30);
        
        // If retention is 0 (indefinite), show last 30 days for stats
        $stats_days = $retention_days > 0 ? $retention_days : 30;
        $stats = $this->database->get_statistics($stats_days);
        
        if ($stats['total'] === 0) {
            return;
        }
        
        ?>
        <div class="ai-comment-guard-stats">
            <h3><?php 
                if ($retention_days > 0) {
                    /* translators: %d is the number of days for statistics display */
                    printf(esc_html__('Statistics (Last %d Days)', 'ai-comment-guard'), esc_html($retention_days));
                } else {
                    esc_html_e('Statistics (Last 30 Days)', 'ai-comment-guard');
                }
            ?></h3>
            
            <div class="stats-grid">
                <div class="stat-item stat-total">
                    <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Processed', 'ai-comment-guard'); ?></span>
                </div>
                
                <?php foreach ($stats['by_action'] as $action => $data): ?>
                <div class="stat-item stat-<?php echo esc_attr($action); ?>">
                    <span class="stat-number"><?php echo esc_html($data['count']); ?></span>
                    <span class="stat-label"><?php echo esc_html($this->get_action_label($action)); ?></span>
                    <span class="stat-meta">
                        <?php 
                        /* translators: %s is the average confidence percentage */
                        printf(esc_html__('Avg: %s%%', 'ai-comment-guard'), esc_html(number_format($data['avg_confidence'] * 100, 1))); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                
                <div class="stat-item stat-performance">
                    <span class="stat-number"><?php echo number_format($stats['avg_processing_time'], 2); ?>s</span>
                    <span class="stat-label"><?php esc_html_e('Avg Processing Time', 'ai-comment-guard'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render filters
     *
     * @param string|null $current_filter Current action filter
     * @return void
     */
    private function render_filters($current_filter) {
        ?>
        <div class="alignleft actions">
            <select name="action_filter" id="action-filter">
                <option value=""><?php esc_html_e('All Actions', 'ai-comment-guard'); ?></option>
                <option value="approve" <?php selected($current_filter, 'approve'); ?>>
                    <?php esc_html_e('Approved', 'ai-comment-guard'); ?>
                </option>
                <option value="spam" <?php selected($current_filter, 'spam'); ?>>
                    <?php esc_html_e('Spam', 'ai-comment-guard'); ?>
                </option>
                <option value="reject" <?php selected($current_filter, 'reject'); ?>>
                    <?php esc_html_e('Rejected', 'ai-comment-guard'); ?>
                </option>
                <option value="hold" <?php selected($current_filter, 'hold'); ?>>
                    <?php esc_html_e('Held', 'ai-comment-guard'); ?>
                </option>
            </select>
            
            <button type="button" class="button" id="filter-logs">
                <?php esc_html_e('Filter', 'ai-comment-guard'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Render actions
     *
     * @return void
     */
    private function render_actions() {
        ?>
        <div class="alignleft actions">
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('ai_comment_guard_clear_logs'); ?>
                <input type="submit" 
                       name="delete_logs" 
                       class="button action" 
                       value="<?php esc_attr_e('Clear All Logs', 'ai-comment-guard'); ?>" 
                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all logs?', 'ai-comment-guard'); ?>');" />
            </form>
        </div>
        <?php
    }
    
    /**
     * Render pagination
     *
     * @param int $current_page Current page number
     * @param int $total_pages Total number of pages
     * @param int $total_items Total number of items
     * @return void
     */
    private function render_pagination($current_page, $total_pages, $total_items) {
        if ($total_pages <= 1) {
            return;
        }
        
        ?>
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php 
                /* translators: %d is the number of log items */
                printf(esc_html(_n('%d item', '%d items', $total_items, 'ai-comment-guard')), esc_html($total_items)); ?>
            </span>
            
            <div class="pagination-links">
                <?php
                $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $base_url = remove_query_arg(['paged'], $request_uri);
                $base_url = add_query_arg('paged', '%#%', $base_url);
                
                echo wp_kses_post(paginate_links([
                    'base' => $base_url,
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'ai-comment-guard'),
                    'next_text' => __('Next &raquo;', 'ai-comment-guard'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'add_args' => false,
                    'type' => 'plain',
                    'end_size' => 2,
                    'mid_size' => 1
                ]));
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render logs table
     *
     * @param array $logs Log entries
     * @return void
     */
    private function render_logs_table($logs) {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Processing Date', 'ai-comment-guard'); ?></th>
                    <th scope="col"><?php esc_html_e('Comment', 'ai-comment-guard'); ?></th>
                    <th scope="col"><?php esc_html_e('Author Info', 'ai-comment-guard'); ?></th>
                    <th scope="col"><?php esc_html_e('AI Analysis', 'ai-comment-guard'); ?></th>
                    <th scope="col"><?php esc_html_e('Final Action', 'ai-comment-guard'); ?></th>
                    <th scope="col"><?php esc_html_e('IP Address', 'ai-comment-guard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No logs available.', 'ai-comment-guard'); ?></td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <?php $this->render_log_row($log); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render single log row
     *
     * @param object $log Log entry
     * @return void
     */
    private function render_log_row($log) {
        $ai_response = json_decode($log->ai_response, true);
        ?>
        <tr>
            <td class="column-date" data-label="<?php esc_attr_e('Date', 'ai-comment-guard'); ?>">
                <?php echo esc_html(mysql2date('Y-m-d H:i:s', $log->created_at)); ?>
            </td>
            
            <td class="column-comment" data-label="<?php esc_attr_e('Comment', 'ai-comment-guard'); ?>">
                <?php if ($log->comment_content): ?>
                    <div class="comment-excerpt">
                        <?php echo esc_html(wp_trim_words($log->comment_content, 15)); ?>
                    </div>
                <?php else: ?>
                    <th scope="col"><?php esc_html_e('Comment Content', 'ai-comment-guard'); ?></th>
                <?php endif; ?>
            </td>
            
            <td class="column-author" data-label="<?php esc_attr_e('Author', 'ai-comment-guard'); ?>">
                <?php echo esc_html($log->comment_author ?: __('Unknown', 'ai-comment-guard')); ?>
                <?php if ($log->comment_author_email): ?>
                    <br><small><?php echo esc_html($log->comment_author_email); ?></small>
                <?php endif; ?>
            </td>
            
            <td class="column-analysis" data-label="<?php esc_attr_e('AI Analysis', 'ai-comment-guard'); ?>">
                <?php if ($ai_response && isset($ai_response['analysis'])): ?>
                    <strong><?php echo esc_html(ucfirst($ai_response['analysis'])); ?></strong>
                    <?php if (isset($ai_response['reason'])): ?>
                        <br><small><?php echo esc_html($ai_response['reason']); ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <em><?php esc_html_e('Invalid response', 'ai-comment-guard'); ?></em>
                <?php endif; ?>
            </td>
            
            <td class="column-action" data-label="<?php esc_attr_e('Action', 'ai-comment-guard'); ?>">
                <span class="action-badge action-<?php echo esc_attr($log->action); ?>">
                    <?php echo esc_html($this->get_action_label($log->action)); ?>
                </span>
            </td>
            
            <td class="column-ip" data-label="<?php esc_attr_e('IP Address', 'ai-comment-guard'); ?>">
                <?php echo esc_html($log->comment_author_ip ?: __('Unknown', 'ai-comment-guard')); ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Get action label
     *
     * @param string $action Action name
     * @return string Translated label
     */
    private function get_action_label($action) {
        $labels = [
            'approve' => __('Approved', 'ai-comment-guard'),
            'spam' => __('Spam', 'ai-comment-guard'),
            'reject' => __('Rejected', 'ai-comment-guard'),
            'hold' => __('Held', 'ai-comment-guard')
        ];
        
        return $labels[$action] ?? ucfirst($action);
    }
    
    /**
     * Handle delete logs AJAX request
     *
     * @return void
     */
    public function handle_delete_logs() {
        check_ajax_referer('ai_comment_guard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 0;
        $deleted = $this->database->clear_logs($days);
        
        wp_send_json_success([
            'message' => sprintf(
                /* translators: %d is the number of logs deleted */
                _n('%d log deleted', '%d logs deleted', $deleted, 'ai-comment-guard'),
                $deleted
            )
        ]);
    }
}
