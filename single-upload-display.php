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


// Shortcode to display the uploaded image on the site
add_shortcode('single_upload_display', function () {
    // Get stored image ID
    $image_id = get_option('sud_uploaded_image_id');
    if (!$image_id) return "<p>No image has been uploaded yet.</p>";

    // Get image URL
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    if (!$image_url) return "<p>Image not found.</p>";

    // Output image HTML
    return "<div class='sud-image-wrapper'><img src='" . esc_url($image_url) . "' alt='Uploaded Image' /></div>";
});


// Add a custom menu item in the admin sidebar
add_action('admin_menu', function() {
    add_menu_page(
        'Single Upload',        // Page title
        'Single Upload',        // Menu title
        'manage_options',       // Capability
        'sud-upload',           // Slug
        'sud_render_admin_page',// Callback function to render page
        'dashicons-upload',     // Icon
        100                     // Position
    );
});


// Render the upload form and preview image in admin
function sud_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Upload Image</h1>

        <!-- Upload form -->
        <form method="post" enctype="multipart/form-data" id="sud-upload-form">
            <?php wp_nonce_field('sud_upload_image', 'sud_nonce'); ?>
            <input type="file" name="sud_image" accept="image/*" required />
            <br><br>
            <input type="submit" class="button button-primary" value="Upload Image" />
            <div id="sud-loading" style="display:none;margin-top:10px;"><em>Uploading...</em></div>
        </form>

        <!-- Preview of currently uploaded image -->
        <?php
        $image_id = get_option(SUD_OPTION_NAME);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($image_url) {
                echo '<h2>Currently Displayed Image</h2>';
                echo '<div class="sud-image-preview"><img src="' . esc_url($image_url) . '" /></div>';
            }
        }
        ?>
    </div>
    <?php

    // Handle the image upload logic
    sud_handle_image_upload();
}


// Process the uploaded image
function sud_handle_image_upload() {
    // Verify form submission
    if (!isset($_POST['sud_nonce']) || !wp_verify_nonce($_POST['sud_nonce'], 'sud_upload_image')) return;
    if (!current_user_can('upload_files') || empty($_FILES['sud_image']['tmp_name'])) return;

    // Load WordPress media handling libraries
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Delete old image if exists
    $old_id = get_option(SUD_OPTION_NAME);

    // Upload the new image
    $uploaded = media_handle_upload('sud_image', 0);

    // Handle errors
    if (is_wp_error($uploaded)) {
        echo '<div class="notice notice-error"><p>Error uploading image: ' . esc_html($uploaded->get_error_message()) . '</p></div>';
        return;
    }

    // Save new image ID
    update_option(SUD_OPTION_NAME, $uploaded);

    // Remove old image from Media Library
    if ($old_id && $old_id !== $uploaded) {
        wp_delete_attachment($old_id, true);
    }

    // Show success message
    echo '<div class="notice notice-success"><p>Image uploaded successfully!</p></div>';
}
