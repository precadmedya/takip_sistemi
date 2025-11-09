<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_NAME') ?: 'precadme_takip',
        'user' => getenv('DB_USER') ?: 'precadme_takip',
        'pass' => getenv('DB_PASS') ?: 'Kolega3452323',
        'charset' => 'utf8mb4'
    ],
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.yandex.com',
        'port' => getenv('SMTP_PORT') ?: 465,
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl',
        'username' => getenv('SMTP_USER') ?: 'muhasebe@precadmedya.com.tr',
        'password' => getenv('SMTP_PASS') ?: 'Precadmedya3452323',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Precad Medya',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'muhasebe@precadmedya.com.tr',
        'timeout' => getenv('SMTP_TIMEOUT') ?: 20,
        'reminder_copy_email' => getenv('REMINDER_COPY_EMAIL') ?: 'info@precadmedya.com.tr'
    ]
];
