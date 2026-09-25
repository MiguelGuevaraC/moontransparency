<?php

use App\Models\SurveyQuestion;

$days = range(1, 7);
$daysAfterFirst = range(2, 7);

$question = static function (
    string $instrumentKey,
    int $order,
    string $text,
    string $fieldType,
    string $scope,
    array $extra = []
): array {
    $isOptions = isset($extra['options']);

    return array_merge([
        'instrument_key' => $instrumentKey,
        'order' => $order,
        'question_text' => $text,
        'question_type' => $isOptions ? 'OPCIONES' : 'LIBRE',
        'type_field' => $fieldType,
        'calculator_key' => null,
        'calculator_value_type' => $isOptions ? 'options' : 'string',
        'calculator_unit' => null,
        'response_scope' => $scope,
        'applicable_days' => null,
        'scenario' => null,
        'section_key' => null,
        'section_title' => null,
        'is_required' => true,
        'eje' => null,
        'justification' => null,
        'options' => [],
    ], $extra);
};

$integer = static fn (
    string $key,
    int $order,
    string $text,
    string $scope,
    array $extra = []
): array => $question($key, $order, $text, 'NUMERICO', $scope, array_merge([
    'calculator_value_type' => 'number',
    'calculator_unit' => 'person',
], $extra));

$decimal = static fn (
    string $key,
    int $order,
    string $text,
    string $scope,
    array $extra = []
): array => $question($key, $order, $text, SurveyQuestion::FIELD_TYPE_DECIMAL, $scope, array_merge([
    'calculator_value_type' => 'number',
    'calculator_unit' => 'kg',
    'justification' => 'Ingrese el peso como número decimal en kilogramos.',
], $extra));

