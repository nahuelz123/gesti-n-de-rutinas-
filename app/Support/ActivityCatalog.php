<?php

namespace App\Support;

class ActivityCatalog
{
    // 2024 Adult Compendium. Values are retained unchanged; labels describe each listed effort.
    // Sources, calculation and limitations: docs/activity-energy-sources.md.
    public static function all(): array
    {
        $rows = [
            ['walk_steps', 'Pasos caminando · tiempo estimado', 3.0, '17170', 'Pasos'],
            ['elliptical_moderate', 'Elíptico · moderado', 5.0, '02048', 'Elíptico'],
            ['elliptical_vigorous', 'Elíptico · intenso', 9.0, '02049', 'Elíptico'],
            ['bike_light', 'Bicicleta fija · suave, ~50 W', 4.0, '01214', 'Bicicleta'],
            ['bike_moderate', 'Bicicleta fija · moderada, 90–100 W', 6.0, '01220', 'Bicicleta'],
            ['bike_vigorous', 'Bicicleta fija · intensa, 126–150 W', 8.0, '01228', 'Bicicleta'],
            ['bike_outdoor', 'Bicicleta al aire libre · ritmo moderado', 7.0, '01016', 'Bicicleta'],
            ['walk_easy', 'Caminata · suave, 3,2–3,9 km/h', 2.8, '17152', 'Caminata y trote'],
            ['walk_moderate', 'Caminata · moderada, 4,5–5,5 km/h', 3.8, '17190', 'Caminata y trote'],
            ['walk_brisk', 'Caminata · rápida, 5,6–6,3 km/h', 4.8, '17200', 'Caminata y trote'],
            ['jogging', 'Trote · ritmo libre', 7.5, '12020', 'Caminata y trote'],
            ['running', 'Correr · ~8 km/h', 8.5, '12030', 'Caminata y trote'],
            ['rowing_moderate', 'Remo ergómetro · moderado, menos de 100 W', 5.0, '02071', 'Otras actividades'],
            ['rowing_vigorous', 'Remo ergómetro · intenso, 100–149 W', 7.5, '02072', 'Otras actividades'],
            ['stairs', 'Escaladora · esfuerzo general', 9.3, '02065', 'Otras actividades'],
            ['rope', 'Saltar la soga · esfuerzo general', 11.0, '02068', 'Otras actividades'],
            ['strength_general', 'Fuerza · sesión habitual con descansos', 3.5, '02054', 'Fuerza'],
            ['strength_vigorous', 'Fuerza · sesión vigorosa', 6.0, '02050', 'Fuerza'],
        ];
        $catalog = [];
        foreach ($rows as [$key, $label, $met, $code, $group]) {
            $catalog[$key] = ['label' => $label, 'met' => $met, 'source_code' => $code,
                'group' => $group, 'category' => $group === 'Fuerza' ? 'strength' : 'cardio'];
        }

        return $catalog;
    }
}
