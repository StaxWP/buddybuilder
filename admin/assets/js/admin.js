var BPBAdmin = BPBAdmin || {};

(function ($) {

    'use strict';

    BPBAdmin.main = {
        init: function() {
            BPBAdmin.main.initNotices();
        },

        initNotices: function() {
            $('div.notice.bpb-dismissable').on('click', '.bpb-dismiss-button', function (event) {
                event.preventDefault();
                var $notice = $(this).closest('.bpb-dismissable');
                $.post(ajaxurl, {
                    action: 'bpb_admin_notice_viewed',
                    notice_id: $notice.data('notice_id'),
                    _wpnonce: $notice.data('nonce')
                });
                var $wrapperElm = $(this).closest('.bpb-dismissable');
                $wrapperElm.fadeTo(100, 0, function () {
                    $wrapperElm.slideUp(100, function () {
                        $wrapperElm.remove();
                    });
                });
            });
        }
    }

    $(document).ready(function () {
        BPBAdmin.main.init();
    });

})(jQuery);