<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
start_secure_session();
$receipt_language = isset($_GET['lang']) && is_string($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';

$booking_id = filter_var($_SESSION['receipt_booking_id'] ?? null, FILTER_VALIDATE_INT);
if (!$booking_id || $booking_id < 1) {
    http_response_code(404);
    exit('This receipt is unavailable. Submit a booking request in this browser before downloading its receipt.');
}

try {
    $pdo = require __DIR__ . '/config/database.php';
    $statement = $pdo->prepare(
        'SELECT id, service, rooms, preferred_date, arrival_time, extra_services, estimated_total, currency_code, full_name, email, phone, service_address, notes, status, created_at
         FROM booking_requests
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $booking_id]);
    $booking = $statement->fetch();
} catch (PDOException $exception) {
    error_log('Bexxy receipt database error: ' . $exception->getMessage());
    http_response_code(503);
    exit('The receipt is temporarily unavailable. Please try again later.');
}

if (!$booking) {
    unset($_SESSION['receipt_booking_id']);
    http_response_code(404);
    exit('This receipt is unavailable.');
}

function receipt_pdf_text(string $text): string
{
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted === false) {
        $converted = '';
    }
    $converted = preg_replace('/[^\x20-\x7E]/', '', $converted) ?? '';
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
}

$extra_services = json_decode((string) $booking['extra_services'], true);
if (!is_array($extra_services)) {
    $extra_services = [];
}

$service_name = (string) $booking['service'];
$receipt_extras = $extra_services;
if ($receipt_language === 'fr') {
    $service_translations = [
        'Residential cleaning' => 'Nettoyage résidentiel',
        'Deep cleaning' => 'Nettoyage en profondeur',
        'Commercial cleaning' => 'Nettoyage commercial',
        'Move-in / move-out cleaning' => 'Nettoyage avant ou après déménagement',
        'Short-term rental cleaning' => 'Nettoyage de location à court terme',
        'Regular maintenance' => 'Entretien régulier',
    ];
    $extra_translations = [
        'Fridge cleaning' => 'Nettoyage du réfrigérateur',
        'Laundry' => 'Lessive',
        'Oven cleaning' => 'Nettoyage du four',
        'Interior windows' => 'Fenêtres intérieures',
        'Inside cabinets' => 'Intérieur des armoires',
        'Other extras listed in notes' => 'Autres options précisées dans les notes',
    ];
    $service_name = $service_translations[$service_name] ?? $service_name;
    $receipt_extras = array_map(
        static fn ($extra): string => is_string($extra) ? ($extra_translations[$extra] ?? $extra) : '',
        $extra_services
    );
    $lines = [
        'BEXXY CARE AND CLEAN',
        'REÇU DE DEMANDE DE RÉSERVATION - PAS UNE FACTURE',
        'Référence de la demande : #' . $booking['id'],
        'Soumise le : ' . date('d/m/Y \à H\hi', strtotime((string) $booking['created_at'])),
        'Statut : ' . ucfirst((string) $booking['status']) . ' - en attente de confirmation',
        '',
        'DÉTAILS DU SERVICE',
        'Service : ' . $service_name,
        'Pièces / zones : ' . $booking['rooms'],
        'Estimation : ' . ($booking['estimated_total'] === null ? 'À déterminer' : $booking['currency_code'] . ' ' . number_format((float) $booking['estimated_total'], 0)),
        'Date souhaitée : ' . date('d/m/Y', strtotime((string) $booking['preferred_date'])),
        'Heure d’arrivée souhaitée : ' . date('H\hi', strtotime((string) $booking['arrival_time'])),
        'Services supplémentaires : ' . ($receipt_extras ? implode(', ', $receipt_extras) : 'Aucun'),
        '',
        'COORDONNÉES DU CLIENT',
        'Nom : ' . $booking['full_name'],
        'Courriel : ' . $booking['email'],
        'Téléphone : ' . $booking['phone'],
        'Adresse du service : ' . $booking['service_address'],
    ];
} else {
    $lines = [
        'BEXXY CARE AND CLEAN',
        'BOOKING REQUEST RECEIPT - NOT AN INVOICE',
        'Request reference: #' . $booking['id'],
        'Submitted: ' . date('F j, Y \a\t g:i a', strtotime((string) $booking['created_at'])),
        'Status: ' . ucfirst((string) $booking['status']) . ' - awaiting confirmation',
        '',
        'SERVICE DETAILS',
        'Service: ' . $booking['service'],
        'Rooms / areas: ' . $booking['rooms'],
        'Estimated total: ' . ($booking['estimated_total'] === null ? 'To be quoted' : $booking['currency_code'] . ' ' . number_format((float) $booking['estimated_total'], 0)),
        'Preferred date: ' . date('F j, Y', strtotime((string) $booking['preferred_date'])),
        'Preferred arrival time: ' . date('g:i a', strtotime((string) $booking['arrival_time'])),
        'Extra services: ' . ($extra_services ? implode(', ', $extra_services) : 'None selected'),
        '',
        'CUSTOMER DETAILS',
        'Name: ' . $booking['full_name'],
        'Email: ' . $booking['email'],
        'Phone: ' . $booking['phone'],
        'Service address: ' . $booking['service_address'],
    ];
}
if ((string) $booking['notes'] !== '') {
    $lines[] = ($receipt_language === 'fr' ? 'Notes : ' : 'Notes: ') . $booking['notes'];
}
if ($receipt_language === 'fr') {
    $lines[] = '';
    $lines[] = 'Ce document confirme uniquement la réception de votre demande.';
    $lines[] = 'Aucun rendez-vous n’est confirmé et aucun paiement n’a été effectué.';
    $lines[] = 'Bexxy vous contactera pour confirmer les disponibilités et le prix.';
    $lines[] = 'Les options sont chiffrées séparément; ceci n’est pas une facture.';
} else {
    $lines[] = '';
    $lines[] = 'This document confirms receipt of your request only. No appointment has';
    $lines[] = 'been confirmed and no payment has been made. Bexxy will contact you';
    $lines[] = 'to confirm availability and any applicable price.';
    $lines[] = 'Extras are quoted separately; this estimate is not an invoice.';
}

