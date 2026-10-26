<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/notifications.php';
date_default_timezone_set('America/Toronto');
start_secure_session();
$pricing = require __DIR__ . '/config/pricing.php';

function posted_string(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

$service_options = array_map(
    static fn (array $service): string => $service['label'],
    $pricing['services']
);
$arrival_times = [
    '06:00' => '6:00 a.m.',
    '07:00' => '7:00 a.m.',
    '08:00' => '8:00 a.m.',
    '09:00' => '9:00 a.m.',
    '10:00' => '10:00 a.m.',
    '11:00' => '11:00 a.m.',
    '12:00' => '12:00 p.m.',
    '13:00' => '1:00 p.m.',
    '14:00' => '2:00 p.m.',
    '15:00' => '3:00 p.m.',
    '16:00' => '4:00 p.m.',
    '17:00' => '5:00 p.m.',
    '18:00' => '6:00 p.m.',
];
$extra_options = [
    'Fridge cleaning',
    'Laundry',
    'Oven cleaning',
    'Interior windows',
    'Inside cabinets',
    'Other extras listed in notes',
];
$form = [
    'service' => '',
    'rooms' => '',
    'preferred_date' => '',
    'arrival_time' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'notes' => '',
];
$requested_service = isset($_GET['service']) && is_string($_GET['service']) ? $_GET['service'] : '';
if (isset($service_options[$requested_service])) {
    $form['service'] = $requested_service;
}
$selected_extras = [];
$errors = [];
$booking_saved = !empty($_SESSION['booking_saved']);
unset($_SESSION['booking_saved']);
$customer_email_sent = !empty($_SESSION['customer_email_sent']);
unset($_SESSION['customer_email_sent']);
$booking_language = isset($_SESSION['booking_language']) && $_SESSION['booking_language'] === 'fr' ? 'fr' : 'en';
unset($_SESSION['booking_language']);
$estimated_total = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = posted_string($key);
    }

    if (isset($_POST['extras']) && is_array($_POST['extras'])) {
        $selected_extras = array_values(array_unique(array_filter(
            $_POST['extras'],
            static fn ($item): bool => is_string($item)
        )));
    }

    $submitted_token = posted_string('csrf_token');
    if (!valid_csrf_token($submitted_token)) {
        $errors[] = 'Your form session expired. Refresh the page and submit your request again.';
    }
    if (posted_string('website') !== '') {
        http_response_code(400);
        exit('Invalid booking request.');
    }
    $submitted_at = filter_var(posted_string('form_started_at'), FILTER_VALIDATE_INT);
    if ($submitted_at === false || $submitted_at > time() || time() - $submitted_at < 3) {
        $errors[] = 'Please wait a moment and submit your request again.';
    }
    if (!isset($_SESSION['booking_submissions'])) {
        $_SESSION['booking_submissions'] = [];
    }
    $_SESSION['booking_submissions'] = array_values(array_filter(
        $_SESSION['booking_submissions'],
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > time() - 3600
    ));
    if (count($_SESSION['booking_submissions']) >= 5) {
        $errors[] = 'Too many booking attempts. Please wait and try again later.';
    }

    if (!in_array($form['service'], array_keys($service_options), true)) {
        $errors[] = 'Choose a valid cleaning service.';
    }

    $rooms = filter_var($form['rooms'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 100],
    ]);
    if ($rooms === false) {
        $errors[] = 'Enter a room or area count from 1 to 100.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['preferred_date']);
    $date_errors = DateTimeImmutable::getLastErrors();
    if (
        !$date
        || ($date_errors !== false && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0))
        || $date->format('Y-m-d') !== $form['preferred_date']
        || $date < new DateTimeImmutable('today')
    ) {
        $errors[] = 'Choose a valid date that is today or later.';
    }

    if (!isset($arrival_times[$form['arrival_time']])) {
        $errors[] = 'Choose an arrival time between 6:00 a.m. and 6:00 p.m.';
    } elseif ($date && $date->format('Y-m-d') === (new DateTimeImmutable('today'))->format('Y-m-d')) {
        $requested_arrival = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $form['preferred_date'] . ' ' . $form['arrival_time']
        );
        if ($requested_arrival && $requested_arrival <= new DateTimeImmutable()) {
            $errors[] = 'Choose a future arrival time for today.';
        }
    }

    if ($form['full_name'] === '' || text_length($form['full_name']) > 150) {
        $errors[] = 'Enter your name (up to 150 characters).';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\s().-]{7,30}$/D', $form['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if ($form['address'] === '' || text_length($form['address']) > 500) {
        $errors[] = 'Enter the service address (up to 500 characters).';
    }
    if (text_length($form['notes']) > 3000) {
        $errors[] = 'Keep your notes under 3,000 characters.';
    }
    if (array_diff($selected_extras, $extra_options)) {
        $errors[] = 'One or more selected extra services are invalid.';
    }

    if (!$errors) {
        try {
            $estimated_total = $pricing['services'][$form['service']]['base']
                + ($pricing['services'][$form['service']]['per_room'] * $rooms);
            $pdo = require __DIR__ . '/config/database.php';
            $statement = $pdo->prepare(
                'INSERT INTO booking_requests
                    (service, rooms, preferred_date, arrival_time, extra_services, estimated_total, currency_code, full_name, email, phone, service_address, notes)
                 VALUES
                    (:service, :rooms, :preferred_date, :arrival_time, :extra_services, :estimated_total, :currency_code, :full_name, :email, :phone, :service_address, :notes)'
            );
            $statement->execute([
                ':service' => $service_options[$form['service']],
                ':rooms' => $rooms,
                ':preferred_date' => $form['preferred_date'],
                ':arrival_time' => $form['arrival_time'] . ':00',
                ':extra_services' => json_encode($selected_extras, JSON_THROW_ON_ERROR),
                ':estimated_total' => $estimated_total,
                ':currency_code' => $pricing['currency_code'],
                ':full_name' => $form['full_name'],
                ':email' => $form['email'],
                ':phone' => $form['phone'],
                ':service_address' => $form['address'],
                ':notes' => $form['notes'],
            ]);
            $booking_details = [
                'id' => (int) $pdo->lastInsertId(),
                'service' => $service_options[$form['service']],
                'preferred_date' => $form['preferred_date'],
                'arrival_time' => $form['arrival_time'] . ':00',
                'full_name' => $form['full_name'],
                'email' => $form['email'],
            ];
            $booking_language = posted_string('language') === 'fr' ? 'fr' : 'en';
            $email_results = send_booking_notifications($booking_details, $booking_language);
            $_SESSION['booking_submissions'][] = time();
            $_SESSION['booking_saved'] = true;
            $_SESSION['customer_email_sent'] = $email_results['customer'];
            $_SESSION['booking_language'] = $booking_language;
            $_SESSION['receipt_booking_id'] = $booking_details['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: booking.php', true, 303);
            exit;
        } catch (PDOException $exception) {
            error_log('Bexxy booking database error: ' . $exception->getMessage());
            $errors[] = 'We could not save your request right now. Please try again later or contact Bexxy directly.';
        } catch (JsonException $exception) {
            error_log('Bexxy booking extras encoding error: ' . $exception->getMessage());
            $errors[] = 'We could not process your request right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e7ebef">
    <meta name="description" content="Request a Bexxy cleaning appointment in Ottawa or Gatineau.">
    <title>Book a cleaning — Bexxy Care and Clean</title>
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

    <main class="booking-page page-shell">
        <div class="booking-heading">
            <div class="eyebrow"><span class="eyebrow-star" aria-hidden="true">✳</span> Ottawa &amp; Gatineau</div>
            <h1>Let’s plan your<br><span class="headline-accent">next clean.</span></h1>
            <p>Share a few details about the space and your preferred time. We’ll follow up to confirm availability and your booking.</p>
        </div>

        <form class="booking-form" id="booking-form" method="post" action="booking.php">
            <input type="hidden" name="csrf_token" value="<?= escape_html(new_csrf_token()) ?>">
            <input type="hidden" name="form_started_at" value="<?= time() ?>">
            <input type="hidden" id="booking-language" name="language" value="en">
            <div class="booking-honeypot" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <?php if ($booking_saved): ?>
                <div class="booking-alert booking-alert-success" role="status">
                    <strong>Your booking request was received.</strong> This is not a confirmed appointment. Bexxy will contact you to confirm availability.
                    <?php if ($customer_email_sent): ?>
                        <span><?= $booking_language === 'fr' ? 'Un accusé de réception a été envoyé à votre adresse courriel.' : 'We sent an acknowledgement to your email address.' ?></span>
                    <?php else: ?>
                        <span><?= $booking_language === 'fr' ? 'Nous n’avons pas pu envoyer de courriel; votre demande est enregistrée. Veuillez conserver votre numéro de référence.' : 'We could not send an email, but your request is saved. Please keep your reference number.' ?></span>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['receipt_booking_id'])): ?>
                        <span class="booking-reference">Request reference: #<?= (int) $_SESSION['receipt_booking_id'] ?></span>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['receipt_booking_id'])): ?>
                        <a class="receipt-download" href="receipt.php" target="_blank" rel="noopener">Download your request receipt</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="booking-alert booking-alert-error" role="alert">
                    <strong>Please review your request:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= escape_html($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <section class="booking-group" aria-labelledby="service-details-heading">
                <div class="booking-group-heading">
                    <span>01</span>
                    <h2 id="service-details-heading">Your cleaning</h2>
                </div>

                <div class="form-grid">
                    <label class="form-field" for="service-type">
                        <span>Type of service <span aria-hidden="true">*</span></span>
                        <select id="service-type" name="service" required>
                            <option value="">Choose a service</option>
                            <?php foreach ($service_options as $slug => $label): ?>
                                <option value="<?= escape_html($slug) ?>" <?= $form['service'] === $slug ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-field" for="room-count">
                        <span>Number of rooms / areas <span aria-hidden="true">*</span></span>
                        <input id="room-count" name="rooms" type="number" min="1" max="100" inputmode="numeric" placeholder="For example, 5" value="<?= escape_html($form['rooms']) ?>" required>
                    </label>
                    <label class="form-field" for="booking-date">
                        <span>Preferred date <span aria-hidden="true">*</span></span>
                        <input id="booking-date" name="preferred_date" type="date" value="<?= escape_html($form['preferred_date']) ?>" required>
                    </label>
                    <label class="form-field" for="arrival-time">
                        <span>Preferred arrival time <span aria-hidden="true">*</span></span>
                        <select id="arrival-time" name="arrival_time" required>
                            <option value="">Choose a time</option>
                            <?php foreach ($arrival_times as $value => $label): ?>
                                <option value="<?= escape_html($value) ?>" <?= $form['arrival_time'] === $value ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </section>
            <!-- <section class="price-estimate" aria-labelledby="estimate-heading" aria-live="polite">
                <div>
                    <h2 id="estimate-heading">Estimated total</h2>
                    <p id="estimate-value">Select a service and room count to see an estimate.</p>
                </div>
                <p class="estimate-note">The displayed per-room rate applies. Extras are quoted separately and aren’t included. Bexxy will confirm the final price and availability before the appointment is booked.</p>
            </section> -->

            <fieldset class="booking-group extras-group">
                <legend class="booking-group-heading">
                    <span>02</span>
                    <span class="booking-group-title">Extra services</span>
                </legend>
                <p class="group-help">Select any extras you’d like us to discuss.</p>
                <div class="extras-grid">
                    <?php foreach ($extra_options as $extra): ?>
                        <label class="extra-option">
                            <input type="checkbox" name="extras[]" value="<?= escape_html($extra) ?>" <?= in_array($extra, $selected_extras, true) ? 'checked' : '' ?>>
                            <span><?= $extra === 'Other extras listed in notes' ? 'Other (describe in notes)' : escape_html($extra) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <section class="booking-group" aria-labelledby="client-details-heading">
                <div class="booking-group-heading">
                    <span>03</span>
                    <h2 id="client-details-heading">Your details</h2>
                </div>
                <div class="form-grid">
                    <label class="form-field" for="client-name">
                        <span>Full name <span aria-hidden="true">*</span></span>
                        <input id="client-name" name="full_name" type="text" autocomplete="name" maxlength="150" value="<?= escape_html($form['full_name']) ?>" required>
                    </label>
                    <label class="form-field" for="client-email">
                        <span>Email address <span aria-hidden="true">*</span></span>
                        <input id="client-email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= escape_html($form['email']) ?>" required>
                    </label>
                    <label class="form-field" for="client-phone">
                        <span>Phone number <span aria-hidden="true">*</span></span>
                        <input id="client-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= escape_html($form['phone']) ?>" required>
                    </label>
                    <label class="form-field" for="client-address">
                        <span>Service address <span aria-hidden="true">*</span></span>
                        <input id="client-address" name="address" type="text" autocomplete="street-address" maxlength="500" value="<?= escape_html($form['address']) ?>" required>
                    </label>
                    <label class="form-field form-field-wide" for="booking-notes">
                        <span>Notes for our team</span>
                        <textarea id="booking-notes" name="notes" rows="4" maxlength="3000" placeholder="Anything else we should know about the space or your request?"><?= escape_html($form['notes']) ?></textarea>
                    </label>
                </div>
            </section>

            <div class="booking-submit">
                <p>Your request is not confirmed until Bexxy replies to confirm the date and time. No payment is taken on this form.</p>
                <button class="button button-primary" type="submit">Send booking request <span aria-hidden="true">↗</span></button>
            </div>
        </form>
    </main>
    <footer class="site-footer">
        <div class="footer-bottom page-shell">
            <p>© 2026 Bexxy Care and Clean · Ottawa &amp; Gatineau</p>
            <a href="privacy.php">Privacy notice</a>
        </div>
    </footer>

    <script>
        const serviceSelect = document.querySelector("#service-type");
        const roomCount = document.querySelector("#room-count");
        const estimateValue = document.querySelector("#estimate-value");
        const requestedService = new URLSearchParams(window.location.search).get("service");
        const serviceOptions = {
            <?php foreach ($service_options as $slug => $label): ?>
                <?= json_encode($slug) ?>: <?= json_encode($label, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            <?php endforeach; ?>
        };
        const pricing = <?= json_encode($pricing, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;

        function updateEstimate() {
            if (!estimateValue) {
                return;
            }

            const service = pricing.services[serviceSelect.value];
            const rooms = Number.parseInt(roomCount.value, 10);
            if (!service || !Number.isInteger(rooms) || rooms < 1 || rooms > 100) {
                estimateValue.textContent = "Select a service and room count to see an estimate.";
                return;
            }

            const total = service.base + service.per_room * rooms;
            estimateValue.textContent = `${pricing.currency_label} ${new Intl.NumberFormat("en").format(total)}`;
        }

        if (requestedService && serviceOptions[requestedService] && !serviceSelect.value) {
            serviceSelect.value = serviceOptions[requestedService];
        }
        serviceSelect.addEventListener("change", updateEstimate);
        roomCount.addEventListener("input", updateEstimate);
        updateEstimate();

        const today = new Date();
        today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
        document.querySelector("#booking-date").min = today.toISOString().slice(0, 10);
        try {
            document.querySelector("#booking-language").value = localStorage.getItem("bexxy-language") === "fr" ? "fr" : "en";
        } catch (error) {
            console.error("Could not read the saved language preference.", error);
        }
    </script>
    <script src="navigation.js" defer></script>
    <script src="language.js?v=1" defer></script>
</body>
</html>
