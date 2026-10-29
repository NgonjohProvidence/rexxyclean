<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
start_secure_session();
$pricing = require __DIR__ . '/config/pricing.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e7ebef">
    <meta name="description" content="See Bexxy's hourly cleaning estimates and learn how extras are quoted.">
    <title>Cleaning prices — Bexxy Care and Clean</title>
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
            <a href="about.php">About</a>
            <a href="index.php#services">Services</a>
            <a href="pricing.php" aria-current="page">Pricing</a>
            <a href="index.php#contact">Contact</a>
            <a class="nav-cta" href="booking.php">Book now <span aria-hidden="true">↗</span></a>
        </nav>
    </header>

    <main class="content-page page-shell">
        <section class="content-hero">
            <div class="eyebrow"><span class="eyebrow-star" aria-hidden="true">✳</span> Hourly estimates</div>
            <h1>Know how your<br><span class="headline-accent">estimate adds up.</span></h1>
            <p>Each service has an estimated rate per cleaner-hour. Choose the cleaning duration and team size in the booking form to see an estimate before sending your request.</p>
            <a class="button button-primary" href="booking.php">Get an estimate <span aria-hidden="true">↗</span></a>
        </section>

        <section class="content-section" aria-labelledby="pricing-heading">
            <div class="eyebrow">CAD per cleaner-hour</div>
            <h2 id="pricing-heading">One clear starting<br><span class="headline-accent">point for each service.</span></h2>
            <div class="pricing-grid">
                <?php foreach ($pricing['services'] as $slug => $service): ?>
                    <article class="pricing-card">
                        <h3><?= escape_html($service['label']) ?></h3>
                        <p class="pricing-amount"><?= escape_html($pricing['currency_label']) ?> <?= number_format($service['per_hour'], 2) ?><span> / cleaner-hour</span></p>
                        <p>Preliminary estimate; final pricing depends on the scope and is confirmed by Bexxy.</p>
                        <a class="text-link" href="booking.php?service=<?= rawurlencode((string) $slug) ?>">Request this service <span aria-hidden="true">↗</span></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-section content-process" aria-labelledby="pricing-formula-heading">
            <div>
                <div class="eyebrow">How estimates work</div>
                <h2 id="pricing-formula-heading">A simple<br><span class="headline-accent">calculation.</span></h2>
            </div>
            <div class="content-copy">
                <p><strong>Estimate = hourly rate × estimated duration × number of cleaners.</strong></p>
                <p>For example, 2.5 hours with 2 cleaners at <?= escape_html($pricing['currency_label']) ?> <?= number_format($pricing['services']['deep']['per_hour'], 2) ?> per cleaner-hour gives an estimated service subtotal of <?= escape_html($pricing['currency_label']) ?> <?= number_format(2.5 * 2 * $pricing['services']['deep']['per_hour'], 2) ?> before optional extras.</p>
                <p>Fridge cleaning, laundry, oven cleaning, interior windows, inside cabinets, and other requested extras are quoted separately. Nothing is added for an extra until the price is discussed with you.</p>
                <p>Rates are preliminary estimates, not an official market standard or a final quote. Bexxy will confirm scope, availability, and the final price before the service is booked. No no-show or lateness fee is currently set.</p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-bottom page-shell">
            <p>© 2026 Bexxy Care and Clean</p>
            <a href="privacy.php">Privacy notice</a>
        </div>
    </footer>
    <script src="navigation.js" defer></script>
    <script src="language.js?v=5" defer></script>
</body>
</html>