$wrapped_lines = [];
foreach ($lines as $line) {
    $line = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line) ?: '';
    foreach (explode("\n", wordwrap($line, 88, "\n", true)) as $wrapped_line) {
        $wrapped_lines[] = $wrapped_line;
    }
}
$pages = array_chunk($wrapped_lines, 46);
$objects = [
    1 => '<< /Type /Catalog /Pages 2 0 R >>',
    3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
];
$page_ids = [];
foreach ($pages as $page_index => $page_lines) {
    $page_id = 4 + $page_index * 2;
    $content_id = $page_id + 1;
    $page_ids[] = $page_id . ' 0 R';

    $content = "BT\n/F1 18 Tf\n54 766 Td\n(" . receipt_pdf_text($page_lines[0] ?? '') . ") Tj\n";
    $content .= "/F1 10 Tf\n0 -24 Td\n(" . receipt_pdf_text($page_lines[1] ?? '') . ") Tj\n";
    foreach (array_slice($page_lines, 2) as $line) {
        $content .= "0 -15 Td\n(" . receipt_pdf_text($line) . ") Tj\n";
    }
    $content .= "ET\n";

    $objects[$page_id] = sprintf(
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
        $content_id
    );
    $objects[$content_id] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
}
$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $page_ids) . '] /Count ' . count($page_ids) . ' >>';
ksort($objects);

$pdf = "%PDF-1.4\n";
$offsets = [0 => 0];
foreach ($objects as $object_id => $object_body) {
    $offsets[$object_id] = strlen($pdf);
    $pdf .= $object_id . " 0 obj\n" . $object_body . "\nendobj\n";
}
$xref_offset = strlen($pdf);
$object_count = max(array_keys($objects)) + 1;
$pdf .= "xref\n0 " . $object_count . "\n";
$pdf .= "0000000000 65535 f \n";
for ($object_id = 1; $object_id < $object_count; $object_id++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$object_id] ?? 0);
}
$pdf .= "trailer\n<< /Size " . $object_count . " /Root 1 0 R >>\n";
$pdf .= "startxref\n" . $xref_offset . "\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Bexxy-request-' . (int) $booking['id'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
