<?php

return [
    'public_name' => env('PUBLIC_PLATFORM_NAME', 'Portal de Impacto Moon Group'),

    // Tarjetas de "Herramientas Digitales" (título, frase y descripción). La
    // imagen referencial de cada tarjeta es un asset del frontend.
    'tools' => [
        'sustainability_dashboard' => [
            'name' => 'Dashboard de Sostenibilidad',
            'tagline' => 'Información que impulsa decisiones sostenibles',
            'description' => 'Plataforma interactiva para registrar, organizar y visualizar información social, ambiental y económica de las comunidades. Facilita el seguimiento de indicadores, la identificación de necesidades y la toma de decisiones para fortalecer proyectos sostenibles.',
        ],
        'co2_calculator' => [
            'name' => 'Calculadora de CO2',
            'tagline' => 'Medir para reducir nuestra huella',
            'description' => 'Herramienta que estima las emisiones asociadas al consumo de leña y otras prácticas energéticas en los hogares. Permite comparar la línea base y el monitoreo para visibilizar la reducción potencial de emisiones mediante soluciones como las cocinas mejoradas.',        ],
        'forest_pressure' => [
            'name' => 'Presión sobre el Bosque',
            'tagline' => 'Comprender para conservar',
            'description' => 'Herramienta de diagnóstico que analiza factores como disponibilidad de leña, distancia de recolección, perturbación, acceso y aprovechamiento forestal. Genera un índice que ayuda a comprender la presión de los hogares sobre el bosque.',
        ],
    ],
];
