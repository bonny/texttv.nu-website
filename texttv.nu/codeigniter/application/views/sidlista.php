<?php

/*
	Sidlistan: de viktigaste Text-TV-sidorna. En lista som både sidomenyn och
	footern visar (footer.php), så att den bara finns på ett ställe i koden.
	På mobil syns den i footern, från 700 px i sidomenyn (css/tt.css).
	Ser ut som Text-TV:s innehållssidor: namn, prickar och sidnummer i cyan.
	Todo #18.

	Sidorna ligger i views/sidlista_data.php.
*/

$sidlista = (include __DIR__ . "/sidlista_data.php")["lista"];

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
