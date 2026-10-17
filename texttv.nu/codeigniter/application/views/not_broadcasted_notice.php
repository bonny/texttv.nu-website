<?php

/*
	Förklaring ovanför en sida som inte sänds just nu. Antingen visar
	Sida::visa() den senast sända versionen i stället för "Sidan ej i
	sändning" ($nb["page"] satt), eller så har sidan inte sänts på länge och
	bara datumet visas. Todo #16.
*/

$nb = $not_broadcasted;

// Inte i delningsbilderna (apiAppShare), där finns varken meny eller plats.
if ($this->input->get("apiAppShare")) {
	return;
}

?>
<div class="not-broadcasted-notice tt-sidbredd">
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
