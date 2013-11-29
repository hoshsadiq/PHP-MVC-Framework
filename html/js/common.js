var map_initialized = false;
var ABSURL = 'http://localhost/overheard/';
$(function () {

    /*
     * Drop-down search results
     */
    $('.search-query').typeahead({
        source: ["Canterbury", "London", "The University of Kent", "Schmanterbury", "University of Brent", "Herp", "Derp", "The Derping"],
        items: 8
    });

    /*
     * Quicklogin popup box
     * @example http://dev.iceburg.net/jquery/jqModal/#where
     */
    var quickloginOptions = {};
    if ($('form.login:visible').length == 0) {
        // only assign popup trigger if theres no login form on the page
        quickloginOptions.trigger = 'a.quicklogin';
    }

    $('.navbar .quicklogin-link').click(function (e) {
        e.preventDefault();
        $('#quicklogin').fadeToggle(90, 'linear');
    });

    $('.navbar .quickregister-link').click(function (e) {
        e.preventDefault();
        $('#quickregister').fadeToggle(90, 'linear');
    });


    $('.login .registration a').click(function () {
        $(this).html('Fill in the fields above to register');
        // @todo: some fancy form transformation
        return false;
    });

    // extend 'posting balloon' top of the page
    $('.submit-overhear textarea').focus(function () {
        $(this).siblings('.options:hidden').show('blind', 400);
    });

    // allow user to enter location
    $('.submit-overhear button.location').click(function () {
        $('.submit-overhear input.more.location').toggle('blind', 400);
    });

    // move 'posting balloon' out of maps way
    $('#index_map').mousedown(function () {
        $('.submit-overhear .options:visible').hide('blind', 400);
        $('.submit-overhear .more:visible').hide('blind', 200);
        $('.submit-overhear textarea').blur();
    });

    // show/hide main map on index page
    $('#index_map_link').click(function () {
        $('#index_map').toggle();
        if (!map_initialized) {
            var myOptions = {
                zoom: 5,
                center: new google.maps.LatLng(53.442873, 359.208453),
                mapTypeId: google.maps.MapTypeId.ROADMAP
            }
            var map = new google.maps.Map(document.getElementById('index_map_canvas'), myOptions);
            map_initialized = true;
        }
        return false;
    });

    // user profile links
    $('.namelabel').hoverIntent(function () {
        $(this).animate({backgroundColor: '#666'}, 300);
    }, function () {
        $(this).animate({backgroundColor: '#999'}, 300);
    });

    /*
     * Blink overhear submission box
     */
    if ($('.submit-overhear textarea').length != 0) {
        setInterval(function () {
            var val = $('.submit-overhear textarea').attr('placeholder');
            if (val.slice(-2) == ' █') {
                $('.submit-overhear textarea').attr('placeholder', val.slice(0, -2));
            } else {
                $('.submit-overhear textarea').attr('placeholder', val + ' █');
            }
        }, 900);
    }

    /*
     * Floating social panel
     */
    var fl_menu = $("#social-float");
    if (fl_menu.length > 0) {
        panel_pos_original = fl_menu.position().top;
        function float_social() {
            if ($(window).scrollTop() > panel_pos_original) {
                var new_pos = $(document).scrollTop() + 15;
            } else {
                var new_pos = panel_pos_original;
            }
            if ($(window).height() < fl_menu.height()) {
                fl_menu.css("top", panel_pos_original);
            } else {
                fl_menu.stop().animate({top: new_pos}, 1500, 'easeOutQuint');
            }

        }

        $(window).scroll(function () {
            float_social();
        });
    }

    // disable links on active and disabled pagination items
    $('.pagination .disabled a, .pagination .active a').click(function () {
        return false;
    });

    // load comments
    $('.post button.show-comments').click(function () {
        $(this).parent().find('.comments .fb-comments').slideToggle(300);
    });


    // For later!
    /**
     * Activate tinymce
     $('textarea.tinymce').tinymce({
        script_url: ABSURL+'/js/tinymce/tiny_mce.js',
        height: "150px",
        theme : 'ovt',
        plugins : 'paste',

        valid_elements: '-b,-strong,-i,-em,-u,#p,-br',

        // Display an alert onclick
        setup : function(ed) {
            ed.onInit.add(function(ed) {
                var dom = ed.dom,
                    doc = ed.getDoc(),
                    el = doc.content_editable ? ed.getBody() : (tinymce.isGecko ? doc : ed.getWin());

               // tinymce.dom.Event.add(el, 'blur', function(e) {
               //     console.log('blur');
               // })
                tinymce.dom.Event.add(el, 'focus', function(e) {
                    $('.submit-overhear textarea').focus()
                })

            });
        },

        forced_root_block : false,
        force_br_newlines : true,
        force_p_newlines : false
    });
     */

    /*
     * Ajaxly post an overhear
     */
    $('form.submit-overhear').submit(function (e) {
        var $form = $(this),
            text = $form.find('textarea[name=text]').val().trim(),
            location = $form.find('input[name=location]').val().trim(),
            url = $form.attr('action');

        //$('#share-error').hide();

        // change button to loading state
        $('.submit-overhear button[type=submit]').button('loading');

        // send data using post
        $.post(url, { content: text, location: location },
            function (data) {
                $('.submit-overhear button[type=submit]').button('reset');

                if ($.isNumeric(data) && data > 0) {
                    // overhear posted
                    $form.find('textarea[name=text]').val('');
                    $form.find('input[name=location]').val('');

                    // if page contains overhear-posts
                    if ($('.posts.row').length > 0) {
                        // update page with newly submitted post
                        var newpost = $.get('getpost', {id: data}, function (response) {

                            // allocate screen space for new post
                            $('.posts').prepend(response).find('.post:first-child').css({opacity: 0});

                            // pretend post is moving from form area to page
                            $form.effect('transfer', {
                                to: $('.posts .post:first-child'),
                                easing: 'easeInOutExpo'
                            }, 700, function () {
                                // unhide post
                                $('.posts .post:first-child').animate({opacity: 1}, 1500);
                            });
                        });
                    } else {
                        // user is not on page with overhears, therefore redirect him to the page where he'll see his post

                        // user entered no location, so just redirect to homepage
                        if (location.length > 0) {
                            window.location = ABSURL;
                        } else {
                            window.location = ABSURL + location;
                        }
                    }
                } else {
                    if (data == null) {
                        data = 'Database error, our hamsters are failing!';
                    }
                    // shake button
                    $form.find('#share-error').effect('shake', {times: 4, distance: 3}, 60);
                    // print error message
                    $('#share-error').show().html(data);
                    // re-enable button
                    $('.submit-overhear button[type=submit]').button('reset');
                    console.log(this);
                }
            }
        );

        return false;
    });
});

// facebook
(function (d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s);
    js.id = id;
    js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
    js.async = true;
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

// G+
window.___gcfg = {lang: 'en-GB'};
(function () {
    var po = document.createElement('script');
    po.type = 'text/javascript';
    po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(po, s);
})();

// gmaps
/*(function() {
 var po = document.createElement('script'); po.type = 'text/javascript'; po.async = false;
 po.src = 'http://maps.googleapis.com/maps/api/js?key=AIzaSyB-gfjo6ZJ2hji4OstCbco2-uw6KjI9bC4&sensor=false&region=GB';
 var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
 })();*/