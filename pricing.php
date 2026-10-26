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
    <meta name="description" content="See Bexxy's per-room cleaning estimates and learn how extras are quoted.">
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
            <div class="eyebrow"><span class="eyebrow-star" aria-hidden="true">✳</span> Straightforward estimates</div>
            <h1>Know how your<br><span class="headline-accent">estimate adds up.</span></h1>
            <p>Cleaning estimates start with the number of rooms or areas you request. Select a service and room count in the booking form to see an estimate before sending your request.</p>
            <a class="button button-primary" href="booking.php">Get an estimate <span aria-hidden="true">↗</span></a>
        </section>

        <section class="content-section" aria-labelledby="pricing-heading">
            <div class="eyebrow">Per-room estimates</div>
            <h2 id="pricing-heading">One clear starting<br><span class="headline-accent">point for each service.</span></h2>
            <div class="pricing-grid">
                <?php foreach ($pricing['services'] as $slug => $service): ?>
                    <article class="pricing-card">
                        <h3><?= escape_html($service['label']) ?></h3>
                        <p class="pricing-amount"><?= escape_html($pricing['currency_label']) ?> <?= number_format($service['per_room'], 0) ?><span> / room</span></p>
                        <p><?= $service['base'] > 0 ? escape_html($pricing['currency_label'] . ' ' . number_format($service['base'], 0) . ' base fee, plus per-room rate.') : 'No additional base fee.' ?></p>
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
                <p><strong>Estimate = service base fee + (per-room rate × rooms).</strong></p>
                <p>For example, 3 rooms at <?= escape_html($pricing['currency_label']) ?> <?= number_format($pricing['services']['deep']['per_room'], 0) ?> per room gives an estimated service subtotal of <?= escape_html($pricing['currency_label']) ?> <?= number_format($pricing['services']['deep']['base'] + 3 * $pricing['services']['deep']['per_room'], 0) ?> before optional extras.</p>
                <p>Fridge cleaning, laundry, oven cleaning, interior windows, inside cabinets, and other requested extras are quoted separately. Nothing is added for an extra until the price is discussed with you.</p>
                <p>The estimate is not a final quote or appointment confirmation. Bexxy will confirm scope, availability, and the final price before the service is booked. No no-show or lateness fee is currently set.</p>
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
    <script src="language.js?v=1" defer></script>
</body>
</html>