<?php

/*
	Förklaring ovanför en sida som inte sänds just nu. Antingen visar
	Sida::visa() den senast sända versionen i stället för "Sidan ej i
	sändning" ($nb["page"] satt), eller så har sidan inte sänts på länge och
	bara datumet visas. Todo #16.
*/

$nb = $not_broadcasted;

?>
<div class="not-broadcasted-notice">
	<?php if (!$nb["page"]) { ?>
	<p>
		<strong>SVT har inte sänt sidan sedan <?php echo html_escape($nb["off_air_since_human"]) ?>.</strong>
		Den visas här igen om SVT börjar sända den.
		<?php if ($nb["last_permalink"]) { ?>
			<a href="<?php echo html_escape($nb["last_permalink"]) ?>">Se sidan som den såg ut senast den sändes.</a>
		<?php } ?>
	</p>
	<?php } else { ?>
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
	<?php } ?>
</div>
<style>
	/* Sidor utan brödsmulor (t.ex. 550) har inget som trycker ner innehållet
	   under den fasta menyn; samma avstånd som arkivsidornas .introtext. */
	.not-broadcasted-notice {
		max-width: 40ex;
		margin: 95px auto 1em;
		padding: .5em .75em;
		border: 1px solid #ffff00;
	}
	.breadcrumbs ~ .not-broadcasted-notice {
		margin-top: 1em;
	}
	.not-broadcasted-notice p {
		margin: 0;
	}
</style>
