// Global variable with array with the current page pages
var pages = [];

// Objekt med texttv.nu:s alla grejjer
var texttvnu = (function ($) {
  var my = {},
    elms = {},
    settings = {
      // autoUpdateInterval: 10
    },
    // autoUpdateIntervalId = null,
    // autoUpdateTimer = 0,
    privateVariable = 1;

  function privateMethod() {
    // ...
  }

  my.moduleProperty = 1;
  my.moduleMethod = function () {
    // ...
  };

  my.addListeners = function () {
    // Fixa auto update
    /*
		elms.autoUpdate.on("change", function(e) {
			var checked = elms.autoUpdate.is(":checked");
			$.cookie('autoUpdateEnabled', checked, { expires: 7 });
			$.cookie('autoUpdateInterval', settings.autoUpdateInterval, { expires: 7 });
			my.checkAutoUpdate();
		});
		*/

    // Gå till sida
    /*
		$("#frmGoPage").on("submit", function(e) {
			e.preventDefault();
			var page_num = $("#goPageNum").val();
			var pattern = /^\d{3}$/;
			if (page_num.match(pattern)) {
				document.location = "/" + parseInt(page_num, 0);
			} else {
				alert("Ange ett giltigt sidnummer");
			}
		});
		*/

    // Share button
    elms.sharebutton.on("click", showPageShare);
  }; // end addListeners

  function showPageShare(e) {
    // add share sheet
    var $share_sheet = $('<div class="pageshare__sheet"></div>');
    var $share_sheet_loading = $(
      '<p class="pageshare__sheet__loading">Hämtar länk...</p>'
    );
    $share_sheet.html($share_sheet_loading);
    $share_sheet.appendTo(elms.body);

    $share_sheet.append('<button class="pageshare__sheet__close">×</button>');

    var $pageshare__sheet_bg = $('<div class="pageshare__sheet_bg"></div>');
    $pageshare__sheet_bg.appendTo(elms.body);

    $share_sheet.on("click", ".pageshare__sheet__text_input", function (e) {
      this.select();
    });

    // Close button
    $share_sheet.on("click", ".pageshare__sheet__close", function (e) {
      $share_sheet.fadeOut("fast", function () {
        $share_sheet.remove();
        $pageshare__sheet_bg.remove();
      });
    });

    // Track share click
    ga("send", "event", "Share", "Show share sheet");

    // get link
    // todo: need to get permaIDs of these pages
    var page_ids = [];
    for (i in pages) {
      page_ids.push(pages[i].id);
    }

    var api_url =
      "https://api.texttv.nu/api/get_permalink/" + page_ids.join(",");
    $.getJSON(
      api_url,
      {
        app: "texttvnu.web",
      },
      function (data) {
        var permalink = data.permalink;
        var permalinkTwitter = escape(encodeURI(permalink));
        var permalinkFacebook = escape(encodeURI(permalink));
        var permalinkEmail = encodeURI(permalink);
        var permalinkLink = encodeURI(permalink);

        var title = data.title;
        var escapedEmailTitle = encodeURI("Text TV: " + title);
        var escapedEmailBody =
          encodeURI("" + title + "\n\n") + escape(permalinkEmail);
        $share_sheet_loading.fadeOut();

        var $share_html = "";
        $share_html += '<h1>Dela "' + title + '"</h1>';
        $share_html += '<ul class="pageshare__sheet__targets">';
        $share_html +=
          '<li class="pageshare__sheet__target pageshare__sheet__target--fb">' +
          '<a href="https://www.facebook.com/sharer.php?u=' +
          permalinkFacebook +
          "&amp;t=" +
          title +
          '"><span class="icon icon-facebook"></span>Facebook</a>' +
          "</li>";
        $share_html +=
          '<li class="pageshare__sheet__target pageshare__sheet__target--twitter">' +
          '<a href="https://twitter.com/intent/tweet?original_referer=&amp;text=' +
          title +
          "&amp;url=" +
          permalinkTwitter +
          '"><span class="icon icon-twitter"></span>' +
          "Twitter" +
          "</li>";
        $share_html +=
          '<li class="pageshare__sheet__target pageshare__sheet__target--email">' +
          '<a href="mailto:?subject=' +
          escapedEmailTitle +
          "&amp;body=" +
          escapedEmailBody +
          '">E-post</a>' +
          "</li>";
        $share_html +=
          '<li class="pageshare__sheet__target pageshare__sheet__target--textarea">' +
          "Direktlänk<br><textarea class='pageshare__sheet__text_input'>" +
          permalinkLink +
          "</textarea>" +
          "</li>";
        $share_html +=
          '<li class="pageshare__sheet__target pageshare__sheet__target--link"><a href="' +
          permalinkLink +
          '">' +
          permalinkLink +
          "</a></li>";
        $share_html += "</ul>";
        $share_html = $($share_html);

        $share_html.appendTo($share_sheet);
      }
    );
  }

  /*
	my.doAutoUpdateRefresh = function() {
		autoUpdateTimer = autoUpdateTimer - 1;
		elms.autoUpdateTimer.text(autoUpdateTimer);
		if (autoUpdateTimer == 0) {
			// nedräkningen är klar. refresh!
			document.location = document.location;
		}
	}
	*/

  /*
	my.checkAutoUpdate = function() {
		clearInterval(autoUpdateIntervalId);
		var autoUpdateEnabled = $.cookie('autoUpdateEnabled');
		if (autoUpdateEnabled && autoUpdateEnabled == "true") {
			var autoUpdateInterval = parseInt($.cookie('autoUpdateInterval'));
			autoUpdateTimer = autoUpdateInterval+1;
			autoUpdateIntervalId = setInterval(my.doAutoUpdateRefresh, 1000);
		} else {
		}
	}
	*/

  my.onDomReady = function () {
    elms.html = $("html");
    elms.document = $(document);
    elms.win = $(window);
    elms.pages = $("#pages");
    elms.inpagePages = $("ul.inpage-pages");
    elms.body = $("body");
    elms.sharebutton = $(".pageshare__sharebutton");

    elms.html.addClass("is-domready");

    // for hover states to work
    elms.body.bind("touchstart", function () {});

    my.addListeners();
    my.addTrackEvents();

    // Focus the goto-page-input
    // But not for touch (probably mobile) so keyboard does not appear (annoying in iOS at least)
    var isTouch = "ontouchstart" in window;
    if (!isTouch) {
      $("#goPageNum").focus().select();
    }

    $("#goPageNum").click(function (e) {
      var t = $(this);
      t.val("");
    });

    // Show sidebar
    var sidebar = $("li.nav-menu a");
    sidebar.on("click", function (e) {
      e.preventDefault();

      // Track click
      if (elms.body.hasClass("menu-active")) {
        ga("send", "event", "Sidebar", "Close");
      } else {
        ga("send", "event", "Sidebar", "Open");
      }

      elms.body.toggleClass("menu-active");
    });
  };

  my.loading = function () {
    elms.html.addClass("is-loading");
  };

  my.addTrackEvents = function () {
    var controls = $("nav.controls");

    // Track nav clicks
    controls.on("click", ".nav-home a", function (e) {
      my.loading();
      ga("send", "event", "Menu", "Nav", "Home", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    // Track download app clicks
    controls.on("click", "a.controls-promo-item", function (e) {
      ga("send", "event", "Menu", "Promo", "App", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    controls.on("submit", "#frmGoPage", function (e) {
      my.loading();
      e.preventDefault();

      ga("send", "event", "Menu", "Nav", "Custom page", {
        hitCallback: function () {
          var page_num = $("#goPageNum").val();
          var pattern = /^\d{3}$/;
          if (page_num.match(pattern)) {
            document.location = "/" + parseInt(page_num, 0);
          } else {
            alert("Ange ett giltigt sidnummer");
          }
        },
      });
    });

    controls.on("click", ".nav-prev a", function (e) {
      my.loading();
      ga("send", "event", "Menu", "Nav", "Prev page", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    controls.on("click", ".nav-next a", function (e) {
      my.loading();
      ga("send", "event", "Menu", "Nav", "Next page", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    // Track clicks on "Senaste nyheterna"
    var $latestNews = $(".latest-pages-list--news");
    var $latestSport = $(".latest-pages-list--sport");

    $latestNews.on("click", ".latest-pages-title", function (e) {
      my.loading();
      ga("send", "event", "More news", "Click news item", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    $latestSport.on("click", ".latest-pages-title", function (e) {
      my.loading();
      ga("send", "event", "More news", "Click sport item", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });

    // Track clicks on breadcrumb
    $breadcrumbs = $(".breadcrumbs");
    $breadcrumbs.on("click", ".breadcrumbs__link", function (e) {
      my.loading();
      ga("send", "event", "Breadcrumbs", "Click", {
        hitCallback: my.onGaHitCallback.call(this, e),
      });
    });
  };

  my.onGaHitCallback = function (e) {
    e.preventDefault();
    var url = e.currentTarget.href;
    if (e.metaKey) {
      window.open(url, "_blank");
    } else {
      document.location = url;
    }
  };

  // my.loadGA = function () {
  //   (function () {
  //     var ga = document.createElement("script");
  //     ga.type = "text/javascript";
  //     ga.async = true;
  //     ga.src =
  //       ("https:" == document.location.protocol
  //         ? "https://ssl"
  //         : "http://www") + ".google-analytics.com/ga.js";
  //     var s = document.getElementsByTagName("script")[0];
  //     s.parentNode.insertBefore(ga, s);
  //   })();
  // };

  // onload, alltså inte domready
  /*my.onLoad = function() {

	};*/

  return my;
})($);

// texttvnu.loadGA();

// OnDomReady baby
//$(function() {
texttvnu.onDomReady();
//});

// onload = när även typsnittet är inladdat
//$(window).load(texttvnu.onLoad);

/**
 * Check for updates for the currently loaded pages.
 * If update found then show message with info.
 * Click on message = reload.
 *
 * Example API call:
 * https://texttv.nu/api/updated/101,102,103,104,105/1438354627
 */
(function () {
  var checkInterval = 5000;
  var pageNums = "";
  var latestUpdate = 0;
  var isArchivePage = false;
  var isTextPage = false;

  pages = $("body").data("pages");

  pages.forEach(function (elm) {
    pageNums += "," + elm.num;
    latestUpdate = Math.max(latestUpdate, elm.added);
  });

  isArchivePage = $("html.page-is-archive").length > 0;
  isTextPage = $(".wrap.textsida").length > 0;

  pageNums = pageNums.replace(/^,/g, "");

  var api_url = "/api/updated/" + pageNums + "/" + latestUpdate;

  $(".pages-updated-reload").on("click", function (e) {
    texttvnu.loading();

    // Update URL to bypass cache.
    // Should be safe because not all click update at the same time or so often.
    // 'https://texttv.nu/101'
    var newUrl = new URL(window.location.href);
    var unixtime = Math.floor(Date.now() / 1000);
    newUrl.searchParams.set("uppdaterad", unixtime);

    // Track click and then reload.
    // Disable since 2025-03-24 since it's not working, after switching to new GA script.
    // ga("send", "event", "button", "click", "update_available", {
    //   hitCallback: function () {

        window.location.replace(newUrl);
    //   },
    // });
  });

  function onData(data) {
    if (data.update_available) {
      $(".pages-updated").addClass("is-updated");

      // Prepend (1) to document title
      if (!document.title.match(/\(1\)/)) {
        document.title = "(1) " + document.title;
      }
    }
  }

  // check api_url for updates every n seconds
  function checkForRemoteUpdate() {
    $.getJSON(api_url, { app: "texttvnu.web" }, onData);
  }

  if (isArchivePage || isTextPage) {
    // no refresh check on archive pages or text pages like blog
  } else {
    setInterval(checkForRemoteUpdate, checkInterval);
  }
})();

/**
 * Do misc things when dom is ready.
 */
window.addEventListener("DOMContentLoaded", (event) => {
  // Post number form automagically when 3 numbers are entered
  $(".controls-topnav-search-input").on("keyup", function (e) {
    var number = parseInt(this.value);
    if (number >= 100 && number <= 999) {
      $(".controls-topnav-form").submit();
    }
  });
});

if ("serviceWorker" in navigator) {
  navigator.serviceWorker.register("/service-worker.js");
}

// Stats
var stats;
(function () {
  stats = Cookies.getJSON("stats");

  if (!stats || typeof stats != "object") {
    stats = {};
  }

  // count current page(s)
  if (pages.length) {
    pages.forEach(function (val) {
      if (val && val.num) {
        if (!(val.num in stats)) {
          stats[val.num] = {
            count: 0,
          };
        }

        stats[val.num].count++;
      }
    });

    // done updating stats, save back to cokie
    Cookies.set("stats", stats, { expires: 30 });
  }
})();
