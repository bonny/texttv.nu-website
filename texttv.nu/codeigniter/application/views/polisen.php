<div class="textcontent">
	
	<div class="most-shared-section-wrap clearfix">
	
		<h1>SVT Text TV i händelser från Polisen</h1>
		
		<p>Ibland förekommer nyheter som Text TV skriver om i Polisens händelserapporter.</p>
		<p>TextTV.nu har ett samarbete med <a href="https://brottsplatskartan.se">Brottsplatskartan.se</a> där
		Brottsplatskartan på vissa händelser länkar till texttv.nu. De senaste händelserna listas nedan:</p>
		</p>

		<?php

		// Inget datum, visa för idag + igår
		echo "<div class='brottsplatskartan-events-wrapper'>";
		// echo '<h2>Mest delat idag</h2>';
		// $result = get_shared_pages_for_period(strtotime("today"), time());
		// output_shared_pages($result);
		echo "</div>";
		
		/*
		echo "<div class='most-shared-section most-shared-section--yesterday'>";	
		echo '<h2>Mest delat igår</h2>';
		$result = get_shared_pages_for_period(strtotime("yesterday 00:00"), strtotime("yesterday 24:00"));
		output_shared_pages($result);
		echo "</div>";
		*/	
		
		?>
	</div>
		
</div>

<script>
	window.addEventListener("load", function(event) {
 		const eventsWrappers = document.querySelectorAll('.brottsplatskartan-events-wrapper');
		const api = 'https://brottsplatskartan.se/api/eventsInMedia?media=texttv&callback=?';
		
		function addDataToWrapper(wrapper, data) {
			var html = '';
			
			for (var i = 0; i < data.length; i++) {
				var event = data[i];
				html += `
						<h2><a href="${event.permalink}">${event.description}</a></h2>
						<p>${event.content_teaser}</p>
						<p><a href="${event.permalink}"><img src="${event.image}" alt=""/></a></p>
					`;
			}

			wrapper.innerHTML = html;
		}
		
		jQuery.getJSON(api).done(function(jsondata) {
			var data = jsondata.data;

			for (var i = 0; i < eventsWrappers.length; i++) {
				addDataToWrapper(eventsWrappers[i], data);
			}
		});
	});	
</script>
