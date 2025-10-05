<?php
/**
 * AI Comment Guard - Database Manager
 *
 * @package AICOG
 * @subpackage Database
 * @since 1.0.0
 */

namespace AICOG\Database;

/**
 * Database Manager
 *
 * @since 1.0.0
 */
class DatabaseManager {
    
    /**
     * @var string Log table name
     */
    private $log_table;
    
    /**
     * @var \wpdb WordPress database object
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->log_table = $wpdb->prefix . 'aicog_log';
    }
    
    /**
     * Create database tables
     *
     * @return void
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->log_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) NOT NULL DEFAULT 0,
            comment_content text,
            comment_author varchar(255),
            comment_author_email varchar(100),
            comment_author_url varchar(200),
            comment_author_ip varchar(45),
            comment_hash varchar(32),
            ai_provider varchar(50),
            ai_response text,
            action varchar(20) NOT NULL,
            confidence float,
            processing_time float,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comment_id (comment_id),
            KEY comment_hash (comment_hash),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store version for future upgrades
        update_option('aicog_db_version', '1.0.0');
    }
    
    /**
     * Drop database tables
     *
     * @return void
     */
    public function drop_tables() {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $drop_sql = "DROP TABLE IF EXISTS " . $this->log_table;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $this->wpdb->query($drop_sql);
        delete_option('aicog_db_version');
    }
    
