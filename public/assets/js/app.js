

$(function() {
	"use strict";

	var isDesktopSidebar = function() {
		return window.matchMedia("(min-width:1025px)").matches;
	};

	var toggleDesktopSidebar = function() {
		$(".wrapper").toggleClass("sidebar-collapsed");
		$(".wrapper").removeClass("toggled sidebar-hovered");
	};

  // Tooltops

    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    })



    $(".nav-toggle-icon").on("click", function() {
		$(".wrapper").toggleClass("toggled");
		$(".wrapper").removeClass("sidebar-hovered");
	});

	$(".mobile-toggle-icon").on("click", function() {
		$(".wrapper").toggleClass("toggled");
		$(".wrapper").removeClass("sidebar-hovered");
	});

	$(".desktop-toggle-icon").on("click", function() {
		toggleDesktopSidebar();
	});

	$(function() {
		for (var e = window.location, o = $(".metismenu li a").filter(function() {
				return this.href == e
			}).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
	})


	$(".toggle-icon").on("click", function() {
		if (isDesktopSidebar()) {
			toggleDesktopSidebar();
			return;
		}
		$(".wrapper").toggleClass("toggled");
		$(".wrapper").removeClass("sidebar-hovered");
	});

	$(window).on("resize", function() {
		if (!isDesktopSidebar()) {
			$(".wrapper").removeClass("sidebar-collapsed");
		}
	});

	$(".sidebar-wrapper").hover(function() {
		if ($(".wrapper").hasClass("toggled")) {
			$(".wrapper").addClass("sidebar-hovered");
		}
	}, function() {
		$(".wrapper").removeClass("sidebar-hovered");
	});



	$(function() {
		$("#menu").metisMenu()
	})


	$(".search-toggle-icon").on("click", function() {
		$(".top-header .navbar form").addClass("full-searchbar")
	})
	$(".search-close-icon").on("click", function() {
		$(".top-header .navbar form").removeClass("full-searchbar")
	})


	$(".chat-toggle-btn").on("click", function() {
		$(".chat-wrapper").toggleClass("chat-toggled")
	}), $(".chat-toggle-btn-mobile").on("click", function() {
		$(".chat-wrapper").removeClass("chat-toggled")
	}), $(".email-toggle-btn").on("click", function() {
		$(".email-wrapper").toggleClass("email-toggled")
	}), $(".email-toggle-btn-mobile").on("click", function() {
		$(".email-wrapper").removeClass("email-toggled")
	}), $(".compose-mail-btn").on("click", function() {
		$(".compose-mail-popup").show()
	}), $(".compose-mail-close").on("click", function() {
		$(".compose-mail-popup").hide()
	})


	$(document).ready(function() {
		$(window).on("scroll", function() {
			$(this).scrollTop() > 300 ? $(".back-to-top").fadeIn() : $(".back-to-top").fadeOut()
		}), $(".back-to-top").on("click", function() {
			return $("html, body").animate({
				scrollTop: 0
			}, 600), !1
		})
	})


	// switcher 
	var THEME_STORAGE_KEY = "papyrus_pos_theme";
	var THEME_CLASSES = ["light-theme", "dark-theme", "semi-dark", "minimal-theme"];

	var setThemeIcon = function(themeName) {
		var isDarkLike = (themeName === "dark-theme" || themeName === "semi-dark");
		$(".dark-mode-icon i").attr("class", isDarkLike ? "bi bi-brightness-high-fill" : "bi bi-moon-fill");
	};

	var applyTheme = function(themeName, persist) {
		if (persist === undefined) persist = true;
		var safeTheme = THEME_CLASSES.indexOf(themeName) > -1 ? themeName : "light-theme";
		$("html").removeClass(THEME_CLASSES.join(" ")).addClass(safeTheme);
		setThemeIcon(safeTheme);

		if (persist) {
			try {
				localStorage.setItem(THEME_STORAGE_KEY, safeTheme);
			} catch (e) {}
		}
	};

	var getCurrentTheme = function() {
		var htmlClass = $("html").attr("class") || "";
		for (var i = 0; i < THEME_CLASSES.length; i++) {
			if (htmlClass.indexOf(THEME_CLASSES[i]) !== -1) return THEME_CLASSES[i];
		}
		return "light-theme";
	};

	(function initSavedTheme() {
		var savedTheme = null;
		try {
			savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
		} catch (e) {}
		applyTheme(savedTheme || getCurrentTheme(), false);
	})();

	$(".dark-mode").on("click", function() {
		var currentTheme = getCurrentTheme();
		var nextTheme = (currentTheme === "dark-theme") ? "light-theme" : "dark-theme";
		applyTheme(nextTheme, true);
	}), 

	$("#LightTheme").on("click", function() {
		applyTheme("light-theme", true);
	}),

	$("#DarkTheme").on("click", function() {
		applyTheme("dark-theme", true);
	}),

	$("#SemiDarkTheme").on("click", function() {
		applyTheme("semi-dark", true);
	}),

	$("#MinimalTheme").on("click", function() {
		applyTheme("minimal-theme", true);
	})


	$("#headercolor1").on("click", function() {
		$("html").addClass("color-header headercolor1"), $("html").removeClass("headercolor2 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor2").on("click", function() {
		$("html").addClass("color-header headercolor2"), $("html").removeClass("headercolor1 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor3").on("click", function() {
		$("html").addClass("color-header headercolor3"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor4").on("click", function() {
		$("html").addClass("color-header headercolor4"), $("html").removeClass("headercolor1 headercolor2 headercolor3 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor5").on("click", function() {
		$("html").addClass("color-header headercolor5"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor3 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor6").on("click", function() {
		$("html").addClass("color-header headercolor6"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor3 headercolor7 headercolor8")
	}), $("#headercolor7").on("click", function() {
		$("html").addClass("color-header headercolor7"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor3 headercolor8")
	}), $("#headercolor8").on("click", function() {
		$("html").addClass("color-header headercolor8"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor3")
	})


	new PerfectScrollbar(".header-message-list")
    new PerfectScrollbar(".header-notifications-list")



});
