<?php

/*
	Dagslista: alla nyheter eller sportnyheter en dag, mest lästa först.
	Laddas av Dagsnyheter::visa() för /nyheter/igar, /sport/igar och
	datumvarianterna. Todo #16.
*/

$d = $day_headlines;
$e = 'html_escape';

// Ankare för hopplänkarna: "Kultur och övrigt" → "kultur-och-ovrigt".
$anchor = function ($name) {
	return str_replace(["å", "ä", "ö", " "], ["a", "a", "o", "-"], mb_strtolower($name));
};

$render_list = function ($items, $ordered = false) use ($e) {
	$tag = $ordered ? "ol" : "ul";
	$out = "<{$tag} class=\"tt-list day-list__items\">";
	foreach ($items as $item) {
		// "Karta →" när Brottsplatskartan kopplat en polishändelse till
		// nyheten (todo #21). Bara den första, listan ska vara tät.
		$karta = "";
		$handelser = bpk_handelser_for_titel($item["title"]);
		if ($handelser) {
			$lank = bpk_lank($handelser[0]["url"], "dagslista");
			if ($lank) {
				$karta = sprintf(
					' <a class="day-list__karta" href="%1$s" aria-label="%2$s">Karta →</a>',
					$e($lank),
					$e("Polisens händelse på Brottsplatskartan: " . trim($handelser[0]["typ"] . ", " . $handelser[0]["plats"], ", "))
				);
			}
		}
		$out .= sprintf(
			// Sidnumret i en egen kolumn till höger, som på Text TV. Efter
			// rubriken hoppade det mellan radslut och ny rad.
			'<li><span class="tt-list__meta">%1$s</span><span class="day-list__rubrik"><a href="%2$s">%3$s</a>%5$s</span><span class="day-list__num">%4$d</span></li>',
			$e($item["time"]),
			$e($item["permalink"]),
			$e($item["title"]),
			$item["page_num"],
			$karta // 5
		);
	}
	return $out . "</{$tag}>";
};

// Strukturerad data: sidan är en samling med en lista av nyheter.
$list_items = [];
$position = 1;
foreach ($d["groups"] as $items) {
	foreach ($items as $item) {
		$list_items[] = [
			"@type" => "ListItem",
			"position" => $position++,
			"url" => "https://texttv.nu" . $item["permalink"],
			"name" => $item["title"],
		];
	}
}
$json_ld = [
	"@context" => "https://schema.org",
	"@type" => "CollectionPage",
	"name" => $d["headline"],
	"url" => $d["canonical"],
	"inLanguage" => "sv",
	"mainEntity" => [
		"@type" => "ItemList",
		"numberOfItems" => sizeof($list_items),
		"itemListElement" => $list_items,
	],
];

?>
<main id="pages" class="day-list tt-sidbredd tt-dag">
	<?php // Datumet står i rubriken, så det står inte en gång till under. Todo #18. ?>
	<h1 class="tt-band"><?php echo $e($d["headline"]) ?></h1>

	<nav class="tt-linkrow tt-dag__nav" aria-label="Bläddra mellan dagar">
		<?php if ($d["prev_url"]) { ?>
			<a href="<?php echo $e($d["prev_url"]) ?>">← <?php echo $e($d["prev_human"]) ?></a>
		<?php } else { ?>
			<span></span>
		<?php } ?>
		<a href="<?php echo $e($d["next_url"]) ?>"><?php echo $e($d["next_human"]) ?> →</a>
	</nav>

	<p class="tt-dag__intro">
		<?php echo $d["headline_count"] ?> nyheter sändes för första gången på
		SVT Text TV den här dagen. Tiden är när nyheten först sändes, och varje
		rubrik leder till nyhetens sista version den dagen.
	</p>

	<?php if (sizeof($d["groups"]) > 1) { ?>
		<p class="tt-dag__hopp">
			<span class="tt-list__meta">Hoppa till:</span>
			<?php
			echo implode(" · ", array_map(function ($name) use ($e, $anchor) {
				return sprintf('<a href="#%1$s">%2$s</a>', $e($anchor($name)), $e($name));
			}, array_keys($d["groups"])));
			?>
		</p>
	<?php } ?>

	<?php if ($d["results_links"]) { ?>
		<p class="tt-dag__hopp">
			<span class="tt-list__meta">Resultat:</span>
			<?php
			echo implode(" · ", array_map(function ($link) use ($e) {
				return sprintf('<a href="%1$s">%2$s %3$d</a>', $e($link["url"]), $e($link["label"]), (int) $link["num"]);
			}, $d["results_links"]));
			?>
		</p>
	<?php } ?>

	<?php if ($d["most_read"]) { ?>
		<h2 class="tt-dag__h2 tt-band">Mest läst</h2>
		<?php echo $render_list($d["most_read"], true) ?>
	<?php } ?>

	<?php foreach ($d["groups"] as $group_name => $items) { ?>
		<h2 class="tt-dag__h2" id="<?php echo $e($anchor($group_name)) ?>"><?php echo $e($group_name) ?></h2>
		<?php echo $render_list($items) ?>
	<?php } ?>

	<p class="tt-dag__vidare">
		<a class="tt-link" href="<?php echo $e($d["other_url"]) ?>"><?php echo $e($d["other_label"]) ?> →</a>
	</p>
</main>
<script type="application/ld+json"><?php echo json_encode($json_ld, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
