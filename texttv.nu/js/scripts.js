/*
	TextTV.nu på webben. Vanlig JS utan jQuery (todo #18).

	- Sidomenyn: "Sidor ≡" (li.nav-menu a) fäller ut den på mobil.
	- ← och → bläddrar till föregående och nästa sida. ? visar alla
	  kortkommandon, / och siffror går till "Gå till sida".
	- Rader med en enda länk blir tryckbara i hela sin bredd (.tt-rad).
	- "Gå till sida": tre siffror skickar formuläret direkt. Förslagen under
	  fältet ligger i js/tt-sok.js.
	- "Dela →": telefonens egen delning om den finns, annars en ruta med
	  Facebook, X, e-post och länken.
	- Ny version av sidan: frågar /api/updated var femte sekund och visar
	  .pages-updated när en nyare version finns.
*/

// Sidorna på den här sidvisningen, från <body data-pages>.
var pages = [];
try {
	pages = JSON.parse(document.body.getAttribute("data-pages")) || [];
} catch (e) {}

function ttLoading() {
	document.documentElement.classList.add("is-loading");
}

document.documentElement.classList.add("is-domready");

// Sidomenyn på mobil. Öppnas den flyttas fokus till första länken i den.
// Den stängs med "Sidor ≡" igen, "Stäng ×" i menyn, Esc eller ett tryck
// utanför, och fokus går då tillbaka till "Sidor ≡".
(function () {
	var toggle = document.querySelector("li.nav-menu a");
	var meny = document.getElementById("sidomeny");
	if (!toggle || !meny) {
		return;
	}

	function arOppen() {
		return document.body.classList.contains("menu-active");
	}

	function oppna() {
		document.body.classList.add("menu-active");
		toggle.setAttribute("aria-expanded", "true");
		var first = meny.querySelector(".sidlista a");
		if (first) {
			// Vänta lite: menyn är dold (visibility) tills övergången börjat.
			setTimeout(function () {
				first.focus({ preventScroll: true });
			}, 60);
		}
	}

	function stang() {
		document.body.classList.remove("menu-active");
		toggle.setAttribute("aria-expanded", "false");
		toggle.focus({ preventScroll: true });
	}

	toggle.setAttribute("aria-controls", "sidomeny");
	toggle.setAttribute("aria-expanded", "false");
	toggle.addEventListener("click", function (e) {
		e.preventDefault();
		if (arOppen()) {
			stang();
		} else {
			oppna();
		}
	});

	var stangKnapp = meny.querySelector(".sidebar__stang button");
	if (stangKnapp) {
		stangKnapp.addEventListener("click", stang);
	}

	document.addEventListener("keydown", function (e) {
		if (!arOppen()) {
			return;
		}
		if (e.key === "Escape" || e.key === "Esc") {
			stang();
			return;
		}
		// Tab stannar i den öppna menyn, i stället för att gå vidare till
		// den dolda sidan bakom (granskning 2026-10-18).
		if (e.key === "Tab") {
			var lankar = meny.querySelectorAll("a[href], button");
			if (!lankar.length) {
				return;
			}
			var forsta = lankar[0];
			var sista = lankar[lankar.length - 1];
			if (e.shiftKey && document.activeElement === forsta) {
				e.preventDefault();
				sista.focus();
			} else if (!e.shiftKey && document.activeElement === sista) {
				e.preventDefault();
				forsta.focus();
			}
		}
	});

	document.addEventListener("click", function (e) {
		if (arOppen() && !meny.contains(e.target) && !toggle.contains(e.target)) {
			stang();
		}
	});
})();

// ← och → på tangentbordet bläddrar som pilarna i ramen, som på tv:n.
// Inte i fält, inte med Ctrl/Alt/Cmd (webbläsarens bakåt), inte när menyn
// är öppen.
document.addEventListener("keydown", function (e) {
	if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") {
		return;
	}
	if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || e.defaultPrevented) {
		return;
	}
	var t = e.target;
	if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) {
		return;
	}
	if (document.body.classList.contains("menu-active") || document.querySelector("dialog[open]")) {
		return;
	}
	var lank = document.querySelector(e.key === "ArrowLeft" ? ".controls__prev" : ".controls__next");
	if (lank) {
		e.preventDefault();
		ttLoading();
		window.location.href = lank.href;
	}
});

