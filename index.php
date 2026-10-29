<?php
declare(strict_types=1);

$pricing = require __DIR__ . '/config/pricing.php';
header('X-Robots-Tag: index, follow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e7ebef">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Bexxy Care and Clean provides home, deep, office, move-out, rental, and recurring cleaning in Ottawa and Gatineau, 24/7.">
    <title>Bexxy Care and Clean — Ottawa &amp; Gatineau</title>
    <link rel="canonical" href="https://bexxycareandclean.ca/">
    <link rel="shortcut icon" href="image/bexxy-logo.png" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="image/bexxy-logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="image/bexxy-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css?v=4">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#home" aria-label="Bexxy Care and Clean home">
            <img src="image/download.png" alt="Bexxy Care and Clean" width="480" height="140">
        </a>
        <nav class="primary-nav" aria-label="Main navigation">
            <a href="#home" aria-current="page">Home</a>
            <a href="about.php">About</a>
            <a href="#services">Services</a>
            <a href="pricing.php">Pricing</a>
            <a href="#contact">Contact</a>
            <a class="nav-cta" href="booking.php">Book now <span aria-hidden="true">↗</span></a>
        </nav>
    </header>

    <main>
        <section class="hero page-shell" id="home">
            <div class="hero-copy">
                <div class="eyebrow"><span class="eyebrow-star" aria-hidden="true">✳</span>Clean and bright · Ottawa &amp; Gatineau</div>
                <h1>Your space,<br><span class="headline-accent">our priority.</span></h1>
                <p class="hero-description">Reliable cleaning for homes, offices, and short-term rentals. Tell us what you need and we’ll help plan the right clean.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="booking.php">Book now <span aria-hidden="true">↗</span></a>
                    <a class="text-link" href="#services">Explore our services <span aria-hidden="true">↓</span></a>
                </div>
            </div>
            <div class="hero-art">
                <div class="art-orbit orbit-one" aria-hidden="true"></div>
                <div class="art-orbit orbit-two" aria-hidden="true"></div>
                <div class="sparkle sparkle-one" aria-hidden="true">✳</div>
                <div class="sparkle sparkle-two" aria-hidden="true">✦</div>
                <div class="sparkle sparkle-three" aria-hidden="true">✧</div>
                <div class="art-label"><span class="label-dot"></span> A little care goes a long way</div>
                <img class="room-photo" src="image/bexxy-room.png" alt="Bexxy cleaning team member carefully cleaning a bright window" width="768" height="1160" fetchpriority="high">
                <div class="clean-card">
                    <span class="clean-card-icon" aria-hidden="true">✧</span>
                    <span><strong>That just-cleaned feeling</strong><small>It’s a Bexxy kind of day</small></span>
                    <span class="clean-card-check" aria-hidden="true">✓</span>
                </div>
            </div>
        </section>

        <section class="trust-bar page-shell" aria-label="Our promises">
            <p>Fast, flexible service.<br><strong>Available 24 hours a day, seven days a week.</strong></p>
            <div class="trust-item"><span aria-hidden="true">✓</span> Ottawa &amp; Gatineau</div>
            <div class="trust-item"><span aria-hidden="true">7</span> Days a week</div>
            <div class="trust-item"><span aria-hidden="true">✓</span> Homes and businesses</div>
        </section>

        <section class="services-section page-shell" id="services">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Our services</div>
                    <h2>Good work.<br><span class="headline-accent">Clean spaces.</span></h2>
                </div>
                <p>Homes, offices, rentals, and regular upkeep — see the services Bexxy offers.</p>
            </div>
            <div class="service-grid">
                <article class="service-card service-pink">
                    <span class="service-number">01</span>
                    <div class="service-copy">
                        <h3>Residential cleaning</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['residential']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/residential-cleaning.jpg" alt="Cleaners caring for a living room floor" width="700" height="467" loading="lazy">
                            <p>Keep your house or apartment fresh with cleaning tailored to your home.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=residential" aria-label="Book residential cleaning"><span aria-hidden="true">↗</span></a>
                </article>
                <article class="service-card service-green">
                    <span class="service-number">02</span>
                    <div class="service-copy">
                        <h3>Deep cleaning</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['deep']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/deep-cleaning.jpg" alt="A gloved hand scrubbing a bathroom fixture" width="700" height="1050" loading="lazy">
                            <p>A detailed clean for the areas that need extra care and attention.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=deep" aria-label="Book deep cleaning"><span aria-hidden="true">↗</span></a>
                </article>
                <article class="service-card service-blue">
                    <span class="service-number">03</span>
                    <div class="service-copy">
                        <h3>Commercial cleaning</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['commercial']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/commercial-cleaning.jpg" alt="A team cleaning a commercial dining space" width="700" height="467" loading="lazy">
                            <p>Cleaning for offices and commercial spaces to help keep your workplace tidy.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=commercial" aria-label="Book commercial cleaning"><span aria-hidden="true">↗</span></a>
                </article>
                <article class="service-card service-pink">
                    <span class="service-number">04</span>
                    <div class="service-copy">
                        <h3>Move-in / move-out cleaning</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['move']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/move-cleaning.jpg" alt="A cleaner vacuuming a bright interior" width="700" height="1050" loading="lazy">
                            <p>Get your place ready for a new arrival or leave it fresh when you move out.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=move" aria-label="Book move-in or move-out cleaning"><span aria-hidden="true">↗</span></a>
                </article>
                <article class="service-card service-green">
                    <span class="service-number">05</span>
                    <div class="service-copy">
                        <h3>Short-term rental cleaning</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['rental']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/rental-cleaning.jpg" alt="Fresh bedding being prepared for a guest" width="700" height="1050" loading="lazy">
                            <p>Cleaning for short-term rental properties, including Airbnb listings.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=rental" aria-label="Book short-term rental cleaning"><span aria-hidden="true">↗</span></a>
                </article>
                <article class="service-card service-blue">
                    <span class="service-number">06</span>
                    <div class="service-copy">
                        <h3>Regular maintenance</h3>
                        <p class="service-price"><?= escape_html($pricing['currency_label'] . ' ' . number_format($pricing['services']['maintenance']['per_hour'], 0)) ?> per cleaner-hour</p>
                        <div class="service-popover">
                            <img src="image/services/regular-maintenance.jpg" alt="A cleaner wiping a kitchen surface" width="700" height="1050" loading="lazy">
                            <p>Choose weekly or monthly visits to keep your space in good shape.</p>
                        </div>
                    </div>
                    <a href="booking.php?service=maintenance" aria-label="Book regular maintenance cleaning"><span aria-hidden="true">↗</span></a>
                </article>
            </div>
        </section>

        <section class="how-section page-shell" id="about">
            <div class="how-intro">
                <div class="eyebrow">How it works</div>
                <h2>Simple from<br><span class="headline-accent">the start.</span></h2>
                <p>Tell us about your home, office, or rental. We’ll talk through the details and help you choose the right service.</p>
                <a class="button button-dark" href="about.php" data-page-transition>About Bexxy <span aria-hidden="true">↗</span></a>
            </div>
            <div class="steps">
                <a class="step" href="about.php" data-page-transition aria-label="Learn about Bexxy and the cleaning services it offers">
                    <span class="step-number">01</span>
                    <div><h3>About Bexxy</h3><p>Learn about the company and the spaces we clean.</p></div>
                    <span class="step-symbol" aria-hidden="true">↗</span>
                </a>
                <a class="step" href="booking.php" data-page-transition aria-label="Choose a service and send a booking request">
                    <span class="step-number">02</span>
                    <div><h3>Choose your service</h3><p>Tell us what you need and request a preferred date.</p></div>
                    <span class="step-symbol" aria-hidden="true">↗</span>
                </a>
                <a class="step" href="pricing.php" data-page-transition aria-label="See cleaning prices and how estimates are calculated">
                    <span class="step-number">03</span>
                    <div><h3>Review the estimate</h3><p>See hourly rates and how extras are quoted.</p></div>
                    <span class="step-symbol" aria-hidden="true">↗</span>
                </a>
            </div>
        </section>

        <section class="content-section home-service-guide page-shell" aria-labelledby="home-service-guide-heading">
            <div class="eyebrow">Cleaning for real-life spaces</div>
            <h2 id="home-service-guide-heading">The right clean<br><span class="headline-accent">for your space.</span></h2>
            <p class="home-section-intro">From lived-in homes and busy workplaces to rental turnovers and moving days, Bexxy Care and Clean serves Ottawa and Gatineau. Choose the type of cleaning you need, then tell us which rooms, priorities, and preferred date to consider.</p>
            <div class="content-card-grid">
                <article class="content-card">
                    <span class="about-card-label">At home</span>
                    <h3>Make home feel fresh</h3>
                    <p>Request residential cleaning for a house or apartment, a deeper clean for areas that need extra attention, or regular maintenance on a weekly or monthly schedule.</p>
                </article>
                <article class="content-card">
                    <span class="about-card-label">At work</span>
                    <h3>Care for your workplace</h3>
                    <p>Arrange commercial cleaning for an office or business space. Share the areas to cover and the schedule you have in mind so the requested service can be discussed.</p>
                </article>
                <article class="content-card">
                    <span class="about-card-label">Between stays</span>
                    <h3>Prepare for a new arrival</h3>
                    <p>Request a short-term rental clean between guests, or cleaning before or after a move. Include the timing and any priorities that will help describe the space.</p>
                </article>
            </div>
        </section>

        <section class="content-section home-faq page-shell" aria-labelledby="home-faq-heading">
            <div class="eyebrow">Helpful details</div>
            <h2 id="home-faq-heading">Before you<br><span class="headline-accent">send a request.</span></h2>
            <div class="home-faq-list">
                <details>
                    <summary>Which areas does Bexxy serve?</summary>
                    <p>Bexxy Care and Clean serves Ottawa and Gatineau. Include your service location in the booking request so the team can discuss availability.</p>
                </details>
                <details>
                    <summary>Is cleaning available 24/7?</summary>
                    <p>Yes. Service is available 24 hours a day, seven days a week. Send your preferred date and arrival time; Bexxy will confirm the appointment with you.</p>
                </details>
                <details>
                    <summary>Can I ask for particular rooms or extra tasks?</summary>
                    <p>Yes. Describe the rooms and priorities in your request. Optional tasks such as oven, fridge, laundry, or interior-window cleaning are discussed and quoted separately.</p>
                </details>
                <details>
                    <summary>Does the booking form confirm my appointment?</summary>
                    <p>No. The form sends a cleaning request, not a confirmed appointment or payment. Bexxy will discuss the scope, availability, and final price with you first.</p>
                </details>
            </div>
            <a class="text-link home-faq-pricing" href="pricing.php">See how estimates work <span aria-hidden="true">↗</span></a>
        </section>

        <section class="content-contact home-contact page-shell" id="contact" aria-labelledby="home-contact-heading">
            <div>
                <div class="eyebrow">Serving Ottawa and Gatineau · available 24/7</div>
                <h2 id="home-contact-heading">Let’s talk about<br>your cleaning needs.</h2>
                <p>Call or email Bexxy Care and Clean with a question, or send a booking request with details about your space.</p>
                <div class="home-contact-links">
                    <a href="tel:+16137102787" aria-label="Call Bexxy Care and Clean at 613-710-2787">613-710-2787</a>
                    <a href="mailto:bexxycare.and.clean26@gmail.com">bexxycare.and.clean26@gmail.com</a>
                </div>
            </div>
            <a class="button button-dark" href="booking.php">Request a cleaning <span aria-hidden="true">↗</span></a>
        </section>

    </main>

    <footer class="site-footer" id="contact">
        <div class="footer-content page-shell">
            <div class="footer-intro">
                <a class="brand footer-brand" href="#home" aria-label="Bexxy Care and Clean home"><img src="image/download.png" alt="" width="480" height="140"></a>
                <p>Reliable home, office, and rental cleaning in Ottawa and Gatineau. From regular upkeep to deep and move-related cleans, Bexxy helps keep your space fresh.</p>
            </div>
            <section class="footer-column" aria-labelledby="footer-contact-heading">
                <h2 id="footer-contact-heading">Contact</h2>
                <a href="tel:+16137102787">613-710-2787</a>
                <a href="mailto:bexxycare.and.clean26@gmail.com">bexxycare.and.clean26@gmail.com</a>
            </section>
            <section class="footer-column" aria-labelledby="footer-hours-heading">
                <h2 id="footer-hours-heading">Service availability</h2>
                <p>24 hours a day</p>
                <p>Seven days a week</p>
                <p>Ottawa &amp; Gatineau</p>
            </section>
        </div>
        <div class="footer-bottom page-shell">
            <p>© <span id="copyright-year">2026</span> Bexxy Care and Clean</p>
            <a href="privacy.php">Privacy notice</a>
        </div>
    </footer>
    <script src="navigation.js" defer></script>
    <script src="language.js?v=5" defer></script>
</body>
</html>
