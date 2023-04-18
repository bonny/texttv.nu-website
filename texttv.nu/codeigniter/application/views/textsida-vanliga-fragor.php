<div class="textcontent">
	<div itemscope itemtype="https://schema.org/FAQPage">
		<!--
		FAQ (FAQPage, Question, Answer) structured data
		https://developers.google.com/search/docs/appearance/structured-data/faqpage
		
		Rich Results Test
		https://search.google.com/test/rich-results
		-->
		
		<h1>Vanliga frågor om Text TV</h1>
		
		<?php
		
		function faq_slugify($string) {
			$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-åäöÅÄÖ]+/', '-', $string)));
			$slug = trim($slug, '-');
			return $slug;
		}
		
		$questions = [
				[
				'question' => 'Vad är Text-TV?',
				'answer' => "
					<p>
					Text TV är namnet på den teknik som gör att det går att skicka text och enkel grafik
					till TV-apparater som har en text-tv-funktion inbyggd.
					</p>
					<p>Det är upp till varje kanal att bestämma vad som ska visas på deras text tv.</p>
					<p>Vanligt är att tekniken används för att visa nyheter, sportresultat, väder, och tv-tablåer. Det är också vanligt att text tv används för att visa undertexter till TV-program, något som används av t.ex. hörselskadade.</p>
					<p>Sedan 1980-talet har i princip alla TV-apparater denna funktion inbyggd.</p>
				"
			],
			[
				'question' => 'Finns text-tv kvar fortfarande?',
				'answer' => "
					<p>Ja, Sverige Television sänder fortfarande t.ex. nyheter och sport på text tv.</p>
					<p>Du som har en TV med livesänd tv kan se text tv direkt på din tv (se din manual för)
					information om hur du aktiverar funktionen), annars finns text tv att tillgå både på <a href='/'>internet</a> och <a href='https://texttv.nu/sida/vanliga-fragor#vilken-text-tv-app-%C3%A4r-b%C3%A4st'>som app</a>.
					</p>
					<p>Så än så länge finns text tv kvar, och vi hoppas att det kommer fortsätta vara så länge till! :)</p>
				"
			],
			[
				"question" => "Vilket år kom text tv?",
				"answer" => "
					<p>I Sverige lanserades SVT Text TV <em>12 mars 1979</em>.</p>
					<p>Sverige var det andra landet i världen med tjänsten.</p>
					<p>Först i världen var BBC som 1972 lanserade sin tjänst <em>Ceefax</em>.</p>
				",
			],
			[
				"question" => "Hur många tittar på SVT text tv?",
				"answer" => "
					<p>När SVT Text TV var som störst på 1990-talet så hade tjänsten en daglig publik på 
					cirka 3 miljoner.</p>
					<p>2017 var den dagliga publiken nere på 2 miljoner.</p>
					<p>Hur många som år 2022 använder text tv har vi tyvärr inga siffror på.</p>
				",
			],
			[
				"question" => "Finns inte börskurser längre?",
				"answer" => "
					<p>Nej, <a href='https://texttv.nu/blogg/ekonomisidorna-laggs-ner'>SVT lade ner börssidorna i september 2022</a>.</p>
				"
			],
			[
				"question" => "Vilken text tv app är bäst?",
				"answer" => "
					<p>
						Nu är vi förvisso lite partiska men vi gillar verkligen vår egen app!
						Den finns både till
						<a href='https://texttv.nu/ios'>Iphone och Ipad</a>
						och till
						<a href='https://texttv.nu/android'>Android</a>
					</p>
				"
			],
			[
				"question" => "Varför är det reklam på webbsidan och i appen?",
				"answer" => "
					<p>
						Den här sajten och dess app TextTV.nu är
						ett privat initiativ för att förbättra text tv och 
						göra tjänsten tillgänglig för mobiler på ett bättre sätt.
					</p>
					<p>
						Eftersom det kostar pengar att ha ett domännamn och en webbserver, 
						och eftersom det kostar pengar att ha en app i Apples Appstore,
						så har vi lagt in annonser för att bekosta våra utgifter.
					</p>
				"
			],
			[
				"question" => "Hur får jag text tv på min smarta TV?",
				"answer" => "
					<p>Om du trots att du läst manualen till din smarta tv från LG, Samsung, Sony eller annat märke, inte hittar hur man aktiverar text tv så har vi ett smart tips:
					öppna webbläsaren som finns inbyggd i din tv och gå till <a href='https://texttv.nu/'>texttv.nu</a>!
					.</p>
				"
			],

			[
				"question" => "Var har ekonomisidorna tagit vägen?",
				"answer" => "
					<p>
						Text-tv:s ekonomisidor togs bort hösten 2022.
					</p>
					<p>
						Anledningen är enligt SVT höga licenskostnader.
					</p>
					<p>
						<a href='https://texttv.nu/200'>Läs mer på sidan 200</a>
					</p>
				"
			],

		];
		
		// Meny först.
		echo "<ul>";
		foreach ($questions as $question) {
			printf(
				'
				<li>
					<a href="%1$s">
						%2$s
					</a>
				</li>
				',
				"#" . faq_slugify($question['question']),
				$question['question']
			);
		}
		echo "</ul>";
		
		// Frågor och svar.
		foreach ($questions as $question) {
			?>
			<div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
				<h2 itemprop="name" id="<?php echo faq_slugify($question['question']) ?>"><?php echo $question['question'] ?></h2>
				<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
					<div itemprop="text">
						<?php echo $question['answer'] ?>
					</div>
				</div>
			</div>
			<?php
		}
		
		?>	
	</div>
</div>