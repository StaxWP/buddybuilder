var BPB_GridListView = BPB_GridListView || {};

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

(function ($) {

    // USE STRICT
    "use strict";

    BPB_GridListView.fn = {
        init: function () {
            $('.bpb-list-mode').on('click', function (e) {
                e.preventDefault();
                var membersList = $('#members-list');
                var groupsList = $('#groups-list');

                if (membersList.length) {
                    membersList.addClass('grid-one-force');
                }

                if (groupsList.length) {
                    groupsList.addClass('grid-one-force');
                }

                $(this).addClass('bpb-active');
                $('.bpb-grid-mode').removeClass('bpb-active');

                setCookie($(this).parent().data('component'), 'list', 99999);
            });

            $('.bpb-grid-mode').on('click', function (e) {
                e.preventDefault();
                var membersList = $('#members-list');
                var groupsList = $('#groups-list');

                if (membersList.length) {
                    membersList.removeClass('grid-one-force');
                }

                if (groupsList.length) {
                    groupsList.removeClass('grid-one-force');
                }

                $(this).addClass('bpb-active');
                $('.bpb-list-mode').removeClass('bpb-active');

                setCookie($(this).parent().data('component'), 'grid', 99999);
            });

            var url = new URLSearchParams(window.location.search);

            if (!url.has('elementor-preview')) {
                var ajax_loader = setInterval(function () {
                    var membersList = $('#members-list');
                    var groupsList = $('#groups-list');

                    if (membersList.length) {
                        var list_type = getCookie('members');

                        if (!list_type) {
                            return false;
                        }

                        if (list_type === 'grid') {
                            membersList.removeClass('grid-one-force');
                        }

                        if (list_type === 'list') {
                            membersList.addClass('grid-one-force');
                        }
                    }

                    if (groupsList.length) {
                        var list_type = getCookie('groups');

                        if (!list_type) {
                            return false;
                        }

                        if (list_type === 'grid') {
                            groupsList.removeClass('grid-one-force');
                        }

                        if (list_type === 'list') {
                            groupsList.addClass('grid-one-force');
                        }
                    }
                }, 200);

                setTimeout(function () {
                    clearInterval(ajax_loader);
                }, 5000);
            }
        },
    };

    $(document).ready(BPB_GridListView.fn.init());

})(jQuery);
