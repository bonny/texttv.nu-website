<?php

/*
	Länkar från en levande sida till gårdagen: sidans dagsarkiv (/377/igar)
	och avdelningens dagslista (/nyheter/igar, /sport/igar). Startsidan får
	alla, och dessutom "Mest läst" med flikarna Idag, Igår och 7 dagar
	(yesterday_top_news(), today_top_news() och week_top_news()). Listan byggs av Sida::visa()
	och skrivs ut av pages_inner_output_current.php direkt under sidan.
	Todo #16 och #25.

	Byggd av komponenterna i css/tt.css (todo #18): tt-sidbredd, tt-band,
	tt-list och tt-linkrow.

	Flikarna (todo #25): alla listorna finns i HTML:en, så att nginx-cachen
	och Google ser samma sida. Utan JS syns "Mest läst igår" som förut. Knappar
	med aria-pressed, inte ARIA-tabs, eftersom ← → redan byter sida
	(js/scripts.js).
*/

?>
<?php
// På startsidan står länkarna mellan sidorna, som är inline-block och står
// bredvid varandra på bred skärm. En vanlig blocknav skulle bryta raden, så
// där blir länkarna en egen ruta i raden, lika bred som en sida.
$inline = !empty($inline);
$yesterday_top = !empty($yesterday_top) ? $yesterday_top : [];
$today_top = !empty($today_top) ? $today_top : [];
$today_links = !empty($today_links) ? $today_links : [];
$week_top = !empty($week_top) ? $week_top : [];
$flikar = $yesterday_top && $today_links;

$lista = function ($items) {
	?>
	<ol class="tt-list">
		<?php foreach (array_values($items) as $i => $item) { ?>
			<li>
				<span class="tt-list__meta" aria-hidden="true"><?php echo $i + 1 ?></span>
				<a href="<?php echo html_escape($item["permalink"]) ?>"><?php echo html_escape($item["title"]) ?></a>
			</li>
		<?php } ?>
	</ol>
	<?php
};
$lankrad = function ($links) {
	?>
	<div class="tt-linkrow">
		<?php foreach ($links as $link) { ?>
			<a href="<?php echo html_escape($link[0]) ?>"><?php echo html_escape($link[1]) ?> →</a>
		<?php } ?>
	</div>
	<?php
};
?>
<?php if ( ! $flikar) { ?>
<nav class="tt-sidbredd day-archive-link<?php echo $inline ? " day-archive-link--inline" : "" ?>" aria-label="Dagslistor">
	<?php if ($yesterday_prefix) { ?>
		<span class="tt-band"><?php echo html_escape($yesterday_prefix) ?></span>
	<?php } ?>
	<?php if ($yesterday_top) { $lista($yesterday_top); } ?>
	<?php $lankrad($yesterday_links) ?>
</nav>
<?php } else { ?>
<nav class="tt-sidbredd day-archive-link mest-last<?php echo $inline ? " day-archive-link--inline" : "" ?>" aria-label="Mest läst">
	<div class="tt-band tt-band--flikar">
		<span class="mest-last__rubrik">Mest läst<span class="mest-last__igar"> igår</span></span>
		<span class="tt-flikar" hidden>
			<button type="button" class="tt-flik" data-flik="idag" aria-controls="mest-last-idag" aria-pressed="false">Idag</button>
			<button type="button" class="tt-flik" data-flik="igar" aria-controls="mest-last-igar" aria-pressed="true">Igår</button>
			<button type="button" class="tt-flik" data-flik="vecka" aria-controls="mest-last-vecka" aria-pressed="false">7 dagar</button>
		</span>
	</div>
	<div class="mest-last__paneler">
		<div class="mest-last__panel" id="mest-last-igar" data-flik="igar">
			<?php $lista($yesterday_top) ?>
			<?php $lankrad($yesterday_links) ?>
		</div>
		<div class="mest-last__panel is-dold" id="mest-last-idag" data-flik="idag">
			<?php if ($today_top) { $lista($today_top); } ?>
			<?php // Tidigt på dagen finns bara några få (todo #25). ?>
			<?php if (count($today_top) < 3) { ?>
				<p class="mest-last__tunt"><?php echo $today_top ? "Fler läses under dagen." : "Inga nyheter har lästs än idag." ?></p>
			<?php } ?>
			<?php $lankrad($today_links) ?>
		</div>
		<div class="mest-last__panel is-dold" id="mest-last-vecka" data-flik="vecka">
			<?php if ($week_top) { $lista($week_top); } else { ?>
				<p class="mest-last__tunt">Inga nyheter har lästs de senaste sju dagarna.</p>
			<?php } ?>
			<?php $lankrad($today_links) ?>
		</div>
	</div>
</nav>
<script>
// Flikarna i "Mest läst" (todo #25). Förval: Igår före kl 10 och Idag
// därefter, eller det användaren själv valt senast. Händelser till GA4:
// most_read_tab vid byte, select_content vid klick i en lista.
(function () {
	var ruta = document.currentScript.previousElementSibling;
	var knappar = ruta.querySelectorAll(".tt-flik");
	var paneler = ruta.querySelectorAll(".mest-last__panel");
	var nyckel = "tt-mest-last";
	var hamta = function () {
		try { return localStorage.getItem(nyckel); } catch (e) { return null; }
	};
	var spara = function (flik) {
		try { localStorage.setItem(nyckel, flik); } catch (e) {}
	};
	var ga = function (namn, data) {
		if (typeof gtag === "function") {
			gtag("event", namn, data);
		}
	};
	var vald = "igar";
	var visa = function (flik) {
		vald = flik;
		knappar.forEach(function (k) {
			k.setAttribute("aria-pressed", k.dataset.flik === flik ? "true" : "false");
		});
		paneler.forEach(function (p) {
			p.classList.toggle("is-dold", p.dataset.flik !== flik);
		});
	};

	var timme = Number(new Intl.DateTimeFormat("sv-SE", { hour: "numeric", hourCycle: "h23", timeZone: "Europe/Stockholm" }).format(new Date()));
	var sparad = hamta();
	visa(["idag", "igar", "vecka"].indexOf(sparad) !== -1 ? sparad : (timme < 10 ? "igar" : "idag"));
	// Rubriken blir "Mest läst"; fliken säger vilken dag.
	ruta.classList.add("mest-last--js");
	ruta.querySelector(".tt-flikar").hidden = false;

	knappar.forEach(function (k) {
		k.addEventListener("click", function () {
			if (k.dataset.flik === vald) {
				return;
			}
			visa(k.dataset.flik);
			spara(vald);
			ga("most_read_tab", { tab: vald });
		});
	});
	paneler.forEach(function (p) {
		p.addEventListener("click", function (e) {
			var a = e.target.closest(".tt-list a");
			if (!a) {
				return;
			}
			var rader = Array.prototype.slice.call(p.querySelectorAll(".tt-list a"));
			ga("select_content", { content_type: "most_read", tab: p.dataset.flik, position: rader.indexOf(a) + 1 });
		});
	});
})();
</script>
<?php } ?>
