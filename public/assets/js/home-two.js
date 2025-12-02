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

})(window.jQuery);