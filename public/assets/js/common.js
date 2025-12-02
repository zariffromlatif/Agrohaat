/*----------------------------------------------------------------------------------- 

Template Name: Agricko - Agriculture Farming HTML Template
URI: pixelfit.agency
Description: Agricko is a clean, modern, and fully responsive HTML template specially designed for agriculture, organic farming, eco-friendly products, and related rural business websites. Whether you’re running a dairy farm, vegetable farm, poultry, or a community-supported agriculture project – Agricko provides a perfect foundation for your online presence.
Author: Pixelfit
Author URI: https://themeforest.net/user/pixelfit
Version: 1.0 


------------------------------------------------------
   JS INDEX
-----------------------------------------------------

	# Main Menu
	# Offcanvas Overlay
	# Preloader
	# Sticky
	# Magnific-Popup JS
	# Counter JS
	# Nice Select Js
	# AOS Animation
	# GSAP Animation

-------------------------------------------------------    */

(function($) {
	'use strict';

	//===== Main Menu

	function mainMenu() {
		var var_window = $(window),
			navContainer = $('.header-navigation'),
			navbarToggler = $('.navbar-toggler'),
			navMenu = $('.theme-nav-menu'),
			navMenuLi = $('.theme-nav-menu ul li ul li'),
			closeIcon = $('.navbar-close');
		navbarToggler.on('click', function() {
			navbarToggler.toggleClass('active');
			navMenu.toggleClass('menu-on');
		});
		closeIcon.on('click', function() {
			navMenu.removeClass('menu-on');
			navbarToggler.removeClass('active');
		});
		navMenu.find("li a").each(function() {
			if ($(this).children('.dd-trigger').length < 1) {
				if ($(this).next().length > 0) {
					$(this).append('<span class="dd-trigger"><i class="far fa-angle-down"></i></span>')
				}
			}
		});
		navMenu.on('click', '.dd-trigger', function(e) {
			e.preventDefault();
			$(this).parent().parent().siblings().children('ul.sub-menu').slideUp();
			$(this).parent().next('ul.sub-menu').stop(true, true).slideToggle(350);
			$(this).toggleClass('sub-menu-open');
		});

	};

	//===== Offcanvas Overlay

	function offCanvas() {
		const $overlay = $(".offcanvas__overlay");
		const $toggler = $(".navbar-toggler");
		const $menu = $(".theme-nav-menu");
		$toggler.add($overlay).add(".navbar-close, .panel-close-btn").on("click", function() {
			$overlay.toggleClass("overlay-open");
			if ($(this).is($overlay)) {
				$toggler.removeClass("active");
				$menu.removeClass("menu-on");
			}
		});
		$(window).on("resize", function() {
			if ($(window).width() > 991) $overlay.removeClass("overlay-open");
		});
	}

	//===== Preloader

	$(window).on('load', function(event) {
		$('.preloader').delay(500).fadeOut(500);
	})

	//===== Back to top

    $(window).on('scroll', function(event) {
        if ($(this).scrollTop() > 600) {
            $('.back-to-top').fadeIn(200)
        } else {
            $('.back-to-top').fadeOut(200)
        }
    });
    $('.back-to-top').on('click', function(event) {
        event.preventDefault();
        $('html, body').animate({
            scrollTop: 0,
        }, 1500);
    });

	//===== Magnific-popup js

	if ($('.video-popup').length) {
		$('.video-popup').magnificPopup({
			type: 'iframe',
			removalDelay: 300,
			mainClass: 'mfp-fade'
		});
	}
	// ===== Counter

	if ($('.counter').length) {
		const observer = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					$(entry.target).counterUp({
						delay: 100,
						time: 4000
					});
					observer.unobserve(entry.target);
				}
			});
		}, {
			threshold: 1.0
		});
		$('.counter').each(function() {
			observer.observe(this);
		});
	}

	//===== Nice select js
    
    if ($('select').length){
        $('select').niceSelect();
    }

  	//====== Aos 

	AOS.init({
		offset: 0
	});

	// Document Ready

	$(function() {
		mainMenu();
		offCanvas();
	});

})(window.jQuery);