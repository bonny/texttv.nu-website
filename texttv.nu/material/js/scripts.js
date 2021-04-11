/**
 * jQuery alterClass plugin
 *
 * Remove element classes with wildcard matching. Optionally add classes:
 *   $( '#foo' ).alterClass( 'foo-* bar-*', 'foobar' )
 *
 * Copyright (c) 2011 Pete Boere (the-echoplex.net)
 * Free under terms of the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 */ (function($) {

    $.fn.alterClass = function(removals, additions) {

        var self = this;

        if (removals.indexOf('*') === -1) {
            // Use native jQuery methods if there is no wildcard matching
            self.removeClass(removals);
            return !additions ? self : self.addClass(additions);
        }

        var patt = new RegExp('\\s' + removals.
        replace(/\*/g, '[A-Za-z0-9-_]+').
        split(' ').
        join('\\s|\\s') + '\\s', 'g');

        self.each(function(i, it) {
            var cn = ' ' + it.className + ' ';
            while (patt.test(cn)) {
                cn = cn.replace(patt, ' ');
            }
            it.className = $.trim(cn);
        });

        return !additions ? self : self.addClass(additions);
    };

})(Zepto);


var $navbutton;
var $refreshbutton;
var $mainnav;
var $document;
var $content;
var $pagenav;
var lastLoadedPageNum;
var $html;
var $body;

$navbutton = $(".mainnav_button");
$refreshbutton = $(".refresh_button");
$mainnav = $(".mainnav");
$document = $(document);
$content = $(".content");
$pagenav = $(".pagenav");
$html = $("html");
$body = $(document.body);

var isTouch = 'ontouchend' in document;
var tapEvent = isTouch ? "tap" : "click";

if (!isTouch) {
	
	$navbutton.on("mouseover", function(e) {
		showNav(e);
	});
	
}

if (isTouch) {
	
	// Don't allow click events on touch devices, because they have already triggered tap	
	$content.on("click", "a", function(e) {
		e.preventDefault();
	});

}

$content.on("swipeLeft", function(e) {
	navNextOrPrev("next");
});

$content.on("swipeRight", function(e) {
	navNextOrPrev("prev");
});

$content.on("swipeDown", function(e) {

	getPage(lastLoadedPageNum);

});

/*$(".pageNumNav__number").on("submit", function() {
	alert("num submit");
});*/

/*$(".pageNumNav__number").on("blur", function() {
	alert("num submit");
});*/


//$(".pageNumNav__form").on("submit", function(e) {


function isValidPageNum(num) {

	return (num >= 100 && num <= 999);

}

$(".pageNumNav__number").on("blur", function(e) {

	e.preventDefault();
	
	var $input = $(".pageNumNav__number");
	var pageNum = parseInt( $input.val() );
	
	if (!isValidPageNum(pageNum)) {
		return;
	}	
	
	getPage(pageNum);
	$input.val("");
	$input.blur();
	
});

$(".pageNumNav__number").on("keyup", function(e) {
	
	//console.log("pagenum event", e);
	var $input = $(e.target);
	var val = e.target.value;

	if (isValidPageNum(val)) {
		getPage(val);

		$input.val("");
		$input.blur();
		
	}
	
});

/*$(".pageNumNav__numberPlaceholder").on(tapEvent, function(e) {
	
	//alert("tap");
	//$(e.target).blur();
	//$(".pageNumNav__numberTheNumber").focus();
	$(".pageNumNav__numberTheNumber").get(0).focus();
	
});*/

// $(".pageNumNav__form").get(0).onsubmit = function() { alert(123); };


function getFirstPageInPageRange(str) {
	
	var num = (str + "").match(/(\d+)/);
	
	return parseInt(num[0]);
	
}

function navNextOrPrev(direction) {

	var currentNum = getFirstPageInPageRange(lastLoadedPageNum);
	var nextNum;
	
	if (direction == "next") {
		nextNum = currentNum + 1;
	} else {
		nextNum = currentNum - 1;
	}

	if (nextNum < 100 || nextNum > 999)	 {
		// At beginning or end
		return;
	}
	
	if (nextNum) {
		getPage(nextNum);
	}
	
}

