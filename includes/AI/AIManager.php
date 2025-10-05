<?php
/**
 * AI Comment Guard - AI Manager
 *
 * @package AICOG
 * @subpackage AI
 * @since 1.0.0
 */

namespace AICOG\AI;

use AICOG\AI\Providers\OpenAIProvider;
use AICOG\AI\Providers\AnthropicProvider;
use AICOG\AI\Providers\OpenRouterProvider;
use AICOG\AI\Providers\AbstractProvider;

/**
 * AI Manager - Factory for AI Providers
 *
 * @since 1.0.0
 */
class AIManager {
    
    /**
     * @var array Available providers
     */
    private static $providers = [
        'openai' => OpenAIProvider::class,
        'anthropic' => AnthropicProvider::class,
        'openrouter' => OpenRouterProvider::class
    ];
    
    /**
     * @var AbstractProvider Current provider instance
     */
    private $provider;
    
    /**
     * Constructor
     *
     * @param string $provider_name Provider name
     * @param string $token API token
     * @throws \Exception
     */
    public function __construct($provider_name, $token) {
        if (!isset(self::$providers[$provider_name])) {
            throw new \Exception('Unsupported AI provider: ' . esc_html($provider_name));
        }
        
        $provider_class = self::$providers[$provider_name];
        $this->provider = new $provider_class($token);
    }
    
    /**
     * Create provider instance
     *
     * @param string $provider_name Provider name
     * @param string $token API token
     * @return AbstractProvider
     * @throws \Exception
     */
    public static function create($provider_name, $token) {
        $manager = new self($provider_name, $token);
        return $manager->get_provider();
    }
    
    /**
     * Get provider instance
     *
     * @return AbstractProvider
     */
    public function get_provider() {
        return $this->provider;
    }
    
    /**
     * Analyze comment using AI
     *
     * @param array $comment_data Comment data
     * @param string $system_message Custom system message
     * @return array Analysis result
     * @throws \Exception
     */
    public function analyze_comment($comment_data, $system_message = '') {
        $prompt = $this->build_prompt($comment_data);
        
        try {
            $result = $this->provider->request($prompt, $system_message);
            $analysis = $this->parse_analysis($result['response']);
            
            if (!$analysis) {
                throw new \Exception('Invalid analysis format');
            }
            
            return array_merge($analysis, [
                'provider' => $result['provider'],
                'processing_time' => $result['processing_time'],
                'prompt_used' => $prompt,
                'system_message' => $system_message
            ]);
            
        } catch (\Exception $e) {
            throw new \Exception('AI analysis failed: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * Build analysis prompt
     *
     * @param array $comment_data Comment data
     * @return string
     */
    private function build_prompt($comment_data) {
        $prompt = __("Analyze the following comment and determine if it should be approved, rejected, or marked as spam.", 'ai-comment-guard') . "\n";
        $prompt .= __("Respond ONLY with a valid JSON with the following structure:", 'ai-comment-guard') . "\n";
        $prompt .= '{"analysis": "approved|rejected|spam", "confidence": 0.0-1.0, "reason": "brief explanation"}' . "\n\n";
        $prompt .= __("Criteria:", 'ai-comment-guard') . "\n";
        $prompt .= __("- SPAM: Promotional comments, suspicious links, nonsensical text, repetitive content", 'ai-comment-guard') . "\n";
        $prompt .= __("- REJECTED: Offensive comments, inappropriate content, off-topic", 'ai-comment-guard') . "\n";
        $prompt .= __("- APPROVED: Constructive comments, relevant, appropriate", 'ai-comment-guard') . "\n\n";
        $prompt .= __("Comment to analyze:", 'ai-comment-guard') . "\n";
        $prompt .= __("Author:", 'ai-comment-guard') . " " . (isset($comment_data['comment_author']) ? $comment_data['comment_author'] : 'Unknown') . "\n";
        $prompt .= __("Email:", 'ai-comment-guard') . " " . (isset($comment_data['comment_author_email']) ? $comment_data['comment_author_email'] : 'No email provided') . "\n";
        $prompt .= __("URL:", 'ai-comment-guard') . " " . (isset($comment_data['comment_author_url']) ? $comment_data['comment_author_url'] : 'No URL provided') . "\n";
        $prompt .= __("Content:", 'ai-comment-guard') . " " . $comment_data['comment_content'];
        
        return $prompt;
    }
    
    /**
     * Parse AI analysis response
     *
     * @param string $response AI response
     * @return array|null
     */
    private function parse_analysis($response) {
        // Clean the response
        $response = trim($response);
        
        // Try to extract JSON from the response
        if (preg_match('/\{[^}]*"analysis"[^}]*\}/s', $response, $matches)) {
            $json_str = $matches[0];
            $analysis = json_decode($json_str, true);
            if ($analysis && isset($analysis['analysis'])) {
                return $this->normalize_analysis($analysis);
            }
        }
        
        // Fallback: try to parse the entire response as JSON
        $analysis = json_decode($response, true);
        if ($analysis && isset($analysis['analysis'])) {
            return $this->normalize_analysis($analysis);
        }
        
        // Fallback: create analysis based on keywords
        return $this->fallback_analysis($response);
    }
    
    /**
     * Normalize analysis result
     *
     * @param array $analysis Analysis data
     * @return array
     */
    private function normalize_analysis($analysis) {
        // Ensure confidence is a float between 0 and 1
        $confidence = isset($analysis['confidence']) ? (float) $analysis['confidence'] : 0.5;
        $confidence = max(0, min(1, $confidence));
        
        // Ensure reason exists
        $reason = isset($analysis['reason']) ? $analysis['reason'] : 'AI analysis completed';
        
        return [
            'status' => $analysis['analysis'],
            'confidence' => $confidence,
            'reasoning' => $reason
        ];
    }
    
    /**
     * Fallback analysis based on keywords
     *
     * @param string $response AI response
     * @return array|null
     */
    private function fallback_analysis($response) {
        $response_lower = strtolower($response);
        
        if (strpos($response_lower, 'spam') !== false) {
            return [
                'status' => 'spam',
                'confidence' => 0.7,
                'reasoning' => 'Detected spam keywords in AI response'
            ];
        } elseif (strpos($response_lower, 'reject') !== false || strpos($response_lower, 'inappropriate') !== false) {
            return [
                'status' => 'rejected',
                'confidence' => 0.7,
                'reasoning' => 'Detected rejection keywords in AI response'
            ];
        } elseif (strpos($response_lower, 'approv') !== false) {
            return [
                'status' => 'approved',
                'confidence' => 0.7,
                'reasoning' => 'Detected approval keywords in AI response'
            ];
        }
        
        return null;
    }
    
    /**
     * Test connection
     *
     * @return bool
     * @throws \Exception
     */
    public function test_connection() {
        return $this->provider->test_connection();
    }
    
    /**
     * Get available providers
     *
     * @return array
     */
    public static function get_available_providers() {
        return [
            'openai' => __('OpenAI (GPT-4, GPT-3.5)', 'ai-comment-guard'),
            'anthropic' => __('Anthropic (Claude)', 'ai-comment-guard'),
            'openrouter' => __('OpenRouter (Multiple models)', 'ai-comment-guard')
        ];
    }
}
