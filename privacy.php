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
    <meta name="description" content="How Bexxy Care and Clean handles information submitted through its booking form.">
    <title>Privacy notice — Bexxy Care and Clean</title>
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
            <a href="pricing.php">Pricing</a>
            <a href="index.php#contact">Contact</a>
            <a class="nav-cta" href="booking.php">Book now <span aria-hidden="true">↗</span></a>
        </nav>
    </header>

    <main class="privacy-page page-shell">
        <div class="eyebrow">Your information matters</div>
        <h1>Privacy <span class="headline-accent">notice.</span></h1>
        <p class="privacy-updated">Last updated: October 22, 2026</p>
        <p>This notice describes how Bexxy Care and Clean handles information you provide through the booking request form. It is a plain-language starting point and should be reviewed for the privacy laws that apply to the business before the website is launched publicly.</p>

        <section>
            <h2>Information you provide</h2>
            <p>The booking form asks for your name, email address, phone number, service address, cleaning service, room or area count, estimated cleaning duration and team size, preferred date and arrival time, optional extras, and notes.</p>
        </section>
        <section>
            <h2>How it is used</h2>
            <p>Bexxy uses these details to review and respond to your cleaning request, discuss availability, and coordinate the requested service. Submitting a form sends a request only; it does not confirm an appointment or make a payment.</p>
        </section>
        <section>
            <h2>Storage and access</h2>
            <p>Booking requests are stored in the website's database. Access should be limited to authorized Bexxy staff. The hosting provider may also process technical information needed to operate the website. Do not use this form to send payment card details or other sensitive information.</p>
        </section>
        <section>
            <h2>Retention and your choices</h2>
            <p>Booking information should be kept only as long as needed to respond to and manage the request, meet applicable recordkeeping requirements, and resolve related issues. Bexxy should define an appropriate retention period and securely remove records that are no longer needed. To ask about, correct, or request removal of information you submitted, contact <a href="mailto:bexxycare.and.clean26@gmail.com">bexxycare.and.clean26@gmail.com</a>.</p>
        </section>
        <section>
            <h2>Contact</h2>
            <p>Questions about this notice can be sent to <a href="mailto:bexxycare.and.clean26@gmail.com">bexxycare.and.clean26@gmail.com</a>.</p>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-bottom page-shell">
            <p>© <span id="copyright-year">2026</span> Bexxy Care and Clean</p>
            <a href="index.php">Return to the website</a>
        </div>
    </footer>
    <script src="navigation.js" defer></script>
    <script src="language.js?v=5" defer></script>
</body>
</html>
