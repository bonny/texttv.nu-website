<?php

/*
	Dagsarkiv: sidans sista version en viss dag, plus länkar till dagens
	tidigare versioner. Laddas av Sida::dag() för /377/igar och
	/377/2026-10-12. Todo #16.
*/

$d = $day_archive;
$e = function ($str) {
	return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
};
$num = (int) $page->num;
$version_count = sizeof($d["version_links"]);
$last_time = $d["version_links"][0]["time"];

?>
<style>
	/* .page-is-archive nollar #pages marginal, och vanliga arkivsidor lägger
	   tillbaka den på .introtext. Samma avstånd under den fasta menyraden. */
	.page-is-day-archive #pages.day-archive {
		margin-top: 95px;
	}
	.day-archive > h1,
	.day-archive__date,
	.day-archive__nav,
	.day-archive__intro,
	.day-archive__versions {
		max-width: 40ex;
		margin-left: auto;
		margin-right: auto;
	}
	.day-archive > h1,
	.day-archive__date {
		text-align: center;
	}
	.day-archive__date {
		margin-top: 0;
		margin-bottom: .5em;
	}
	.day-archive__nav {
		display: flex;
		justify-content: space-between;
		gap: 1em;
		margin-top: .5em;
		margin-bottom: 1em;
	}
	.day-archive__versions ul {
		list-style: none;
		padding: 0;
		display: flex;
		flex-wrap: wrap;
		gap: .25em .75em;
	}
</style>
<main id="pages" class="day-archive">
	<h1><?php echo $e($d["headline"]) ?></h1>
	<p class="day-archive__date"><?php echo $e(ucfirst($d["date_human"])) ?></p>

	<nav class="day-archive__nav" aria-label="Bläddra mellan dagar">
		<a href="<?php echo $e($d["prev_url"]) ?>">← <?php echo $e($d["prev_human"]) ?></a>
		<a href="<?php echo $e($d["next_url"]) ?>"><?php echo $e($d["next_human"]) ?> →</a>
	</nav>

	<p class="day-archive__intro">
		Så här såg <a href="/<?php echo $num ?>">SVT Text TV <?php echo $num ?></a>
		ut när dagen var slut, senast uppdaterad kl <?php echo $e($last_time) ?>.
		<?php if ($version_count > 1) { ?>
			Sidan uppdaterades <?php echo $version_count ?> gånger under dagen.
		<?php } ?>
	</p>

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
	<section class="day-archive__versions">
		<h2>Alla versioner av <?php echo $num ?> <?php echo $e($d["date_human"]) ?></h2>
		<ul>
			<?php foreach ($d["version_links"] as $one_version) { ?>
				<li><a href="<?php echo $e($one_version["permalink"]) ?>"><?php echo $e($one_version["time"]) ?></a></li>
			<?php } ?>
		</ul>
	</section>
<?php } ?>
