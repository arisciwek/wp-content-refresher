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

function get_post_excerpt($post_id) {
    // Cek apakah ada custom excerpt
    $excerpt = get_post_field('post_excerpt', $post_id);
    
    // Jika tidak ada excerpt, gunakan content dengan batasan kata
    if (empty($excerpt)) {
        $content = get_post_field('post_content', $post_id);
        $excerpt = wp_trim_words($content, 20, '...');
    }
    
    return $excerpt;
}

function display_previous_posts($atts) {
    // Get settings dari admin panel
    $options = get_option('wcr_settings');
    $posts_per_page = isset($options['posts_per_shortcode']) ? absint($options['posts_per_shortcode']) : 5;
    
    // Get current post ID
    $current_post_id = get_the_ID();
    
    // Dapatkan semua post ID yang lebih kecil dari current post
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'post' 
        AND post_status = 'publish' 
        AND ID < %d 
        ORDER BY ID DESC 
        LIMIT %d",
        $current_post_id,
        $posts_per_page
    );
    
    $previous_post_ids = $wpdb->get_col($query);
    
    if (empty($previous_post_ids)) {
        return ''; // Return empty if no previous posts found
    }
    
    // Query untuk mendapatkan posts
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'post__in' => $previous_post_ids,
        'orderby' => 'ID',
        'order' => 'DESC',
        'ignore_sticky_posts' => 1
    );
    
    $previous_posts = new WP_Query($args);
    
    // Mulai output buffering
    ob_start();
    
    if ($previous_posts->have_posts()) {
        echo '<div class="previous-posts-container">';
        echo '<h4 class="previous-posts-title">Artikel Terkait:</h4>';
        echo '<ul class="previous-posts-list">';
        
        while ($previous_posts->have_posts()) {
            $previous_posts->the_post();
            
            // Dapatkan excerpt dengan helper function
            $excerpt = get_post_excerpt(get_the_ID());
            
            echo '<li class="previous-post-item">';
            echo '<a href="' . get_permalink() . '" class="previous-post-link">';
            
            // Thumbnail jika ada
            if (has_post_thumbnail()) {
                echo '<div class="previous-post-thumbnail">';
                echo get_the_post_thumbnail(null, 'thumbnail');
                echo '</div>';
            }
            
            echo '<div class="previous-post-content">';
            echo '<h5 class="previous-post-title">' . get_the_title() . '</h5>';
            echo '<p class="previous-post-excerpt">' . $excerpt . '</p>';
            echo '</div>';
            
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        // CSS styles remain the same...
        echo '<style>
            .previous-posts-container {
                margin: 2em 0;
                padding: 1em;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .previous-posts-title {
                margin-bottom: 1em;
                font-size: 1.2em;
                color: #333;
            }
            li.previous-posts-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .previous-post-item {
                margin-bottom: 1em;
                border-bottom: 1px solid #eee;
                padding-bottom: 1em;
            }
            .previous-post-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .previous-post-link {
                display: flex;
                text-decoration: none;
                color: inherit;
            }
            .previous-post-thumbnail {
                flex: 0 0 100px;
                margin-right: 1em;
            }
            .previous-post-thumbnail img {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 4px;
            }
            .previous-post-content {
                flex: 1;
            }
            .previous-post-title {
                margin: 0 0 0.5em 0;
                font-size: 1.1em;
                color: #1a1a1a;
            }
            .previous-post-excerpt {
                margin: 0;
                font-size: 0.9em;
                color: #666;
                line-height: 1.5;
            }
        </style>';
    }
    
    // Reset post data
    wp_reset_postdata();
    
    // Kembalikan output buffer
    return ob_get_clean();
}