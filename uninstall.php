<?php
/**
 * AI Comment Guard Uninstall
 *
 * This file runs when the plugin is uninstalled (deleted).
 * Removes all database tables, options, and transients created by the plugin.
 *
 * @package AI_Comment_Guard
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit('Direct access not allowed');
}

// Load plugin file for access to the new structure
require_once plugin_dir_path(__FILE__) . 'includes/Core/Autoloader.php';
require_once plugin_dir_path(__FILE__) . 'includes/Database/DatabaseManager.php';

use AI_Comment_Guard\Database\DatabaseManager;

// Initialize database manager
$database = new DatabaseManager();

// Drop all plugin tables
$database->drop_tables();

// Delete all plugin options
delete_option('ai_comment_guard_settings');
delete_option('ai_comment_guard_version');
delete_option('ai_comment_guard_db_version');

// Delete all plugin transients
delete_transient('ai_comment_guard_connection_tested');

// Clear any scheduled cron events
wp_clear_scheduled_hook('ai_comment_guard_cleanup');
wp_clear_scheduled_hook('ai_comment_guard_daily_stats');

// Clean up any user meta if exists
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for uninstall cleanup
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'ai_comment_guard_%'));

// Clean up any orphaned comment meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for uninstall cleanup
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE %s", 'ai_comment_guard_%'));
