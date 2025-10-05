<?php
/**
 * AI Comment Guard - Preview Page
 *
 * @package AICOG
 * @subpackage Admin\Pages
 * @since 1.1.0
 */

namespace AICOG\Admin\Pages;

use AICOG\Utils\Config;
use AICOG\AI\AIManager;

/**
 * Preview Page for testing comment analysis
 *
 * @since 1.1.0
 */
class PreviewPage {
    
    /**
     * @var Config Configuration manager
     */
    private $config;
    
    /**
     * Constructor
     *
     * @param Config $config Configuration manager
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Render preview tab
     *
     * @return void
     */
    public function render() {
        $is_configured = $this->config->is_configured();
        
        if (!$is_configured) {
            $this->render_not_configured_notice();
            return;
        }
        
        ?>
        <div class="ai-comment-guard-preview-tab">
            <div class="notice notice-info">
                <p><?php esc_html_e('Test how the AI will analyze comments using your current configuration. This helps you understand the AI behavior before it processes real comments.', 'ai-comment-guard'); ?></p>
            </div>
            
            <div class="ai-comment-guard-preview-form">
                <h3><?php esc_html_e('Comment Analysis Test', 'ai-comment-guard'); ?></h3>
                
                <form id="comment-preview-form">
                    <?php wp_nonce_field('aicog_preview_nonce', 'preview_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test-comment-author"><?php esc_html_e('Author Name', 'ai-comment-guard'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="test-comment-author" 
                                       name="comment_author" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('John Doe', 'ai-comment-guard'); ?>" />
                                <p class="description"><?php esc_html_e('Name of the comment author (optional)', 'ai-comment-guard'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="test-comment-email"><?php esc_html_e('Author Email', 'ai-comment-guard'); ?></label>
                            </th>
                            <td>
                                <input type="email" 
                                       id="test-comment-email" 
                                       name="comment_email" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('john@example.com', 'ai-comment-guard'); ?>" />
                                <p class="description"><?php esc_html_e('Email of the comment author (optional)', 'ai-comment-guard'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="test-comment-content"><?php esc_html_e('Comment Content', 'ai-comment-guard'); ?> *</label>
                            </th>
                            <td>
                                <textarea id="test-comment-content" 
                                          name="comment_content" 
                                          rows="6" 
                                          cols="60" 
                                          class="large-text" 
                                          placeholder="<?php esc_attr_e('Enter the comment text you want to analyze...', 'ai-comment-guard'); ?>" 
                                          required></textarea>
                                <p class="description"><?php esc_html_e('The comment text that will be analyzed by the AI', 'ai-comment-guard'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" id="analyze-comment-btn" class="button button-primary">
                            <?php esc_html_e('Analyze Comment', 'ai-comment-guard'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <div id="analysis-result" class="ai-comment-guard-analysis-result" style="display: none;">
                <!-- Results will be displayed here -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Render not configured notice
     *
     * @return void
     */
    private function render_not_configured_notice() {
        ?>
        <div class="ai-comment-guard-preview-tab">
            <div class="notice notice-warning">
                <h3><?php esc_html_e('AI Provider Not Configured', 'ai-comment-guard'); ?></h3>
                <p><?php esc_html_e('To use the comment analysis preview, you need to configure an AI provider first.', 'ai-comment-guard'); ?></p>
                <p>
                    <a href="?page=ai-comment-guard&tab=settings" class="button button-primary">
                        <?php esc_html_e('Configure AI Provider', 'ai-comment-guard'); ?>
                    </a>
                </p>
            </div>
            
            <div class="ai-comment-guard-preview-placeholder">
                <h3><?php esc_html_e('What is Comment Analysis Preview?', 'ai-comment-guard'); ?></h3>
                <p><?php esc_html_e('The preview feature allows you to test how the AI will analyze comments before they go live on your site. You can:', 'ai-comment-guard'); ?></p>
                <ul>
                    <li><?php esc_html_e('Test different types of comments (legitimate, spam, inappropriate)', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('See the AI decision (approve, reject, spam, or hold for review)', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('View the confidence score and reasoning behind each decision', 'ai-comment-guard'); ?></li>
                    <li><?php esc_html_e('Fine-tune your prompts and thresholds based on test results', 'ai-comment-guard'); ?></li>
                </ul>
                <p><?php esc_html_e('Once you configure your AI provider in the Settings tab, you can use this feature to test and optimize your comment moderation.', 'ai-comment-guard'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle comment analysis AJAX request
     *
     * @return void
     */
    public function handle_analyze_comment() {
        check_ajax_referer('aicog_preview_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Get form data
        $comment_author = isset($_POST['comment_author']) ? sanitize_text_field(wp_unslash($_POST['comment_author'])) : '';
        $comment_email = isset($_POST['comment_email']) ? sanitize_email(wp_unslash($_POST['comment_email'])) : '';
        $comment_content = isset($_POST['comment_content']) ? sanitize_textarea_field(wp_unslash($_POST['comment_content'])) : '';
        
        if (empty($comment_content)) {
            wp_send_json_error(__('Comment content is required', 'ai-comment-guard'));
        }
        
        // Check if AI is configured
        if (!$this->config->is_configured()) {
            wp_send_json_error(__('AI provider is not configured', 'ai-comment-guard'));
        }
        
        try {
            // Get AI configuration
            $provider = $this->config->get('ai_provider');
            $token = $this->config->get('ai_provider_token');
            
            // Initialize AI manager
            $ai_manager = new AIManager($provider, $token);
            
            // Get system message from configuration
            $system_message = $this->config->get_prompt('system');
            
            // Create mock comment data
            $comment_data = [
                'comment_author' => $comment_author,
                'comment_author_email' => $comment_email,
                'comment_author_url' => '', // Empty URL for preview
                'comment_content' => $comment_content,
                'comment_author_IP' => '127.0.0.1', // Mock IP for testing
                'comment_agent' => 'Preview Test', // Mock user agent
                'comment_date' => current_time('mysql'),
                'comment_post_ID' => 1 // Mock post ID
            ];
            
            // Analyze the comment
            $result = $ai_manager->analyze_comment($comment_data, $system_message);
            
            if ($result) {
                wp_send_json_success([
                    'status' => $result['status'],
                    'confidence' => $result['confidence'],
                    'reasoning' => $result['reasoning'],
                    'prompt_used' => isset($result['prompt_used']) ? $result['prompt_used'] : null,
                    'system_message' => isset($result['system_message']) ? $result['system_message'] : null,
                    'raw_response' => isset($result['raw_response']) ? $result['raw_response'] : null
                ]);
            } else {
                wp_send_json_error(__('Failed to analyze comment', 'ai-comment-guard'));
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(sprintf(
                /* translators: %s is the error message from the analysis attempt */
                __('Analysis error: %s', 'ai-comment-guard'),
                $e->getMessage()
            ));
        }
    }
}