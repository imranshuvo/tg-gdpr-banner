/**
 * TG GDPR Cookie Consent - Admin JavaScript
 *
 * @package TG_GDPR_Cookie_Consent
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize color pickers
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.tg-gdpr-color-picker').wpColorPicker();
        }
        
        // Form validation
        $('.tg-gdpr-settings-form').on('submit', function(e) {
            // Add any validation here
        });
        
    });

})(jQuery);
