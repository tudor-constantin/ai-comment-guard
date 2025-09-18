<?php
/**
 * AI Comment Guard - Anthropic Provider
 *
 * @package AI_Comment_Guard
 * @subpackage AI\Providers
 * @since 1.0.0
 */

namespace AI_Comment_Guard\AI\Providers;

/**
 * Anthropic Provider
 *
 * @since 1.0.0
 */
class AnthropicProvider extends AbstractProvider {
    
    /**
     * Setup provider configuration
     *
     * @return void
     */
    protected function setup() {
        $this->name = 'anthropic';
        $this->endpoint = 'https://api.anthropic.com/v1/messages';
        $this->model = 'claude-3-haiku-20240307';
        $this->headers = [
            'x-api-key' => $this->token,
            'anthropic-version' => '2023-06-01'
        ];
    }
    
    /**
     * Build request body
     *
     * @param string $prompt User prompt
     * @param string $system_message System message
     * @return array
     */
    protected function build_request_body($prompt, $system_message) {
        $content = !empty($system_message) 
            ? $system_message . "\n\n" . $prompt 
            : $prompt;
        
        return [
            'model' => $this->model,
            'max_tokens' => 150,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ]
        ];
    }
    
    /**
     * Extract response from API result
     *
     * @param array $response API response
     * @return string|null
     */
    protected function extract_response($response) {
        return $response['content'][0]['text'] ?? null;
    }
}
