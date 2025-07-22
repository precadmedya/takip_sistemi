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
        'host' => getenv('SMTP_HOST') ?: 'smtp.yandex.com.tr',
        'port' => getenv('SMTP_PORT') ?: 465,
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl',
        'username' => getenv('SMTP_USER') ?: 'info@precadmedya.com.tr',
        'password' => getenv('SMTP_PASS') ?: 'Precadmedya34523',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Precad Medya Hizmet Takibi',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'info@precadmedya.com.tr'
    ]
];