// return page range from string
// page
function getPageRangeFromString(str) {
	
	var pagerange;
	
	// If str contains comma then it's comma separated list, not minus separated
	if (str.match(",")) {
		pageRange = str.match(/([\d]+(,\d+)?)/);		
	} else if (str.match("/")) {
		pageRange = str.match(/([\d]+(-\d+)?)/);		
	}

	console.log("getPageRangeFromString", str, pageRange);

	return pageRange;
	
}

$pagenav.on(tapEvent, function(e) {

	var num = (lastLoadedPageNum + "").match(/(\d+)/);

	var currentNum = parseInt(num[0]);
	var nextNum;
	var $target = $(this);
	var direction = $target.hasClass("pagenav--next") ? "next" : "prev";
	
	navNextOrPrev(direction);
		
});

// Click link/number in pages
$content.on(tapEvent, "a", function(e) {

	var $a = $(this);
	var href = $a.attr("href");
	var pageNums = getPageRangeFromString( href );
	// $a.attr("href", "#");
	
	getPage(pageNums[0]);

	e.preventDefault();
	e.stopPropagation();
		
});


$navbutton.on(tapEvent, function(e) {
	
	var $target = $(e.target);

	if ($target.hasClass("mainnav_button_inner--second")) {

		load();
		hideNav();
		e.preventDefault();
		e.stopPropagation();
		
	} else {

		e.preventDefault();
		e.stopPropagation();
		showNav(e);
		
	}
		
});

$document.on(tapEvent, function(e) {

	var eFromNav = $.contains($mainnav.get(0), e.target);
	
	if (!eFromNav) {
		hideNav(e);
	}
	
});

function showNav(e) {

	$html.addClass("mainnav--active");      
	e.stopPropagation();
}

function hideNav(e) {
				
	$html.removeClass("mainnav--active");

}

function getPage(pageNum) {
	
	if (pageNum == "start") {
		pageNum = 100;
	}

	lastLoadedPageNum = pageNum;
	
	var api = "http://texttv.nu/api/get/";
	api = api + pageNum;
	api = api + "?jsoncallback=?";

	$body.alterClass("page--afterAjax", "page--beforeAjax");
	
	$.getJSON(api, function(data) {
		$body.alterClass("page--beforeAjax", "page--afterAjax");
		renderPage(data);
		// $body.alterClass("page--afterAjax");
	});
	
}

function renderPage(pages) {

	$body.toggleClass("page--afterRender", "page--beforeRender");

	var html = "";

	$.each(pages, function(index, item) {

		$.each(item.content, function(pageIndex, pageContent) {
			html += pageContent;
		});
		
	});

	$content.html(html);
	
	window.scroll(0, 0);
	
	$body.alterClass("page--num*", "page--afterRender page--rendered page--num" + getFirstPageInPageRange(lastLoadedPageNum));
}

$mainnav.on(tapEvent, "button", function(e) {

	var $target = $(e.target);
	var $parent = $target.closest("li");
	var pageNum = $parent.data("page");
	hideNav();
	getPage(pageNum);	

});

$mainnav.on("submit", ".mainnav_gotoform", function(e) {
	
	var $input = $(".mainnav_gotonumber");
	var pageNum = $input.val();

	// Hide keyboard on ios
	$input.blur();

	$input.val("");

	getPage(pageNum);

	hideNav(e);
	
	return false;

});

function load() {
	
	// show all
	$(document.body).animate({ opacity: 1 }, 500, "ease-in-out");
	
	// show nav
	$navbutton.addClass("animated fadeInUp");
	
	// show nav	
	setTimeout(function() {
		$pagenav.filter(".pagenav--prev").addClass("animated fadeInLeft");
		$pagenav.filter(".pagenav--next").addClass("animated fadeInRight");		
	}, 500);
	//$refreshbutton.addClass("animated fadeInUp");
	
	// load start page
	getPage("100,300,700");
	
	$body.addClass("loaded");
	
}

load();
