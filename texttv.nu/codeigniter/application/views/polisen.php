<div class="textcontent">
	
	<div class="most-shared-section-wrap clearfix">
	
		<h1>SVT Text TV i händelser från Polisen</h1>
		
		<p>Ibland förekommer nyheter som Text TV skriver om i Polisens händelserapporter.</p>
		<p>TextTV.nu har ett samarbete med <a href="https://brottsplatskartan.se">Brottsplatskartan.se</a> där
		Brottsplatskartan på vissa händelser länkar till texttv.nu. De senaste händelserna listas nedan:</p>
		</p>

		<?php
				
		echo "<div class='brottsplatskartan-events-wrapper'>";

		foreach ( $events_in_media->data as $one_event ) {
			?>
			<h2><a href="<?php echo $one_event->permalink ?>"><?php echo html_escape( $one_event->description ) ?></a></h2>
			<p><?php echo html_escape( $one_event->content_teaser ) ?></p>
			<p><a href="<?php echo $one_event->permalink ?>"><img src="<?php echo html_escape( $one_event->image ) ?>" alt=""/></a></p>
			<?php
		}

		echo "</div>";
				
		?>
	</div>
		
</div>
