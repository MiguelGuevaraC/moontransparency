<?php

/*
| Índice de Presión sobre el Bosque (IPB).
| Fuente: "Indice de Presion sobre el Bosque IPB V2.xlsx" (hojas 02_Criterios y
| 03_Evaluacion). Cada factor se valora de 1 a 3 y el IPB es su promedio.
*/

return [
    'methodology' => 'IPB V2',
    'source' => 'Indice de Presion sobre el Bosque IPB V2.xlsx',

    // El dashboard muestra un solo proyecto. Si FOREST_PRESSURE_PROJECT_ID no
    // está configurado se usa el proyecto de la primera encuesta activa con el
    // código del instrumento GeoBosques.
    'project_id' => env('FOREST_PRESSURE_PROJECT_ID'),
    'survey_code' => env('FOREST_PRESSURE_SURVEY_CODE', 'GEOBOSQUES_PRESSURE'),

    'embed_link_ttl_minutes' => (int) env('FOREST_PRESSURE_LINK_TTL_MINUTES', 30),
    'public_url' => rtrim((string) env('FOREST_PRESSURE_PUBLIC_URL', ''), '/'),
    'embed_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'FOREST_PRESSURE_EMBED_ORIGINS',
            'https://moongroup-admin.vercel.app,https://www.moongroup.com.pe'
        ))
    ))),

    // Interpretación del IPB (hoja Generales): 1,00–1,50 Baja; 1,51–2,30 Media; 2,31–3,00 Alta.
    'levels' => [
        ['code' => 'BAJA', 'label' => 'Baja', 'max' => 1.5],
        ['code' => 'MEDIA', 'label' => 'Media', 'max' => 2.3],
        ['code' => 'ALTA', 'label' => 'Alta', 'max' => 3.0],
    ],

    // Las preguntas se identifican por el eje del instrumento GeoBosques.
    'location' => [
        'ubigeo_eje' => 'Ubicación',
        'ubigeo_type' => 'UBICACION',
        'community_eje' => 'Ubicación',
        'community_type' => 'LIBRE',
    ],

    // type=distance: nivel 3 si km <= high_max_km, nivel 2 si km <= medium_max_km, si no nivel 1.
    // type=options: la posición de la opción (1, 2 o 3) es el nivel.
    'factors' => [
        [
            'key' => 'accessibility',
            'label' => 'Accesibilidad',
            'eje' => 'Accesibilidad',
            'type' => 'distance',
            'unit' => 'km',
            'high_max_km' => 1.0,
            'medium_max_km' => 5.0,
            'indicator' => 'Distancia al acceso más cercano (carretera, trocha o río navegable)',
            'criteria' => ['> 5 km', '1 – 5 km', '≤ 1 km'],
        ],
        [
            'key' => 'agriculture',
            'label' => 'Actividad agrícola',
            'eje' => 'Actividad agrícola',
            'type' => 'options',
            'indicator' => 'Distancia y evidencia de expansión agrícola',
            'criteria' => [
                '> 2 km y sin expansión',
                '≤ 2 km o expansión limitada',
                'Colindante/dentro + expansión evidente',
            ],
        ],
        [
            'key' => 'settlements',
            'label' => 'Asentamientos humanos',
            'eje' => 'Asentamientos humanos',
            'type' => 'distance',
            'unit' => 'km',
            'high_max_km' => 1.0,
            'medium_max_km' => 5.0,
            'indicator' => 'Distancia al centro poblado o comunidad más cercana',
            'criteria' => ['> 5 km', '1 – 5 km', '≤ 1 km'],
        ],
        [
            'key' => 'forest_use',
            'label' => 'Aprovechamiento forestal',
            'eje' => 'Aprovechamiento forestal',
            'type' => 'options',
            'indicator' => 'Intensidad de extracción de leña, madera o productos del bosque',
            'criteria' => [
                'Sin evidencia o mínima (1 – 2 días por semana)',
                'Ocasional/moderado (3 – 4 días por semana)',
                'Frecuente/intensivo (5 – 7 días por semana)',
            ],
        ],
        [
            'key' => 'disturbance',
            'label' => 'Perturbación del bosque',
            'eje' => 'Perturbación del bosque',
            'type' => 'options',
            'indicator' => 'Deforestación, incendios o degradación observada',
            'criteria' => [
                'Poca o ninguna evidencia de perturbación reciente',
                'Perturbaciones puntuales: claros, pequeños focos de deforestación o incendios',
                'Deforestación, degradación, incendios recurrentes o fragmentación significativa',
            ],
        ],
    ],
];
