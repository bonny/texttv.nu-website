<?php

/*
	Dagslista: alla nyheter eller sportnyheter en dag, mest lästa först.
	Laddas av Dagsnyheter::visa() för /nyheter/igar, /sport/igar och
	datumvarianterna. Todo #16.
*/

$d = $day_headlines;
$e = 'html_escape';

$render_list = function ($items, $ordered = false) use ($e) {
	$tag = $ordered ? "ol" : "ul";
	$out = "<{$tag} class=\"day-list__items\">";
	foreach ($items as $item) {
		$out .= sprintf(
			'<li><span class="day-list__time">%1$s</span> <a href="%2$s">%3$s</a> <span class="day-list__num">%4$d</span></li>',
			$e($item["time"]),
			$e($item["permalink"]),
			$e($item["title"]),
			$item["page_num"]
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
<style>
	.page-is-day-archive #pages.day-list {
		margin-top: 95px;
	}
	.day-list {
		max-width: 52ch;
		margin-left: auto;
		margin-right: auto;
		padding: 0 1ch;
		/* #pages centrerar texten; en lista läses lättare vänsterställd. */
		text-align: left;
	}
	.day-list__date {
		margin-top: 0;
	}
	.day-list__nav {
		display: flex;
		justify-content: space-between;
		gap: 1em;
		margin: .5em 0 1em;
	}
	.day-list h2 {
		color: #ffff00;
		margin: 1.5em 0 .5em;
	}
	.day-list__items {
		padding: 0;
		margin: 0;
	}
	ul.day-list__items {
		list-style: none;
	}
	ol.day-list__items {
		padding-left: 3ch;
	}
	.day-list__items li {
		margin: .3em 0;
	}
	.day-list__time {
		color: #00ffff;
	}
	.day-list__num {
		opacity: .6;
	}
	.day-list__related {
		margin-top: 1.5em;
	}
</style>
<main id="pages" class="day-list">
	<h1><?php echo $e($d["headline"]) ?></h1>
	<p class="day-list__date"><?php echo $e(ucfirst($d["date_human"])) ?></p>

	<nav class="day-list__nav" aria-label="Bläddra mellan dagar">
		<?php if ($d["prev_url"]) { ?>
			<a href="<?php echo $e($d["prev_url"]) ?>">← <?php echo $e($d["prev_human"]) ?></a>
		<?php } else { ?>
			<span></span>
		<?php } ?>
		<a href="<?php echo $e($d["next_url"]) ?>"><?php echo $e($d["next_human"]) ?> →</a>
	</nav>

	<p>
		<?php echo $d["headline_count"] ?> rubriker sändes på SVT Text TV den här dagen.
		Tiden är när nyheten först sändes, och varje rubrik leder till nyheten
		som den såg ut då.
	</p>

	<?php if ($d["results_links"]) { ?>
		<p class="day-list__results">
			Resultat:
			<?php
			echo implode(" · ", array_map(function ($link) use ($e) {
				return sprintf('<a href="%1$s">%2$s %3$d</a>', $e($link["url"]), $e($link["label"]), (int) $link["num"]);
			}, $d["results_links"]));
			?>
		</p>
	<?php } ?>

	<?php if ($d["most_read"]) { ?>
		<h2>Mest lästa</h2>
		<?php echo $render_list($d["most_read"], true) ?>
	<?php } ?>

	<?php foreach ($d["groups"] as $group_name => $items) { ?>
		<h2><?php echo $e($group_name) ?></h2>
		<?php echo $render_list($items) ?>
	<?php } ?>

	<p class="day-list__related">
		<a href="<?php echo $e($d["other_url"]) ?>"><?php echo $e($d["other_label"]) ?> →</a>
	</p>
</main>
<script type="application/ld+json"><?php echo json_encode($json_ld, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
