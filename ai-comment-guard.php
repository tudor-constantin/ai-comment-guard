<?php
/**
 * Plugin Name: AI Comment Guard
 * Plugin URI: https://www.linkedin.com/in/tudor-eusebiu-constantin/
 * Description: Un plugin para gestionar con IA los comentarios de WordPress. Analiza automáticamente los comentarios y los aprueba, rechaza o marca como spam usando un proveedor de IA personalizable.
 * Version: 1.1.0
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
define('AI_COMMENT_GUARD_VERSION', '1.1.0');
define('AI_COMMENT_GUARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_COMMENT_GUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_COMMENT_GUARD_PLUGIN_FILE', __FILE__);
define('AI_COMMENT_GUARD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load autoloader
require_once AI_COMMENT_GUARD_PLUGIN_DIR . 'includes/Core/Autoloader.php';

use AI_Comment_Guard\Core\Autoloader;
use AI_Comment_Guard\Core\Plugin;

// Register autoloader
$autoloader = new Autoloader(AI_COMMENT_GUARD_PLUGIN_DIR);
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
