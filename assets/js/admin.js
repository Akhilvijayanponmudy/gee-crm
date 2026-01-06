jQuery(document).ready(function ($) {

    // Sync Contacts
    $('#gee-crm-sync-btn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#gee-crm-sync-status');

        $btn.prop('disabled', true).text('Syncing...');
        $status.text('Starting sync process...');

        $.ajax({
            url: geeWooCRM.ajaxurl,
            type: 'POST',
            data: {
                action: 'gee_crm_sync_contacts',
                nonce: geeWooCRM.nonce
            },
            success: function (response) {
                if (response.success) {
                    $status.text(response.data.message).css('color', 'green');
                } else {
                    $status.text('Error: ' + response.data).css('color', 'red');
                }
            },
            error: function () {
                $status.text('AJAX Error').css('color', 'red');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Sync WooCommerce Customers');
            }
        });
    });

    // Dropdown Menu Toggle
    $('.gee-woo-crm-nav-dropdown > .gee-woo-crm-nav-item').on('click', function(e) {
        e.preventDefault();
        var $dropdown = $(this).parent();
        var $menu = $dropdown.find('.gee-woo-crm-dropdown-menu');
        
        // Close other dropdowns
        $('.gee-woo-crm-dropdown-menu').not($menu).hide();
        
        // Toggle current dropdown
        $menu.toggle();
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.gee-woo-crm-nav-dropdown').length) {
            $('.gee-woo-crm-dropdown-menu').hide();
        }
    });

});
