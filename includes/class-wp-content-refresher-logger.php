<?php
/**
 * WP Content Refresher Logger
 * 
 * Handles logging functionality for content updates and provides
 * exclusion management for posts/pages from auto-updates.
 *
 * @package     WPContentRefresher
 * @subpackage  Logger
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: includes/class-wp-content-refresher-logger.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Content_Refresher_Logger {
    private static $log_table = 'wcr_update_logs';

    public static function init() {
        self::create_log_table();
    }

    private static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$log_table;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            update_time datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL,
            message text,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function log_update($post_id, $status, $message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$log_table;

        return $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'status' => $status,
                'message' => $message
            ),
            array('%d', '%s', '%s')
        );
    }

    // Add exclude functionality
    public static function is_post_excluded($post_id) {
        return get_post_meta($post_id, '_wcr_exclude_from_update', true) === 'yes';
    }

    // Add meta box for exclusion
    public static function add_exclude_meta_box() {
        $post_types = get_option('wcr_settings')['post_types'] ?? array('post', 'page');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'wcr_exclude_update',
                'Content Refresher Settings',
                array(__CLASS__, 'render_exclude_meta_box'),
                $post_type,
                'side'
            );
        }
    }

    public static function render_exclude_meta_box($post) {
        wp_nonce_field('wcr_exclude_update', 'wcr_exclude_nonce');
        $excluded = self::is_post_excluded($post->ID);
        ?>
        <label>
            <input type="checkbox" name="wcr_exclude_update" value="yes" <?php checked($excluded); ?>>
            Exclude from auto-updates
        </label>
        <?php
    }
}

// Initialize
add_action('init', array('WP_Content_Refresher_Logger', 'init'));
add_action('add_meta_boxes', array('WP_Content_Refresher_Logger', 'add_exclude_meta_box'));
