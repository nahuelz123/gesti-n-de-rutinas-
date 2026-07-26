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
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0025-EIeI8Vf.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=rT7DgCr-3pg',
            ],
            [
                'title'        => 'Press banca inclinado con mancuernas',
                'muscle_group' => 'pecho',
                'tips'         => 'Controlá la bajada, rango completo.',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0314-ns0SIbU.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=8iPEnn-ltC8',
            ],
            [
                'title'        => 'Press banca declinado',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0033-GrO65fd.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=LfyQmbqCv0',
            ],
            [
                'title'        => 'Aperturas con mancuernas',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0308-yz9nUhF.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=eozdVDA78K0',
            ],
            [
                'title'        => 'Cruces en polea',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1269-UKWTJWR.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=taI4XduLpTk',
            ],
            [
                'title'        => 'Flexiones de brazos',
                'muscle_group' => 'pecho',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0259-x6KpKpq.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=IODxDxX7oi4',
            ],

            // ─── ESPALDA ─────────────────────────────────────────────
            [
                'title'        => 'Dominadas pronas',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0017-kiJ4Z2K.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=eGo4IYlbE5g',
            ],
            [
                'title'        => 'Jalón al pecho',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/2330-LEprlgG.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=CAwf7n6Luuc',
            ],
            [
                'title'        => 'Remo con barra',
                'muscle_group' => 'espalda',
                'tips'         => 'Espalda neutra, no balancear.',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0027-eZyBC3j.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=FWJR5Ve8bnQ',
            ],
            [
                'title'        => 'Remo con mancuerna a una mano',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0292-C0MA9bC.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=pYcpY20QaE8',
            ],
            [
                'title'        => 'Remo en polea baja',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0180-hvV79Si.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=GZbfZ033f74',
            ],
            [
                'title'        => 'Pullover en polea',
                'muscle_group' => 'espalda',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0073-i6LWjok.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=KC2J7MrXAgs',
            ],

            // ─── PIERNAS ─────────────────────────────────────────────
            [
                'title'        => 'Sentadilla con barra',
                'muscle_group' => 'piernas',
                'tips'         => 'Rodillas alineadas, core firme.',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0043-qXTaZnJ.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=ultWZbUMPL8',
            ],
            [
                'title'        => 'Sentadilla frontal',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0042-zG0zs85.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=m4ytaCJZpl0',
            ],
            [
                'title'        => 'Prensa de piernas',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1425-WWD6FzI.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=IZxyjW7MPJQ',
            ],
            [
                'title'        => 'Zancadas caminando',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1460-IZVHb27.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=L8fvypPrzzs',
            ],
            [
                'title'        => 'Extensiones de cuádriceps',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0585-my33uHU.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=YyvSfVjQeL0',
            ],
            [
                'title'        => 'Curl femoral',
                'muscle_group' => 'piernas',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0586-17lJ1kr.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=1Tq3QdYUuHs',
            ],

            // ─── GLÚTEOS ─────────────────────────────────────────────
            [
                'title'        => 'Hip thrust con barra',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1409-qKBpF7I.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=SEdqd1n0cvg',
            ],
            [
                'title'        => 'Puente de glúteos',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/3561-GibBPPg.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=OUgsJ8-Vi0E',
            ],
            [
                'title'        => 'Patada de glúteo en polea',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0860-HEJ6DIX.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=TXhMRMHnFHc',
            ],
            [
                'title'        => 'Sentadilla sumo',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/3142-dzz6BiV.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=QKKZ9AGYTes',
            ],
            [
                'title'        => 'Peso muerto rumano',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0085-wQ2c4XD.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=JCXUYuzwNrM',
            ],
            [
                'title'        => 'Abducción de cadera (máquina)',
                'muscle_group' => 'gluteos',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0597-CHpahtl.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=Vqq6oDOyQS0',
            ],

            // ─── HOMBROS ─────────────────────────────────────────────
            [
                'title'        => 'Press militar',
                'muscle_group' => 'hombros',
                'tips'         => 'No arquear lumbar, control total.',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1457-Kyd9Rz5.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=2yjwXTZQDDI',
            ],
            [
                'title'        => 'Press con mancuernas',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0405-znQUdHY.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=qEwKCR5JCog',
            ],
            [
                'title'        => 'Elevaciones laterales',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0311-AQ0mC4Y.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=3VcKaXpzqRo',
            ],
            [
                'title'        => 'Elevaciones frontales',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0310-3eGE2JC.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=sOoBE8G30wo',
            ],
            [
                'title'        => 'Pájaros / posteriores con mancuernas',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0378-8DiFDVA.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=ttvAjHVNDpU',
            ],
            [
                'title'        => 'Face pull',
                'muscle_group' => 'hombros',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1724-NN8nSNT.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=rep-qVOkqgk',
            ],

            // ─── BÍCEPS ──────────────────────────────────────────────
            [
                'title'        => 'Curl bíceps con barra',
                'muscle_group' => 'biceps',
                'tips'         => 'Sin balanceo.',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0031-25GPyDY.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=kwG2ipFRgfo',
            ],
            [
                'title'        => 'Curl martillo',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0313-slDvUAU.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=zC3nLlEvin4',
            ],
            [
                'title'        => 'Curl inclinado',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0318-ae9UoXQ.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=soxrZlIl35U',
            ],
            [
                'title'        => 'Curl concentrado',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/2414-vsMcDi9.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=Jvj2wV0vOYU',
            ],
            [
                'title'        => 'Curl en polea',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0868-G08RZcQ.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=NFzTWp2qpiE',
            ],
            [
                'title'        => 'Curl predicador',
                'muscle_group' => 'biceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0070-qOgPVf6.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=fIWP-FRFNU0',
            ],

            // ─── TRÍCEPS ─────────────────────────────────────────────
            [
                'title'        => 'Fondos en paralelas',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0814-X6C6i5Y.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=2z8JmcrW-As',
            ],
            [
                'title'        => 'Press cerrado',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0030-J6Dx1Mu.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=BQnSBqAJM04',
            ],
            [
                'title'        => 'Extensión de tríceps en polea',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0241-gAwDzB3.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=2-LAMcpzODU',
            ],
            [
                'title'        => 'Extensión por encima de la cabeza',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0109-dZl9Q27.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=YbX7Wd8jQ-Q',
            ],
            [
                'title'        => 'Patada de tríceps con mancuerna',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0333-W6PxUkg.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=6SS6K3lAwZ8',
            ],
            [
                'title'        => 'Rompecráneos (extensión en banco)',
                'muscle_group' => 'triceps',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0060-h8LFzo9.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=d_KZxkY_0cM',
            ],

            // ─── ABDOMEN ─────────────────────────────────────────────
            [
                'title'        => 'Plancha',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0464-CosupLu.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=pSHjTRCQxIw',
            ],
            [
                'title'        => 'Crunch',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1005-Kzg30R7.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=Xyd_fa5zoEU',
            ],
            [
                'title'        => 'Elevación de piernas',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1002-bbLR7fB.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=hdng3Nm1x_E',
            ],
            [
                'title'        => 'Russian twist',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0687-XVDdcoj.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=wkD8rjkodUI',
            ],
            [
                'title'        => 'Ab wheel',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0857-NAgVB3t.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=rRTaLMFkCVE',
            ],
            [
                'title'        => 'Crunch en polea',
                'muscle_group' => 'abdomen',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0212-8xUv4J7.gif',
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
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/2141-rjtuP6X.gif',
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
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0032-ila4NZS.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=op9kVnSso6Q',
            ],
            [
                'title'        => 'Kettlebell swing',
                'muscle_group' => 'fullbody',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/0549-UHJlbu3.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=YSxHifyI6s8',
            ],
            [
                'title'        => 'Burpees',
                'muscle_group' => 'fullbody',
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/1160-dK9394r.gif',
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
                'gif_url'      => 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/videos/3305-f7Y9eDZ.gif',
                'video_url'    => 'https://www.youtube.com/watch?v=ioLGGEEFVy4',
            ],
        ];
    }
}
