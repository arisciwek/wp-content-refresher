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
 * File: wp-content/plugins/wp-content-refresher/includes/shortcodes/recent-updated-posts-shortcode.php
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

function get_cached_recent_posts($args) {
    // Include post type dan timestamp dalam cache key
    $cache_key = 'wcr_recent_posts_' . get_post_type() . '_' . md5(serialize($args));
    
    $cached_results = wp_cache_get($cache_key, 'wcr_recent_posts');
    
    if (false !== $cached_results) {
        return $cached_results;
    }
    
    // Tambah timestamp terakhir update
    $results['last_update'] = current_time('timestamp');
    
    wp_cache_set($cache_key, $results, 'wcr_recent_posts', HOUR_IN_SECONDS);
    
    return $results;
}

// Invalidate cache saat post diupdate
function invalidate_recent_posts_cache($post_id) {
    $post_type = get_post_type($post_id);
    $cache_key = 'wcr_recent_posts_' . $post_type . '_*';
    wp_cache_delete($cache_key, 'wcr_recent_posts');
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
    // Get file modification time sebagai version
    $css_ver = filemtime(plugin_dir_path(__FILE__) . 'assets/css/wcr-styles.css');
    $js_ver = filemtime(plugin_dir_path(__FILE__) . 'assets/js/wcr-script.js');
    
    wp_enqueue_style(
        'wcr-styles',
        plugins_url('assets/css/wcr-styles.css', __FILE__),
        array(),
        $css_ver
    );

    wp_enqueue_script(
        'wcr-script',
        plugins_url('assets/js/wcr-script.js', __FILE__),
        array('jquery'),
        $js_ver,
        true
    );
}

function display_recent_updated_posts($atts) {


    // Get settings
    $options = get_option('wcr_settings', array());
    $posts_per_page = isset($options['posts_per_shortcode']) ? 
                      absint($options['posts_per_shortcode']) : 5;
    
    $attributes = shortcode_atts(
        array(
            'start' => 0,
            'post_type' => 'post',
            'count' => $posts_per_page
        ),
        $atts
    );
    
    $args = array(
        'post_type' => sanitize_text_field($attributes['post_type']),
        'post_status' => 'publish',
        'posts_per_page' => absint($attributes['count']),
        'offset' => absint($attributes['start']),
        'orderby' => 'modified',
        'order' => 'DESC',
        'ignore_sticky_posts' => 1,
        'no_found_rows' => true, // Performance improvement
        'update_post_term_cache' => false, // Performance improvement
        'update_post_meta_cache' => false // Performance improvement for queries not using meta
    );
    
    // Use caching
    $posts = get_cached_recent_posts($args);
    
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
            $excerpt = get_post_excerpt(get_the_ID()); // Gunakan fungsi helper yang sama
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

// Add error handling
function wcr_log_error($message, $data = array()) {
    if (WP_DEBUG === true) {
        error_log('WCR Error: ' . $message . ' Data: ' . print_r($data, true));
    }
}

// Gunakan dalam fungsi display
try {
    $recent_posts = new WP_Query($args);
} catch (Exception $e) {
    wcr_log_error('Query failed', array(
        'error' => $e->getMessage(),
        'args' => $args
    ));
    return '<!-- WCR Error: Failed to load recent posts -->';
}