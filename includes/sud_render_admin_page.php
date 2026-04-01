<?php

/**
 * Renders the Single Upload Display admin page and handles all form actions:
 *   - sud_create_slot  : create a new named slot (optionally upload an image)
 *   - sud_upload_image : replace the image for an existing slot
 *   - sud_delete_slot  : delete a slot and its associated media attachment
 *
 * Data model:
 *   sud_slots_list       – serialized array of all slot tag strings
 *   sud_slot_{tag}       – attachment ID for each individual slot
 */

// ---------------------------------------------------------------------------
// Page renderer
// ---------------------------------------------------------------------------

function sud_render_admin_page() {
    $notices = array_merge(
        sud_handle_create_slot(),
        sud_handle_upload_image(),
        sud_handle_delete_slot()
    );

    $slots = get_option('sud_slots_list', []);
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 0;">Single Upload Display</h1>
        <p style="font-style: italic; color: #555; margin-top: 4px;">
            Manage named upload slots. Use <code>[single_upload_display tag="your-tag"]</code> to display each image on the front-end.
        </p>

        <?php foreach ($notices as $notice) : ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                <p><?php echo wp_kses_post($notice['message']); ?></p>
            </div>
        <?php endforeach; ?>

        <hr style="margin-top: 20px; margin-bottom: 30px;">

        <h2>Create New Slot</h2>
        <form method="post" enctype="multipart/form-data" id="sud-create-form" style="margin-bottom: 40px;">
            <?php wp_nonce_field('sud_create_slot', 'sud_nonce'); ?>
            <input type="hidden" name="sud_action" value="sud_create_slot" />
            <table class="form-table" style="max-width: 600px;">
                <tr>
                    <th scope="row"><label for="sud_new_tag">Tag / Key <span style="color:red;">*</span></label></th>
                    <td>
                        <input type="text" id="sud_new_tag" name="sud_new_tag" class="regular-text"
                               placeholder="e.g. home, bulletin" required />
                        <p class="description">Lowercase letters, numbers, and hyphens only. Must be unique.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sud_new_image">Image (optional)</label></th>
                    <td>
                        <input type="file" id="sud_new_image" name="sud_image" accept="image/*" />
                        <p class="description">You can upload an image now or add one later.</p>
                    </td>
                </tr>
            </table>
            <input type="submit" class="button button-primary" value="Create Slot" />
            <span class="sud-loading" style="display:none; margin-left:10px;"><em>Working&hellip;</em></span>
        </form>

        <hr style="margin-bottom: 30px;">

        <h2>Existing Slots</h2>

        <?php if (empty($slots)) : ?>
            <p style="color: #777;">No slots created yet. Use the form above to add your first slot.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped sud-slots-table">
                <thead>
                    <tr>
                        <th class="sud-col-tag">Tag</th>
                        <th class="sud-col-shortcode">Shortcode</th>
                        <th class="sud-col-preview">Current Image</th>
                        <th class="sud-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($slots as $tag) :
                    $image_id  = get_option('sud_slot_' . $tag);
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : null;
                ?>
                    <tr>
                        <td class="sud-col-tag">
                            <strong><?php echo esc_html($tag); ?></strong>
                        </td>
                        <td class="sud-col-shortcode">
                            <code>[single_upload_display tag="<?php echo esc_attr($tag); ?>"]</code>
                        </td>
                        <td class="sud-col-preview">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" class="sud-thumb" alt="<?php echo esc_attr($tag); ?>" />
                            <?php else : ?>
                                <em class="sud-no-image">No image yet</em>
                            <?php endif; ?>
                        </td>
                        <td class="sud-col-actions">
                            <form method="post" enctype="multipart/form-data" class="sud-upload-form">
                                <?php wp_nonce_field('sud_upload_' . $tag, 'sud_nonce'); ?>
                                <input type="hidden" name="sud_action" value="sud_upload_image" />
                                <input type="hidden" name="sud_tag" value="<?php echo esc_attr($tag); ?>" />
                                <input type="file" name="sud_image" accept="image/*" required class="sud-file-input" />
                                <input type="submit" class="button button-secondary"
                                       value="<?php echo $image_id ? 'Replace Image' : 'Upload Image'; ?>" />
                                <span class="sud-loading" style="display:none;"><em>Uploading&hellip;</em></span>
                            </form>

                            <form method="post" class="sud-delete-form"
                                  data-confirm="Delete slot &quot;<?php echo esc_attr($tag); ?>&quot; and its image?">
                                <?php wp_nonce_field('sud_delete_' . $tag, 'sud_nonce'); ?>
                                <input type="hidden" name="sud_action" value="sud_delete_slot" />
                                <input type="hidden" name="sud_tag" value="<?php echo esc_attr($tag); ?>" />
                                <input type="submit" class="button button-link-delete" value="Delete Slot" />
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr style="margin-top: 50px; margin-bottom: 10px;">
        <p style="font-size: 12px; color: #777;">
            Made with ❤️ by <a href="https://github.com/simonachkar/single-upload-display" target="_blank">Simon Achkar</a>
        </p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Form handlers  (each returns an array of notice arrays)
// ---------------------------------------------------------------------------

/**
 * Handle "Create New Slot" form submission.
 *
 * @return array Notice arrays with 'type' and 'message' keys.
 */
function sud_handle_create_slot() {
    if (empty($_POST['sud_action']) || $_POST['sud_action'] !== 'sud_create_slot') {
        return [];
    }
    if (!isset($_POST['sud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sud_nonce'])), 'sud_create_slot')) {
        return [];
    }
    if (!current_user_can('manage_options')) {
        return [];
    }

    $raw_tag = isset($_POST['sud_new_tag']) ? wp_unslash($_POST['sud_new_tag']) : '';
    $tag     = sanitize_key($raw_tag);

    if ($tag === '') {
        return [['type' => 'error', 'message' => 'Tag is required and may only contain lowercase letters, numbers, and hyphens.']];
    }

    $slots = get_option('sud_slots_list', []);
    if (in_array($tag, $slots, true)) {
        return [['type' => 'error', 'message' => 'A slot with tag <strong>' . esc_html($tag) . '</strong> already exists. Please choose a different tag.']];
    }

    // Register the new slot
    $slots[] = $tag;
    update_option('sud_slots_list', $slots);

    // Optionally upload an image right away
    if (!empty($_FILES['sud_image']['tmp_name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploaded = media_handle_upload('sud_image', 0);
        if (is_wp_error($uploaded)) {
            return [['type' => 'error', 'message' => 'Slot created but image upload failed: ' . esc_html($uploaded->get_error_message())]];
        }
        update_option('sud_slot_' . $tag, $uploaded);
    }

    return [['type' => 'success', 'message' => 'Slot <strong>' . esc_html($tag) . '</strong> created successfully.']];
}

/**
 * Handle "Upload / Replace Image" form submission for an existing slot.
 *
 * @return array Notice arrays with 'type' and 'message' keys.
 */
function sud_handle_upload_image() {
    if (empty($_POST['sud_action']) || $_POST['sud_action'] !== 'sud_upload_image') {
        return [];
    }

    $tag = isset($_POST['sud_tag']) ? sanitize_key(wp_unslash($_POST['sud_tag'])) : '';
    if ($tag === '') {
        return [];
    }

    if (!isset($_POST['sud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sud_nonce'])), 'sud_upload_' . $tag)) {
        return [];
    }
    if (!current_user_can('manage_options')) {
        return [];
    }

    // Ensure the slot exists
    $slots = get_option('sud_slots_list', []);
    if (!in_array($tag, $slots, true)) {
        return [['type' => 'error', 'message' => 'Unknown slot: ' . esc_html($tag)]];
    }

    if (empty($_FILES['sud_image']['tmp_name'])) {
        return [];
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $old_id   = get_option('sud_slot_' . $tag);
    $uploaded = media_handle_upload('sud_image', 0);

    if (is_wp_error($uploaded)) {
        return [['type' => 'error', 'message' => 'Error uploading image: ' . esc_html($uploaded->get_error_message())]];
    }

    update_option('sud_slot_' . $tag, $uploaded);

    // Remove the old attachment from the Media Library
    if ($old_id && $old_id !== $uploaded) {
        wp_delete_attachment($old_id, true);
    }

    return [['type' => 'success', 'message' => 'Image for slot <strong>' . esc_html($tag) . '</strong> updated successfully.']];
}

/**
 * Handle "Delete Slot" form submission.
 *
 * @return array Notice arrays with 'type' and 'message' keys.
 */
function sud_handle_delete_slot() {
    if (empty($_POST['sud_action']) || $_POST['sud_action'] !== 'sud_delete_slot') {
        return [];
    }

    $tag = isset($_POST['sud_tag']) ? sanitize_key(wp_unslash($_POST['sud_tag'])) : '';
    if ($tag === '') {
        return [];
    }

    if (!isset($_POST['sud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sud_nonce'])), 'sud_delete_' . $tag)) {
        return [];
    }
    if (!current_user_can('manage_options')) {
        return [];
    }

    // Delete the media attachment
    $image_id = get_option('sud_slot_' . $tag);
    if ($image_id) {
        wp_delete_attachment($image_id, true);
    }

    // Remove the slot option and the slot from the list
    delete_option('sud_slot_' . $tag);

    $slots = get_option('sud_slots_list', []);
    $slots = array_values(array_filter($slots, function ($s) use ($tag) {
        return $s !== $tag;
    }));
    update_option('sud_slots_list', $slots);

    return [['type' => 'success', 'message' => 'Slot <strong>' . esc_html($tag) . '</strong> and its image have been deleted.']];
}
