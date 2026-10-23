/*
	TextTV.nu på webben. Vanlig JS utan jQuery (todo #18).

	- Sidomenyn: "Sidor ≡" (li.nav-menu a) fäller ut den på mobil.
	- ← och → bläddrar till föregående och nästa sida. ? visar alla
	  kortkommandon, / och siffror går till "Gå till sida".
	- Rader med en enda länk blir tryckbara i hela sin bredd (.tt-rad).
	- "Gå till sida": tre siffror skickar formuläret direkt. Förslagen under
	  fältet ligger i js/tt-sok.js.
	- "Dela sidan": telefonens egen delning om den finns, annars en ruta med
	  Facebook, X, e-post och länken.
	- Ny version av sidan: frågar /api/updated var femte sekund och visar
	  .pages-updated när en nyare version finns. Resultatsidorna
	  (html.page-is-live) byts i stället ut på plats, och raderna som
	  ändrats lyser upp en stund.
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
	// rel=prev/next: dagarna på /sida/delat.
	var lank = document.querySelector(e.key === "ArrowLeft" ? ".controls__prev, a[rel=prev]" : ".controls__next, a[rel=next]");
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
function ttMarkeraRader(root) {
	root.querySelectorAll(".TextTVPage .line").forEach(function (line) {
		if (line.querySelectorAll("a").length === 1) {
			line.classList.add("tt-rad");
		}
	});
}
ttMarkeraRader(document);

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

	// Delningen till GA4 ("share", som appen skickar till Firebase), så att
	// webb och app går att jämföra. Bara när delningen faktiskt gjordes.
	function matDelning(metod) {
		if (typeof gtag === "function") {
			gtag("event", "share", {
				method: metod,
				content_type: "page",
				item_id: pages.map(function (p) {
					return p.id;
				}).join(","),
			});
		}
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
		links.querySelectorAll("a").forEach(function (a) {
			a.addEventListener("click", function () {
				matDelning(a.textContent.replace(" →", "").toLowerCase());
			});
		});
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
				matDelning("kopiera länk");
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
				navigator.share({ title: "Text TV: " + data.title, url: data.permalink }).then(function () {
					matDelning("webbläsarens delning");
				}).catch(function (e) {
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
	function senasteVersion(p) {
		return Math.max.apply(null, p.map(function (page) {
			return page.added;
		}));
	}
	var latestUpdate = senasteVersion(pages);
	var isLive = document.documentElement.classList.contains("page-is-live");

	// Läser upp uppdateringen för skärmläsare.
	var status = document.createElement("div");
	status.className = "sr-only";
	status.setAttribute("role", "status");
	document.body.appendChild(status);

	document.querySelectorAll(".pages-updated-reload").forEach(function (b) {
		b.addEventListener("click", function () {
			ttLoading();
			// Ny adress så att nginx-cachen inte ger den gamla versionen.
			var newUrl = new URL(window.location.href);
			newUrl.searchParams.set("uppdaterad", Math.floor(Date.now() / 1000));
			window.location.replace(newUrl);
		});
	});

	// misslyckad: live-uppdateringen gav upp, rutan säger det i stället för
	// att det finns en ny version.
	function visaRuta(misslyckad) {
		misslyckad = misslyckad === true;
		document.querySelectorAll(".pages-updated").forEach(function (e) {
			e.classList.add("is-updated");
			var text = e.querySelector(".pages-updated-reload").firstChild;
			if (misslyckad && text && text.nodeType === 3) {
				text.nodeValue = "Sidan kunde inte uppdateras automatiskt. ";
			}
		});
		// "(1)" i fliken betyder att det finns en ny version, inte att
		// uppdateringen gett upp.
		if (!misslyckad && !/\(1\)/.test(document.title)) {
			document.title = "(1) " + document.title;
		}
		// "Uppdateras live" stämmer inte längre: markören stannar och texten
		// blir "Uppdaterad".
		document.removeEventListener("visibilitychange", vidSynlig);
		document.documentElement.classList.add("tt-live-pausad");
		document.querySelectorAll(".pageshare__updated--live").forEach(function (p) {
			if (p.firstChild && p.firstChild.nodeType === 3) {
				p.firstChild.nodeValue = "Uppdaterad ";
			}
		});
		clearInterval(timer);
	}

	// Hämtar sidan igen och byter ut Text TV-rutan. En rad som inte fanns i
	// förra versionen (ett nytt resultat) får tt-rad-ny en stund. Annonserna
	// och scrollpositionen rörs inte.
	//
	// Ett nätverksfel eller en cachad sida från innan uppdateringen försöks
	// igen vid nästa fråga. Efter MAX_MISSAR i rad visas rutan i stället, så
	// att en flik inte hämtar hela sidan var femte sekund i all evighet. Har
	// sidan ändrat form (fler sidor, "sänds inte"-rutan kommit eller gått)
	// visas rutan direkt.
	var MAX_MISSAR = 4;
	var missar = 0;
	var hamtar = false;

	function miss() {
		missar++;
		if (missar >= MAX_MISSAR) {
			visaRuta(true);
		}
	}

	// Ersätter gammal med ny (redan importerad). Står fokus på en länk i
	// den gamla flyttas det till samma länk i den nya.
	function ersattBehallFokus(gammal, ny) {
		var fokus = gammal.contains(document.activeElement) && document.activeElement.getAttribute("href");
		gammal.replaceWith(ny);
		if (fokus) {
			var lank = Array.prototype.find.call(ny.querySelectorAll("a[href]"), function (a) {
				return a.getAttribute("href") === fokus;
			});
			if (lank) {
				lank.focus({ preventScroll: true });
			}
		}
	}

	// Byter gammal mot ny om båda finns. Ger den nya, eller null.
	function bytUt(selector, doc) {
		var gammal = document.querySelector(selector);
		var ny = doc.querySelector(selector);
		if (!gammal || !ny) {
			return null;
		}
		ny = document.importNode(ny, true);
		ersattBehallFokus(gammal, ny);
		return ny;
	}

	// Blocken under sidan i den ordning de står. "Tidigare i dag" finns
	// bara när det finns fler versioner i dag, och "Gårdagens resultat"
	// flyttar dit när den gör det (Sida::visa()). Ett block kan alltså
	// komma eller gå mellan två versioner, t.ex. vid midnatt.
	var UNDER_SIDAN = [".pageshare", ".tt-tidigare", ".day-archive-link"];

	function bytBlockUnderSidan(doc) {
		var ruta = document.querySelector(".tt-under-sidan");
		if (!ruta || !doc.querySelector(".tt-under-sidan")) {
			return;
		}
		UNDER_SIDAN.forEach(function (selector, i) {
			var s = ".tt-under-sidan > " + selector;
			if (selector === ".pageshare") {
				return; // Bara tiden byts, se nedan. Dela-knappen har sin lyssnare.
			}
			var gammal = document.querySelector(s);
			var ny = doc.querySelector(s);
			if (gammal && ny) {
				ersattBehallFokus(gammal, document.importNode(ny, true));
			} else if (gammal) {
				gammal.remove();
			} else if (ny) {
				// Efter närmaste block före som finns, annars först.
				var fore = null;
				for (var j = i - 1; j >= 0 && !fore; j--) {
					fore = document.querySelector(".tt-under-sidan > " + UNDER_SIDAN[j]);
				}
				ny = document.importNode(ny, true);
				if (fore) {
					fore.after(ny);
				} else {
					ruta.prepend(ny);
				}
			}
		});
	}

	function bytSida(gammal, ny, andrade) {
		// Raderna i förra versionen, räknade, så att en rad som står två
		// gånger bara räknas som oförändrad så många gånger den fanns.
		var forut = {};
		gammal.querySelectorAll(".line").forEach(function (l) {
			forut[l.textContent] = (forut[l.textContent] || 0) + 1;
		});
		ny = document.importNode(ny, true);
		ny.querySelectorAll(".line:not(.toprow)").forEach(function (l) {
			var text = l.textContent;
			if (forut[text]) {
				forut[text]--;
			} else if (text.trim()) {
				l.classList.add("tt-rad-ny");
				andrade.push(l);
			}
		});
		ersattBehallFokus(gammal, ny);
		ttMarkeraRader(ny.parentNode);
	}

	function uppdateraPaPlats() {
		if (hamtar) {
			return;
		}
		hamtar = true;
		var url = new URL(window.location.href);
		// Samma adress för alla som tittar inom tio sekunder, så att de delar
		// nginx-cachen i stället för att rendera sidan var för sig.
		url.searchParams.set("live", Math.floor(Date.now() / 10000));
		fetch(url)
			.then(function (r) {
				return r.ok ? r.text() : Promise.reject(r.status);
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, "text/html");
				var nyaPages = JSON.parse(doc.body.getAttribute("data-pages")) || [];
				if (!nyaPages.length || senasteVersion(nyaPages) <= latestUpdate) {
					miss();
					return;
				}
				var gamla = document.querySelectorAll(".one-page[data-sida]");
				var nya = doc.querySelectorAll(".one-page[data-sida]");
				var sands = !document.querySelector(".not-broadcasted-notice") === !doc.querySelector(".not-broadcasted-notice");
				if (!nya.length || nya.length !== gamla.length || !sands) {
					visaRuta();
					return;
				}
				missar = 0;
				var andrade = [];
				nya.forEach(function (ny, i) {
					bytSida(gamla[i], ny, andrade);
				});
				// "Uppdaterad 22:40" och "Tidigare i dag" under sidan.
				var tid = bytUt(".pageshare__updated", doc);
				bytBlockUnderSidan(doc);
				if (tid) {
					var time = tid.querySelector("time");
					if (time) {
						time.classList.add("tt-tid-ny");
					}
				}
				setTimeout(function () {
					andrade.forEach(function (l) {
						l.classList.remove("tt-rad-ny");
					});
				}, 10000);

				// Delningen och nästa fråga ska gälla den nya versionen.
				pages = nyaPages;
				document.body.setAttribute("data-pages", JSON.stringify(nyaPages));
				latestUpdate = senasteVersion(nyaPages);

				// Bara när något syns ha ändrats, t.ex. inte när bara datumet i
				// översta raden bytts vid midnatt.
				if (andrade.length) {
					status.textContent = "Sidan uppdaterad, " + andrade.length + (andrade.length === 1 ? " rad ändrad" : " rader ändrade");
					if (typeof gtag === "function") {
						gtag("event", "live_update", { page_num: pageNums, changed_rows: andrade.length });
					}
				}
			})
			.catch(miss)
			.then(function () {
				hamtar = false;
			});
	}

	// En fråga i taget: timern och visibilitychange kan annars fråga
	// samtidigt med samma gamla tid, och den andra räknas då som en miss.
	// En fråga som hängt sig (skärmen släckt, nätbyte) släpps efter 15 s, så
	// att den inte stoppar alla kommande.
	var fragadeSenast = 0;
	var fragar = false;

	function kolla() {
		// Ingen fråga när fliken inte syns; nästa fråga kommer när den gör det.
		if (document.hidden || hamtar || (fragar && Date.now() - fragadeSenast < 15000)) {
			return;
		}
		fragar = true;
		fragadeSenast = Date.now();
		fetch("/api/updated/" + pageNums + "/" + latestUpdate + "?app=texttvnu.web")
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (data.update_available) {
					if (isLive) {
						uppdateraPaPlats();
					} else {
						visaRuta();
					}
				}
			})
			.catch(function () {})
			.then(function () {
				fragar = false;
			});
	}

	var timer = setInterval(kolla, 5000);
	// Fråga direkt när fliken syns igen (skärmen tänds, man byter tillbaka),
	// i stället för att vänta upp till fem sekunder. Det är då man annars
	// letar efter en ladda om-knapp. Inte oftare än timern: snabba flikbyten
	// inom samma cachefönster (?live=, 10 s) skulle annars hämta en gammal
	// sida och räknas som missar.
	function vidSynlig() {
		if (Date.now() - fragadeSenast >= 5000) {
			kolla();
		}
	}
	document.addEventListener("visibilitychange", vidSynlig);
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
