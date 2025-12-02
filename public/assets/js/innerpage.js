/*----------------------------------------------------------------------------------- 

Template Name: Agricko - Agriculture Farming HTML Template
URI: pixelfit.agency
Description: Agricko is a clean, modern, and fully responsive HTML template specially designed for agriculture, organic farming, eco-friendly products, and related rural business websites. Whether you’re running a dairy farm, vegetable farm, poultry, or a community-supported agriculture project – Agricko provides a perfect foundation for your online presence.
Author: Pixelfit
Author URI: https://themeforest.net/user/pixelfit
Version: 1.0 


------------------------------------------------------
   CSS INDEX
-----------------------------------------------------

    # Components
        # Base CSS
        # Common CSS
        # Preloader CSS
        # Offcanvas CSS
        # Animation CSS
        # Button CSS
        # Header CSS
        # Footer CSS
-------------------------------------------------------    */

(function($) {
    'use strict';

    if ($('.testimonial-slider').length) {
        $('.testimonial-slider').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 600,
            autoplay: true,
            slidesToShow: 2,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="fal fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="fal fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 800,
                    settings: {
                        slidesToShow: 1,
                    }
                }
            ]
        });
    }
    if ($('.product-big-slider').length) {
        $('.product-big-slider').slick({
            dots: false,
            arrows: false,
            speed: 800,
            autoplay: true,
            fade: true,
            asNavFor: '.product-thumb-slider',
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>'
        });
    }

    if ($('.product-thumb-slider').length) {
        $('.product-thumb-slider').slick({
            dots: false,
            arrows: false,
            speed: 800,
            autoplay: true,
            asNavFor: '.product-big-slider',
            vertical: true,
            focusOnSelect: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>'
        });
    }

    if ($('.related-product-slider').length) {
        $('.related-product-slider').slick({
            dots: false,
            arrows: false,
            speed: 800,
            autoplay: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1450,
                    settings: {
                        slidesToShow: 3,
                    }
                },
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2,
                    }
                },
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2,
                    }
                },
                {
                    breakpoint: 700,
                    settings: {
                        slidesToShow: 1,
                    }
                }
            ]
        });
    }

    if ($('.instagram-slider').length) {
        $('.instagram-slider').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 6000,
            autoplaySpeed: 0,
            autoplay: true,
            slidesToShow: 5,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4,
                    }
                },
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3,
                    }
                }
            ]
        });
    }


    //======= Quantity Number js

    $('.quantity-down').on('click', function(){
        var numProduct = Number($(this).next().val());
        if(numProduct > 1) $(this).next().val(numProduct - 1);
    });
    $('.quantity-up').on('click', function(){
        var numProduct = Number($(this).prev().val());
        $(this).prev().val(numProduct + 1);
    });


    //===== Slider Range

    if ($('#slider-range').length) {
        $("#slider-range").slider({
            range: true,
            min: 0,
            max: 599,
            values: [50, 300],
            slide: function (event, ui) {
                $("#min-label").text(pluralizePrice(ui.values[0]));
                $("#max-label").text(pluralizePrice(ui.values[1]));
            }
        });
        const values = $("#slider-range").slider("values");
        $("#min-label").text(pluralizePrice(values[0]));
        $("#max-label").text(pluralizePrice(values[1]));
    }
    function pluralizePrice(value) {
        return "$" + value;
    }


})(window.jQuery);