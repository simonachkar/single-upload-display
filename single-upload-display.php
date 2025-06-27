 <?php
/**
 * Plugin Name: Single Upload Display
 * Description: Upload a single image from the admin panel and display it on the front-end. Replaces the old one automatically.
 * Version: 1.2
 * Author: Simon Achkar
 *
 * Prefix Reference:
 * - `sud` stands for "Single Upload Display"
 * - Used to namespace functions, constants, and identifiers to avoid conflicts
 */


// Prevent direct file access
if (!defined('ABSPATH')) exit; 

// Constant to store/retrieve the image ID
define('SUD_OPTION_NAME', 'sud_uploaded_image_id');

// Load CSS/JS in Admin area
add_action('admin_enqueue_scripts', function($hook) {
    // Only load assets on our custom admin page
    if ($hook === 'toplevel_page_sud-upload') {
        wp_enqueue_style('sud-admin-style', plugin_dir_url(__FILE__) . 'css/admin.css');
        wp_enqueue_script('sud-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], false, true);
    }
});

// Load front-end styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('sud-style', plugin_dir_url(__FILE__) . 'css/style.css');
});

// Add shortcode to display the uploaded image on the site
require_once plugin_dir_path(__FILE__) . 'includes/sud_add_shortcode.php';  

// Add a custom menu item in the admin sidebar
add_action('admin_menu', function() {
    add_menu_page(
        'Single Upload Display',        // Page title
        'Single Upload',                // Menu title
        'manage_options',               // Capability
        'single-upload-display',        // Slug
        'sud_render_admin_page',        // Callback function to render page
        'dashicons-upload',             // Icon
        100                             // Position
    );
});

// Render the upload form and preview image in admin
require_once plugin_dir_path(__FILE__) . 'includes/sud_render_admin_page.php';  