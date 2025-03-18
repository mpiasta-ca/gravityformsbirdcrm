window.GFBirdCRMSettings = null;

(function ($) {
    GFBirdCRMSettings = function () {
        var self = this;

        this.init = function () {
            this.pageURL = gform_birdcrm_pluginsettings_strings.settings_url;

            this.bindDeauthorize();
            this.bindEmailTemplateSync();
        }

        this.bindDeauthorize = function () {
            // De-Authorize Bird CRM.
            $('#gform_birdcrm_deauth_button').on('click', function (e) {
                e.preventDefault();

                // Get button.
                var $button = $('#gform_birdcrm_deauth_button');

                // Confirm deletion.
                if (!confirm(gform_birdcrm_pluginsettings_strings.disconnect)) {
                    return false;
                }

                // Set disabled state.
                $button.attr('disabled', 'disabled');

                // De-Authorize.
                $.ajax( {
                    async:    false,
                    url:      ajaxurl,
                    dataType: 'json',
                    data:     {
                        action: 'gfbirdcrm_deauthorize',
                        nonce:  gform_birdcrm_pluginsettings_strings.nonce_deauthorize
                    },
                    success:  function ( response ) {
                        if ( response.success ) {
                            window.location.href = self.pageURL;
                        } else {
                            alert( response.data.message );
                        }

                        $button.removeAttr( 'disabled' );
                    }
                } );

            });
        }

        this.bindEmailTemplateSync = function() {
            $( '#gf_birdcrm_sync' ).on( 'click', function ( e ) {

                e.preventDefault();
                e.stopImmediatePropagation();

                var $button = $( this );
                $button.attr( 'disabled', true );

                $.ajax({
                    method: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'gfbirdcrm_email_sync',
                        nonce: gform_birdcrm_pluginsettings_strings.nonce_email_sync,
                    },
                    success: function( response ) {
                        if ( 'last_clearance' in response.data ) {
                            jQuery('.success-alert-container').fadeIn();
                            $( '#last_email_template_sync .time' ).text( response.data.last_clearance );
                        }
                    },
                    error: function () {
                        jQuery('.error-alert-container').fadeIn();
                    },
                    complete: function () {
                        $button.attr( 'disabled', false );
                        setTimeout( function () { jQuery('.alert-container').fadeOut(); }, 10000 );
                    }
                });
            });
        };

        this.init();
    }

    $(document).ready(GFBirdCRMSettings);
})(jQuery);
