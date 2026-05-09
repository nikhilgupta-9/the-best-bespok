(function ($) {
	"use strict";


	// sticky header
	window.addEventListener('scroll', function () {
		const header = document.querySelector('header.header-area');
		if (header) {
			header.classList.toggle("sticky", window.scrollY > 200);
		}
	});
	
	document.querySelector('.sidebar-button').addEventListener('click', () =>
		document.querySelector('.main-menu').classList.toggle('show-menu'));

	$('.sidebar-button').on("click", function () {
		$(this).toggleClass('active');
	});

	jQuery('.dropdown-icon, .category-dropdown-icon').on('click', function () {
		jQuery(this).toggleClass('active').next('ul, .mega-menu').slideToggle();
		jQuery(this).parent().siblings().children('ul, .mega-menu').slideUp();
		jQuery(this).parent().siblings().children('.active').removeClass('active');
	});
	// Serch Btn
	$(".search-btn, .lang-btn, .currency-btn").on("click", function (e) {
		let parent = $(this).parent();
		
		// Remove 'active' class from all other elements
		$(".search-input, .lang-card, .currency-card").not(parent.find(".search-input, .lang-card, .currency-card")).removeClass("active");
		
		// Toggle 'active' class for the clicked element's target
		parent.find(".search-input, .lang-card, .currency-card").toggleClass("active");
	
		e.stopPropagation();
	});
	
	$(document).on("click", function (e) {
		// Remove 'active' class when clicking outside any target element
		if (!$(e.target).closest(".search-input, .lang-card, .currency-card, .search-btn, .lang-btn, .currency-btn").length) {
			$(".search-input, .lang-card, .currency-card").removeClass("active");
		}
	});
	
	$(".serch-close").on("click", function (e) {
		$(".search-input").removeClass("active");
	});

	// mobile dropdown

	jQuery(".category-area").on("click", function () {
		jQuery(this).toggleClass("active");
	});

	$(".category-btn").on("click", function (e) {
		e.stopPropagation();
		$(".category-menu-list").toggleClass("active");
	});
	$(document).on("click", function (e) {
	if (!$(e.target).closest(".category-menu-list, .category-btn").length) {
		$(".category-menu-list").removeClass("active");
	}
	})
	$(".category-menu-close").on("click", function (e) {
	$(".category-menu-list").removeClass("active");
	});

	// popup on load
	$(document).ready(function () {
		setTimeout(function () {
		  $("#myModal").modal("show");
		}, 500);
	});

	// Back To Top
	$(document).ready(function(){"use strict";
		
		var progressPath = document.querySelector('.progress-wrap .progress-circle path');
		var pathLength = progressPath.getTotalLength();
		progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
		progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
		progressPath.style.strokeDashoffset = pathLength;
		progressPath.getBoundingClientRect();
		progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';		
		var updateProgress = function () {
			var scroll = $(window).scrollTop();
			var height = $(document).height() - $(window).height();
			var progress = pathLength - (scroll * pathLength / height);
			progressPath.style.strokeDashoffset = progress;
		}
		updateProgress();
		$(window).scroll(updateProgress);	
		var offset = 50;
		var duration = 550;
		jQuery(window).on('scroll', function() {
			if (jQuery(this).scrollTop() > offset) {
				jQuery('.progress-wrap').addClass('active-progress');
			} else {
				jQuery('.progress-wrap').removeClass('active-progress');
			}
		});				
		jQuery('.progress-wrap').on('click', function() {
			window.scrollTo({ top: 0, behavior: "smooth" });
		})
		
		
	});


	// img hover zoom in
	$(".product-img--main")
		// tile mouse actions
		.on("mouseover", function () {
			$(this)
				.children(".product-img--main__image")
				.css({ transform: "scale(" + $(this).attr("data-scale") + ")" });
		})
		.on("mouseout", function () {
			$(this)
				.children(".product-img--main__image")
				.css({ transform: "scale(1)" });
		})
		.on("mousemove", function (e) {
			$(this)
				.children(".product-img--main__image")
				.css({
					"transform-origin":
						((e.pageX - $(this).offset().left) / $(this).width()) * 100 +
						"% " +
						((e.pageY - $(this).offset().top) / $(this).height()) * 100 +
						"%",
				});
		})
		.each(function () {
			$(this)
				// add a image container
				.append('<div class="product-img--main__image"></div>')
				// set up a background image for each tile based on data-image attribute
				.children(".product-img--main__image")
				.css({ "background-image": "url(" + $(this).attr("data-image") + ")" });
		});
	$(".qty-btn").on("click", function (e) {
		e.stopPropagation();
		$(this).next(".quantity-area").toggleClass("active");
		$(".quantity-area").not($(this).next(".quantity-area")).removeClass("active");
	});
	$(document).on("click", function (e) {
		if (!$(e.target).closest(".quantity-area").length) {
			$(".quantity-area").removeClass("active");
		}
	});
	$(".quantity__minus").on("click", function (e) {
		e.preventDefault();
		var input = $(this).siblings(".quantity__input");
		var value = parseInt(input.val(), 10);
		if (value > 1) {
			value--;
		}
		input.val(value.toString().padStart(2, "0"));
	});
	$(".quantity__plus").on("click", function (e) {
		e.preventDefault();
		var input = $(this).siblings(".quantity__input");
		var value = parseInt(input.val(), 10);
		value++;
		input.val(value.toString().padStart(2, "0"));
	});
	// timer start
	$("[data-countdown]").each(function () {
		var $deadline = new Date($(this).data("countdown")).getTime();
		var $dataDays = $(this).children("[data-days]");
		var $dataHours = $(this).children("[data-hours]");
		var $dataMinutes = $(this).children("[data-minutes]");
		var $dataSeconds = $(this).children("[data-seconds]");
		var x = setInterval(function () {
			var now = new Date().getTime();
			var t = $deadline - now;
			var days = Math.floor(t / (1000 * 60 * 60 * 24));
			var hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
			var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
			var seconds = Math.floor((t % (1000 * 60)) / 1000);
			$dataDays.html(`<strong>${days}</strong> <span>Days</span>`);
			$dataHours.html(`<strong>${hours}</strong> <span>Hours</span>`);
			$dataMinutes.html(`<strong>${minutes}</strong> <span>Mins</span>`);
			$dataSeconds.html(`<strong>${seconds}</strong> <span>Secs</span>`);
			if (t <= 0) {
				clearInterval(x);
				$dataDays.html(`<strong>00</strong> <span>Days</span>`);
				$dataHours.html(`<strong>00</strong> <span>Hours</span>`);
				$dataMinutes.html(`<strong>00</strong> <span>Minutes</span>`);
				$dataSeconds.html(`<strong>00</strong> <span>Seconds</span>`);
			}
		}, 1000);
	});

	var swiper = new Swiper(".menu-product-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},

	});
	var swiper = new Swiper(".banner-swiper-slide", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		effect: "fade", // Use the fade effect
		fadeEffect: {
			crossFade: true, // Enable cross-fade transition
		},
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 1,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 1,
			},
		}

	});
	var swiper = new Swiper(".banner2-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		effect: "fade", // Use the fade effect
		fadeEffect: {
			crossFade: true, // Enable cross-fade transition
		},
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 1,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 1,
			},
		}

	});
	var swiper = new Swiper(".home1-product-swiper", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".home1-product-swiper2", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2200, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination2",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".testimonial-swiper-slide", {
		slidesPerView: 1,
		speed: 2000,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination3",
			type: "fraction",
		},
		navigation: {
			nextEl: ".testimonial-slider-next",
			prevEl: ".testimonial-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3,
			},
		}

	});
	var swiper = new Swiper(".testimonial-swiper-slide2", {
		slidesPerView: 1,
		speed: 2000,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination3",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3,
			},
		}

	});
	var swiper = new Swiper(".home1-product3-swipe", {
		slidesPerView: 1,
		speed: 2000,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		breakpoints: {
			280: {
				slidesPerView: 1.5,
			},
			386: {
				slidesPerView: 1.5,
			},
			576: {
				slidesPerView: 2.5,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2.5,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3.5,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3.5,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3.5,
			},
		}

	});
	var swiper = new Swiper(".gallery-slider", {
		speed: 1500,
		spaceBetween: 0,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},

		breakpoints: {
			280: {
				slidesPerView: 2,
			},
			386: {
				slidesPerView: 2,
			},
			576: {
				slidesPerView: 3,
			},
			768: {
				slidesPerView: 3,
			},
			992: {
				slidesPerView: 4,
			},
			1200: {
				slidesPerView: 5,
			},
			1400: {
				slidesPerView: 5,
			},
		},
	});
	var swiper = new Swiper(".home2-product-swiper", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2300, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3,
			},
		}

	});
	var swiper = new Swiper(".home2-product3-swipe", {
		slidesPerView: 1,
		speed: 2000,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},
		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3,
			},
		}

	});
	var swiper = new Swiper(".home2-product-swiper2", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2300, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".fractional-pagination2",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 3,
			},
		}

	});
	var swiper = new Swiper(".home3-product-swiper", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2300, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-pagination",
			clickable: true,
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".home3-product-swiper2", {
		slidesPerView: 1,
		speed: 1600,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2600, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-pagination2",
			clickable: true,
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});

	var swiper = new Swiper(".recomend-product-swiper", {
		slidesPerView: 1,
		speed: 1600,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2600, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".recommend-pagi",
			clickable: true,
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".home4-categori-section-swipe", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2300, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-pagination3",
			clickable: true,
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 1,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 1,
			},
		}

	});
	var swiper = new Swiper(".home5-banner-slider", {
		slidesPerView: 1,
		speed: 1500,
		autoplay: {
			delay: 3000, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".home5-banner-slider-next",
			prevEl: ".home5-banner-slider-prev",
		},
		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
			},
			768: {
				slidesPerView: 1.6,
			},
			992: {
				slidesPerView: 1.5,
			},
			1200: {
				slidesPerView: 1.7,
			},
			1400: {
				slidesPerView: 1.7,
				spaceBetween: 0,
			},
		}
	});
	var swiper = new Swiper(".home5-categori-swipe", {
		slidesPerView: 1,
		speed: 1600,
		spaceBetween: 24,
		loop: true,
		// autoplay: {
		// 	delay: 2600, // Autoplay duration in milliseconds
		// 	disableOnInteraction: false,
		// },
		pagination: {
			el: ".swiper-pagination2",
			clickable: true,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 2,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 4,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 5,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 6,
			},
		}

	});
	var swiper = new Swiper(".home6-testimonial-swipe", {
		slidesPerView: 1,
		speed: 1600,
		spaceBetween: 30,
		loop: true,
		effect: "fade", // Use the fade effect
		fadeEffect: {
			crossFade: true, // Enable cross-fade transition
		},
		autoplay: {
			delay: 2600, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-pagination10",
			clickable: true,
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 1,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 1,
			},
		}

	});
	var swiper = new Swiper(".home3-banner-swipe", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 5,
		loop: true,
		centeredSlides: true,
		autoplay: {
			delay: 2600, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-2",
			prevEl: ".prev-2",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 5,
			},
			992: {
				slidesPerView: 2,
			},
			1200: {
				slidesPerView: 2,
			},
			1400: {
				slidesPerView: 2.4,
			},
		}

	});
	var swiper = new Swiper(".category-swiper", {
		slidesPerView: 1,
		speed: 1600,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2600, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			350: {
				slidesPerView: 2,
			},
			576: {
				slidesPerView: 3,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 4,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 5,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 6,
			},
		}

	});
	var swiper = new Swiper(".home6-swiper-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		effect: "fade", // Use the fade effect
		fadeEffect: {
			crossFade: true, // Enable cross-fade transition
		},
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-pagination3",
			clickable: true,
		},

	});
	var swiper = new Swiper(".mens-product-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2200, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".mens-fraction",
			type: "fraction",
		},
		navigation: {
			nextEl: ".product-slider-next",
			prevEl: ".product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".womens-product-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".womens-fractional",
			type: "fraction",
		},
		navigation: {
			nextEl: ".women-product-slider-next",
			prevEl: ".women-product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".kids-product-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2100, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".kids-fractional",
			type: "fraction",
		},
		navigation: {
			nextEl: ".kids-product-slider-next",
			prevEl: ".kids-product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".accessories-product-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		pagination: {
			el: ".accessories-fractional",
			type: "fraction",
		},
		navigation: {
			nextEl: ".accessories-product-slider-next",
			prevEl: ".accessories-product-slider-prev",
		},

		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 3,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 4,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 4,
			},
		}

	});
	var swiper = new Swiper(".product-details-slider", {
		slidesPerView: 1,
		speed: 1500,
		spaceBetween: 30,
		loop: true,
		effect: "fade", // Use the fade effect
		fadeEffect: {
			crossFade: true, // Enable cross-fade transition
		},
		autoplay: {
			delay: 2500, // Autoplay duration in milliseconds
			disableOnInteraction: false,
		},
		navigation: {
			nextEl: ".next-1",
			prevEl: ".prev-1",
		},


		breakpoints: {
			280: {
				slidesPerView: 1,
			},
			386: {
				slidesPerView: 1,
			},
			576: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			768: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			992: {
				slidesPerView: 1,
				spaceBetween: 20,
			},
			1200: {
				slidesPerView: 1,
				spaceBetween: 15,
			},
			1400: {
				slidesPerView: 1,
			},
		}

	});

	// Filter sidebar
	$(".filter").on("click", function (e) {
		e.stopPropagation();
		$(".filter-sidebar, .filter-top").toggleClass("slide");
	});
	$(document).on("click", function (e) {
		if (!$(e.target).closest(".filter-sidebar, .filter-top, .filter").length) {
		$(".filter-sidebar, .filter-top").removeClass("slide");
		}
	});

	//wow js 
	jQuery(window).on('load', function () {
		new WOW().init();
		window.wow = new WOW({
			boxClass: 'wow',
			animateClass: 'animated',
			offset: 0,
			mobile: true,
			live: true,
			offset: 80
		})
		window.wow.init();
	}); 
	//Select wrap
	$(".select-wrap").on("click", function () {
		$(this).addClass("selected").siblings().removeClass("selected");
	});
	// Select all list items
	const listItems = document.querySelectorAll('.indicator-area ul li');

	// Function to add active class on hover
	listItems.forEach(item => {
		item.addEventListener('mouseenter', () => {
			// Remove active class from all list items
			listItems.forEach(li => li.classList.remove('active'));

			// Add active class to the hovered item
			item.classList.add('active');
		});
	});
	// Video Popup
	$('[data-fancybox="gallery"]').fancybox({
		buttons: [
			"close"
		],
		loop: false,
		protect: true
	});
	$('.video-player').fancybox({
		buttons: [
			"close"
		],
		loop: false,
		protect: true
	});

	 // password-hide and show
	 const togglePassword = document.querySelector('#togglePassword');
	 const password = document.querySelector('#password');
	 if(togglePassword){
	 togglePassword.addEventListener('click', function (e) {
	 // toggle the type attribute
	 const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
	 password.setAttribute('type', type);
	 // toggle the eye / eye slash icon
	 this.classList.toggle('bi-eye');
	 });
	 }
	 // confirm-password
	 const togglePassword2= document.getElementById('togglePassword2');
	 const password2 = document.querySelector('#password2');
	 if (togglePassword2){
	 togglePassword2.addEventListener('click', function (e) {
	 // toggle the type attribute
	 const type = password2.getAttribute('type') === 'password' ? 'text' : 'password';
	 password2.setAttribute('type', type);
	 // toggle the eye / eye slash icon
	 this.classList.toggle('bi-eye');
	 });
	 }
	 // confirm-password
	 const togglePassword3= document.getElementById('togglePassword3');
	 const password3 = document.querySelector('#password3');
	 if (togglePassword3){
	 togglePassword3.addEventListener('click', function (e) {
	 // toggle the type attribute
	 const type = password3.getAttribute('type') === 'password' ? 'text' : 'password';
	 password3.setAttribute('type', type);
	 // toggle the eye / eye slash icon
	 this.classList.toggle('bi-eye');
	 });
	 }
	 // confirm-password
	 const togglePassword4= document.getElementById('togglePassword4');
	 const password4 = document.querySelector('#password4');
	 if (togglePassword4){
	 togglePassword4.addEventListener('click', function (e) {
	 // toggle the type attribute
	 const type = password4.getAttribute('type') === 'password' ? 'text' : 'password';
	 password4.setAttribute('type', type);
	 // toggle the eye / eye slash icon
	 this.classList.toggle('bi-eye');
	 });
	 }
	 // confirm-password
	 const togglePassword5= document.getElementById('togglePassword5');
	 const password5 = document.querySelector('#password5');
	 if (togglePassword5){
	 togglePassword5.addEventListener('click', function (e) {
	 // toggle the type attribute
	 const type = password5.getAttribute('type') === 'password' ? 'text' : 'password';
	 password5.setAttribute('type', type);
	 // toggle the eye / eye slash icon
	 this.classList.toggle('bi-eye');
	 });
	 }

	 //Counter up
	$('.counter').counterUp({
		delay: 10,
		time: 1500
	});

	$('select').niceSelect();

	//list grid view
	$(".grid-view li").on("click", function () {
		// Get the class of the clicked li element
		var clickedClass = $(this).attr("class");
		// Extract the class name without "item-" prefix
		var className = clickedClass.replace("item-", "");
		// Add a new class to the target div and remove old classes
		var targetDiv = $(".list-grid-product-wrap");
		targetDiv.removeClass().addClass("list-grid-product-wrap " + className + "-wrapper");
		// Remove the 'selected' class from siblings and add it to the clicked element
		$(this).siblings().removeClass("active");
		$(this).addClass("active");
	});

	$(function() {
		$('.payment-method-area .payment-list li').on('click', function() {
		  $('.payment-method-area .payment-list li').removeClass('active'); // Remove active class from all list items
		  if ($(this).hasClass('check-payment')) {
			$('#strip-payment').hide();
			$(this).addClass('active'); // Add active class to the clicked list item
		  }
		  else if ($(this).hasClass('cash-delivery')) {
			$('#strip-payment').hide();
			$(this).addClass('active'); // Add active class to the clicked list item
		  }
		  else if ($(this).hasClass('other-transfer')) {
			$('#strip-payment').hide();
			$(this).addClass('active'); // Add active class to the clicked list item
		  }
		  else if ($(this).hasClass('stripe')) {
			$('#strip-payment').show();
			$(this).addClass('active'); // Add active class to the clicked list item
		  }
		  else {
			$('#strip-payment').hide();
		  }
		});
	  });

	// Select all slider elements with the same class
	document.querySelectorAll('.home4-product-swiper2').forEach((slider, index) => {
		// Add unique pagination class to each slider
		$(slider).next('.pagination-area').children('.swiper-pagination5').addClass(`swiper-pagination5-${index}`);
		setTimeout(() => {
			new Swiper(slider, {
				slidesPerView: 1,
				speed: 1600,
				spaceBetween: 30,
				loop: true,
				autoplay: {
					delay: 2100, // Autoplay duration in milliseconds
					disableOnInteraction: false,
				},
				pagination: {
					el: `.swiper-pagination5-${index}`, // Assign a unique class for pagination
					clickable: true,
				},
				breakpoints: {
					280: {
						slidesPerView: 1,
					},
					386: {
						slidesPerView: 1,
					},
					576: {
						slidesPerView: 2,
						spaceBetween: 15,
					},
					768: {
						slidesPerView: 2,
						spaceBetween: 15,
					},
					992: {
						slidesPerView: 3,
						spaceBetween: 20,
					},
					1200: {
						slidesPerView: 4,
						spaceBetween: 15,
					},
					1400: {
						slidesPerView: 4,
					},
				},
			});
		}, 0);
	});
  

}(jQuery));