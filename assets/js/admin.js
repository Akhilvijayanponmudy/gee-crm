jQuery(document).ready(function ($) {
    // === Editor Toggle Logic (for templates) ===
    var $visualSection = $('.gee-crm-editor-section.visual');
    var $htmlSection = $('.gee-crm-editor-section.html');
    var $visualTab = $('#gee-crm-visual-tab');
    var $htmlTab = $('#gee-crm-html-tab');
    var $hiddenField = $('#template-content-hidden'); // For form submission

    // Show the visual editor by default
    function setEditorMode(mode) {
        if (mode === 'visual') {
            $visualSection.show();
            $htmlSection.hide();
            $visualTab.addClass('active');
            $htmlTab.removeClass('active');
        } else {
            $visualSection.hide();
            $htmlSection.show();
            $visualTab.removeClass('active');
            $htmlTab.addClass('active');
        }
    }
    setEditorMode('visual');

    $visualTab.on('click', function () {
        setEditorMode('visual');
    });
    $htmlTab.on('click', function () {
        setEditorMode('html');
    });

    // On form submit, copy the content from the active editor to the hidden field
    $('#gee-template-form').on('submit', function () {
        if ($visualSection.is(':visible')) {
            // Visual (TinyMCE) mode
            if (typeof tinymce !== 'undefined' && tinymce.get('template-content-visual')) {
                $hiddenField.val(tinymce.get('template-content-visual').getContent());
            }
        } else {
            // HTML textarea mode
            $hiddenField.val($('#template-content').val());
        }
    });

    // ---- Existing code below -----

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

    // Dropdown Menu - Enhanced hover handling to prevent closing when moving to menu
    var dropdownTimeout;
    $('.gee-woo-crm-nav-dropdown').on('mouseenter', function() {
        clearTimeout(dropdownTimeout);
        var $menu = $(this).find('.gee-woo-crm-dropdown-menu');
        $menu.show();
    }).on('mouseleave', function() {
        var $menu = $(this).find('.gee-woo-crm-dropdown-menu');
        var $self = $(this);
        // Small delay to allow mouse to move to dropdown menu
        dropdownTimeout = setTimeout(function() {
            // Check if mouse is still over dropdown or its parent
            if (!$self.is(':hover') && !$menu.is(':hover')) {
                $menu.hide();
            }
        }, 200);
    });

    // Keep dropdown open when hovering over menu itself
    $('.gee-woo-crm-dropdown-menu').on('mouseenter', function() {
        clearTimeout(dropdownTimeout);
        $(this).show();
    }).on('mouseleave', function() {
        var $self = $(this);
        dropdownTimeout = setTimeout(function() {
            $self.hide();
        }, 200);
    });

    // Click toggle for accessibility
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

    // Development Banner Dismiss
    $('.gee-crm-dev-banner-dismiss').on('click', function() {
        var $banner = $(this).closest('.gee-crm-dev-banner');
        $banner.fadeOut(300, function() {
            $banner.addClass('hidden');
            // Save dismissal state in localStorage
            localStorage.setItem('gee_crm_dev_banner_dismissed', 'true');
        });
    });

    // Check if banner was previously dismissed
    if (localStorage.getItem('gee_crm_dev_banner_dismissed') === 'true') {
        $('.gee-crm-dev-banner').addClass('hidden');
    }

});
