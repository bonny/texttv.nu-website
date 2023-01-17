<!-- Cookie banner + inställningar -->
<section role="dialog" class="site-cookie-banner site-cookie-banner--approved">
	<div class="site-cookie-banner__message">
		<h1 class="site-cookie-banner__messageHeadline">Får vi använda din data till att skräddarsy annonser åt dig?</h1>
		<p>
			Vi och våra partners samlar in och använder cookies till annonsanpassning, 
			mätning och för att möjliggöra viktig webbplatsfunktionalitet.
			<a href="/sida/cookies/" class="site-cookie-banner__readmore">Läs mer om hur TextTV.nu och våra partners samlar in och använder data.</a>
		</p>
	</div>
	<p class="site-cookie-banner__options">
		<button class="site-cookie-banner__ok">Okej!</button>
		<button class="site-cookie-banner__no">Nej</button>
	</p>
</section>
<style>
	.site-cookie-banner {
		display: block;
		position: fixed;
		bottom: 0;
		width: 100%;
		background: blue;
		padding: 1rem;
		text-align: left;
		font-size: 1rem;
	}
	.site-cookie-banner--showAsSettings {
		bottom: auto;
		top: 50%;
		left: 50%;
		width: 75%;
		transform: translateX(-50%) translateY(-50%);
	}
	.site-cookie-banner__message {
		max-height: 15em;
		overflow-y: auto;
	}
	.site-cookie-banner__messageHeadline {
		display: block;
		margin-bottom: .5em;
	}
	.site-cookie-banner__message > p {
		line-height: 1.1;
	}
	.site-cookie-banner--approved {
		display: none;
	}
	.site-cookie-banner__options {
		margin-top: .5rem;
	}
	.site-cookie-banner__ok {
		background-color: #0f0;
		border: 2px solid #018a01;
	}
	.site-cookie-banner__ok,
	.site-cookie-banner__no {
		padding: .5rem .75rem;
	}
	.site-cookie-banner__no {
		border: none;
		background: transparent;
		color: white;
		text-decoration: underline;
	}
	.site-cookie-banner__readmore {
		text-decoration-color: rgb(255 255 255 / 50%);
	}
</style>
<script>
	(function() {
		let cookieConsentCookie = document.cookie.split('; ').find(row => row.startsWith('cookieConsent='));
		let cookieValue = cookieConsentCookie?.split('=')[1];
		var containerSelector = '.site-cookie-banner';
		var showSettingsSelector = '.site-cookie-banner__showSettings';
		let approveCookiesSelector = '.site-cookie-banner__ok';
		let disapproveCookiesSelector = '.site-cookie-banner__no';
		var cookiesApproved = (cookieValue === 'ok');

		// Rutan visas som standard inte men aktiveras för de som inte har okejat.
		if (cookiesApproved) {
			// Cookies godkända, fortsätta med gömd ruta.
			// console.log('cookies ok');
		} else {
			// Cookies inte godkända, visa ruta.
			document.querySelector('.site-cookie-banner.site-cookie-banner--approved').classList.remove('site-cookie-banner--approved');
		}
		
		window.addEventListener('DOMContentLoaded', (event) => {
			/**
			 * Sätt cookie och göm cookie-ruta när användare klickar ok-knappen.
			 */
			document.querySelector(approveCookiesSelector)
				.addEventListener(
					'click', 
					evt => {
						let maxAge = 60*60*24*365; // 1 year
						let newCookie = `cookieConsent=ok;path=/;max-age=${maxAge}`;
						document.cookie = newCookie;
						document.querySelector('.site-cookie-banner').classList.add('site-cookie-banner--approved');
					}
				);
			
			// Ta bort cookie om nej-knappen klickas på
			document.querySelector(disapproveCookiesSelector)
			.addEventListener(
				'click', 
				evt => {
					let maxAge = 60*60*24*365; // 1 year
					let newCookie = `cookieConsent=no;path=/;max-age=${maxAge}`;
					document.cookie = newCookie;
					document.querySelector('.site-cookie-banner').classList.add('site-cookie-banner--approved');
				}
			);

			/**
			 * Visa knapp för att visa cookie-rutan igen om man godkänt/gömt den.
			 */		
			if (cookiesApproved) {
				var containerElm = document.querySelector(containerSelector);
				var showSettingsElm = document.querySelector(showSettingsSelector);
				
				// Visa knappen om man godkänt, dvs. det finns nåt att visa igen.
				showSettingsElm?.classList.remove('hidden');
				
				showSettingsElm?.addEventListener(
					'click',
					evt => {
						containerElm.classList.remove('site-cookie-banner--approved');
						containerElm.classList.add('site-cookie-banner--showAsSettings');
					}
				);
			}
			console.log({containerElm, cookieConsentCookie, cookieValue, showSettingsElm});
		});
	})();
 
</script>
