/**
 * WP Content Refresher Frontend Scripts
 * 
 * Handles all frontend functionalities including lazy loading images
 * and AJAX loading for recent posts.
 *
 * @package     WPContentRefresher
 * @subpackage  Frontend
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: assets/js/wcr-script.js
 */

document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('.wcr-lazy-image');
    
    const lazyLoadImage = (target) => {
        const io = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.disconnect();
                }
            });
        });

        io.observe(target);
    };

    lazyImages.forEach(lazyLoadImage);
});

// AJAX loading untuk recent posts
jQuery(document).ready(function($) {
    $('.wcr-load-more').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const container = button.closest('.recent-updated-posts');
        const offset = container.data('offset');
        
        button.addClass('wcr-loading');
        
        $.ajax({
            url: wcrData.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_more_posts',
                nonce: wcrData.nonce,
                offset: offset
            },
            success: function(response) {
                if (response.success) {
                    container.find('ul').append(response.data.html);
                    container.data('offset', offset + 5);
                    
                    if (!response.data.has_more) {
                        button.remove();
                    }
                    
                    // Initialize lazy loading for new images
                    const newImages = container.find('.wcr-lazy-image:not(.loaded)');
                    newImages.each(function() {
                        lazyLoadImage(this);
                    });
                }
            },
            complete: function() {
                button.removeClass('wcr-loading');
            }
        });
    });
});