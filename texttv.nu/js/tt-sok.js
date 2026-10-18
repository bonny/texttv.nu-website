/*
	Förslag under "Gå till sida" (views/controls.php). Todo #18.

	Tomt fält: senast besökta (sparas i webbläsaren), mest läst nu
	(/api/most_read/news) och populära sidor. När man skriver: siffror
	filtrerar på sidnummer, bokstäver på namn. Tre siffror går till sidan
	direkt, det sköter js/scripts.js som förut.

	Combobox enligt WAI-ARIA APG: fokus stannar i fältet, pil upp/ned
	flyttar markeringen (aria-activedescendant), Enter går dit, Esc stänger
	och tömmer fältet.
*/
(function () {
	"use strict";

	var input = document.getElementById("search-input");
	var list = document.getElementById("tt-sok-forslag");
	var panel = list && list.parentNode; // .tt-sok: listan och "Stäng ×"
	var dataElm = document.getElementById("tt-sok-data");
	if (!input || !list || !dataElm) {
		return;
	}

	var form = input.form;
	var data = JSON.parse(dataElm.textContent);

	// Läser upp antalet förslag för skärmläsare.
	var status = document.createElement("div");
	status.className = "sr-only";
	status.setAttribute("role", "status");
	status.setAttribute("aria-live", "polite");
	panel.parentNode.appendChild(status);
	var sidor = data.sidor;
	var SENASTE_KEY = "tt-senaste";
	var SENASTE_MAX = 4;
	var mestLast = null; // null = inte hämtat, [] = hämtat men tomt eller fel
	var options = [];
	var active = -1;

	// Läs och skriv senast besökta. localStorage kan saknas eller kasta
	// (privat läge), då blir listan bara tom.
	function lasSenaste() {
		try {
			return JSON.parse(localStorage.getItem(SENASTE_KEY)) || [];
		} catch (e) {
			return [];
		}
	}

	function sparaBesok() {
		// Bara enskilda sidor (/101), inte startsidan eller /100,300.
		if (!/^\/\d{3}$/.test(location.pathname)) {
			return;
		}
		var pages;
		try {
			pages = JSON.parse(document.body.getAttribute("data-pages")) || [];
		} catch (e) {
			return;
		}
		if (pages.length !== 1) {
			return;
		}
		var num = String(pages[0].num);
		var senaste = lasSenaste().filter(function (s) {
			return s.num !== num;
		});
		// En sida som står i sidlistan med exakt det numret (358 "SHL tabell")
		// får det namnet; sidans egen titel kan på tabellsidor vara
		// tabellhuvudet ("S  V  F VÖ FÖ"). Annars titeln ("Inrikes" på 101).
		var kand = sidaMedNum(num, true);
		var namn = kand ? kand.namn : String(pages[0].title || "").replace(/\s+/g, " ").trim();
		senaste.unshift({ num: num, namn: namn || "Sida " + num, url: "/" + num });
		try {
			localStorage.setItem(SENASTE_KEY, JSON.stringify(senaste.slice(0, SENASTE_MAX)));
		} catch (e) {}
	}

	function hamtaMestLast() {
		if (mestLast !== null) {
			return;
		}
		mestLast = [];
		// count=5 är samma adress som appen använder, så nginx-cachen delas.
		fetch("/api/most_read/news?count=5")
			.then(function (r) {
				return r.json();
			})
			.then(function (json) {
				mestLast = (json.pages || []).slice(0, 3).map(function (p) {
					return { num: String(p.page_num), namn: p.title, url: "/" + p.page_num };
				});
				if (!panel.hidden && input.value === "") {
					rita();
				}
			})
			.catch(function () {});
	}

	// Sidan med numret num. En exakt träff (visa "377") går före ett
	// intervall som börjar på numret ("101-105"); exakt = bara exakta.
	function sidaMedNum(num, exakt) {
		var forsta = null;
		for (var i = 0; i < sidor.length; i++) {
			if (sidor[i].num !== num) {
				continue;
			}
			var s = { num: sidor[i].visa, namn: sidor[i].namn, url: sidor[i].url };
			if (sidor[i].visa === num) {
				return s;
			}
			forsta = forsta || s;
		}
		if (!exakt && forsta) {
			return forsta;
		}
		return null;
	}

	function sektioner(q) {
		if (q === "") {
			var s = [];
			// Sidan man redan står på är inget förslag.
			var senaste = lasSenaste().filter(function (s) {
				return s.url !== location.pathname;
			});
			if (senaste.length) {
				s.push({ rubrik: "Senast besökta", rader: senaste });
			}
			if (mestLast && mestLast.length) {
				s.push({ rubrik: "Mest läst nu", rader: mestLast });
			}
			s.push({ rubrik: "Populära sidor", rader: data.populara.map(function (n) { return sidaMedNum(n); }).filter(Boolean) });
			return s;
		}

		var lq = q.toLowerCase();
		var traffar = sidor.filter(function (sida) {
			if (/^\d+$/.test(q)) {
				return sida.num !== null && sida.num.indexOf(q) === 0;
			}
			return sida.namn.toLowerCase().indexOf(lq) !== -1;
		}).map(function (sida) {
			return { num: sida.visa, namn: sida.namn, url: sida.url };
		});

		// Ett sidnummer som inte står i listan går ändå att välja.
		if (/^\d{3}$/.test(q) && !traffar.some(function (t) { return t.url === "/" + q; })) {
			traffar.unshift({ num: q, namn: "Gå till sida " + q, url: "/" + q });
		}

		return traffar.length ? [{ rubrik: "Träffar", rader: traffar.slice(0, 8) }] : [];
	}

	function rita() {
		var q = input.value.trim();
		var s = sektioner(q);
		list.textContent = "";
		options = [];

		s.forEach(function (sektion, si) {
			var grupp = document.createElement("div");
			grupp.setAttribute("role", "group");
			grupp.setAttribute("aria-labelledby", "tt-sok-rubrik-" + si);
			grupp.className = "tt-sok__grupp";

			var rubrik = document.createElement("div");
			rubrik.id = "tt-sok-rubrik-" + si;
			rubrik.className = "tt-sok__rubrik";
			rubrik.textContent = sektion.rubrik;
			grupp.appendChild(rubrik);

			sektion.rader.forEach(function (rad) {
				var opt = document.createElement("div");
				opt.id = "tt-sok-opt-" + options.length;
				opt.className = "tt-sok__rad";
				opt.setAttribute("role", "option");
				opt.setAttribute("aria-selected", "false");
				opt.dataset.url = rad.url;

				var num = document.createElement("span");
				num.className = "tt-sok__num";
				num.textContent = rad.num || "";
				var namn = document.createElement("span");
				namn.className = "tt-sok__namn";
				namn.textContent = rad.namn;

				opt.appendChild(num);
				opt.appendChild(namn);
				grupp.appendChild(opt);
				options.push(opt);
			});

			list.appendChild(grupp);
		});

		if (!s.length) {
			var tom = document.createElement("div");
			tom.className = "tt-sok__tom";
			tom.setAttribute("role", "option");
			tom.setAttribute("aria-disabled", "true");
			tom.textContent = /^\d+$/.test(q) ? "Skriv tre siffror." : "Ingen sida heter så.";
			list.appendChild(tom);
		}

		markera(q === "" ? -1 : 0);
		status.textContent = options.length ? options.length + " förslag" : "Inga förslag";
	}

	function markera(i) {
		if (active >= 0 && options[active]) {
			options[active].setAttribute("aria-selected", "false");
		}
		active = options.length ? Math.max(-1, Math.min(i, options.length - 1)) : -1;
		if (active >= 0) {
			options[active].setAttribute("aria-selected", "true");
			input.setAttribute("aria-activedescendant", options[active].id);
			options[active].scrollIntoView({ block: "nearest" });
		} else {
			input.removeAttribute("aria-activedescendant");
		}
	}

	function oppna() {
		hamtaMestLast();
		rita();
		panel.hidden = false;
		input.setAttribute("aria-expanded", "true");
	}

	function stang() {
		panel.hidden = true;
		input.setAttribute("aria-expanded", "false");
		input.removeAttribute("aria-activedescendant");
		active = -1;
	}

	function ga(url) {
		stang();
		document.documentElement.classList.add("is-loading");
		location.href = url;
	}

	// Det som står kvar i fältet markeras, så att nästa tecken ersätter det.
	input.addEventListener("focus", function () {
		input.select();
		oppna();
	});
	input.addEventListener("click", function () {
		if (panel.hidden) {
			oppna();
		}
	});
	input.addEventListener("input", function () {
		if (panel.hidden) {
			oppna();
		} else {
			rita();
		}
	});
	input.addEventListener("blur", stang);

	input.addEventListener("keydown", function (e) {
		if (e.key === "ArrowDown" || e.key === "ArrowUp") {
			e.preventDefault();
			if (panel.hidden) {
				oppna();
			}
			var n = options.length;
			if (n) {
				markera(e.key === "ArrowDown" ? (active + 1) % n : (active - 1 + n) % n);
			}
		} else if (e.key === "Escape") {
			// Esc stänger listan och tömmer fältet; med tomt fält och stängd
			// lista görs inget, så att Esc når annat på sidan.
			if (!panel.hidden || input.value !== "") {
				e.preventDefault();
				input.value = "";
				stang();
			}
		}
	});

	// Formuläret skickas bara med tre siffror (GET /?number=377). Med något
	// annat i fältet går Enter till det markerade förslaget, eller ingenstans;
	// "xyz" gav annars /?number=xyz och en omdirigering till /0.
	if (form) {
		form.addEventListener("submit", function (e) {
			if (/^\d{3}$/.test(input.value.trim())) {
				return;
			}
			e.preventDefault();
			if (active >= 0 && options[active]) {
				ga(options[active].dataset.url);
			}
		});
	}

	// mousedown i stället för click: blur på fältet stänger annars listan
	// innan klicket kommer fram.
	list.addEventListener("mousedown", function (e) {
		var opt = e.target.closest(".tt-sok__rad");
		e.preventDefault();
		if (opt) {
			ga(opt.dataset.url);
		}
	});

	// På mobil stängs listan inte alltid av blur: Androids bakåtknapp och
	// iOS "dölj tangentbord" tar bort tangentbordet men fältet behåller
	// fokus, och ett tryck på något som inte är en länk tar inte fokus
	// från fältet i Safari. Därför stängs den på tre sätt till:

	// "Stäng ×" i listan (syns bara med pekskärm, se css/tt.css).
	panel.querySelector(".tt-sok__stang button").addEventListener("click", function () {
		input.blur();
		stang();
	});

	// Ett tryck utanför fältet och listan.
	document.addEventListener("pointerdown", function (e) {
		if (!panel.hidden && !panel.contains(e.target) && !(form && form.contains(e.target))) {
			input.blur();
			stang();
		}
	});

	// Tangentbordet döljs: den synliga ytan blir plötsligt mycket högre.
	var vv = window.visualViewport;
	if (vv) {
		var hojd = vv.height;
		vv.addEventListener("resize", function () {
			if (!panel.hidden && document.activeElement === input && vv.height - hojd > 120) {
				input.blur();
				stang();
			}
			hojd = vv.height;
		});
	}

	sparaBesok();
})();
