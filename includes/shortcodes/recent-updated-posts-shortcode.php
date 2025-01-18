<?php
/**
 * Recent Updated Posts Shortcode
 * 
 * Menampilkan 5 post berdasarkan tanggal update dengan fitur offset dan featured image
 *
 * @package     WPContentRefresher
 * @subpackage  Shortcodes
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: includes/shortcodes/recent-updated-posts-shortcode.php
 * 
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mendaftarkan shortcode [recent_updated_posts]
add_shortcode('recent_updated_posts', 'display_recent_updated_posts');
add_action('wp_enqueue_scripts', 'enqueue_wcr_assets');
add_filter('the_content', 'add_schema_markup', 10, 2);

add_action('save_post', 'invalidate_recent_posts_cache');
add_action('delete_post', 'invalidate_recent_posts_cache');

// Tambahkan ke recent-updated-posts-shortcode.php

function get_cached_recent_posts($args) {
    // Generate cache key berdasarkan arguments
    $cache_key = 'wcr_recent_posts_' . md5(serialize($args));
    
    // Coba ambil dari cache
    $cached_results = wp_cache_get($cache_key, 'wcr_recent_posts');
    
    if (false !== $cached_results) {
        return $cached_results;
    }
    
    // Jika tidak ada cache, jalankan query
    $recent_posts = new WP_Query($args);
    $results = array();
    
    if ($recent_posts->have_posts()) {
        while ($recent_posts->have_posts()) {
            $recent_posts->the_post();
            $results[] = array(
                'ID' => get_the_ID(),
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'modified_date' => get_the_modified_date('j F Y'),
                'thumbnail' => has_post_thumbnail() ? get_the_post_thumbnail_url(null, array(100, 100)) : null
            );
        }
        wp_reset_postdata();
    }
    
    // Simpan ke cache selama 1 jam
    wp_cache_set($cache_key, $results, 'wcr_recent_posts', HOUR_IN_SECONDS);
    
    return $results;
}

// Invalidate cache saat post diupdate
function invalidate_recent_posts_cache($post_id) {
    wp_cache_delete('wcr_recent_posts_', 'wcr_recent_posts');
}

function add_schema_markup($content, $post) {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title($post),
        'datePublished' => get_the_date('c', $post),
        'dateModified' => get_the_modified_date('c', $post),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author)
        )
    );

    $content .= '<script type="application/ld+json">' . 
        wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . 
        '</script>';

    return $content;
}

function enqueue_wcr_assets() {
    wp_enqueue_style(
        'wcr-styles',
        plugins_url('assets/css/wcr-styles.css', __FILE__),
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'wcr-script',
        plugins_url('assets/js/wcr-script.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script('wcr-script', 'wcrData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wcr_ajax_nonce')
    ));
}

function display_recent_updated_posts($atts) {
    // Parse attributes
    $attributes = shortcode_atts(
        array(
            'start' => 0, // Default mulai dari 0 (post terbaru)
        ),
        $atts
    );
    
    // Convert start parameter ke integer
    $offset = intval($attributes['start']);
    
    // Query untuk mendapatkan posts
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'offset' => $offset,
        'orderby' => 'modified',
        'order' => 'DESC',
        'ignore_sticky_posts' => 1
    );
    
    $recent_posts = new WP_Query($args);
    
    // Mulai output buffering
    ob_start();
    
    if ($recent_posts->have_posts()) {
        // Add CSS styles
        echo '<style>
            .recent-updated-posts ul {
                list-style-type: none;
                padding-left: 0;
                margin: 0;
            }
            .updated-post-item {
                display: flex;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .post-thumbnail {
                flex: 0 0 100px;
                margin-right: 15px;
            }
            .post-thumbnail img {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 4px;
            }
            .post-content {
                flex: 1;
            }
            .post-title {
                margin: 0 0 8px 0;
                font-size: 16px;
                line-height: 1.3;
            }
            .post-meta {
                font-size: 12px;
                color: #666;
                margin-bottom: 8px;
            }
            .post-excerpt {
                font-size: 14px;
                color: #444;
                line-height: 1.5;
                margin: 0;
            }
        </style>';

        echo '<div class="recent-updated-posts">';
        echo '<h4>Artikel Yang Diperbarui:</h4>';
        echo '<ul>';
        
        while ($recent_posts->have_posts()) {
            $recent_posts->the_post();
            
            // Dapatkan tanggal update
            $modified_date = get_the_modified_date('j F Y');
            
            // Dapatkan excerpt atau potong content jika excerpt kosong
            $excerpt = get_the_excerpt();
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(get_the_content(), 20, '...');
            }
            
            echo '<li>';
            echo '<div class="updated-post-item">';
            
            // Featured Image
            echo '<div class="post-thumbnail">';
            if (has_post_thumbnail()) {
                echo get_the_post_thumbnail(null, array(100, 100));
            } else {
                // Default image jika tidak ada featured image
                echo '<img src="' . plugins_url('assets/default-thumbnail.jpg', __FILE__) . '" 
                    alt="Default thumbnail" width="100" height="100">';
            }
            echo '</div>';
            
            // Post Content
            echo '<div class="post-content">';
            
            // Judul dengan link
            echo '<a href="' . get_permalink() . '" style="text-decoration: none; color: #333;">';
            echo '<h5 class="post-title">' . get_the_title() . '</h5>';
            echo '</a>';
            
            // Tanggal update
            echo '<div class="post-meta">';
            echo 'Diperbarui: ' . $modified_date;
            echo '</div>';
            
            // Excerpt
            echo '<p class="post-excerpt">' . $excerpt . '</p>';
            
            echo '</div>'; // End post-content
            echo '</div>'; // End updated-post-item
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>Tidak ada artikel yang ditemukan.</p>';
    }
    
    // Reset post data
    wp_reset_postdata();
    
    // Kembalikan output buffer
    return ob_get_clean();
}

// Tambahkan file ini ke plugin utama
function include_recent_updated_posts_shortcode() {
    include_once plugin_dir_path(__FILE__) . 'recent-updated-posts-shortcode.php';
}
add_action('init', 'include_recent_updated_posts_shortcode');
