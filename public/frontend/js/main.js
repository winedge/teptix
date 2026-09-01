(function($) {
    "use strict";

    // Preloader
    $(window).on('load', function() {
        if ($('#preloader').length) {
            $('#preloader').delay(100).fadeOut('slow', function() {
                $(this).remove();
            });
        }
    });

    // Back to top button
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').on('click',function() {
        $('html, body').animate({
            scrollTop: 0
        }, 1500, 'easeInOutExpo');
        return false;
    });

    var nav = $('nav');
    var navHeight = nav.outerHeight();

    /*--/ ScrollReveal /Easy scroll animations for web and mobile browsers /--*/
    window.sr = ScrollReveal({
        distance: '30px',
        duration: 700,
        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
        origin: 'bottom',
        interval: 90,
        reset: false,
        cleanup: true
    });

    sr.reveal('.reveal-up', { origin: 'bottom' });
    sr.reveal('.reveal-fade', { distance: '0px', duration: 900 });
    sr.reveal('.reveal-left', { origin: 'left', distance: '40px' });
    sr.reveal('.reveal-right', { origin: 'right', distance: '40px' });

    /*--/ Carousel owl /--*/
    $('#carousel').owlCarousel({
        rtl: $('input[name=rtl_direction]').val(),
        loop: true,
        margin: -1,
        items: 1,
        nav: true,
        navText: ['<i class="ion-ios-arrow-forward" aria-hidden="true"></i>', '<i class="ion-ios-arrow-back" aria-hidden="true"></i>'],
        autoplay: true,
        autoplayTimeout: 3000,
        autoplayHoverPause: true
    });

    /*--/ Animate Carousel /--*/
    $('.intro-carousel').on('translate.owl.carousel', function() {
        $('.intro-content .intro-title').removeClass('animate__zoomIn animate__animated').hide();
        $('.intro-content .intro-price').removeClass('animate__fadeInUp animate__animated').hide();
        $('.intro-content .intro-title-top, .intro-content .spacial').removeClass('animate__fadeIn animate__animated').hide();
    });

    $('.intro-carousel').on('translated.owl.carousel', function() {
        $('.intro-content .intro-title').addClass('animate__zoomIn animate__animated').show();
        $('.intro-content .intro-price').addClass('animate__fadeInUp animate__animated').show();
        $('.intro-content .intro-title-top, .intro-content .spacial').addClass('animate__fadeIn animate__animated').show();
    });

    /*--/ Navbar Collapse /--*/
    $('.navbar-toggle-box-collapse').on('click', function() {
        $('body').removeClass('box-collapse-closed').addClass('box-collapse-open');
    });
    $('.close-box-collapse, .click-closed').on('click', function() {
        $('body').removeClass('box-collapse-open').addClass('box-collapse-closed');
        $('.menu-list ul').slideUp(700);
    });

    /*--/ Navbar Menu Reduce /--*/
    $(window).trigger('scroll');
    $(window).on('bind','scroll', function() {
        var pixels = 50;
        var top = 1200;
        if ($(window).scrollTop() > pixels) {
            $('.top-header').addClass('navbar-reduce');
            $('.top-header').removeClass('navbar-trans');
            $('.second-menu').removeClass('menu-header');
        } else {
            $('.top-header').addClass('navbar-trans');
            $('.second-menu').addClass('menu-header');
            $('.top-header').removeClass('navbar-reduce');
        }
        if ($(window).scrollTop() > top) {
            $('.scrolltop-mf').fadeIn(1000, "easeInOutExpo");
        } else {
            $('.scrolltop-mf').fadeOut(1000, "easeInOutExpo");
        }
    });

    /*--/ Property owl /--*/
    $('#event-carousel').owlCarousel({
        rtl: $('input[name=rtl_direction]').val(),
        loop: true,
        margin: 30,
        responsive: {
            0: {
                items: 1,
            },
            769: {
                items: 2,
            },
            992: {
                items: 3,
            }
        }
    });

    /*--/ Property owl owl /--*/
    $('#property-single-carousel').owlCarousel({
        rtl: $('input[name=rtl_direction]').val(),
        loop: true,
        margin: 0,
        nav: true,
        navText: ['<i class="ion-ios-arrow-forward" aria-hidden="true"></i>', '<i class="ion-ios-arrow-back" aria-hidden="true"></i>'],
        responsive: {
            0: {
                items: 1,
            }
        }
    });

    /*--/ News owl /--*/
    $('#blog-carousel').owlCarousel({
        rtl: $('input[name=rtl_direction]').val(),
        loop: true,
        margin: 30,
        responsive: {
            0: {
                items: 1,
            },
            769: {
                items: 3,
            },
            992: {
                items: 4,
            }
        }
    });

    /*--/ Testimonials owl /--*/
    $('#testimonial-carousel').owlCarousel({
        rtl: $('input[name=rtl_direction]').val(),
        margin: 0,
        autoplay: true,
        nav: true,
        animateOut: 'fadeOut',
        animateIn: 'fadeInUp',
        navText: ['<i class="ion-ios-arrow-forward" aria-hidden="true"></i>', '<i class="ion-ios-arrow-back" aria-hidden="true"></i>'],
        autoplayTimeout: 4000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1,
            }
        }
    });

})(jQuery);