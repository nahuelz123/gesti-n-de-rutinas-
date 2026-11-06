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
            ['boxing_bag_general', 'Boxeo · bolsa, general', 5.8, '15110', 'Boxeo y combate'],
            ['boxing_bag_120', 'Boxeo · bolsa, ritmo de 120 golpes/min', 8.5, '15115', 'Boxeo y combate'],
            ['boxing_sparring', 'Boxeo · sparring', 7.8, '15120', 'Boxeo y combate'],
            ['boxing_ring', 'Boxeo · combate en ring', 12.3, '15100', 'Boxeo y combate'],
            ['basketball_general', 'Básquetbol · práctica sin partido', 6.0, '15050', 'Deportes de equipo'],
            ['basketball_game', 'Básquetbol · partido', 8.0, '15040', 'Deportes de equipo'],
            ['basketball_drills', 'Básquetbol · ejercicios y práctica intensa', 9.3, '15072', 'Deportes de equipo'],
            ['soccer_casual', 'Fútbol · recreativo', 7.0, '15610', 'Deportes de equipo'],
            ['soccer_competitive', 'Fútbol · competitivo', 9.5, '15605', 'Deportes de equipo'],
            ['futsal', 'Futsal', 7.8, '15195', 'Deportes de equipo'],
            ['volleyball_general', 'Vóley · recreativo', 4.0, '15710', 'Deportes de equipo'],
            ['volleyball_competitive', 'Vóley · competitivo en gimnasio', 6.0, '15711', 'Deportes de equipo'],
            ['tennis_general', 'Tenis · ritmo moderado', 6.8, '15675', 'Deportes de raqueta'],
            ['tennis_competitive', 'Tenis · competitivo', 8.0, '15676', 'Deportes de raqueta'],
            ['swimming_freestyle_easy', 'Natación · crol recreativo', 5.8, '18240', 'Natación'],
            ['swimming_freestyle_vigorous', 'Natación · crol intenso', 9.8, '18230', 'Natación'],
            ['swimming_breaststroke_easy', 'Natación · pecho recreativo', 5.3, '18265', 'Natación'],
            ['swimming_backstroke_easy', 'Natación · espalda recreativa', 4.8, '18255', 'Natación'],
            ['track_field_throws', 'Atletismo · lanzamientos (bala, disco, martillo)', 4.0, '15732', 'Atletismo'],
            ['track_field_jumps', 'Atletismo · saltos y saltos con pértiga', 6.0, '15733', 'Atletismo'],
            ['track_field_hurdles', 'Atletismo · vallas y obstáculos', 10.0, '15734', 'Atletismo'],
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
