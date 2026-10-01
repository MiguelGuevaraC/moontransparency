<?php

namespace App\Services;

class ForestPressureIndexCalculator
{
    /**
     * Nivel (1–3) de un factor de distancia: a menor distancia, mayor presión.
     */
    public function distanceLevel(float $kilometers, array $factor): int
    {
        if ($kilometers <= (float) $factor['high_max_km']) {
            return 3;
        }

        return $kilometers <= (float) $factor['medium_max_km'] ? 2 : 1;
    }

    /**
     * Nivel (1–3) de un factor de opciones según la posición de la opción elegida.
     */
    public function optionLevel(int $position): ?int
    {
        return $position >= 1 && $position <= 3 ? $position : null;
    }

    /**
     * Calcula el IPB a partir de los niveles por factor. Devuelve null si falta
     * algún factor, igual que la hoja 03_Evaluacion (COUNT(D10:D14)=5).
     *
     * @param  array<string, int|null>  $levels  nivel por clave de factor
     */
    public function evaluate(array $levels): ?array
    {
        $factors = config('forest_pressure.factors');
        $values = [];

        foreach ($factors as $factor) {
            $level = $levels[$factor['key']] ?? null;
            if ($level === null) {
                return null;
            }
            $values[$factor['key']] = (int) $level;
        }

        $index = round(array_sum($values) / count($values), 4);

        return [
            'ipb' => round($index, 2),
            'level' => $this->pressureLevel($index),
            'high_factors' => collect($factors)
                ->filter(fn (array $factor) => $values[$factor['key']] === 3)
                ->pluck('label')
                ->values()
                ->all(),
        ];
    }

    public function pressureLevel(float $index): string
    {
        foreach (config('forest_pressure.levels') as $level) {
            if ($index <= (float) $level['max']) {
                return $level['code'];
            }
        }

        return 'ALTA';
    }
}
