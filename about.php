<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
start_secure_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e7ebef">
    <meta name="description" content="Learn about Bexxy Care and Clean and the homes, workplaces, and rentals it serves.">
    <title>About Bexxy — Bexxy Care and Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="Bexxy Care and Clean home">
            <img src="image/download.png" alt="Bexxy Care and Clean" width="480" height="140">
        </a>
        <nav class="primary-nav" aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="about.php" aria-current="page">About</a>
            <a href="index.php#services">Services</a>
            <a href="pricing.php">Pricing</a>
            <a href="index.php#contact">Contact</a>
            <a class="nav-cta" href="booking.php">Book now <span aria-hidden="true">↗</span></a>
        </nav>
    </header>

    <main class="content-page page-shell">
        <section class="content-hero about-hero">
            <div class="about-hero-copy">
                <div class="eyebrow"><span class="eyebrow-star" aria-hidden="true">✳</span> A bit about Bexxy</div>
                <h1>A little less<br>to <span class="headline-accent">worry about.</span></h1>
                <p>Cleaning is one of those jobs that can keep getting pushed to tomorrow. Bexxy helps take it off your list, with cleaning for homes, workplaces, and short-term rentals in Ottawa and Gatineau.</p>
                <a class="button button-primary" href="booking.php">Tell us what you need <span aria-hidden="true">↗</span></a>
            </div>
            <figure class="about-hero-image">
                <img src="image/services/commercial-cleaning.jpg" alt="A cleaning team working in a bright commercial kitchen" width="700" height="467">
                <figcaption>Good to come home to.</figcaption>
            </figure>
        </section>

        <section class="content-section" aria-labelledby="about-spaces-heading">
            <div class="eyebrow">What we can help with</div>
            <h2 id="about-spaces-heading">From the weekly tidy-up<br>to <span class="headline-accent">moving day.</span></h2>
            <div class="content-card-grid">
                <article class="content-card">
                    <span class="about-card-label">At home</span>
                    <h3>Your place, your priorities</h3>
                    <p>Regular upkeep or a deeper clean. Let us know which rooms need attention and what matters most to you.</p>
                </article>
                <article class="content-card">
                    <span class="about-card-label">At work</span>
                    <h3>Workplaces</h3>
                    <p>Tell us about your space, the areas you want cleaned, and a schedule that works for your business.</p>
                </article>
                <article class="content-card">
                    <span class="about-card-label">Between stays</span>
                    <h3>Moves and rentals</h3>
                    <p>Cleaning for move-in and move-out days, or a short-term rental getting ready for its next guest.</p>
                </article>
            </div>
        </section>

        <section class="content-section content-process" aria-labelledby="about-process-heading">
            <div>
                <div class="eyebrow">No guesswork</div>
                <h2 id="about-process-heading">First, we’ll figure out<br><span class="headline-accent">what you need.</span></h2>
            </div>
            <div class="content-copy">
                <p>Send us a few details about the space, the date you have in mind, and anything you’d like us to pay extra attention to. We’ll get back to you to talk through availability and price.</p>
                <p>Filling in the form doesn’t book or charge you for anything. We’ll confirm the appointment and final price with you first. Extras are priced separately.</p>
                <a class="text-link" href="pricing.php">See how pricing works <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        <section class="content-contact" aria-labelledby="about-contact-heading">
            <div>
                <div class="eyebrow">Need to ask something first?</div>
                <h2 id="about-contact-heading">We’re happy to hear from you.</h2>
                <p>Email <a href="mailto:bexxycare.and.clean26@gmail.com">bexxycare.and.clean26@gmail.com</a>, or include your question in a booking request.</p>
            </div>
            <a class="button button-dark" href="booking.php">Get in touch <span aria-hidden="true">↗</span></a>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-bottom page-shell">
            <p>© 2026 Bexxy Care and Clean</p>
            <a href="privacy.php">Privacy notice</a>
        </div>
    </footer>
    <script src="navigation.js" defer></script>
    <script src="language.js?v=1" defer></script>
</body>
</html>