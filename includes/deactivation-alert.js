jQuery(document).ready(function ($) {
    $('#deactivate-email-marketing').on('click', function (event) {
        event.preventDefault();
        if (confirm('Are you sure you want to deactivate this plugin? This will delete all entries from the database.')) {
            $.ajax({
                type: 'POST',
                url: cf7_ajax_object.ajax_url,
                data: {
                    action: 'cf7_delete_email_marketing_table', 
                    security: cf7_ajax_object.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert('All entries have been deleted.');
                        window.location.href = $('#deactivate-email-marketing').attr('href');
                    }
                },
                error: function () {
                    alert('An error occurred while trying to delete the entries.');
                }
            });
        }
    });
});
 