<?php
if ($this->input->get("apiAppShare")) {
	return;
}

/**
 * Work in progress.
 * Not released yet.
 */
return;

// Hämta mest lästa sidorna.
$pages = get_most_read_pages_for_period();

// If empty fallback to most recently updated.
if ($pages->num_rows() == 0) {
	$pages = get_latest_updated_pages(100, 200);
}

?>
<style>
	/**
	 * Create a nice overthumb thumb listing of all pages.
	 * Most users are on mobile, so show in 2 column grid.
	 * Pages should be zoomed out and only the first part of the page should be shown.
	 * Create nice zoom effect on hover.
	 * Rest of page should be faded out.
	 */
	.pageThumbList {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 1rem;
		padding: 1rem;
	}

	.pageThumbList-item {
		zoom: .5;
	}
</style>
<section class="pageThumbList">
	<?php
	// Loop through the result and output the pages.
	foreach ($pages->result() as $row) {
		$page_content = unserialize($row->page_content);
		$first_page = $page_content[0];
	?>
		<article class="TextTVPage pageThumbList-item">
			<!-- <a href="/<?= $row->page_num ?>" class="TextTV-pageThumbLink">
			</a> -->

			<div class="TextTV-page">
				<?php
				echo $first_page;
				?>
			</div>
		</article>
	<?php
	}
	?>
</section>