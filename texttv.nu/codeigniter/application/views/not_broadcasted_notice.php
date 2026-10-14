<?php

/*
	Förklaring ovanför en sida som inte sänds just nu, där Sida::visa() visar
	den senast sända versionen i stället för "Sidan ej i sändning". Todo #16.
*/

$nb = $not_broadcasted;

?>
<div class="not-broadcasted-notice">
	<p>
		<strong>Sidan sänds inte just nu.</strong>
		Här är den senast sända versionen, från <?php echo html_escape($nb["broadcast_human"]) ?>.
		<?php if ($nb["weekdays_human"]) { ?>
			Sidan brukar sändas <?php echo html_escape($nb["weekdays_human"]) ?>,
			och visas här direkt när SVT sänder den igen.
		<?php } else { ?>
			Den visas här direkt när SVT sänder den igen.
		<?php } ?>
	</p>
</div>
<style>
	.not-broadcasted-notice {
		max-width: 40ex;
		margin: 1em auto;
		padding: .5em .75em;
		border: 1px solid #ffff00;
	}
	.not-broadcasted-notice p {
		margin: 0;
	}
</style>
