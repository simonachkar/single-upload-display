<?php

/**
 * Registers the shortcode [single_upload_display]
 * & Displays the latest uploaded image stored by the plugin.
 * 
 * - If no image is found:
 *     - Display nothing to regular users
 *     - Display a muted admin-only notice to logged-in users with permission
 */

add_shortcode('single_upload_display', function () {
    $image_id = get_option(SUD_OPTION_NAME);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : null;

    if (!$image_url) {
        if (current_user_can('manage_options')) {
            return "<div style='text-align: center;'>
                        <small style='opacity: 0.6; font-style: italic;'>
                            No image uploaded yet (visible to admins only).
                        </small>
                    </div>";
        }
        return ""; // Silent fail for non-admins
    }

    // Output the image HTML
    return "<div class='sud-image-wrapper'>
                <img src='" . esc_url($image_url) . "' alt='Uploaded Image' />
            </div>";
});
