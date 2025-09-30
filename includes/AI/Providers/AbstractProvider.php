<?php
/**
 * AI Comment Guard - Abstract AI Provider
 *
 * @package AICOG
 * @subpackage AI\Providers
 * @since 1.0.0
 */

namespace AICOG\AI\Providers;

/**
 * Abstract AI Provider
 *
 * @since 1.0.0
 */
abstract class AbstractProvider {
    
    /**
     * @var string Provider name
     */
    protected $name;
    
    /**
     * @var string API endpoint
     */
    protected $endpoint;
    
    /**
     * @var string API token
     */
    protected $token;
    
    /**
     * @var string Model to use
     */
    protected $model;
    
    /**
     * @var array Request headers
     */
    protected $headers = [];
    
    /**
     * @var int Request timeout
     */
    protected $timeout = 30;
    
    /**
     * Constructor
     *
     * @param string $token API token
     */
    public function __construct($token) {
        $this->token = $token;
        $this->setup();
    }
    
    /**
     * Setup provider-specific configuration
     *
     * @return void
     */
    abstract protected function setup();
    
    /**
     * Build request body
     *
     * @param string $prompt User prompt
     * @param string $system_message System message
     * @return array
     */
    abstract protected function build_request_body($prompt, $system_message);
    
    /**
     * Extract response from API result
     *
     * @param array $response API response
     * @return string|null
     */
    abstract protected function extract_response($response);
    
    /**
     * Make API request
     *
     * @param string $prompt User prompt
     * @param string $system_message System message
     * @return array
     * @throws \Exception
     */
    public function request($prompt, $system_message = '') {
        $start_time = microtime(true);
        
        $body = $this->build_request_body($prompt, $system_message);
        
        $args = [
            'body' => json_encode($body),
            'headers' => $this->get_headers(),
            'timeout' => $this->timeout,
            'method' => 'POST',
            'sslverify' => true
        ];
        
        $response = wp_remote_request($this->endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . esc_html($response->get_error_message()));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $this->handle_error($response_code, $response_body);
        }
        
        $data = json_decode($response_body, true);
        
        if (!$data) {
            throw new \Exception('Invalid JSON response: ' . esc_html(json_last_error_msg()));
        }
        
        $result = $this->extract_response($data);
        
        if (!$result) {
            throw new \Exception('Could not extract response from API result');
        }
        
        return [
            'response' => $result,
            'provider' => $this->name,
            'processing_time' => microtime(true) - $start_time,
            'raw_response' => $data
        ];
    }
    
    /**
     * Test connection
     *
     * @return bool
     * @throws \Exception
     */
    public function test_connection() {
        try {
            $result = $this->request('Test connection. Reply with: OK', 'You are a test assistant.');
            return !empty($result['response']);
        } catch (\Exception $e) {
            throw new \Exception('Connection test failed: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * Get request headers
     *
     * @return array
     */
    protected function get_headers() {
        return array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Comment-Guard/2.0'
        ], $this->headers);
    }
    
    /**
     * Handle API errors
     *
     * @param int $code HTTP response code
     * @param string $body Response body
     * @throws \Exception
     */
    protected function handle_error($code, $body) {
        $data = json_decode($body, true);
        $message = "HTTP {$code}";
        
        if ($data && isset($data['error'])) {
            if (isset($data['error']['message'])) {
                $message .= ': ' . $data['error']['message'];
            } elseif (is_string($data['error'])) {
                $message .= ': ' . $data['error'];
            }
        }
        
        throw new \Exception(esc_html($message));
    }
    
    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Set model
     *
     * @param string $model Model name
     * @return void
     */
    public function set_model($model) {
        $this->model = $model;
    }
}
