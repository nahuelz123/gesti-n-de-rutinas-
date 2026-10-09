<?php

return [
    'operator_name' => env('LEGAL_OPERATOR_NAME'),
    'contact_email' => env('LEGAL_CONTACT_EMAIL'),

    'versions' => [
        'privacy' => env('LEGAL_PRIVACY_VERSION', '2026-10-04'),
        'terms' => env('LEGAL_TERMS_VERSION', '2026-10-04'),
        'ai_data_processing' => env('LEGAL_AI_CONSENT_VERSION', '1.0'),
        'routine_photo_upload' => env('LEGAL_PHOTO_CONSENT_VERSION', '1.0'),
        'meal_photo_analysis' => env('LEGAL_MEAL_PHOTO_CONSENT_VERSION', '1.0'),
    ],
];