// Kortkommandon på tangentbordet: ? visar en ruta med alla, / går till
// "Gå till sida", och en siffra börjar skriva ett sidnummer där, som att
// slå numret på fjärrkontrollen. e.key är tecknet, så det fungerar med
// svenskt tangentbord (? = Shift + +, / = Shift + 7) och amerikanskt.
(function () {
	var sok = document.getElementById("search-input");

	function iFalt(t) {
		return t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName));
	}

	var KOMMANDON = [
		["← →", "Föregående och nästa sida"],
		["0–9", "Skriv ett sidnummer"],
		["/", "Gå till sida"],
		["Esc", "Stäng menyn, rutan eller förslagen"],
		["?", "Visa kortkommandon"],
	];

	function visaKortkommandon() {
		if (document.querySelector("dialog[open]")) {
			return;
		}
		var dialog = document.createElement("dialog");
		dialog.className = "pageshare__sheet tt-kortkommandon";
		dialog.setAttribute("aria-labelledby", "tt-kortkommandon-rubrik");
		var h = document.createElement("h2");
		h.id = "tt-kortkommandon-rubrik";
		h.className = "tt-band";
		h.textContent = "Kortkommandon";
		dialog.appendChild(h);
		var dl = document.createElement("dl");
		KOMMANDON.forEach(function (k) {
			var dt = document.createElement("dt");
			var kbd = document.createElement("kbd");
			kbd.textContent = k[0];
			dt.appendChild(kbd);
			var dd = document.createElement("dd");
			dd.textContent = k[1];
			dl.appendChild(dt);
			dl.appendChild(dd);
		});
		dialog.appendChild(dl);
		var rad = document.createElement("div");
		rad.className = "tt-linkrow";
		var stang = document.createElement("button");
		stang.type = "button";
		stang.className = "tt-link";
		stang.textContent = "Stäng";
		rad.appendChild(stang);
		dialog.appendChild(rad);

		var modal = typeof dialog.showModal === "function";
		function stangRuta() {
			if (modal) {
				dialog.close();
			} else if (dialog.parentNode) {
				dialog.parentNode.removeChild(dialog);
				document.removeEventListener("keydown", esc);
			}
		}
		function esc(e) {
			if (e.key === "Escape" || e.key === "Esc") {
				stangRuta();
			}
		}
		stang.addEventListener("click", stangRuta);
		dialog.addEventListener("close", function () {
			if (dialog.parentNode) {
				dialog.parentNode.removeChild(dialog);
			}
		});
		dialog.addEventListener("click", function (e) {
			if (e.target === dialog) {
				stangRuta();
			}
		});
		document.body.appendChild(dialog);
		if (modal) {
			dialog.showModal();
		} else {
			dialog.setAttribute("open", "");
			dialog.className += " pageshare__sheet--utan-dialog";
			document.addEventListener("keydown", esc);
		}
		stang.focus();
	}

	document.querySelectorAll(".tt-kortkommandon-knapp").forEach(function (b) {
		b.addEventListener("click", function (e) {
			e.preventDefault();
			visaKortkommandon();
		});
	});

	document.addEventListener("keydown", function (e) {
		if (e.ctrlKey || e.metaKey || e.altKey || e.defaultPrevented || e.isComposing) {
			return;
		}
		if (iFalt(e.target) || document.querySelector("dialog[open]")) {
			return;
		}
		if (e.key === "?") {
			e.preventDefault();
			visaKortkommandon();
			return;
		}
		if (!sok || document.body.classList.contains("menu-active")) {
			return;
		}
		if (e.key === "/") {
			e.preventDefault();
			sok.focus();
			return;
		}
		if (/^[0-9]$/.test(e.key)) {
			// Siffran skrivs i fältet. keyup kommer i fältet, så tre siffror
			// skickar formuläret som vanligt, och input visar förslagen.
			e.preventDefault();
			sok.focus();
			sok.value = e.key;
			sok.dispatchEvent(new Event("input", { bubbles: true }));
		}
	});
})();

