<?php
declare(strict_types=1);

return [
    'database' => [
        'host' => '127.0.0.1',
        'name' => 'bexxy_cleaning',
        'username' => 'bexxy_app',
        'password' => 'REPLACE_WITH_A_UNIQUE_DATABASE_PASSWORD',
    ],
    'admin' => [
        'username' => 'REPLACE_WITH_A_PRIVATE_ADMIN_USERNAME',
        'password_hash' => 'REPLACE_WITH_OUTPUT_FROM_PASSWORD_HASH_COMMAND',
        'rate_limit_key' => 'REPLACE_WITH_OUTPUT_FROM_RANDOM_BYTES_COMMAND',
    ],
    'notifications' => [
        'from_email' => 'REPLACE_WITH_A_VERIFIED_SENDER_ADDRESS',
        'admin_email' => 'REPLACE_WITH_YOUR_BUSINESS_EMAIL',
    ],
];
