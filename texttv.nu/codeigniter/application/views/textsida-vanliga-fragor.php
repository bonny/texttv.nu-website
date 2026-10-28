<div class="textcontent">
	<div itemscope itemtype="https://schema.org/FAQPage">
		<!--
		FAQ (FAQPage, Question, Answer) structured data
		https://developers.google.com/search/docs/appearance/structured-data/faqpage

		Varje svar börjar med en mening som står på egna ben, så att Google
		kan citera den i ett utdrag. Allt är kontrollerat mot källan som står
		under svaret (oktober 2026). Ändrar du ett svar: kolla källan igen och
		uppdatera datumet nedan.

		Id:na är fasta. Google länkar redan till #finns-text-tv-kvar-fortfarande
		och #vad-är-text-tv, så byt inte id när en fråga formuleras om.
		-->

		<h1>Vanliga frågor om text-tv</h1>

		<p>
			Här svarar vi på det folk oftast undrar om text-tv: om det finns kvar,
			hur man hittar det på sin tv och hur det hela började.
			<br>Senast uppdaterad <time datetime="2026-10-28">28 oktober 2026</time>.
		</p>

		<?php

		$avsnitt = [
			"Använda text-tv" => [
				[
					"id" => "finns-text-tv-kvar-fortfarande",
					"question" => "Finns text-tv kvar?",
					"answer" => "
						<p>Ja. SVT Text sänds varje dag med nyheter, sport, väder och tv-tablåer, och SVT:s sändningstillstånd för 2026–2033 gäller både tv och sökbar text-tv.</p>
						<p>Du kan läsa text-tv på tre sätt: på tv:n med fjärrkontrollen, på <a href='https://www.svt.se/text-tv/100'>svt.se/text-tv</a>, eller här på <a href='/'>texttv.nu</a>, som visar SVT:s sidor anpassade för mobil och dator.</p>
						<p>Alla länder har inte behållit sin text-tv. BBC lade ner världens första text-tv, Ceefax, 2012, och norska NRK slutade med sin 20 augusti 2025.</p>
					",
					"sources" => [
						"Regeringen: sändningstillstånd och uppdrag 2026–2033" => "https://regeringen.se/pressmeddelanden/2025/12/regeringen-beslutar-om-de-narmare-villkoren-for-srs-svts-och-urs-public-service-uppdrag-de-kommande-atta-aren/",
						"SVT: Tillstånd att sända tv och sökbar text-tv (pdf)" => "https://omoss.svt.se/download/18.315b06c619ba949d942140a5/1768229691428/Tillst%C3%A5nd%20f%C3%B6r%20Sveriges%20Television%20AB%20att%20s%C3%A4nda%20tv%20och%20s%C3%B6kbar%20text%20tv_.pdf",
						"Tek.no: Snart er det slutt for NRKs tekst-TV" => "https://www.tek.no/nyheter/nyhet/i/W0G5Ed/snart-er-det-slutt-for-nrks-tekst-tv",
					],
				],
				[
					"id" => "hur-far-jag-fram-text-tv",
					"question" => "Hur får jag fram text-tv på tv:n?",
					"answer" => "
						<p>Tryck på text-tv-knappen på fjärrkontrollen när du tittar på en kanal som sänder text-tv, till exempel SVT1. Knappen heter oftast TEXT eller TTX, och på Samsung TTX/MIX.</p>
						<p>Skriv sedan sidnumret med sifferknapparna: 100 för nyheter, 300 för sport, 377 för målservice och 400 för väder. Tryck på knappen igen för att gå tillbaka till tv-bilden.</p>
						<p>Text-tv följer med kanalens sändning. Står det att text-tv saknas kan det bero på kanalen, på att din tv-box inte visar text-tv, eller på tv-modellen. Se svaren om Samsung, LG och tv-boxar nedan.</p>
					",
				],
				[
					"id" => "text-tv-samsung",
					"question" => "Hur får jag text-tv på en Samsung-tv?",
					"answer" => "
						<p>Har fjärrkontrollen en TTX/MIX-knapp trycker du på den. På Samsungs nyare smarta fjärrkontroller utan den knappen trycker du på 123-knappen, väljer TTX/MIX och trycker OK.</p>
						<p>Skriv sidnumret med sifferknapparna. För att stänga väljer du TTX/MIX igen.</p>
						<p>Enligt Samsung finns text-tv bara på kanaler som sänder tjänsten, och hur det fungerar kan skilja sig mellan modeller och länder. Hittar du inget alternativ för TTX/MIX, sök på text-tv eller teletext i tv:ns bruksanvisning (e-manualen i menyn).</p>
					",
					"sources" => [
						"Samsung: Smart TV-fjärrkontrollens knappar (tyska)" => "https://www.samsung.com/de/support/tv-audio-video/smart-tv-fernbedienung-tasten-im-ueberblick/",
					],
				],
				[
					"id" => "text-tv-lg",
					"question" => "Hur får jag text-tv på en LG-tv?",
					"answer" => "
						<p>Tryck på TEXT på fjärrkontrollen medan du tittar på tv. Knappen T.OPT ger fler val, som att låsa en sida så att den inte bläddrar vidare.</p>
						<p>LG skriver att text-tv bara finns i vissa länder och att den kan visas fel om tv:n är inställd på fel land. Kontrollera därför att landet är Sverige i inställningarna.</p>
					",
					"sources" => [
						"LG: bruksanvisning, Använda Text-TV (webOS)" => "http://kr.eguide.lgappstv.com/manual/w18_mr/dvb/Contents/livetv/teletext_e_a/swe/w40__livetv__teletext_e_a__swe.html",
					],
				],
				[
					"id" => "text-tv-tv-box",
					"question" => "Fungerar text-tv med Telia, Tele2 och Allente?",
					"answer" => "
						<p>Det beror på boxen. Telias Play Hub har text-tv förinstallerad, medan Tele2:s Tv Hub och Allente 1 kräver att du laddar ner en text-tv-app från Google Play.</p>
						<ul>
							<li><strong>Telia Play Hub:</strong> öppna appen Text-TV i appöversikten. Byt sida med upp och ner, undersida med höger och vänster. Tryck OK för att skriva ett sidnummer.</li>
							<li><strong>Tele2 Tv Hub:</strong> sök på Text-tv i Play Store och installera appen. Tele2 påpekar att appen kommer från någon annan och att de inte ger support för den.</li>
							<li><strong>Allente 1:</strong> har inte inbyggd text-tv. Allente hänvisar till en text-tv-app från Google Play.</li>
						</ul>
					",
					"sources" => [
						"Telia: Koppla in Telia Play Hub" => "https://www.telia.se/support/tv-och-streaming/guider/koppla-in-teliaplay-hub",
						"Tele2: Tv Hub-guider" => "https://www.tele2.se/kundservice/tv-och-play/tv-hub-guider",
						"Allente: Undertext i box" => "https://www.allente.se/kundservice/boxar-och-hardvara/undertext-i-box/",
					],
				],
				[
					"id" => "undertexter-sida-199",
					"question" => "Hur slår jag på undertexter på SVT?",
					"answer" => "
						<p>SVT:s undertexter finns på text-tv sida 199. Tryck på text-tv-knappen och skriv 199 så visas texten över bilden. Gör samma sak för att stänga av den.</p>
						<p>Det gäller SVT1, SVT2, SVT Barn, SVT24 och Kunskapskanalen. Vilka program som textas den närmaste veckan står på sidorna 601–619. Vissa av SVT:s egna program har danska undertexter på 591 (SVT1) och 592 (SVT2).</p>
						<p>I SVT Play slår du på undertexter i spelaren. På en smart-tv trycker du nedåt eller paus, sedan uppåt, och väljer Svenska under Undertexter.</p>
						<p>Har du tv-box gäller boxens egna inställningar. Hos Telia finns ett val för textning för hörselskadade i menyn, och på Allentes Viasat-box går du till sida 199.</p>
					",
					"sources" => [
						"SVT: Slå på och av undertexter" => "https://www.svt.se/kontakt/sla-pa-och-av-undertexter",
						"Telia: Undertexter för hörselnedsättning" => "https://www.telia.se/support/tv-och-streaming/guider/undertexter-horselnedsattning",
						"Allente: Undertext i box" => "https://www.allente.se/kundservice/boxar-och-hardvara/undertext-i-box/",
					],
				],
				[
					"id" => "vilken-text-tv-app-är-bäst",
					"question" => "Kan jag läsa text-tv i mobilen?",
					"answer" => "
						<p>Ja. Öppna <a href='/'>texttv.nu</a> i mobilens webbläsare, eller ladda ner vår app TextTV.nu för <a href='https://texttv.nu/ios'>iPhone och iPad</a> eller <a href='https://texttv.nu/android'>Android</a>.</p>
						<p>Sidorna är desamma som på SVT Text, men länkade, sökbara och lätta att dela. SVT har också text-tv på <a href='https://www.svt.se/text-tv/100'>svt.se/text-tv</a>.</p>
						<p>Har din smarta tv en webbläsare fungerar texttv.nu där också, om du inte hittar text-tv i tv:ns egen meny.</p>
					",
				],
			],

			"Historia" => [
				[
					"id" => "vad-är-text-tv",
					"question" => "Vad är text-tv?",
					"answer" => "
						<p>Text-tv är sidor med text och enkel blockgrafik som sänds tillsammans med tv-bilden och väljs med fjärrkontrollens sifferknappar. Varje sida har ett tresiffrigt nummer, och tv:n visar 24 rader med 40 tecken.</p>
						<p>Tekniken togs fram i Storbritannien på 1970-talet och blev en europeisk standard. SVT använder den för nyheter, sportresultat, väder, tv-tablåer och undertexter.</p>
					",
					"sources" => [
						"ETSI EN 300 706: Enhanced Teletext specification (pdf)" => "https://www.etsi.org/deliver/etsi_en/300700_300799/300706/01.02.01_60/en_300706v010201p.pdf",
					],
				],
				[
					"id" => "vilket-år-kom-text-tv",
					"question" => "När kom text-tv?",
					"answer" => "
						<p>Världens första text-tv startade 23 september 1974, när BBC började sända Ceefax. Ceefax lades ner 23 oktober 2012, samtidigt som de analoga tv-sändningarna i Storbritannien.</p>
						<p>I Sverige började SVT sända text-tv 12 mars 1979.</p>
					",
					"sources" => [
						"SVT: Populära text-tv fyller 40" => "https://www.svt.se/kultur/populara-text-tv-fyller-40",
						"informitv: 50 years of teletext" => "https://informitv.com/2024/09/23/50-years-of-teletext/",
					],
				],
				[
					"id" => "nar-kom-text-tv-till-sverige",
					"question" => "När kom text-tv till Sverige?",
					"answer" => "
						<p>SVT började sända text-tv 12 mars 1979. Enligt SVT var Sverige då tvåa i världen, efter Storbritannien.</p>
						<p>Starten skedde lite i smyg. Projektledaren Åke ”Bob” Holmberg hade fått uppdraget året innan, och först 1980 beslutade SVT:s styrelse att text-tv skulle bli permanent.</p>
						<p>Den första text-tv-datorn kallades Mormor. Den rymde 64 sidor, som laddades in från hålremsor.</p>
					",
					"sources" => [
						"SVT: Hurra! Nu firar text-tv 40 år" => "https://www.svt.se/nyheter/inrikes/hurra-nu-firar-text-tv-40-ar",
						"SVT Sport: Text-tv är sportnördens bästa vän" => "https://www.svt.se/sport/artikel/fakta-om-text-tv",
					],
				],
				[
					"id" => "hur-manga-tittar-pa-svt-text-tv",
					"question" => "Hur många använder text-tv?",
					"answer" => "
						<p>Som mest hade SVT Text omkring tre miljoner användare om dagen, enligt SVT:s egen uppskattning för 1990- och 2000-talet. 2019 var siffran cirka 1,3 miljoner om dagen.</p>
						<p>Andelen svenskar som använde text-tv en vanlig dag var 31 procent år 2000 och 21 procent 2013. Enligt SVT kom det stora genombrottet med Gulfkriget 1991.</p>
					",
					"sources" => [
						"SVT: Hurra! Nu firar text-tv 40 år" => "https://www.svt.se/nyheter/inrikes/hurra-nu-firar-text-tv-40-ar",
						"SVT: Populära text-tv fyller 40" => "https://www.svt.se/kultur/populara-text-tv-fyller-40",
						"SVT Sport: Text-tv är sportnördens bästa vän" => "https://www.svt.se/sport/artikel/fakta-om-text-tv",
					],
				],
				[
					"id" => "varfor-heter-malservice-377",
					"question" => "Varför ligger målservice på sidan 377?",
					"answer" => "
						<p>Sidnumret valdes på känsla. Björn Grundel, som SVT kallar text-tv:s grundare, berättade 2019: ”Jag satt och gaggade för mig själv: 365, 366... och så kom 377 och då var det pang!”</p>
						<p>Exakt när 377 kom vet inte ens Grundel. Han tror att det var i slutet av 1980-talet. Sidan blev så populär att den fick ett eget konto på Twitter, @svt377. Här hittar du <a href='/377'>dagens målservice på 377</a>.</p>
					",
					"sources" => [
						"SVT Sport: Mr text-tv smakade sig fram till 377" => "https://www.svt.se/sport/artikel/text-tv-2",
						"SVT Sport: Text-tv är sportnördens bästa vän" => "https://www.svt.se/sport/artikel/fakta-om-text-tv",
					],
				],
				[
					"id" => "var-har-ekonomisidorna-tagit-vägen",
					"question" => "Var har börssidorna tagit vägen?",
					"answer" => "
						<p>SVT tog bort börs- och ekonomisidorna i september 2022. Enligt SVT var kostnaderna för licensavtalet för höga, så avtalet sades upp.</p>
						<p><a href='https://texttv.nu/blogg/ekonomisidorna-laggs-ner'>Vi skrev om det när det hände</a>, och SVT:s egen förklaring finns på <a href='/200'>sidan 200</a>.</p>
					",
					"sources" => [
						"Dagens PS: Ekonomisidorna borttagna från text-tv" => "https://www.dagensps.se/privatekonomi/ekonomisidorna-borttagna-fran-text-tv/",
					],
				],
			],

			"Om TextTV.nu" => [
				[
					"id" => "ar-texttv-nu-fran-svt",
					"question" => "Är TextTV.nu från SVT?",
					"answer" => "
						<p>Nej. TextTV.nu är ett fristående projekt som har funnits sedan 2012. Innehållet kommer från SVT Text, men sajten och apparna gör vi själva för att text-tv ska vara lätt att läsa i mobilen och på datorn.</p>
						<p><a href='/sida/om-texttv-nu'>Läs mer om TextTV.nu</a>.</p>
					",
				],
				[
					"id" => "varför-är-det-reklam-på-webbsidan-och-i-appen",
					"question" => "Varför är det reklam på sajten och i appen?",
					"answer" => "
						<p>TextTV.nu är ett privat initiativ. Annonserna betalar för domänen, servern och apparnas plats i App Store och Google Play.</p>
					",
				],
			],
		];

		// Innehållsförteckning först.
		echo "<ul>";
		foreach ($avsnitt as $rubrik => $fragor) {
			foreach ($fragor as $fraga) {
				printf(
					'<li><a href="#%1$s">%2$s</a></li>',
					$fraga['id'],
					$fraga['question']
				);
			}
		}
		echo "</ul>";

		// Frågor och svar, i avsnitt.
		foreach ($avsnitt as $rubrik => $fragor) {
			?>
			<h2><?php echo $rubrik ?></h2>
			<?php
			foreach ($fragor as $fraga) {
				?>
				<div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
					<h3 itemprop="name" id="<?php echo $fraga['id'] ?>"><?php echo $fraga['question'] ?></h3>
					<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
						<div itemprop="text">
							<?php echo $fraga['answer'] ?>
						</div>
					</div>
					<?php
					if (!empty($fraga['sources'])) {
						$lankar = [];
						foreach ($fraga['sources'] as $titel => $url) {
							$lankar[] = sprintf('<a href="%1$s" rel="noopener">%2$s</a>', htmlspecialchars($url, ENT_QUOTES), htmlspecialchars($titel));
						}
						echo '<p class="kallor">Källor: ' . implode(" · ", $lankar) . '</p>';
					}
					?>
				</div>
				<?php
			}
		}

		?>
	</div>
</div>
