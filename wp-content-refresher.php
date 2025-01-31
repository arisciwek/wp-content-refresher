<?php
/**
 * Plugin Name: WP Content Refresher
 * Plugin URI: 
 * Description: Plugin untuk memperbarui konten secara otomatis menggunakan cron
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: arisciwek
 * Author URI: 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package     WPContentRefresher
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}


// Define plugin constants
define('WCR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WCR_PLUGIN_DIR . 'admin/class-wp-content-refresher-admin.php';
require_once WCR_PLUGIN_DIR . 'includes/class-wp-content-refresher-security.php';
require_once WCR_PLUGIN_DIR . 'includes/class-wp-content-refresher-logger.php';
require_once WCR_PLUGIN_DIR . 'includes/shortcodes/previous-posts-shortcode.php';
require_once WCR_PLUGIN_DIR . 'includes/shortcodes/recent-updated-posts-shortcode.php';
require_once WCR_PLUGIN_DIR . 'includes/shortcodes/process-daily-content-update.php';


// Initialize admin
if (is_admin()) {
    add_action('plugins_loaded', function() {
        new WP_Content_Refresher_Admin();
    });
}

// Sanitize shortcode attributes
function sanitize_shortcode_atts($atts) {
    return array_map(function($value) {
        if (is_numeric($value)) {
            return absint($value);
        }
        return sanitize_text_field($value);
    }, $atts);
}

// Initialize security features
WP_Content_Refresher_Security::init();

// Aktivasi plugin
register_activation_hook(__FILE__, 'daily_content_updater_activation');
function daily_content_updater_activation() {
    if (!wp_next_scheduled('daily_content_update_event')) {
        // Menjadwalkan event untuk dijalankan setiap hari pada jam 1 pagi
        wp_schedule_event(strtotime('tomorrow 1:00 am'), 'daily', 'daily_content_update_event');
    }
}

// Deaktivasi plugin
register_deactivation_hook(__FILE__, 'daily_content_updater_deactivation');
function daily_content_updater_deactivation() {
    wp_clear_scheduled_hook('daily_content_update_event');
}

// Fungsi utama untuk update konten
add_action('daily_content_update_event', 'process_daily_content_update');
function process_daily_content_update() {
    // Update Pages
    $pages = get_pages(array(
        'sort_column' => 'post_modified',
        'sort_order' => 'ASC',
        'number' => 5
    ));

    foreach ($pages as $page) {
        $content = $page->post_content;
        
        // Menambahkan spasi di akhir konten untuk memicu perubahan
        $updated_content = $content . ' ';
        
        // Update page
        wp_update_post(array(
            'ID' => $page->ID,
            'post_content' => $updated_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));
    }

    // Update Posts
    $posts = get_posts(array(
        'orderby' => 'modified',
        'order' => 'ASC',
        'numberposts' => 5,
        'post_type' => 'post'
    ));

    foreach ($posts as $post) {
        $content = $post->post_content;
        
        // Menambahkan spasi di akhir konten untuk memicu perubahan
        $updated_content = $content . ' ';
        
        // Update post
        wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $updated_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));
    }

    // Trigger untuk memperbarui sitemap jika menggunakan plugin Yoast SEO
    if (defined('WPSEO_VERSION')) {
        do_action('wpseo_ping_search_engines');
    }
}

// Menambahkan filter untuk memastikan cron berjalan harian
add_filter('cron_schedules', 'add_daily_cron_interval');
function add_daily_cron_interval($schedules) {
    $schedules['daily'] = array(
        'interval' => 86400, // 24 jam dalam detik
        'display'  => __('Once Daily')
    );
    return $schedules;
}



function wcr_load_more_posts() {
    check_ajax_referer('wcr_ajax_nonce', 'nonce');
    
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'offset' => $offset,
        'orderby' => 'modified',
        'order' => 'DESC'
    );
    
    $query = new WP_Query($args);
    $response = array(
        'html' => '',
        'has_more' => $query->found_posts > ($offset + 5)
    );
    
    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            // Render post template
            get_template_part('template-parts/content', 'recent-post');
        }
        $response['html'] = ob_get_clean();
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_load_more_posts', 'wcr_load_more_posts');
add_action('wp_ajax_nopriv_load_more_posts', 'wcr_load_more_posts');
