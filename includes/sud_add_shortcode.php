<?php

/**
 * Registers the shortcode [single_upload_display tag="your-tag"]
 * Displays the image uploaded for the given tag/slot.
 *
 * Usage:
 *   [single_upload_display tag="home"]
 *   [single_upload_display tag="bulletin"]
 *
 * Backward compatibility:
 *   [single_upload_display] (no tag) falls back to the v1 legacy image option.
 */

add_shortcode('single_upload_display', function ($atts) {
    $atts = shortcode_atts(['tag' => ''], $atts, 'single_upload_display');
    $tag  = sanitize_key($atts['tag']);

    if ($tag !== '') {
        $image_id = get_option('sud_slot_' . $tag);
    } else {
        // Legacy fallback for v1 shortcode usage (no tag attribute)
        $image_id = get_option(SUD_OPTION_NAME);
    }

    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : null;

    if (!$image_url) {
        if (current_user_can('manage_options')) {
            $label = $tag !== '' ? esc_html($tag) : 'default';
            return '<div style="text-align:center;">
                        <small style="opacity:0.6; font-style:italic;">
                            No image uploaded yet for tag &ldquo;' . $label . '&rdquo; (visible to admins only).
                        </small>
                    </div>';
        }
        return ''; // Silent for non-admins
    }

    $alt = $tag !== '' ? $tag : 'Uploaded Image';

    return '<div class="sud-image-wrapper">
                <img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt) . '" />
            </div>';
});
