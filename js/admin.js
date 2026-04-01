jQuery(document).ready(function ($) {

    // Show a loading indicator on any upload or create form submission
    $('#sud-create-form, .sud-upload-form').on('submit', function () {
        $(this).find('.sud-loading').show();
        $(this).find(':submit').prop('disabled', true);
    });

    // Confirm before deleting a slot
    $('.sud-delete-form').on('submit', function (e) {
        var msg = $(this).data('confirm') || 'Delete this slot and its image?';
        if (!window.confirm(msg)) {
            e.preventDefault();
        }
    });

});

  