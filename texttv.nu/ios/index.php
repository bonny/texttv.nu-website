<!doctype html>
<html lang="sv">

<head>
    <title>Text TV-app för iPhone och iPad | SVT Text TV i din mobil | TextTV.nu</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Få SVT Text TV direkt i din iPhone eller iPad. Snabba nyheter, sport och resultat från text-tv sidorna 100, 300, 377 med flera. Gratis app med pushnotiser och favoritsidor.">
    <link rel="canonical" href="https://texttv.nu/ios">
    
    <!-- Open Graph -->
    <meta property="og:url" content="https://texttv.nu/ios">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Text TV-app för iPhone & iPad | Snabba nyheter & sport i mobilen">
    <meta property="og:description" content="Ladda ner vår gratis Text-TV-app för iOS och få nyheter & sportresultat direkt i din iPhone eller iPad. Favoritmärk sidor som 100, 300 och 377 för snabb åtkomst.">
    <meta property="og:image" content="https://texttv.nu/ios/iphone-text-tv-app-screenshot.png">

    <!-- Schema.org markup -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "TextTV.nu: smartare SVT Text",
            "operatingSystem": "iOS",
            "applicationCategory": "NewsApplication",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "SEK"
            },
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.5",
                "ratingCount": "10792",
                "bestRating": "5",
                "worstRating": "1"
            },
            "description": "Officiell Text TV-app från TextTV.nu för iPhone och iPad. Få nyheter och sport direkt i mobilen.",
            "image": "https://texttv.nu/ios/iphone-text-tv-app-screenshot.png",
            "downloadUrl": "https://apps.apple.com/se/app/texttv-nu/id607998045"
        }
    </script>

    <style>
        :root {
            --primary-color: #1779ba;
            --primary-dark: #126195;
            --text-color: #222;
            --bg-color: #fff;
            --light-gray: #f8f8f8;
            --spacing: 1rem;
            --border-radius: 8px;
            --gradient: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: var(--bg-color);
            padding: var(--spacing);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing);
        }

        .header {
            text-align: center;
            margin: 2rem 0;
        }

        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        h2 {
            font-size: 1.5rem;
            margin: 2rem 0 1rem;
        }

        .hero {
            position: relative;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            align-items: center;
            margin: 0 -1rem 2rem;
            padding: 3rem 1rem;
            background: var(--gradient);
            color: white;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/images/text-tv-nu-logotyp.svg') no-repeat center;
            opacity: 0.05;
            transform: scale(4);
            pointer-events: none;
        }

        @media (min-width: 768px) {
            .hero {
                grid-template-columns: 1.2fr 0.8fr;
                margin: 0 calc(50% - 50vw);
                margin-bottom: 3rem;
                padding: 4rem calc(50vw - 50%);
            }
        }

        .hero-content {
            position: relative;
            text-align: left;
            z-index: 1;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: white;
        }

        .hero-description {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-image {
            position: relative;
            z-index: 1;
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.3s ease;
        }

        .hero-image:hover {
            transform: perspective(1000px) rotateY(-8deg);
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .button {
            display: inline-flex;
            align-items: center;
            background: white;
            color: var(--primary-color);
            padding: 1rem 2rem;
            border-radius: 2rem;
            text-decoration: none;
            margin: 1rem 0;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .button:hover {
            background: var(--light-gray);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .hero .button {
            font-size: 1.1rem;
            padding: 1.2rem 2.5rem;
        }

        .hero a:not(.button) {
            color: white;
            text-decoration: underline;
            text-underline-offset: 2px;
            opacity: 0.9;
        }

        .hero a:not(.button):hover {
            opacity: 1;
        }

        .features {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        .features ul {
            list-style-position: inside;
            margin-left: 1rem;
        }

        .features li {
            margin-bottom: 0.5rem;
        }

        .reviews {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .review {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: var(--border-radius);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stars {
            color: #f4b400;
            margin-right: 1rem;
        }

        .review-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .review-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* New footer styles */
        .site-footer {
            margin-top: 4rem;
            padding: 3rem 0;
            background: var(--gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .site-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/images/text-tv-nu-logotyp.svg') no-repeat center;
            opacity: 0.05;
            transform: scale(4);
            pointer-events: none;
        }

        .footer-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }

        @media (min-width: 768px) {
            .footer-content {
                grid-template-columns: repeat(3, 1fr);
                text-align: left;
            }
        }

        .footer-section {
            padding: 0 1rem;
        }

        .footer-section h3 {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .footer-links a:hover {
            color: white;
            transform: translateX(3px);
        }

        .footer-links .page-number {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
            font-size: 0.9rem;
            font-family: monospace;
        }

        .footer-download {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .footer-download {
                align-items: flex-start;
            }
        }

        .footer-bottom {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
    </style>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-J9BM4E3WHD"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-J9BM4E3WHD');
    </script>
</head>

<body>
    <div class="container">
        <header class="header">
            <img class="logo" src="/images/text-tv-nu-logotyp.svg" alt="TextTV.nu logotyp">
            <h1>Text TV-app för iPhone och iPad</h1>
        </header>

        <section class="hero">
            <div class="hero-content">
                <h2 class="hero-title">SVT Text TV i din ficka</h2>
                <p class="hero-description">
                    Med vår <a href="https://apps.apple.com/se/app/texttv.nu/id607998045">text-tv-app</a> 
                    från <a href="https://texttv.nu/">texttv.nu</a> får du nyheter och sportresultat 
                    snabbt och enkelt direkt i din mobil. Alltid uppdaterad, alltid tillgänglig.
                </p>
                <a href="https://apps.apple.com/se/app/texttv.nu/id607998045" class="button">
                    Ladda ner kostnadsfritt
                </a>
                <p style="margin-top: 1rem; font-size: 0.9rem;">
                    eller besök <a href="https://TextTV.nu/">TextTV.nu</a> i din webbläsare
                </p>
            </div>
            <div class="hero-image">
                <a href="https://apps.apple.com/se/app/texttv.nu/id607998045">
                    <img src="/ios/iphone-text-tv-app-screenshot.png" alt="Skärmdump av Text TV-appen på iPhone som visar nyhetssidan">
                </a>
            </div>
        </section>

        <section class="features">
            <h2>Funktioner i appen</h2>
            <ul>
                <li>Mobilanpassad text tv från grunden och upp</li>
                <li>Möjlighet att dela text tv-sidor till Twitter och Facebook, med skärmdump och permalänk</li>
                <li>Visa flera sidor samtidigt</li>
                <li>Favoritmärk dina favoritsidor och visa dom automatiskt när appen startas</li>
                <li>Få meddelande när det finns en uppdatering av sidan</li>
                <li>Se sportnyheterna på <a href="/300">text tv 300</a> och resultaten på <a href="/377">text tv 377</a></li>
            </ul>

            <div style="margin-top: 2rem; padding: 1.5rem; background: white; border-radius: var(--border-radius); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">Älskad av användarna</h3>
                <p style="margin-bottom: 0.5rem;">Appen har ett genomsnittligt betyg på <strong>4,5 av 5 stjärnor</strong> baserat på hela <strong>10 792 recensioner</strong>. Hela <strong>7 140</strong> användare har gett appen högsta betyg!</p>
                <div style="color: #f4b400; font-size: 1.5rem;">★★★★½</div>
            </div>
        </section>

        <section>
            <h2>Vad användarna tycker</h2>
            <p>Detta är äkta <a href="https://apps.apple.com/se/app/texttv-nu-svt-text-tv/id607998045?see-all=reviews">recensioner av appen på App Store</a>.</p>

            <?php
            $reviews = [
                [
                    'stars' => 5,
                    'user' => "Liberty 21",
                    'date' => null,
                    'title' => 'Sakliga',
                    'text' => 'Bra och snabba med riksnyheter ;-)',
                ],
                [
                    'stars' => 5,
                    'user' => "istvanjonyer2",
                    'date' => "Sep 18, 2022",
                    'title' => 'Text-tv är bäst!',
                    'text' => 'Nyheter kort och gott!',
                ],
                [
                    'stars' => 5,
                    'user' => "Kastrullhäxans make",
                    'date' => "Apr 4, 2023",
                    'title' => 'Jag är beroende!',
                    'text' => 'Klarar mig inte utan Text-tv! Det är livet, sa fantastisk bra jag byter hellre bort huset och frun än än avstär Text-tv.',
                ],
                [
                    'stars' => 5,
                    'user' => "Bengeten",
                    'date' => "May 2, 2023",
                    'title' => 'Text tv',
                    'text' => 'Bästa resultatbörsen! Enkelt o lättfattligt!',
                ],
                [
                    'stars' => 5,
                    'user' => "Thomas S W",
                    'date' => "May 10, 2023",
                    'title' => 'Bra app !',
                    'text' => 'Denna nya version av SVT Text-TV är mycket bättre än den första/förra versionen. Är nästan sà att man inte kan vara utan Text-TV i denna form.',
                ],
                [
                    'stars' => 5,
                    'user' => "utan biljett",
                    'date' => "Jul 1, 2023",
                    'title' => 'Bra text tv',
                    'text' => 'Riatade an for setig nyhee ett enket sätt. Med tv pa natet ar det svart att fa in text tv Men nuär et enkelt när man har appen. Text tv ger',
                ],
                [
                    'stars' => 5,
                    'user' => "text tv är cult",
                    'date' => "Jul 1, 2023",
                    'title' => 'Text tv',
                    'text' => 'Uppuxen med text tv. Kommer aldrig sluta med det. Bästa och lagom med nyheter.',
                ],
                [
                    'stars' => 5,
                    'user' => "kälarne60",
                    'date' => "Oct 7, 2023",
                    'title' => 'Text-tv',
                    'text' => 'Fick en ny hub till tv som saknade text-tv. Jag fick ren panik. Varje morgon sà startade jag dagen med text-tv och avslutade dagen med text-tv. Nu satt jag utan. Jag hade ingen tanke pa att den skulle finnas pa min telefon. Stort stort tack, niär räddare i nöden.',
                ],
                [
                    'stars' => 5,
                    'user' => "TvText",
                    'date' => "Nov 7, 2023",
                    'title' => 'TextTV',
                    'text' => 'Bäde snabbt och informationsrikt. Lätt att använda, tydlig text!',
                ],
                [
                    'stars' => 5,
                    'user' => "morgongäng",
                    'date' => "Jan 11, 2024",
                    'title' => 'Uppdaterad',
                    'text' => 'Perfekt för att halla sig uppdaterad',
                ],
                [
                    'stars' => 5,
                    'user' => "kungs Barkaro",
                    'date' => "Jan 13, 2024",
                    'title' => 'Snabb info',
                    'text' => 'Snabb och korrekt information pa ett enkelt sätt',
                ],
                [
                    'stars' => 5,
                    'user' => "Ragge65",
                    'date' => "Jan 14, 2024",
                    'title' => 'Tidlös favorit',
                    'text' => 'En klassiker som stär sig, även i IT o Al-äldern. Kortfattat, komprimerat alldagsvetande. Det man behöver, när man behöver det. Länge leve Text-teve!',
                ],
                [
                    'stars' => 5,
                    'user' => "cgyrt",
                    'date' => "Jan 16, 2024",
                    'title' => 'Snabbt och enkelt!',
                    'text' => 'Raka snabba nyheter utan smaskiga kvällstidningsrubriker.',
                ],
                [
                    'stars' => 5,
                    'user' => "kippe k",
                    'date' => "Jan 24, 2024",
                    'title' => 'Text-tv',
                    'text' => 'Det viktigaste stär där och sporten är bra',
                ],
                [
                    'stars' => 5,
                    'user' => "Äldst à bäst",
                    'date' => "Jan 27, 2024",
                    'title' => 'Battre an bast',
                    'text' => 'När man är med dalig mobiltäckning och vill ha nyheter. När man vill läsa nyheter kortfattat utan massa onödigt tjafs. När man vill följa malservice och just malservice. Ja dã är TextTv bästa appen.',
                ],
                [
                    'stars' => 5,
                    'user' => "Koi nk",
                    'date' => "May 3, 2024",
                    'title' => 'Nyheter Enkelt snabbt och funktionellt',
                    'text' => 'Inga fake news. Nostalgiskt',
                ],
                [
                    'stars' => 5,
                    'user' => "Karhu75",
                    'date' => "Jul 4, 2024",
                    'title' => 'Ultimata appen',
                    'text' => 'Nyheter och sport i korthet, funnits i mitt liv sedan urminnes tider. Tacka vet jag <a href="/377">sidan 377!</a>',
                ],
            ];
            ?>

            <div class="reviews">
                <?php foreach ($reviews as $review): ?>
                    <article class="review">
                        <div class="review-header">
                            <div class="stars">
                                <?php echo str_repeat('★', $review['stars']) . str_repeat('☆', 5 - $review['stars']); ?>
                            </div>
                        </div>
                        <h3 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h3>
                        <div class="review-meta">
                            <span><?php echo htmlspecialchars($review['user']); ?></span>
                            <?php if ($review['date']): ?>
                                <span> • <?php echo htmlspecialchars($review['date']); ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?php echo $review['text']; ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <footer class="site-footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>Populära sidor</h3>
                        <ul class="footer-links">
                            <li>
                                <a href="/100">
                                    <span class="page-number">100</span>
                                    Nyheter
                                </a>
                            </li>
                            <li>
                                <a href="/300">
                                    <span class="page-number">300</span>
                                    Sport
                                </a>
                            </li>
                            <li>
                                <a href="/377">
                                    <span class="page-number">377</span>
                                    Resultatbörsen
                                </a>
                            </li>
                            <li>
                                <a href="/330">
                                    <span class="page-number">330</span>
                                    Fotboll
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="footer-section">
                        <h3>Snabblänkar</h3>
                        <ul class="footer-links">
                            <li>
                                <a href="https://texttv.nu/">TextTV.nu Webbversion</a>
                            </li>
                            <li>
                                <a href="https://texttv.nu/ios">iOS App</a>
                            </li>
                            <li>
                                <a href="https://texttv.nu/android">Android App</a>
                            </li>
                        </ul>
                    </div>

                    <div class="footer-section footer-download">
                        <a href="https://apps.apple.com/se/app/texttv.nu/id607998045" class="button">
                            Hämta för iPhone & iPad
                        </a>
                        <p style="font-size: 0.9rem; opacity: 0.9;">
                            Gratis • Snabb • Alltid uppdaterad
                        </p>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p>TextTV.nu - Din smarta genväg till SVT Text TV</p>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
