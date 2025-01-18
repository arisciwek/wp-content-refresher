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

add_shortcode('previous_posts', 'display_previous_posts');

function get_post_excerpt($post_id) {
    $excerpt = get_post_field('post_excerpt', $post_id);
    if (empty($excerpt)) {
        $content = get_post_field('post_content', $post_id);
        $excerpt = wp_trim_words($content, 20, '...');
    }
    return $excerpt;
}

function display_previous_posts($atts) {
    // Get current post ID
    $current_post_id = get_the_ID();
    
    // Get options dari admin panel
    $options = get_option('wcr_settings');
    $posts_per_page = isset($options['posts_per_shortcode']) ? absint($options['posts_per_shortcode']) : 5;
    
    // Query untuk mendapatkan posts dengan ID di bawah current post
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'orderby' => 'ID',
        'order' => 'DESC',
        'post__in' => range($current_post_id - $posts_per_page, $current_post_id - 1),
        'ignore_sticky_posts' => 1
    );
    
    $previous_posts = new WP_Query($args);
    
    ob_start();
    
    if ($previous_posts->have_posts()) {
        echo '<div class="previous-posts-container">';
        echo '<h4 class="previous-posts-title">Artikel Terkait:</h4>';
        echo '<ul class="previous-posts-list">';
        
        while ($previous_posts->have_posts()) {
            $previous_posts->the_post();
            $excerpt = get_post_excerpt(get_the_ID());
            
            echo '<li class="previous-post-item">';
            echo '<a href="' . get_permalink() . '" class="previous-post-link">';
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
    }
    
    wp_reset_postdata();
    
    return ob_get_clean();
}

// Daftarkan CSS untuk shortcode
function register_previous_posts_styles() {
    $css = '
        .previous-posts-container {
            margin: 2em 0;
            padding: 1.5em;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .previous-posts-title {
            margin: 0 0 1em 0;
            padding: 0;
            font-size: 1.2em;
            color: #333;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5em;
        }
        .previous-posts-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .previous-post-item {
            padding: 0.8em 0;
            border-bottom: 1px solid #e9ecef;
        }
        .previous-post-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .previous-post-link {
            display: flex;
            text-decoration: none;
            color: inherit;
            gap: 1em;
        }
        .previous-post-thumbnail {
            flex: 0 0 100px;
        }
        .previous-post-thumbnail img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
        }
        .previous-post-content {
            flex: 1;
            min-width: 0;
        }
        .previous-post-title {
            margin: 0 0 0.5em 0;
            font-size: 1em;
            color: #2c3e50;
            font-weight: 600;
        }
        .previous-post-excerpt {
            margin: 0;
            font-size: 0.9em;
            color: #666;
            line-height: 1.5;
        }
    ';
    
    wp_add_inline_style('wp-content-refresher', $css);
}
add_action('wp_enqueue_scripts', 'register_previous_posts_styles');
