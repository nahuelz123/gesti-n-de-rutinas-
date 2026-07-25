<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use App\Notifications\CoachMessageNotification;
use Illuminate\Support\Facades\DB;

class CoachAiTools
{
    /**
     * Definiciones en formato OpenAI "tools" (function calling).
     */
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'send_notification',
                    'description' => 'Envía una notificación (in-app) al cliente que está seleccionado en esta conversación. Usalo cuando el coach te pida avisarle, recordarle o notificarle algo al cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'description' => 'El mensaje que va a recibir el cliente, en español, breve y claro.',
                            ],
                        ],
                        'required' => ['message'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_and_assign_routine',
                    'description' => 'Crea una rutina de entrenamiento nueva y se la asigna como activa al cliente seleccionado. Usalo cuando el coach te pida armar/crear una rutina para ese cliente. Los nombres de ejercicios se buscan en la biblioteca existente; si un ejercicio no existe, se omite y se avisa.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Título de la rutina.'],
                            'description' => ['type' => 'string', 'description' => 'Descripción u objetivo de la rutina.'],
                            'days' => [
                                'type' => 'array',
                                'description' => 'Días de entrenamiento de la rutina.',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'day_number' => ['type' => 'integer', 'description' => 'Número de día (1, 2, 3...).'],
                                        'title' => ['type' => 'string', 'description' => 'Nombre del día, ej: "Torso", "Pierna".'],
                                        'exercises' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'exercise_title' => ['type' => 'string', 'description' => 'Nombre del ejercicio, debe coincidir (aprox) con uno de la biblioteca.'],
                                                    'sets' => ['type' => 'integer'],
                                                    'reps' => ['type' => 'string', 'description' => 'Ej: "8-10", "12", "AMRAP".'],
                                                    'rest' => ['type' => 'string', 'description' => 'Ej: "90s", "2min". Opcional.'],
                                                    'notes' => ['type' => 'string', 'description' => 'Notas del ejercicio. Opcional.'],
                                                ],
                                                'required' => ['exercise_title', 'sets', 'reps'],
                                            ],
                                        ],
                                    ],
                                    'required' => ['day_number', 'title', 'exercises'],
                                ],
                            ],
                        ],
                        'required' => ['title', 'days'],
                    ],
                ],
            ],
        ];
    }

    public static function execute(string $name, array $args, User $coach, User $client): string
    {
        return match ($name) {
            'send_notification' => static::sendNotification($args, $coach, $client),
            'create_and_assign_routine' => static::createAndAssignRoutine($args, $coach, $client),
            default => "Herramienta desconocida: {$name}",
        };
    }

    private static function sendNotification(array $args, User $coach, User $client): string
    {
        $message = trim($args['message'] ?? '');

        if ($message === '') {
            return 'Error: el mensaje de la notificación llegó vacío.';
        }

        $client->notify(new CoachMessageNotification($coach, $message));

        return "Notificación enviada a {$client->name}: \"{$message}\".";
    }

    private static function createAndAssignRoutine(array $args, User $coach, User $client): string
    {
        $title = trim($args['title'] ?? '');
        $days = $args['days'] ?? [];

        if ($title === '' || empty($days)) {
            return 'Error: falta el título o los días de la rutina.';
        }

        $missingExercises = [];

        $routine = DB::transaction(function () use ($title, $args, $days, $coach, $client, &$missingExercises) {
            $routine = Routine::create([
                'gym_id' => $coach->gym_id,
                'coach_id' => $coach->id,
                'title' => $title,
                'description' => $args['description'] ?? null,
            ]);

            foreach ($days as $dayData) {
                $day = $routine->days()->create([
                    'day_number' => $dayData['day_number'] ?? 1,
                    'title' => $dayData['title'] ?? 'Día',
                ]);

                $order = 1;
                foreach ($dayData['exercises'] ?? [] as $exData) {
                    $exerciseTitle = trim($exData['exercise_title'] ?? '');
                    if ($exerciseTitle === '') continue;

                    $exercise = Exercise::query()
                        ->where(function ($q) use ($coach) {
                            $q->where('is_global', true)->orWhere('gym_id', $coach->gym_id);
                        })
                        ->where('title', 'like', '%'.$exerciseTitle.'%')
                        ->first();

                    if (! $exercise) {
                        $missingExercises[] = $exerciseTitle;
                        continue;
                    }

                    $day->exercises()->create([
                        'exercise_id' => $exercise->id,
                        'sets' => $exData['sets'] ?? 3,
                        'reps' => (string) ($exData['reps'] ?? '10'),
                        'rest' => $exData['rest'] ?? null,
                        'notes' => $exData['notes'] ?? null,
                        'order' => $order++,
                    ]);
                }
            }

            // Si el cliente ya tenía una rutina activa, la marcamos como completada
            // para no dejar dos "activas" a la vez.
            Assignment::query()
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->whereNull('end_date')
                ->update(['status' => 'completed', 'end_date' => now()->toDateString()]);

            Assignment::create([
                'gym_id' => $coach->gym_id,
                'routine_id' => $routine->id,
                'client_id' => $client->id,
                'assigned_by_id' => $coach->id,
                'assigned_at' => now(),
                'start_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            return $routine;
        });

        $summary = "Rutina \"{$routine->title}\" creada y asignada a {$client->name} como activa, con ".count($days).' día(s).';

        if (! empty($missingExercises)) {
            $summary .= ' Ojo: no encontré en la biblioteca estos ejercicios, así que los omití: '.implode(', ', array_unique($missingExercises)).'.';
        }

        return $summary;
    }
}
