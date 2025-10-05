<?php
/**
 * Plugin Name: AI Comment Guard
 * Plugin URI: https://www.linkedin.com/in/tudor-eusebiu-constantin/
 * Description: AI-powered WordPress comment management plugin. Automatically analyzes comments and approves, rejects or marks them as spam using a customizable AI provider.
 * Version: 1.2.2
 * Author: Tudor Constantin
 * License: GPL v2 or later
 * Text Domain: ai-comment-guard
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

// Define plugin constants
define('AICOG_VERSION', '1.2.2');
define('AICOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOG_PLUGIN_FILE', __FILE__);
define('AICOG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load autoloader
require_once AICOG_PLUGIN_DIR . 'includes/Core/Autoloader.php';

use AICOG\Core\Autoloader;
use AICOG\Core\Plugin;

// Register autoloader
$autoloader = new Autoloader(AICOG_PLUGIN_DIR);
$autoloader->register();

// Get plugin instance early for activation hooks
$plugin = Plugin::get_instance();

// Register activation/deactivation hooks directly here
register_activation_hook(__FILE__, [$plugin, 'activate']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', function() use ($plugin) {
    $plugin->init();
});
