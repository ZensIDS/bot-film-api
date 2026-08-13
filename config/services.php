<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'channel_id' => env('TELEGRAM_CHANNEL_ID'),
        // Base URL Telegram Mini App (TWA), mis. https://xxxx.ngrok-free.app
        // Dipakai untuk tombol "Ruang Drama" dan link checkout dari bot.
        'webapp_url' => env('TELEGRAM_WEBAPP_URL'),
        // Username bot tanpa "@", mis. "reel_gate_bot" -> https://t.me/reel_gate_bot
        // Dipakai untuk generate deep link/share link kalau dibutuhkan fitur lain.
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        // Daftar chat_id (user pribadi maupun channel) yang boleh pakai fitur ambil
        // telegram_file_id otomatis dari video yang dikirim/di-post ke bot, dipisah koma.
        // Contoh: TELEGRAM_ADMIN_IDS=111111111,-1001234567890
        'admin_ids' => env('TELEGRAM_ADMIN_IDS', ''),
    ],

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

];
