<?php

if (! isset($prev_blog_posts)) {
	return;
}

?>

<section class="PrevBlogPostsNav">
	<h3 class="PrevBlogPostsNav-title">Alla blogginlägg</h3>

	<?php

	foreach ($prev_blog_posts->result() as $row) {
		printf(
			'
			<article class="PrevBlogPostsNav-article">
				<p class="PrevBlogPostsNav-article-date meta">%3$s</p>
				<p class="PrevBlogPostsNav-article-title"><a href="/blogg/%1$s">%2$s</a></p>
			</article>
		',
			$row->permalink,
			$row->title,
			date('Y-m-d', $row->date_published_unix),
		);
	}

	?>
</section>