    /**
     * Insert log entry
     *
     * @param array $data Log data
     * @return int|false Insert ID or false on failure
     */
    public function insert_log($data) {
        $defaults = [
            'comment_id' => 0,
            'comment_content' => '',
            'comment_author' => '',
            'comment_author_email' => '',
            'comment_author_url' => '',
            'comment_author_ip' => '',
            'comment_hash' => '',
            'ai_provider' => '',
            'ai_response' => '',
            'action' => '',
            'confidence' => 0,
            'processing_time' => 0,
            'error_message' => null,
            'created_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate hash if not provided
        if (empty($data['comment_hash']) && !empty($data['comment_content']) && !empty($data['comment_author'])) {
            $data['comment_hash'] = md5($data['comment_content'] . $data['comment_author']);
        }
        
        $result = $this->wpdb->insert(
            $this->log_table,
            $data,
            [
                '%d', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s', '%f', '%f', 
                '%s', '%s'
            ]
        );
        
        // Clear cache when new log is inserted
        if ($result) {
            $this->clear_log_cache();
        }
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Get logs with pagination
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_logs($args = []) {
        $defaults = [
            'page' => 1,
            'per_page' => 10,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'action' => null,
            'date_from' => null,
            'date_to' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Build WHERE clause
        $where = [];
        $where_values = [];
        
        if ($args['action']) {
            $where[] = 'action = %s';
            $where_values[] = $args['action'];
        }
        
        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Sanitize orderby and order values
        $allowed_orderby = ['id', 'created_at', 'action', 'confidence', 'processing_time'];
        $allowed_order = ['ASC', 'DESC'];
        
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';
        
        // Get total count
        if (!empty($where_values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $count_sql = "SELECT COUNT(*) FROM " . $this->log_table . " " . $where_clause;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $prepared_count = $this->wpdb->prepare($count_sql, $where_values);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = $this->wpdb->get_var($prepared_count);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $count_sql = "SELECT COUNT(*) FROM " . $this->log_table . " " . $where_clause;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = $this->wpdb->get_var($count_sql);
        }
        
        // Get logs
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $query = "SELECT l.*, 
                  COALESCE(c.comment_content, l.comment_content) as comment_content,
                  COALESCE(c.comment_author, l.comment_author) as comment_author,
                  COALESCE(c.comment_date, l.created_at) as comment_date
                  FROM " . $this->log_table . " l 
                  LEFT JOIN " . $this->wpdb->comments . " c ON l.comment_id = c.comment_ID 
                  " . $where_clause . "
                  ORDER BY " . $orderby . " " . $order . "
                  LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $prepared_query = $this->wpdb->prepare($query, $query_values);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $logs = $this->wpdb->get_results($prepared_query);
        
        return [
            'logs' => $logs,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
    
    /**
     * Get statistics
     *
     * @param int $days Number of days to look back
     * @return array
     */
    public function get_statistics($days = 30) {
        $date_limit = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $stats_sql = "
            SELECT 
                action,
                COUNT(*) as count,
                AVG(confidence) as avg_confidence,
                AVG(processing_time) as avg_processing_time
            FROM " . $this->log_table . "
            WHERE created_at >= %s
            GROUP BY action
        ";
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $prepared_stats = $this->wpdb->prepare($stats_sql, $date_limit);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $stats = $this->wpdb->get_results($prepared_stats, ARRAY_A);
        
        $result = [
            'total' => 0,
            'by_action' => [],
            'avg_confidence' => 0,
            'avg_processing_time' => 0
        ];
        
        foreach ($stats as $stat) {
            $result['by_action'][$stat['action']] = [
                'count' => (int) $stat['count'],
                'avg_confidence' => (float) $stat['avg_confidence'],
                'avg_processing_time' => (float) $stat['avg_processing_time']
            ];
            $result['total'] += (int) $stat['count'];
        }
        
        // Calculate overall averages
        if ($result['total'] > 0) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $overall_sql = "
                SELECT 
                    AVG(confidence) as avg_confidence,
                    AVG(processing_time) as avg_processing_time
                FROM " . $this->log_table . "
                WHERE created_at >= %s
            ";
            
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $prepared_overall = $this->wpdb->prepare($overall_sql, $date_limit);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $overall = $this->wpdb->get_row($prepared_overall);
            
            $result['avg_confidence'] = (float) $overall->avg_confidence;
            $result['avg_processing_time'] = (float) $overall->avg_processing_time;
        }
        
        return $result;
    }
    
    /**
     * Clean old logs based on retention policy
     *
     * @return int Number of deleted rows
     */
    public function clean_old_logs() {
        $config = new \AICOG\Utils\Config();
        $retention_days = $config->get('log_retention_days', 30);
        
        // If retention is 0, don't delete anything
        if ($retention_days <= 0) {
            return 0;
        }
        
        return $this->clear_logs($retention_days);
    }
    
    /**
     * Clear logs
     *
     * @param int $older_than_days Delete logs older than this many days (0 = all)
     * @return int Number of deleted rows
     */
    public function clear_logs($older_than_days = 0) {
        $deleted_rows = 0;
        
        if ($older_than_days > 0) {
            $date_limit = gmdate('Y-m-d H:i:s', strtotime("-{$older_than_days} days"));
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $delete_sql = "DELETE FROM " . $this->log_table . " WHERE created_at < %s";
            
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $prepared_delete = $this->wpdb->prepare($delete_sql, $date_limit);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $deleted_rows = $this->wpdb->query($prepared_delete);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $truncate_sql = "TRUNCATE TABLE " . $this->log_table;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $deleted_rows = $this->wpdb->query($truncate_sql);
        }
        
        // Clear cache when logs are deleted
        if ($deleted_rows > 0) {
            $this->clear_log_cache();
        }
        
        return $deleted_rows;
    }
    
    /**
     * Check if log exists for comment
     *
     * @param string $comment_hash Comment hash
     * @return bool
     */
    public function log_exists($comment_hash) {
        static $cache = [];
        
        // Handle cache reset
        if ($comment_hash === '__RESET_CACHE__') {
            $cache = [];
            return false;
        }
        
        // Check in-memory cache first
        if (isset($cache[$comment_hash])) {
            return $cache[$comment_hash];
        }
        
        // Check database using optimized query with indexed hash column
        $exists_sql = "SELECT COUNT(*) FROM {$this->log_table} WHERE comment_hash = %s LIMIT 1";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $prepared_exists = $this->wpdb->prepare($exists_sql, $comment_hash);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $exists = (int) $this->wpdb->get_var($prepared_exists);
        
        // Cache the result (both positive and negative)
        $cache[$comment_hash] = $exists > 0;
        
        return $cache[$comment_hash];
    }
    
    /**
     * Clear the log exists cache
     *
     * @return void
     */
    public function clear_log_cache() {
        // Reset the static cache array in log_exists method
        $this->reset_log_cache();
    }
    
    /**
     * Reset the static cache array
     *
     * @return void
     */
    private function reset_log_cache() {
        // Call log_exists with a special flag to reset cache
        static $reset_cache = false;
        $reset_cache = true;
        
        // This will trigger the cache reset in log_exists
        $this->log_exists('__RESET_CACHE__');
        
        $reset_cache = false;
    }
}
