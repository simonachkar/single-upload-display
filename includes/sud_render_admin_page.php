<?php

function sud_render_admin_page() {
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 0;">Single Upload Display</h1>
        <p style="font-style: italic; color: #555; margin-top: 4px">
            Upload one image at a time from the admin panel. Each new upload automatically deletes the previous image from the Media Library.
        </p>

        <hr style="margin-top: 20px; margin-bottom: 30px;">

        <h2>Upload a New Image</h2>
        <form method="post" enctype="multipart/form-data" id="sud-upload-form" style="margin-bottom: 40px;">
            <?php wp_nonce_field('sud_upload_image', 'sud_nonce'); ?>
            <input type="file" name="sud_image" accept="image/*" required style="margin-bottom: 12px;" />
            <br>
            <input type="submit" class="button button-primary" value="Upload Image" />
            <div id="sud-loading" style="display:none; margin-top:10px;"><em>Uploading...</em></div>
        </form>

        <?php
        $image_id = get_option(SUD_OPTION_NAME);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($image_url) {
                echo '<h2>Currently Displayed Image</h2>';
                echo '<div class="sud-image-preview" style="margin-top: 10px;"><img src="' . esc_url($image_url) . '" style="max-width:100%; height:auto;" /></div>';
            }
        }
        ?>

        <hr style="margin-top: 50px; margin-bottom: 10px;">

        <p style="font-size: 12px; color: #777;">
            Made with ❤️ by <a href="https://github.com/simonachkar/single-upload-display" target="_blank">Simon Achkar</a>
        </p>
    </div>
    <?php

    // Handle image upload
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
    echo '<div class="notice notice-success is-dismissible"><p>Image uploaded successfully! <strong>Please refresh the page</strong> to see the updated preview.</p></div>';
}