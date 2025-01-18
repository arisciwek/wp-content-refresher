<?php
/**
 * Previous Posts Shortcode
 * 
 * Menampilkan 5 post sebelumnya berdasarkan Post ID untuk internal linking
 *
 * @package     WPContentRefresher
 * @subpackage  Shortcodes
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: includes/shortcodes/previous-posts-shortcode.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mendaftarkan shortcode [previous_posts]
add_shortcode('previous_posts', 'display_previous_posts');

function display_previous_posts($atts) {
    // Get current post ID
    $current_post_id = get_the_ID();
    
    // Hitung range post ID yang akan dicari (5 post sebelumnya)
    $start_id = $current_post_id - 1;
    $end_id = $current_post_id - 5;
    
    // Query untuk mendapatkan posts
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'ID',
        'order' => 'DESC',
        'post__in' => range($end_id, $start_id)
    );
    
    $previous_posts = new WP_Query($args);
    
    // Mulai output buffering
    ob_start();
    
    if ($previous_posts->have_posts()) {
        echo '<div class="previous-posts-list">';
        echo '<h4>Artikel Terkait:</h4>';
        echo '<ul style="list-style-type: none; padding-left: 0;">';
        
        while ($previous_posts->have_posts()) {
            $previous_posts->the_post();
            
            // Dapatkan excerpt atau potong content jika excerpt kosong
            $excerpt = get_the_excerpt();
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(get_the_content(), 20, '...');
            }
            
            echo '<li style="margin-bottom: 15px;">';
            echo '<div class="related-post-item">';
            echo '<a href="' . get_permalink() . '" style="text-decoration: none; color: #333;">';
            echo '<h5 style="margin: 0 0 5px 0;">' . get_the_title() . '</h5>';
            echo '</a>';
            echo '<p style="margin: 0; font-size: 0.9em; color: #666;">' . $excerpt . '</p>';
            echo '</div>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    // Reset post data
    wp_reset_postdata();
    
    // Kembalikan output buffer
    return ob_get_clean();
}

// Tambahkan file ini ke plugin utama
function include_previous_posts_shortcode() {
    include_once plugin_dir_path(__FILE__) . 'previous-posts-shortcode.php';
}
add_action('init', 'include_previous_posts_shortcode');

