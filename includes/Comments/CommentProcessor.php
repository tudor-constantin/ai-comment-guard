<?php
/**
 * AI Comment Guard - Comment Processor
 *
 * @package AICOG
 * @subpackage Comments
 * @since 1.0.0
 */

namespace AICOG\Comments;

use AICOG\Utils\Config;
use AICOG\Database\DatabaseManager;
use AICOG\AI\AIManager;

/**
 * Comment Processor
 *
 * @since 1.0.0
 */
class CommentProcessor {
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * @var DatabaseManager Database manager
     */
    private $database;
    
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
     * Initialize the comment processor
     *
     * @return void
     */
    public function init() {
        // Hook into comment processing
        add_filter('pre_comment_approved', [$this, 'process_comment'], 10, 2);
        
        // Always hook into notification filters - they will check the setting dynamically
        add_filter('comment_notification_notify_author', [$this, 'maybe_disable_notification'], 10, 2);
        add_filter('comment_moderation_notify_author', [$this, 'maybe_disable_moderation_notification'], 10, 2);
        
        // Also hook into the broader notification filters
        add_filter('notify_post_author', [$this, 'maybe_disable_post_author_notification'], 10, 2);
        add_filter('notify_moderator', [$this, 'maybe_disable_moderator_notification'], 10, 2);
    }
    
    /**
     * Process comment with AI
     *
     * @param int|string $approved Current approval status
     * @param array $commentdata Comment data
     * @return int|string Modified approval status
     */
    public function process_comment($approved, $commentdata) {
        // Skip if auto processing is disabled
        if (!$this->config->is_enabled('auto_process')) {
            return $approved;
        }
        
        // Skip if AI provider is not configured
        if (!$this->config->is_configured()) {
            return $approved;
        }
        
        // Skip for logged-in users with moderation capabilities
        if (is_user_logged_in() && current_user_can('moderate_comments')) {
            return $approved;
        }
        
        try {
            // Analyze comment with AI
            $analysis = $this->analyze_comment($commentdata);
            
            if ($analysis) {
                // Determine action based on analysis
                $action = $this->determine_action($analysis);
                
                // Mark comment as AI processed for notification filtering
                $this->mark_comment_as_ai_processed($commentdata);
                
                // Log the analysis if enabled
                if ($this->config->is_enabled('logging')) {
                    $this->log_analysis($commentdata, $analysis, $action);
                }
                
                // Return appropriate status
                return $this->map_action_to_status($action);
            }
        } catch (\Exception $e) {
            // Error logging removed for production compliance
            // WordPress.org plugins should not use error_log() in production
        }
        
        return $approved;
    }
    
    /**
     * Analyze comment with AI
     *
     * @param array $commentdata Comment data
     * @return array|null Analysis result
     * @throws \Exception
     */
    private function analyze_comment($commentdata) {
        $provider_name = $this->config->get('ai_provider');
        $token = $this->config->get('ai_provider_token');
        
        if (empty($provider_name) || empty($token)) {
            throw new \Exception('AI provider not configured');
        }
        
        // Create AI manager
        $ai_manager = new AIManager($provider_name, $token);
        
        // Get system message
        $system_message = $this->config->get_prompt('system');
        
        // Analyze comment
        return $ai_manager->analyze_comment($commentdata, $system_message);
    }
    
    /**
     * Determine action based on analysis
     *
     * @param array $analysis AI analysis result
     * @return string Action to take
     */
    private function determine_action($analysis) {
        $result = $analysis['status'];
        $confidence = $analysis['confidence'];
        
        $spam_threshold = $this->config->get_threshold('spam');
        $approval_threshold = $this->config->get_threshold('approval');
        
        switch ($result) {
            case 'spam':
                return ($confidence >= $spam_threshold) ? 'spam' : 'hold';
                
            case 'rejected':
                return ($confidence >= $spam_threshold) ? 'reject' : 'hold';
                
            case 'approved':
                return ($confidence >= $approval_threshold) ? 'approve' : 'hold';
                
            default:
                return 'hold';
        }
    }
    
