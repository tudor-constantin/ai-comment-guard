<?php
/**
 * AI Comment Guard - OpenAI Provider
 *
 * @package AICOG
 * @subpackage AI\Providers
 * @since 1.0.0
 */

namespace AICOG\AI\Providers;

/**
 * OpenAI Provider
 *
 * @since 1.0.0
 */
class OpenAIProvider extends AbstractProvider {
    
    /**
     * Setup provider configuration
     *
     * @return void
     */
    protected function setup() {
        $this->name = 'openai';
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->model = 'gpt-3.5-turbo';
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->token
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
        $messages = [];
        
        if (!empty($system_message)) {
            $messages[] = [
                'role' => 'system',
                'content' => $system_message
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
        return [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 150,
            'temperature' => 0.1
        ];
    }
    
    /**
     * Extract response from API result
     *
     * @param array $response API response
     * @return string|null
     */
    protected function extract_response($response) {
        return $response['choices'][0]['message']['content'] ?? null;
    }
}