// Rader i Text-TV-sidan med exakt en länk ("Ambulansförare dödad … 130")
// får klassen tt-rad, och CSS gör då hela raden tryckbar. Rader med flera
// länkar ("Utrikes 104 Sport 300") behåller små ytor per nummer.
document.querySelectorAll(".TextTVPage .line").forEach(function (line) {
	if (line.querySelectorAll("a").length === 1) {
		line.classList.add("tt-rad");
	}
});

// "Gå till sida": tre siffror skickar formuläret (GET /?number=377).
document.querySelectorAll(".controls-topnav-search-input").forEach(function (input) {
	input.addEventListener("keyup", function () {
		var number = parseInt(this.value, 10);
		if (/^\d{3}$/.test(this.value.trim()) && number >= 100 && number <= 999) {
			ttLoading();
			this.form.submit();
		}
	});
});

// Dela.
(function () {
	var button = document.querySelector(".pageshare__sharebutton");
	if (!button) {
		return;
	}

	function hamtaPermalank() {
		var ids = pages.map(function (p) {
			return p.id;
		});
		return fetch("/api/get_permalink/" + ids.join(",") + "?app=texttvnu.web").then(function (r) {
			return r.json();
		});
	}

	function el(tag, attrs, text) {
		var e = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			e.setAttribute(k, attrs[k]);
		});
		if (text) {
			e.textContent = text;
		}
		return e;
	}

	function visaRuta(data) {
		var url = data.permalink;
		var title = data.title || "Text TV";

		var dialog = el("dialog", { class: "pageshare__sheet", "aria-labelledby": "pageshare-rubrik" });
		dialog.appendChild(el("h2", { id: "pageshare-rubrik", class: "tt-band" }, "Dela " + title));

		var links = el("div", { class: "tt-linkrow pageshare__sheet__targets" });
		links.appendChild(el("a", { href: "https://www.facebook.com/sharer.php?u=" + encodeURIComponent(url), target: "_blank", rel: "noopener" }, "Facebook →"));
		links.appendChild(el("a", { href: "https://twitter.com/intent/tweet?text=" + encodeURIComponent(title) + "&url=" + encodeURIComponent(url), target: "_blank", rel: "noopener" }, "X →"));
		links.appendChild(el("a", { href: "mailto:?subject=" + encodeURIComponent("Text TV: " + title) + "&body=" + encodeURIComponent(title + "\n\n" + url) }, "E-post →"));
		dialog.appendChild(links);

		var label = el("label", { for: "pageshare-lank", class: "pageshare__sheet__label" }, "Länk");
		var input = el("input", { id: "pageshare-lank", class: "pageshare__sheet__text_input", type: "text", readonly: "", value: url });
		input.addEventListener("focus", function () {
			this.select();
		});
		dialog.appendChild(label);
		dialog.appendChild(input);

		var actions = el("div", { class: "tt-linkrow" });
		var kopiera = el("button", { type: "button", class: "tt-link" }, "Kopiera länk");
		kopiera.addEventListener("click", function () {
			var klar = function () {
				kopiera.textContent = "Kopierad ✓";
			};
			if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(klar, function () {
					input.select();
				});
			} else {
				input.select();
				document.execCommand("copy");
				klar();
			}
		});
		var stang = el("button", { type: "button", class: "tt-link" }, "Stäng");
		stang.addEventListener("click", function () {
			stangRuta();
		});
		actions.appendChild(kopiera);
		actions.appendChild(stang);
		dialog.appendChild(actions);

		// Äldre webbläsare (3 % 2026-10) saknar <dialog>: då är rutan en
		// vanlig fast positionerad ruta som tas bort när den stängs.
		var modal = typeof dialog.showModal === "function";
		function stangRuta() {
			if (modal) {
				dialog.close();
			} else if (dialog.parentNode) {
				dialog.parentNode.removeChild(dialog);
			}
		}
		dialog.addEventListener("close", function () {
			if (dialog.parentNode) {
				dialog.parentNode.removeChild(dialog);
			}
		});
		// Klick på bakgrunden stänger.
		dialog.addEventListener("click", function (e) {
			if (e.target === dialog) {
				stangRuta();
			}
		});

		document.body.appendChild(dialog);
		if (modal) {
			dialog.showModal();
		} else {
			dialog.setAttribute("open", "");
			dialog.className += " pageshare__sheet--utan-dialog";
			// <dialog> stänger själv med Esc; reservrutan behöver hjälp.
			var esc = function (e) {
				if (e.key === "Escape" || e.key === "Esc") {
					document.removeEventListener("keydown", esc);
					stangRuta();
				}
			};
			document.addEventListener("keydown", esc);
		}
	}

	button.addEventListener("click", function () {
		hamtaPermalank().catch(function () {
			// Utan permalänk delas adressen man står på.
			return { permalink: location.href, title: document.title };
		}).then(function (data) {
			if (navigator.share) {
				navigator.share({ title: "Text TV: " + data.title, url: data.permalink }).catch(function (e) {
					// AbortError = användaren stängde delningen själv.
					if (e && e.name !== "AbortError") {
						visaRuta(data);
					}
				});
			} else {
				visaRuta(data);
			}
		});
	});
})();

