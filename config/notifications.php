<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

function send_booking_email(string $recipient, string $subject, string $message, string $from_email): bool
{
    $headers = [
        'From: Bexxy Care and Clean <' . $from_email . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    set_error_handler(
        static function (int $severity, string $message, string $file, int $line): void {
            throw new ErrorException($message, 0, $severity, $file, $line);
        },
        E_WARNING
    );

    try {
        $sent = mail($recipient, $subject, $message, implode("\r\n", $headers));
    } catch (ErrorException $exception) {
        error_log('Bexxy booking email transport error: ' . $exception->getMessage());
        return false;
    } finally {
        restore_error_handler();
    }

    if (!$sent) {
        error_log('Bexxy booking email transport returned failure.');
    }

    return $sent;
}

function send_booking_notifications(array $booking, string $language): array
{
    try {
        $config = private_config();
    } catch (RuntimeException $exception) {
        error_log('Bexxy booking email configuration error: ' . $exception->getMessage());
        return ['customer' => false, 'admin' => false];
    }

    $settings = $config['notifications'] ?? null;
    if (!is_array($settings)) {
        error_log('Bexxy booking email configuration is missing notification settings.');
        return ['customer' => false, 'admin' => false];
    }

    $from_email = $settings['from_email'] ?? '';
    $admin_email = $settings['admin_email'] ?? '';
    if (!is_string($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        error_log('Bexxy booking email configuration has an invalid sender address.');
        return ['customer' => false, 'admin' => false];
    }
    if (!is_string($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        error_log('Bexxy booking email configuration has an invalid admin address.');
        $admin_email = '';
    }

    $is_french = $language === 'fr';
    $reference = '#' . (int) $booking['id'];
    $date = date($is_french ? 'd/m/Y' : 'F j, Y', strtotime((string) $booking['preferred_date']));
    $time = date($is_french ? 'H\hi' : 'g:i a', strtotime((string) $booking['arrival_time']));
    $hours = rtrim(rtrim(number_format((float) $booking['estimated_hours'], 1), '0'), '.');
    $total = (string) $booking['currency_code'] . ' ' . number_format(
        (float) $booking['estimated_total'],
        $booking['currency_code'] === 'CAD' ? 2 : 0
    );
    $customer_message = $is_french
        ? "Bonjour " . $booking['full_name'] . ",\n\nNous avons bien reçu votre demande de nettoyage " . $reference . ".\n\nService : " . $booking['service'] . "\nDurée estimée : " . $hours . " h avec " . $booking['cleaner_count'] . " préposé(s)\nEstimation : " . $total . "\nDate souhaitée : " . $date . "\nHeure d’arrivée souhaitée : " . $time . "\n\nCeci est un accusé de réception, pas une confirmation de rendez-vous. Nous vous contacterons pour confirmer les disponibilités et le prix. Votre estimation n’est pas une facture.\n\nVous pouvez télécharger le reçu PDF depuis la page de confirmation dans le navigateur utilisé pour envoyer la demande.\n\nBexxy Care and Clean"
        : "Hello " . $booking['full_name'] . ",\n\nWe received your cleaning request " . $reference . ".\n\nService: " . $booking['service'] . "\nEstimated duration: " . $hours . " hours with " . $booking['cleaner_count'] . " cleaner(s)\nEstimate: " . $total . "\nPreferred date: " . $date . "\nPreferred arrival time: " . $time . "\n\nThis is an acknowledgement, not a confirmed appointment. We will contact you to confirm availability and pricing. Your estimate is not an invoice.\n\nYou can download the PDF receipt from the confirmation page in the browser you used to submit the request.\n\nBexxy Care and Clean";
    $customer_subject = $is_french
        ? 'Demande de nettoyage recue ' . $reference
        : 'Cleaning request received ' . $reference;
    $customer_sent = filter_var((string) $booking['email'], FILTER_VALIDATE_EMAIL)
        ? send_booking_email((string) $booking['email'], $customer_subject, $customer_message, $from_email)
        : false;

    $admin_sent = false;
    if ($admin_email !== '') {
        $admin_subject = 'New cleaning request ' . $reference;
        $admin_message = "A new cleaning request was submitted.\n\n"
            . "Reference: " . $reference . "\n"
            . "Service: " . $booking['service'] . "\n"
            . "Estimated duration: " . $hours . " hours with " . $booking['cleaner_count'] . " cleaner(s)\n"
            . "Estimate: " . $total . "\n"
            . "Preferred date: " . $date . "\n"
            . "Preferred arrival time: " . $time . "\n\n"
            . "Review the request in the private Bexxy booking dashboard.";
        $admin_sent = send_booking_email($admin_email, $admin_subject, $admin_message, $from_email);
    } else {
        error_log('Bexxy admin booking notification was not sent because no valid admin email is configured.');
    }

    return ['customer' => $customer_sent, 'admin' => $admin_sent];
}
