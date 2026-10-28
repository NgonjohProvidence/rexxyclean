<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

$pricing_json = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'pricing.json');
if ($pricing_json === false) {
    throw new RuntimeException('Pricing configuration could not be read.');
}

$pricing = json_decode($pricing_json, true, 512, JSON_THROW_ON_ERROR);
if (
    !is_array($pricing)
    || !isset($pricing['currency_code'], $pricing['currency_label'], $pricing['services'], $pricing['extras'])
    || !is_string($pricing['currency_code'])
    || !is_string($pricing['currency_label'])
    || !is_array($pricing['services'])
    || !is_array($pricing['extras'])
) {
    throw new RuntimeException('Pricing configuration is incomplete.');
}

foreach (['residential', 'deep', 'commercial', 'move', 'rental', 'maintenance'] as $service_slug) {
    $service = $pricing['services'][$service_slug] ?? null;
    if (
        !is_array($service)
        || !isset($service['label'], $service['per_hour'])
        || !is_string($service['label'])
        || !is_int($service['per_hour'])
        || $service['per_hour'] < 0
    ) {
        throw new RuntimeException('Pricing configuration has an invalid service rate: ' . $service_slug);
    }
}

return $pricing;
