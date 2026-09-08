<?php

return [
    'default_expected_days' => (int) env('SURVEY_DEFAULT_EXPECTED_DAYS', 7),
    'max_expected_days' => (int) env('SURVEY_MAX_EXPECTED_DAYS', 31),
    'calculator' => [
        'contract_version' => '1.3',
        'canonical_weight_unit' => 'kg',
        'supported_weight_units' => ['kg', 'g'],
    ],
];
