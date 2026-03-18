<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Exercise;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $items = $this->globalExercises();

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Exercise::updateOrCreate(
                    [
                        'gym_id' => null,
                        'title'  => $item['title'],
                    ],
                    [
                        'gym_id'        => null,
                        'is_global'     => true,
                        'created_by_id' => null,
                        'muscle_group'  => $item['muscle_group'],
                        'description'   => $item['description'] ?? null,
                        'tips'          => $item['tips'] ?? null,
                        'video_url'     => $item['video_url'] ?? null,
                        'gif_url'       => $item['gif_url'] ?? null,
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ]
                );
            }
        });
    }

    private function globalExercises(): array
    {
        return [

            // ─── PECHO ───────────────────────────────────────────────
            [
                'title'        => 'Press banca con barra',
                'muscle_group' => 'pecho',
                'tips'         => 'Escápulas retraídas, pies firmes.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/218.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=rT7DgCr-3pg',
            ],
            [
                'title'        => 'Press banca inclinado con mancuernas',
                'muscle_group' => 'pecho',
                'tips'         => 'Controlá la bajada, rango completo.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/314.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=8iPEnn-ltC8',
            ],
            [
                'title'        => 'Press banca declinado',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/350.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=LfyQmbqCv0',
            ],
            [
                'title'        => 'Aperturas con mancuernas',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/113.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=eozdVDA78K0',
            ],
            [
                'title'        => 'Cruces en polea',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/126.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=taI4XduLpTk',
            ],
            [
                'title'        => 'Flexiones de brazos',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/20.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=IODxDxX7oi4',
            ],

            // ─── ESPALDA ─────────────────────────────────────────────
            [
                'title'        => 'Dominadas pronas',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/31.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=eGo4IYlbE5g',
            ],
            [
                'title'        => 'Jalón al pecho',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/90.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=CAwf7n6Luuc',
            ],
            [
                'title'        => 'Remo con barra',
                'muscle_group' => 'espalda',
                'tips'         => 'Espalda neutra, no balancear.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/73.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=FWJR5Ve8bnQ',
            ],
            [
                'title'        => 'Remo con mancuerna a una mano',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/289.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=pYcpY20QaE8',
            ],
            [
                'title'        => 'Remo en polea baja',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/91.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=GZbfZ033f74',
            ],
            [
                'title'        => 'Pullover en polea',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/253.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=KC2J7MrXAgs',
            ],

            // ─── PIERNAS ─────────────────────────────────────────────
            [
                'title'        => 'Sentadilla con barra',
                'muscle_group' => 'piernas',
                'tips'         => 'Rodillas alineadas, core firme.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/226.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=ultWZbUMPL8',
            ],
            [
                'title'        => 'Sentadilla frontal',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/227.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=m4ytaCJZpl0',
            ],
            [
                'title'        => 'Prensa de piernas',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/254.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=IZxyjW7MPJQ',
            ],
            [
                'title'        => 'Zancadas caminando',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/25.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=L8fvypPrzzs',
            ],
            [
                'title'        => 'Extensiones de cuádriceps',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/100.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=YyvSfVjQeL0',
            ],
            [
                'title'        => 'Curl femoral',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/99.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=1Tq3QdYUuHs',
            ],

            // ─── GLÚTEOS ─────────────────────────────────────────────
            [
                'title'        => 'Hip thrust con barra',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/349.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=SEdqd1n0cvg',
            ],
            [
                'title'        => 'Puente de glúteos',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/348.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=OUgsJ8-Vi0E',
            ],
            [
                'title'        => 'Patada de glúteo en polea',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/282.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=TXhMRMHnFHc',
            ],
            [
                'title'        => 'Sentadilla sumo',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/228.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=QKKZ9AGYTes',
            ],
            [
                'title'        => 'Peso muerto rumano',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/242.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=JCXUYuzwNrM',
            ],
            [
                'title'        => 'Abducción de cadera (máquina)',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/266.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=Vqq6oDOyQS0',
            ],

            // ─── HOMBROS ─────────────────────────────────────────────
            [
                'title'        => 'Press militar',
                'muscle_group' => 'hombros',
                'tips'         => 'No arquear lumbar, control total.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/75.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=2yjwXTZQDDI',
            ],
            [
                'title'        => 'Press con mancuernas',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/76.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=qEwKCR5JCog',
            ],
            [
                'title'        => 'Elevaciones laterales',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/68.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=3VcKaXpzqRo',
            ],
            [
                'title'        => 'Elevaciones frontales',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/67.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=sOoBE8G30wo',
            ],
            [
                'title'        => 'Pájaros / posteriores con mancuernas',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/69.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=ttvAjHVNDpU',
            ],
            [
                'title'        => 'Face pull',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/346.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=rep-qVOkqgk',
            ],

            // ─── BÍCEPS ──────────────────────────────────────────────
            [
                'title'        => 'Curl bíceps con barra',
                'muscle_group' => 'biceps',
                'tips'         => 'Sin balanceo.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/86.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=kwG2ipFRgfo',
            ],
            [
                'title'        => 'Curl martillo',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/194.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=zC3nLlEvin4',
            ],
            [
                'title'        => 'Curl inclinado',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/193.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=soxrZlIl35U',
            ],
            [
                'title'        => 'Curl concentrado',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/192.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=Jvj2wV0vOYU',
            ],
            [
                'title'        => 'Curl en polea',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/191.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=NFzTWp2qpiE',
            ],
            [
                'title'        => 'Curl predicador',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/190.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=fIWP-FRFNU0',
            ],

            // ─── TRÍCEPS ─────────────────────────────────────────────
            [
                'title'        => 'Fondos en paralelas',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/30.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=2z8JmcrW-As',
            ],
            [
                'title'        => 'Press cerrado',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/84.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=BQnSBqAJM04',
            ],
            [
                'title'        => 'Extensión de tríceps en polea',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/87.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=2-LAMcpzODU',
            ],
            [
                'title'        => 'Extensión por encima de la cabeza',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/88.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=YbX7Wd8jQ-Q',
            ],
            [
                'title'        => 'Patada de tríceps con mancuerna',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/89.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=6SS6K3lAwZ8',
            ],
            [
                'title'        => 'Rompecráneos (extensión en banco)',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/85.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=d_KZxkY_0cM',
            ],

            // ─── ABDOMEN ─────────────────────────────────────────────
            [
                'title'        => 'Plancha',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/172.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=pSHjTRCQxIw',
            ],
            [
                'title'        => 'Crunch',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/141.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=Xyd_fa5zoEU',
            ],
            [
                'title'        => 'Elevación de piernas',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/196.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=hdng3Nm1x_E',
            ],
            [
                'title'        => 'Russian twist',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/343.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=wkD8rjkodUI',
            ],
            [
                'title'        => 'Ab wheel',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/344.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=rRTaLMFkCVE',
            ],
            [
                'title'        => 'Crunch en polea',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/142.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=AV5PmXwFqQo',
            ],

            // ─── CARDIO ──────────────────────────────────────────────
            [
                'title'        => 'Caminata inclinada',
                'muscle_group' => 'cardio',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=4bCn3fxQNbg',
            ],
            [
                'title'        => 'Bicicleta fija',
                'muscle_group' => 'cardio',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=g1FdkDV-akQ',
            ],
            [
                'title'        => 'Elíptico',
                'muscle_group' => 'cardio',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=0PmSXNHHtaM',
            ],
            [
                'title'        => 'Soga',
                'muscle_group' => 'cardio',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/284.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=u3zgHI8QnqE',
            ],
            [
                'title'        => 'Remo ergómetro',
                'muscle_group' => 'cardio',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=H6l4_LppCkM',
            ],
            [
                'title'        => 'HIIT (intervalos)',
                'muscle_group' => 'cardio',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=ml6cT4AZdqI',
            ],

            // ─── FULL BODY / FUNCIONAL ────────────────────────────────
            [
                'title'        => 'Peso muerto convencional',
                'muscle_group' => 'fullbody',
                'tips'         => 'Barra pegada, espalda neutra.',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/241.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=op9kVnSso6Q',
            ],
            [
                'title'        => 'Kettlebell swing',
                'muscle_group' => 'fullbody',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/340.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=YSxHifyI6s8',
            ],
            [
                'title'        => 'Burpees',
                'muscle_group' => 'fullbody',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/283.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=dZgVxmf6jkA',
            ],
            [
                'title'        => 'Farmer walk',
                'muscle_group' => 'fullbody',
                'gif_url'      => null,
                'video_url'    => 'https://www.youtube.com/watch?v=Fkzk_RqlYig',
            ],
            [
                'title'        => 'Thrusters',
                'muscle_group' => 'fullbody',
                'gif_url'      => 'https://wger.de/static/images/exercises/svg/342.svg',
                'video_url'    => 'https://www.youtube.com/watch?v=ioLGGEEFVy4',
            ],
        ];
    }
}
