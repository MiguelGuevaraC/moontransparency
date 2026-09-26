<?php

return [
    'default_expected_days' => (int) env('SURVEY_DEFAULT_EXPECTED_DAYS', 7),
    'max_expected_days' => (int) env('SURVEY_MAX_EXPECTED_DAYS', 31),
    'preview_link_ttl_minutes' => (int) env('SURVEY_PREVIEW_LINK_TTL_MINUTES', 30),
    'preview_public_url' => rtrim((string) env('SURVEY_PREVIEW_PUBLIC_URL', ''), '/'),
    'preview_embed_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'SURVEY_PREVIEW_EMBED_ORIGINS',
            'https://moongroup-admin.vercel.app'
        ))
    ))),
    'calculator' => [
        'contract_version' => '1.3',
        'canonical_weight_unit' => 'kg',
        'supported_weight_units' => ['kg', 'g'],
    ],
];
