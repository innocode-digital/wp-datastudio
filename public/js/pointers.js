(function ($, settings) {

    'use strict';

    $(function ($) {
        $(settings.pointerSelector)
            .pointer(
                $.extend(
                    {},
                    settings.pointerOptions,
                    {
                        position: {
                            edge: 'left',
                            align: 'middle'
                        },
                        close: function() {
                            $.post(settings.ajaxURL, {
                                action: settings.dismissAction,
                                _wpnonce: settings.dismissNonce
                            });
                        }
                    }
                )
            ).pointer('open');
    });
})(jQuery, window.innocodeGoogleDataStudioPointers);