// Ny version av sidan.
(function () {
	var isArchivePage = document.documentElement.classList.contains("page-is-archive");
	var isTextPage = !!document.querySelector(".wrap.textsida");
	if (!pages.length || isArchivePage || isTextPage) {
		return;
	}

	var pageNums = pages.map(function (p) {
		return p.num;
	}).join(",");
	var latestUpdate = Math.max.apply(null, pages.map(function (p) {
		return p.added;
	}));
	var apiUrl = "/api/updated/" + pageNums + "/" + latestUpdate + "?app=texttvnu.web";

	document.querySelectorAll(".pages-updated-reload").forEach(function (b) {
		b.addEventListener("click", function () {
			ttLoading();
			// Ny adress så att nginx-cachen inte ger den gamla versionen.
			var newUrl = new URL(window.location.href);
			newUrl.searchParams.set("uppdaterad", Math.floor(Date.now() / 1000));
			window.location.replace(newUrl);
		});
	});

	var timer = setInterval(function () {
		fetch(apiUrl)
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (data.update_available) {
					document.querySelectorAll(".pages-updated").forEach(function (e) {
						e.classList.add("is-updated");
					});
					if (!/\(1\)/.test(document.title)) {
						document.title = "(1) " + document.title;
					}
					clearInterval(timer);
				}
			})
			.catch(function () {});
	}, 5000);
})();

// Annonsskriptet blockerat (annonsblockerare): göm annonsrutorna, annars står
// "Annons" och "Fler nyheter efter annonsen" kvar utan annons (todo #19).
// adsbygoogle.loaded sätts av Googles skript när det har körts. Ett skript
// som bara väntar på samtycke räknas som laddat och påverkas inte.
window.addEventListener("load", function () {
	setTimeout(function () {
		if (window.adsbygoogle && window.adsbygoogle.loaded === true) {
			return;
		}
		document.querySelectorAll(".ad").forEach(function (ad) {
			ad.classList.add("ad--tom");
		});
	}, 3000);
});

if ("serviceWorker" in navigator) {
	navigator.serviceWorker.register("/service-worker.js");
}

// Cookien "stats" räknade sidvisningar i webbläsaren men lästes aldrig av
// servern och skickades med i varje request. Ta bort den hos dem som har den.
if (document.cookie.indexOf("stats=") !== -1) {
	document.cookie = "stats=; max-age=0; path=/";
}
