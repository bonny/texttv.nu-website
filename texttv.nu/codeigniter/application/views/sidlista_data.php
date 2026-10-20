<?php

/*
	Sidorna i sidlistan (views/sidlista.php: sidomenyn och footern) och i
	förslagen under "Gå till sida" (views/controls.php, js/tt-sok.js).
	Todo #18.

	lista:     [adress, namn, sidnummer eller null, underrader]
	fler:      sidor som bara finns med i sökningen (rubrikerna i
	           pages_inner_output_current.php)
	populara:  "Populära sidor" när sökfältet är tomt, efter klicken i
	           Search Console 2026-09-17–10-14 (377, startsidan, 358, 300)
*/

return [
	"lista" => [
		["/", "Hem", null, []],
		// SVT:s egen startsida heter "Start" på 100 (Pär 2026-10-18).
		["/100", "Start", "100", []],
		["/101-102,103-105", "Nyheter", "101-105", [
			["/101-103", "Inrikes", "101-103"],
			["/104-105", "Utrikes", "104-105"],
			["/nyheter/idag", "Dagens nyheter", null],
			["/nyheter/igar", "Gårdagens nyheter", null],
		]],
		["/300-302", "Sport", "300-302", [
			["/330", "Resultatbörsen", "330"],
			["/376", "Målservice", "376"],
			["/377", "Målservice, resultat", "377"],
			["/376-395", "Målservice & resultat", "376-395"],
			["/sport/idag", "Dagens sportnyheter", null],
			["/sport/igar", "Gårdagens sportnyheter", null],
			["/377/igar", "Gårdagens 377", null], // Inte "377" i nummerspalten: den är dagens sida.
		]],
		["/400", "Väder", "400", [
			["/401", "Vädret i dag/i morgon", "401"],
		]],
		["/600,650-656", "TV-tablåer", "600", []],
		["/700", "Innehåll", "700", []],
		["/sida/senast-uppdaterat", "Senast uppdaterat", null, []],
	],
	"fler" => [
		["/336", "Premier League", "336"],
		["/358", "SHL tabell", "358"],
		["/360", "Ishockey Allsvenskan", "360"],
		["/378", "Målservice utländska ligor", "378"],
		["/379", "Matchfakta", "379"],
		["/551", "Stryktipset", "551"],
		["/571", "V75-resultat", "571"],
		// Inte i menyn: Mest läst på startsidan och dagssidorna svarar på
		// samma fråga med mycket mer underlag (2026-10-20).
		["/sida/delat", "Mest delat", null],
	],
	"populara" => ["377", "100", "358", "300", "330", "101"],
];
