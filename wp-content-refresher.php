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