return [
    'version' => '2026-09-24',
    'confirmation' => 'SYNC_KPT_CO2',
    'variants' => [
        'MONITORING_COMBINED' => [
            'label' => 'Cocina tradicional + cocina mejorada Moon Group',
            'description' => 'CASO 1 del instrumento de monitoreo.',
        ],
        'MONITORING_MOON_ONLY' => [
            'label' => 'Solo cocina mejorada Moon Group',
            'description' => 'CASO 2 del instrumento de monitoreo.',
        ],
    ],
    'surveys' => [
        'baseline' => [
            'code' => 'KPT_CO2_BASELINE',
            'legacy_names' => ['KPT línea base', 'KPT linea base'],
            'name' => 'KPT línea base',
            'type' => 'PRE',
            'description' => 'Medición KPT de línea base durante siete días.',
            'expected_days' => 7,
            'questions' => [
                $question('baseline.household_id', 1, 'ID del hogar', 'CORTO', 'PARTICIPATION', [
                    'calculator_key' => 'household.identifier',
                    'calculator_value_type' => 'string',
                    'section_key' => 'baseline.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $question('baseline.kitchen_type', 2, 'Tipo de cocina medida', 'LISTADO', 'PARTICIPATION', [
                    'section_key' => 'baseline.setup',
                    'section_title' => 'Datos iniciales',
                    'options' => ['Cocina tradicional', 'Fogón'],
                ]),
                $question('baseline.wood_condition', 3, 'Condición de la leña (se recomienda usar solo leña seca)', 'LISTADO', 'PARTICIPATION', [
                    'section_key' => 'baseline.setup',
                    'section_title' => 'Datos iniciales',
                    'options' => ['Seca', 'Húmeda', 'Semi seca'],
                ]),
                $decimal('baseline.initial_weight', 4, 'Peso inicial de leña', 'PARTICIPATION', [
                    'calculator_key' => 'baseline.initial_wood_kg',
                    'applicable_days' => [1],
                    'section_key' => 'baseline.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $integer('baseline.children', 5, 'Número de niños/as de 0 a 14 años', 'MEASUREMENT', [
                    'calculator_key' => 'household.children_0_14',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $integer('baseline.women', 6, 'Número de mujeres mayores de 14 años', 'MEASUREMENT', [
                    'calculator_key' => 'household.women_over_14',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $integer('baseline.men_15_59', 7, 'Número de hombres de 15 a 59 años', 'MEASUREMENT', [
                    'calculator_key' => 'household.men_15_59',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $integer('baseline.men_over_59', 8, 'Número de hombres mayores de 59 años', 'MEASUREMENT', [
                    'calculator_key' => 'household.men_over_59',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $question('baseline.measurement_date', 9, 'Fecha de medición', 'FECHA', 'MEASUREMENT', [
                    'calculator_key' => 'measurement.date',
                    'calculator_value_type' => 'date',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $question('baseline.start_time', 10, 'Hora de inicio', 'CORTO', 'MEASUREMENT', [
                    'calculator_key' => 'measurement.start_time',
                    'calculator_value_type' => 'time',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $decimal('baseline.remaining_weight', 11, 'Peso de leña sobrante (lo que no se usó en el día)', 'MEASUREMENT', [
                    'calculator_key' => 'baseline.remaining_wood_kg',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $decimal('baseline.ash_weight', 12, 'Peso de ceniza y leña que no terminó de quemarse', 'MEASUREMENT', [
                    'calculator_key' => 'baseline.charcoal_kg',
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $question('baseline.observations', 13, 'Observaciones', 'LARGO', 'MEASUREMENT', [
                    'legacy_question_texts' => ['Observaciones del día de medición'],
                    'applicable_days' => $days,
                    'section_key' => 'baseline.daily',
                    'section_title' => 'Medición diaria',
                    'is_required' => false,
                ]),
            ],
        ],
        'monitoring' => [
            'code' => 'KPT_CO2_MONITORING',
            'legacy_names' => ['KPT monitoreo'],
            'name' => 'KPT monitoreo',
            'type' => 'POST',
            'description' => 'Medición KPT de monitoreo durante siete días.',
            'expected_days' => 7,
            'questions' => [
                $question('monitoring.household_id', 1, 'ID del hogar', 'CORTO', 'PARTICIPATION', [
                    'calculator_key' => 'household.identifier',
                    'calculator_value_type' => 'string',
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $integer('monitoring.children', 2, 'Número de niños/as de 0 a 14 años', 'PARTICIPATION', [
                    'calculator_key' => 'household.children_0_14',
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $integer('monitoring.women', 3, 'Número de mujeres mayores de 14 años', 'PARTICIPATION', [
                    'calculator_key' => 'household.women_over_14',
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $integer('monitoring.men_15_59', 4, 'Número de hombres de 15 a 59 años', 'PARTICIPATION', [
                    'calculator_key' => 'household.men_15_59',
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $integer('monitoring.men_over_59', 5, 'Número de hombres mayores de 59 años', 'PARTICIPATION', [
                    'calculator_key' => 'household.men_over_59',
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                ]),
                $question('monitoring.wood_condition', 6, 'Condición de la leña (se recomienda usar solo leña seca)', 'LISTADO', 'PARTICIPATION', [
                    'section_key' => 'monitoring.setup',
                    'section_title' => 'Datos iniciales',
                    'options' => ['Seca', 'Húmeda', 'Semi seca'],
                ]),
                $decimal('monitoring.combined.moon_initial', 7, 'Peso inicial de leña - cocina Moon Group', 'PARTICIPATION', [
                    'calculator_key' => 'monitoring.moon.initial_wood_kg',
                    'applicable_days' => [1],
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.traditional_initial', 8, 'Peso inicial de leña - cocina tradicional', 'PARTICIPATION', [
                    'calculator_key' => 'monitoring.traditional.initial_wood_kg',
                    'applicable_days' => [1],
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $question('monitoring.measurement_date', 9, 'Fecha de medición', 'FECHA', 'MEASUREMENT', [
                    'calculator_value_type' => 'date',
                    'applicable_days' => $days,
                    'section_key' => 'monitoring.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $question('monitoring.start_time', 10, 'Hora de pesaje inicial', 'CORTO', 'MEASUREMENT', [
                    'calculator_value_type' => 'time',
                    'applicable_days' => $days,
                    'section_key' => 'monitoring.daily',
                    'section_title' => 'Medición diaria',
                ]),
                $decimal('monitoring.combined.moon_additional', 11, 'Peso de leña adicional - cocina Moon Group', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.moon.additional_wood_kg',
                    'applicable_days' => $daysAfterFirst,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.moon_remaining', 12, 'Peso de leña sobrante - cocina Moon Group', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.moon.remaining_wood_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.moon_ash', 13, 'Peso de ceniza y leña que no terminó de quemarse - cocina Moon Group', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.moon.charcoal_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.traditional_additional', 14, 'Peso de leña adicional - cocina tradicional', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.traditional.additional_wood_kg',
                    'applicable_days' => $daysAfterFirst,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.traditional_remaining', 15, 'Peso de leña sobrante - cocina tradicional', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.traditional.remaining_wood_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.combined.traditional_ash', 16, 'Peso de ceniza y leña que no terminó de quemarse - cocina tradicional', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.traditional.charcoal_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_COMBINED',
                    'section_key' => 'monitoring.combined',
                    'section_title' => 'Caso 1: cocina tradicional + cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.moon_only.initial', 17, 'Peso inicial de leña', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.exclusive.initial_wood_kg',
                    'applicable_days' => [1],
                    'scenario' => 'MONITORING_MOON_ONLY',
                    'section_key' => 'monitoring.moon_only',
                    'section_title' => 'Caso 2: solo cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.moon_only.additional', 18, 'Peso de leña adicional', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.exclusive.additional_wood_kg',
                    'applicable_days' => $daysAfterFirst,
                    'scenario' => 'MONITORING_MOON_ONLY',
                    'section_key' => 'monitoring.moon_only',
                    'section_title' => 'Caso 2: solo cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.moon_only.remaining', 19, 'Peso de leña sobrante', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.exclusive.remaining_wood_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_MOON_ONLY',
                    'section_key' => 'monitoring.moon_only',
                    'section_title' => 'Caso 2: solo cocina mejorada Moon Group',
                ]),
                $decimal('monitoring.moon_only.ash', 20, 'Peso de ceniza y leña que no terminó de quemarse', 'MEASUREMENT', [
                    'calculator_key' => 'monitoring.exclusive.charcoal_kg',
                    'applicable_days' => $days,
                    'scenario' => 'MONITORING_MOON_ONLY',
                    'section_key' => 'monitoring.moon_only',
                    'section_title' => 'Caso 2: solo cocina mejorada Moon Group',
                ]),
                $question('monitoring.observations', 21, 'Observaciones', 'LARGO', 'MEASUREMENT', [
                    'legacy_question_texts' => ['Observaciones del día de medición'],
                    'applicable_days' => $days,
                    'section_key' => 'monitoring.daily',
                    'section_title' => 'Medición diaria',
                    'is_required' => false,
                ]),
            ],
        ],
    ],
];
