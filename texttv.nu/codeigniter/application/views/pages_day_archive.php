<?php

/*
	Dagsarkiv: sidans sista version en viss dag, plus länkar till dagens
	tidigare versioner. Laddas av Sida::dag() för /377/igar och
	/377/2026-10-12. Todo #16.
*/

$d = $day_archive;
$e = 'html_escape';
$num = (int) $page->num;
$version_count = sizeof($d["version_links"]);

// Sista versionen kan vara från en tidigare dag om sidan inte ändrades.
$final_time = substr($d["final_date_added"], 11, 5);
$final_is_other_day = $version_count === 0;
$final_date_human = date_sv("l j F", strtotime(substr($d["final_date_added"], 0, 10) . " 12:00"));

?>
<main id="pages" class="day-archive">
	<div class="tt-sidbredd tt-dag">
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
		Så här såg SVT Text TV <?php echo $num ?> ut när dagen var slut.
		<?php if ($final_is_other_day) { ?>
			Ingen ny version den här dagen. Senaste versionen med innehåll är från <?php echo $e($final_date_human) ?> kl <?php echo $e($final_time) ?>.
		<?php } elseif ($version_count > 1) { ?>
			Senast uppdaterad kl <?php echo $e($final_time) ?>, och sidan uppdaterades <?php echo $version_count ?> gånger under dagen.
		<?php } else { ?>
			Senast uppdaterad kl <?php echo $e($final_time) ?>.
		<?php } ?>
	</p>
	</div>

	<div><?php echo $page->get_output() ?></div>
</main>

<?php if (!$this->input->get("apiAppShare")) { ?>
	<!-- texttv.nu efter sida -->
	<div class="ad ad--before-latest">
		<p class="ad-header">Annons</p>
		<ins class="adsbygoogle"
				style="display:block"
				data-ad-client="ca-pub-1689239266452655"
				data-ad-slot="8021374801"
				data-ad-format="horizontal"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
	</div>
<?php } ?>

<?php if ($version_count > 1) { ?>
	<section class="tt-sidbredd tt-dag__versioner">
		<h2 class="tt-band">Alla versioner av <?php echo $num ?> <?php echo $e($d["date_human"]) ?></h2>
		<ul class="tt-linkrow">
			<?php foreach ($d["version_links"] as $one_version) { ?>
				<li><a href="<?php echo $e($one_version["permalink"]) ?>" aria-label="<?php echo $e($num . " kl " . $one_version["time"]) ?>"><?php echo $e($one_version["time"]) ?></a></li>
			<?php } ?>
		</ul>
	</section>
<?php } ?>
