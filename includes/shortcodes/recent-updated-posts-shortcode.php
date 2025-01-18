<?php
/**
 * Recent Updated Posts Shortcode
 * 
 * Menampilkan 5 post berdasarkan tanggal update dengan fitur offset dan featured image
 *
 * @package     WPContentRefresher
 * @subpackage  Shortcodes
 * @version     1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function untuk mendapatkan excerpt yang konsisten
function wcr_get_post_excerpt($post_id) {
    // Cek apakah ada custom excerpt
    $excerpt = get_post_field('post_excerpt', $post_id);
    
    // Jika tidak ada excerpt, gunakan content dengan batasan kata
    if (empty($excerpt)) {
        $content = get_post_field('post_content', $post_id);
        $excerpt = wp_trim_words($content, 20, '...');
    }
    
    return $excerpt;
}

// Fungsi untuk mendapatkan cached posts dengan excerpt yang lebih baik
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
                'excerpt' => wcr_get_post_excerpt(get_the_ID()), // Menggunakan helper function
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

function display_recent_updated_posts($atts) {
    // Parse attributes
    $attributes = shortcode_atts(
        array(
            'start' => 0,
        ),
        $atts
    );
    
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
    
    $recent_posts = get_cached_recent_posts($args);
    
    ob_start();
    
    if (!empty($recent_posts)) {
        // Add CSS styles - mengikuti pola previous-posts-shortcode
        echo '<style>
        .recent-updated-posts {
            /* Container style */
            margin: 1em 0;
            background: transparent;
            padding: 0;
        }

        .recent-updated-posts h4 {
            /* Judul "Artikel Yang Diperbarui" */
            color: #c00;  /* Warna merah seperti di gambar */
            font-size: 1em;
            margin: 0 0 1em 0;
            font-weight: normal;
        }

        .recent-updated-posts ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .updated-post-item {
            /* Container untuk setiap item */
            display: flex;
            margin-bottom: 1em;
            padding-bottom: 0;
            border: none;
        }

        .post-thumbnail {
            /* Container thumbnail */
            flex: 0 0 90px;
            margin-right: 1em;
        }

        .post-thumbnail img {
            /* Gambar thumbnail */
            width: 90px;
            height: 90px;
            object-fit: cover;
        }

        .post-content {
            /* Container konten */
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .post-link {
            /* Link judul */
            text-decoration: none;
            color: inherit;
            font-size:14pt;
        }

        .post-title {
            /* Judul post */
            font-size: 1em;
            margin: 0 0 0.3em 0;
            font-weight: normal;
            color: #0073aa;
            line-height: 1.4;
        }

        .post-link:hover .post-title {
            text-decoration: underline;
        }

        .post-meta {
            /* Tanggal update */
            font-size: 0.85em;
            color: #666;
            margin: 0.3em 0;
            font-style: italic;
        }

        .post-excerpt {
            /* Excerpt text */
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
            margin: 0;
        }

        /* Load More button style */
        .wcr-load-more {
            display: inline-block;
            padding: 0.5em 1em;
            margin-top: 1em;
            background: #f0f0f0;
            border: 1px solid #ddd;
            color: #666;
            text-align: center;
            cursor: pointer;
            font-size: 0.9em;
        }

        .wcr-load-more:hover {
            background: #e5e5e5;
        }

        .wcr-loading {
            opacity: 0.7;
            cursor: wait;
        }



        </style>';

        echo '<div class="recent-updated-posts" data-offset="' . esc_attr($offset) . '">';
        echo '<h4>Artikel Yang Diperbarui:</h4>';
        echo '<ul>';
        
        foreach ($recent_posts as $post) {
            echo '<li class="rescent-post-list">';
            echo '<div class="updated-post-item">';
            
            // Featured Image
            echo '<div class="post-thumbnail">';
            if ($post['thumbnail']) {
                echo '<img src="' . esc_url($post['thumbnail']) . '" 
                    alt="' . esc_attr($post['title']) . '" width="90" height="90" 
                    class="wcr-lazy-image" data-src="' . esc_url($post['thumbnail']) . '">';
            } else {
                echo '<img src="' . esc_url(plugins_url('assets/default-thumbnail.jpg', __FILE__)) . '" 
                    alt="' . esc_attr($post['title']) . '" width="150" height="150">';
            }
            echo '</div>';
            
            // Post Content
            echo '<div class="post-content">';
            echo '<a href="' . esc_url($post['permalink']) . '" class="post-link">';
            echo '<h5 class="post-title">' . esc_html($post['title']) . '</h5>';
            echo '</a>';
            
            echo '<p class="post-excerpt">' . esc_html($post['excerpt']) . '</p>';
            
            echo '</div>'; // End post-content
            echo '</div>'; // End updated-post-item
            echo '</li>';
        }
        
        echo '</ul>';
        
        // Load More button
        if (count($recent_posts) >= 5) {
            echo '<button class="wcr-load-more">Muat Artikel Lainnya</button>';
        }
        
        echo '</div>';
    } else {
        echo '<p>Tidak ada artikel yang ditemukan.</p>';
    }
    
    return ob_get_clean();
}

// Register shortcode
add_shortcode('recent_updated_posts', 'display_recent_updated_posts');
