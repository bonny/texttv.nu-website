<?php

/*
	Sidlistan: de viktigaste Text-TV-sidorna. En lista som både sidomenyn och
	footern visar (footer.php), så att den bara finns på ett ställe i koden.
	På mobil syns den i footern, från 700 px i sidomenyn (css/tt.css).
	Ser ut som Text-TV:s innehållssidor: namn, prickar och sidnummer i cyan.
	Todo #18.

	Varje rad: [adress, namn, sidnummer eller null, underrader].
*/

$sidlista = [
	["/", "Hem", null, []],
	["/101-102,103-105", "Nyheter", "101-105", [
		["/101-103", "Inrikes", "101-103"],
		["/104-105", "Utrikes", "104-105"],
		["/nyheter/igar", "Gårdagens nyheter", null],
	]],
	["/300-302", "Sport", "300-302", [
		["/330", "Resultatbörsen", "330"],
		["/376", "Målservice", "376"],
		["/377", "Målservice, resultat", "377"],
		["/376-395", "Målservice & resultat", "376-395"],
		["/sport/igar", "Gårdagens sportnyheter", null],
		["/377/igar", "Gårdagens resultat", "377"],
	]],
	["/400", "Väder", "400", [
		["/401", "Vädret i dag/i morgon", "401"],
	]],
	["/600,650-656", "TV-tablåer", "600", []],
	["/700", "Innehåll", "700", []],
];

$sidlista_rad = function ($url, $name, $num) {
	return sprintf(
		'<a href="%1$s"><span class="sidlista__namn">%2$s</span>%3$s</a>',
		html_escape($url),
		html_escape($name),
		$num ? '<span class="sidlista__fyll" aria-hidden="true"></span><span class="sidlista__num">' . html_escape($num) . '</span>' : ''
	);
};
?>
<ul class="sidlista">
	<?php foreach ($sidlista as $item) { ?>
		<li>
			<?php echo $sidlista_rad($item[0], $item[1], $item[2]) ?>
			<?php if ($item[3]) { ?>
				<ul>
					<?php foreach ($item[3] as $sub) { ?>
						<li><?php echo $sidlista_rad($sub[0], $sub[1], $sub[2]) ?></li>
					<?php } ?>
				</ul>
			<?php } ?>
		</li>
	<?php } ?>
</ul>
