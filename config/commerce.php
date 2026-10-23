<?php

return [
    'public_url' => env('COMMERCE_PUBLIC_URL', env('APP_URL', 'http://localhost')),
    'indexable' => env('COMMERCE_INDEXABLE', true),
    'search_console_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'inbox_email' => env('COMMERCE_INBOX_EMAIL', env('LEGAL_CONTACT_EMAIL')),
    'coaching_gym_id' => env('COACHING_GYM_ID'),
    'trial_days' => 14,
    'grace_days' => 7,
    'one_off_routine_price' => 29900,
    'mercadopago_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'mercadopago_webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'plans' => [
        'coach-inicial' => ['name' => 'Entrenador Inicial', 'audience' => 'coach', 'price' => 14900, 'students' => 10, 'staff' => 1, 'kind' => 'software'],
        'coach-profesional' => ['name' => 'Entrenador Profesional', 'audience' => 'coach', 'price' => 24900, 'students' => 30, 'staff' => 1, 'kind' => 'software'],
        'gym-inicial' => ['name' => 'Gimnasio Inicial', 'audience' => 'gym', 'price' => 34900, 'students' => 100, 'staff' => 3, 'kind' => 'software'],
        'gym-profesional' => ['name' => 'Gimnasio Profesional', 'audience' => 'gym', 'price' => 59900, 'students' => 300, 'staff' => 10, 'kind' => 'software'],
        'asesoria' => ['name' => 'Asesoría mensual', 'audience' => 'personal', 'price' => 69900, 'kind' => 'coaching'],
        'asesoria-premium' => ['name' => 'Acompañamiento premium', 'audience' => 'personal', 'price' => 109900, 'kind' => 'coaching'],
    ],
];
