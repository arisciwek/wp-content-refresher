<?php
/**
 * WP Content Refresher Security
 * 
 * Handles all security aspects of the WP Content Refresher plugin including
 * nonce verification, capability checks, and secure AJAX handling.
 *
 * @package     WPContentRefresher
 * @subpackage  Security
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: includes/class-wp-content-refresher-security.php
 */

if (!defined('ABSPATH')) {
    exit;
}


class WP_Content_Refresher_Security {
    public static function init() {
        // Hanya check permissions pada halaman plugin ini
        add_action('load-settings_page_wp-content-refresher', array(__CLASS__, 'check_permissions'));
        add_action('wp_ajax_refresh_content', array(__CLASS__, 'handle_manual_refresh'));
    }

    public static function check_permissions() {
        // Check permissions hanya untuk halaman settings plugin
        if (!current_user_can('manage_options')) {
            wp_die(__('Maaf, Anda tak diizinkan mengakses laman ini.'));
        }
    }

    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error('Token keamanan tidak valid');
            exit;
        }
        return true;
    }

    public static function handle_manual_refresh() {
        // Verify nonce
        self::verify_nonce($_POST['security'], 'manual_content_refresh');
        
        // Verify user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Izin tidak mencukupi');
            exit;
        }

        // Sanitize input
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if ($post_id > 0) {
            // Process single post update
            process_single_content_update($post_id);
        } else {
            // Process bulk update
            process_daily_content_update();
        }

        wp_send_json_success('Pembaruan konten selesai');
    }

    // Tambahkan method untuk memverifikasi akses ke fitur spesifik
    public static function can_access_settings() {
        return current_user_can('manage_options');
    }

    public static function can_update_content() {
        return current_user_can('edit_posts');
    }
}
