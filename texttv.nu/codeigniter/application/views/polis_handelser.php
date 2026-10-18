<?php
/*
	"Polisen rapporterade": polishändelser som Brottsplatskartan kopplat till
	nyheten på sidan (bpk_handelser_for_titel()). Under levande nyhetssidor
	(100–199) och arkivsidor. Todo #21.

	$polis_handelser: array med typ, plats, tid_iso8601 och url.
*/
if (empty($polis_handelser)) {
	return;
}
?>
<aside class="tt-sidbredd tt-polis" aria-label="Polisens händelse">
	<p class="tt-polis__rubrik">Polisen rapporterade</p>
	<ul class="tt-polis__lista">
		<?php foreach ($polis_handelser as $h) {
			$lank = bpk_lank($h["url"], "polisruta");
			if (!$lank) {
				continue;
			}
			$tid = strtotime($h["tid_iso8601"]);
			?>
			<li>
				<a href="<?php echo html_escape($lank) ?>"><?php echo html_escape(trim($h["typ"] . ", " . $h["plats"], ", ")) ?> →</a>
				<?php if ($tid) { ?>
					<span class="tt-polis__tid"><time datetime="<?php echo html_escape(date("c", $tid)) ?>"><?php echo html_escape(date_sv("D j M", $tid) . " " . date("H:i", $tid)) ?></time> · Brottsplatskartan</span>
				<?php } ?>
			</li>
		<?php } ?>
	</ul>
</aside>
