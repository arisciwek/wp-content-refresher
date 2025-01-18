<?php
/**
 * WP Content Refresher Admin
 * 
 * Handles the admin functionality of the WP Content Refresher plugin
 * including settings page and options management.
 *
 * @package     WPContentRefresher
 * @subpackage  Admin
 * @version     1.0.0
 * @author      arisciwek
 * @copyright   Copyright (c) 2025, arisciwek
 * @license     GPL v2 or later
 * 
 * File: admin/class-wp-content-refresher-admin.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Content_Refresher_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

	public function add_admin_menu() {
	    if (WP_Content_Refresher_Security::can_access_settings()) {
	        add_options_page(
	            'WP Content Refresher Settings',
	            'Content Refresher',
	            'manage_options',
	            'wp-content-refresher',
	            array($this, 'render_settings_page')
	        );
	    }
	}

    public function register_settings() {
        register_setting('wp_content_refresher', 'wcr_settings');

        add_settings_section(
            'wcr_general_section',
            'General Settings',
            array($this, 'render_section_info'),
            'wp-content-refresher'
        );

        add_settings_field(
            'posts_per_shortcode',
            'Posts per Shortcode',
            array($this, 'render_number_field'),
            'wp-content-refresher',
            'wcr_general_section',
            array('field' => 'posts_per_shortcode', 'default' => 5)
        );

        add_settings_field(
            'cron_hour',
            'Update Hour',
            array($this, 'render_hour_field'),
            'wp-content-refresher',
            'wcr_general_section',
            array('field' => 'cron_hour', 'default' => 1)
        );

        add_settings_field(
            'post_types',
            'Post Types to Update',
            array($this, 'render_post_types_field'),
            'wp-content-refresher',
            'wcr_general_section'
        );
    }

    public function render_section_info() {
        echo '<p>Configure how the content refresher should work.</p>';
    }

    public function render_number_field($args) {
        $options = get_option('wcr_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        ?>
        <input type="number" 
               name="wcr_settings[<?php echo esc_attr($args['field']); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="20">
        <?php
    }

    public function render_hour_field($args) {
        $options = get_option('wcr_settings');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
        ?>
        <select name="wcr_settings[<?php echo esc_attr($args['field']); ?>]">
            <?php for ($i = 0; $i < 24; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($value, $i); ?>>
                    <?php echo sprintf('%02d:00', $i); ?>
                </option>
            <?php endfor; ?>
        </select>
        <?php
    }

    public function render_post_types_field() {
        $options = get_option('wcr_settings');
        $post_types = isset($options['post_types']) ? $options['post_types'] : array('post');
        $available_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($available_types as $type) {
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" 
                       name="wcr_settings[post_types][]" 
                       value="<?php echo esc_attr($type->name); ?>"
                       <?php checked(in_array($type->name, $post_types)); ?>>
                <?php echo esc_html($type->label); ?>
            </label>
            <?php
        }
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_content_refresher');
                do_settings_sections('wp-content-refresher');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }
}
