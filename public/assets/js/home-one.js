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
    if ($('.hero-slider').length) {
        $('.hero-slider').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 800,
            fade: true,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            autoplay: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>'
        });
    }

    if ($('.testimonial-slider').length) {
        $('.testimonial-slider').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 600,
            autoplay: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="fal fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="fal fa-angle-right"></i></div>'
        });
    }

})(window.jQuery);