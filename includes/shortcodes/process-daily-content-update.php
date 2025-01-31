<?php
/**
 * Process Daily Content Update
 * 
 * Handles the core functionality of updating content automatically
 * including intelligent shortcode insertion and content refresh logic.
 *
 * @package     WPContentRefresher
 * @subpackage  Shortcodes
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: includes/shortcodes/process-daily-content-update.php
 */


if (!defined('ABSPATH')) {
    exit;
}


function process_daily_content_update() {
    // Update Pages
    $pages = get_pages(array(
        'sort_column' => 'post_modified',
        'sort_order' => 'ASC',
        'number' => 5
    ));

    foreach ($pages as $page) {
        $content = $page->post_content;
        $updated_content = $content;
        
        // Cek shortcode previous_posts
        if (strpos($content, '[previous_posts]') === false) {
            $updated_content .= "\n\n[previous_posts]";
        } else {
            // Jika sudah ada shortcode, tambahkan spasi saja
            $updated_content .= ' ';
        }
        
        // Update page
        wp_update_post(array(
            'ID' => $page->ID,
            'post_content' => $updated_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));

        // Log update
        WP_Content_Refresher_Logger::log_update(
            $page->ID, 
            'success', 
            strpos($content, '[previous_posts]') === false ? 'Added previous_posts shortcode' : 'Updated with space'
        );
    }

    // Update Posts
    $posts = get_posts(array(
        'orderby' => 'modified',
        'order' => 'ASC',
        'numberposts' => 5,
        'post_type' => 'post'
    ));

    foreach ($posts as $post) {
        // Skip if post is excluded from updates
        if (WP_Content_Refresher_Logger::is_post_excluded($post->ID)) {
            continue;
        }

        $content = $post->post_content;
        $updated_content = $content;

        // Cek keberadaan kedua shortcode
        $has_previous = strpos($content, '[previous_posts]') !== false;
        $has_recent = strpos($content, '[recent_updated_posts]') !== false;
        
        if (!$has_previous && !$has_recent) {
            // Jika tidak ada shortcode sama sekali, tambahkan keduanya
            $updated_content .= "\n\n[previous_posts]\n\n[recent_updated_posts]";
            $update_message = 'Added both shortcodes';
        } elseif (!$has_previous) {
            // Jika hanya kurang previous_posts
            $updated_content .= "\n\n[previous_posts]";
            $update_message = 'Added previous_posts shortcode';
        } elseif (!$has_recent) {
            // Jika hanya kurang recent_updated_posts
            $updated_content .= "\n\n[recent_updated_posts]";
            $update_message = 'Added recent_updated_posts shortcode';
        } else {
            // Jika sudah ada kedua shortcode, tambah spasi saja
            $updated_content .= ' ';
            $update_message = 'Updated with space';
        }
        
        // Update post
        wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $updated_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));

        // Log the update
        WP_Content_Refresher_Logger::log_update($post->ID, 'success', $update_message);
    }

    // Trigger untuk memperbarui sitemap jika menggunakan plugin Yoast SEO
    if (defined('WPSEO_VERSION')) {
        do_action('wpseo_ping_search_engines');
    }
}