    /**
     * Map action to WordPress comment status
     *
     * @param string $action Action name
     * @return int|string WordPress comment status
     */
    private function map_action_to_status($action) {
        switch ($action) {
            case 'approve':
                return 1;
            case 'spam':
                return 'spam';
            case 'reject':
                return 'trash';
            case 'hold':
            default:
                return 0;
        }
    }
    
    /**
     * Log analysis result
     *
     * @param array $commentdata Comment data
     * @param array $analysis Analysis result
     * @param string $action Action taken
     * @return void
     */
    private function log_analysis($commentdata, $analysis, $action) {
        // Create unique hash to prevent duplicate logging
        $comment_hash = md5($commentdata['comment_content'] . $commentdata['comment_author']);
        
        if ($this->database->log_exists($comment_hash)) {
            return;
        }
        
        $this->database->insert_log([
            'comment_id' => 0,
            'comment_content' => $commentdata['comment_content'],
            'comment_author' => $commentdata['comment_author'],
            'comment_author_email' => $commentdata['comment_author_email'],
            'comment_author_url' => $commentdata['comment_author_url'],
            'comment_author_ip' => $commentdata['comment_author_IP'] ?? (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''),
            'ai_provider' => $analysis['provider'] ?? '',
            'ai_response' => json_encode([
                'analysis' => $analysis['status'],
                'confidence' => $analysis['confidence'],
                'reason' => $analysis['reasoning']
            ]),
            'action' => $action,
            'confidence' => $analysis['confidence'],
            'processing_time' => $analysis['processing_time'] ?? 0
        ]);
    }
    
    /**
     * Mark comment as AI processed for notification filtering
     *
     * @param array $commentdata Comment data
     * @return void
     */
    private function mark_comment_as_ai_processed($commentdata) {
        // Create a unique identifier for this comment
        $comment_hash = md5($commentdata['comment_content'] . $commentdata['comment_author'] . ($commentdata['comment_author_email'] ?? ''));
        
        // Store in a transient that expires after 5 minutes (should be enough for processing)
        set_transient('aicog_ai_processed_' . $comment_hash, true, 300);
    }
    
    /**
     * Check if comment was processed by AI
     *
     * @param array $commentdata Comment data
     * @return bool
     */
    private function is_comment_ai_processed($commentdata) {
        $comment_hash = md5($commentdata['comment_content'] . $commentdata['comment_author'] . ($commentdata['comment_author_email'] ?? ''));
        return get_transient('aicog_ai_processed_' . $comment_hash) !== false;
    }
    
    /**
     * Maybe disable notification for AI processed comments
     *
     * @param bool $notify Whether to notify
     * @param int $comment_id Comment ID
     * @return bool
     */
    public function maybe_disable_notification($notify, $comment_id) {
        if (!$notify) {
            return $notify;
        }
        
        // Check if email notifications are disabled in settings
        if (!$this->config->get('disable_email_notifications', false)) {
            return $notify;
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            return $notify;
        }
        
        // Create comment data array to check if it was AI processed
        $commentdata = [
            'comment_content' => $comment->comment_content,
            'comment_author' => $comment->comment_author,
            'comment_author_email' => $comment->comment_author_email
        ];
        
        // If this comment was processed by AI, don't send notification
        if ($this->is_comment_ai_processed($commentdata)) {
            return false;
        }
        
        return $notify;
    }
    
    /**
     * Maybe disable moderation notification for AI processed comments
     *
     * @param bool $notify Whether to notify
     * @param int $comment_id Comment ID
     * @return bool
     */
    public function maybe_disable_moderation_notification($notify, $comment_id) {
        return $this->maybe_disable_notification($notify, $comment_id);
    }
    
    /**
     * Maybe disable post author notification for AI processed comments
     *
     * @param bool $notify Whether to notify
     * @param int $comment_id Comment ID
     * @return bool
     */
    public function maybe_disable_post_author_notification($notify, $comment_id) {
        return $this->maybe_disable_notification($notify, $comment_id);
    }
    
    /**
     * Maybe disable moderator notification for AI processed comments
     *
     * @param bool $notify Whether to notify
     * @param int $comment_id Comment ID
     * @return bool
     */
    public function maybe_disable_moderator_notification($notify, $comment_id) {
        return $this->maybe_disable_notification($notify, $comment_id);
    }
}
