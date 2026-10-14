<?php

/*
	Länkar från en levande sida till gårdagen: sidans dagsarkiv (/377/igar)
	och avdelningens dagslista (/nyheter/igar, /sport/igar). Startsidan får
	alla. Listan byggs av Sida::visa() och skrivs ut av
	pages_inner_output_current.php direkt under sidan. Todo #16.
*/

?>
<nav class="day-archive-link" aria-label="Gårdagen">
	<?php if ($yesterday_prefix) { ?>
		<span class="day-archive-link__label"><?php echo html_escape($yesterday_prefix) ?></span>
	<?php } ?>
	<?php foreach ($yesterday_links as $link) { ?>
		<a href="<?php echo html_escape($link[0]) ?>"><?php echo html_escape($link[1]) ?> →</a>
	<?php } ?>
</nav>
<style>
	.day-archive-link {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		align-items: center;
		gap: .5em;
		margin: 1em auto;
		max-width: 60ch;
	}
	.day-archive-link a {
		display: inline-block;
		padding: .35em .7em;
		border: 1px solid #ffff00;
		color: #ffff00;
		text-decoration: none;
	}
	.day-archive-link a:hover,
	.day-archive-link a:focus {
		background: #ffff00;
		color: #000;
	}
	.day-archive-link__label {
		width: 100%;
		text-align: center;
	}
</style>
