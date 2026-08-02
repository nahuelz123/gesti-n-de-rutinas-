<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\DietAssignment;
use App\Models\DietPlan;
use App\Models\Exercise;
use App\Models\Recipe;
use App\Models\Routine;
use App\Models\User;
use App\Notifications\CoachMessageNotification;
use Illuminate\Support\Facades\DB;

class CoachAiTools
{
    /**
     * Herramientas que NO se ejecutan solas: arman una propuesta que el coach
     * tiene que revisar y aprobar a mano antes de tocar la base de datos.
     */
    public const PROPOSAL_TOOLS = [
        'propose_routine',
        'propose_routine_edit',
        'propose_diet_plan',
        'propose_diet_plan_edit',
        'propose_recipe',
    ];

    /**
     * Definiciones en formato OpenAI "tools" (function calling).
     *
     * @param  bool  $generalChat  Si es true, estamos en el chat general (sin cliente
     *                              fijado en la conversación), así que las herramientas
     *                              que apuntan a un cliente necesitan client_query.
     */
    public static function definitions(bool $generalChat = false): array
    {
        $clientArg = $generalChat ? [
            'client_query' => [
                'type' => 'string',
                'description' => 'Nombre o email (o parte) del cliente al que aplica esta acción. Obligatorio en el chat general: si no está claro a quién, usá search_client primero o preguntale al coach.',
            ],
        ] : [];

        $clientRequired = $generalChat ? ['client_query'] : [];

        $tools = [];

        if ($generalChat) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'search_client',
                    'description' => 'Busca clientes del gimnasio por nombre o email. Usalo en el chat general antes de crear/editar algo para un cliente puntual, para confirmar de quién se trata (sobre todo si hay nombres parecidos).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Nombre o parte del nombre/email a buscar.'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ];
        }

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'send_notification',
                'description' => 'Envía una notificación (in-app) a un cliente. Usalo cuando el coach te pida avisarle, recordarle o notificarle algo. Esta acción se manda al toque, no requiere confirmación aparte.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_merge($clientArg, [
                        'message' => [
                            'type' => 'string',
                            'description' => 'El mensaje que va a recibir el cliente, en español, breve y claro.',
                        ],
                    ]),
                    'required' => array_merge($clientRequired, ['message']),
                ],
            ],
        ];

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'propose_routine',
                'description' => 'Arma una PROPUESTA de rutina nueva para un cliente (no la crea ni la asigna todavía). El coach va a ver la propuesta completa y tiene que aprobarla explícitamente antes de que se cree y se asigne de verdad. Usalo cuando el coach te pida armar/crear una rutina. Si te pide cambios sobre la propuesta anterior, volvé a llamar esta herramienta con la versión corregida completa.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_merge($clientArg, [
                        'title' => ['type' => 'string', 'description' => 'Título de la rutina.'],
                        'description' => ['type' => 'string', 'description' => 'Descripción u objetivo de la rutina.'],
                        'days' => self::daysSchema(),
                    ]),
                    'required' => array_merge($clientRequired, ['title', 'days']),
                ],
            ],
        ];

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'propose_routine_edit',
                'description' => 'Arma una PROPUESTA de edición sobre la rutina ACTIVA actual de un cliente (no la modifica todavía). Mandá la versión completa de los días/ejercicios como debería quedar después del cambio (no un diff). El coach tiene que aprobarla. Si el cliente no tiene rutina activa, avisale y sugerile usar propose_routine en cambio.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_merge($clientArg, [
                        'title' => ['type' => 'string', 'description' => 'Nuevo título de la rutina (podés dejar el mismo si no cambia).'],
                        'description' => ['type' => 'string', 'description' => 'Nueva descripción u objetivo.'],
                        'days' => self::daysSchema(),
                    ]),
                    'required' => array_merge($clientRequired, ['title', 'days']),
                ],
            ],
        ];

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'propose_recipe',
                'description' => 'Arma una PROPUESTA de receta nueva para el catálogo (no la crea todavía). El coach tiene que aprobarla. No apunta a un cliente puntual, es para el catálogo general del gimnasio.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'meal_type' => ['type' => 'string', 'enum' => ['desayuno', 'almuerzo', 'merienda', 'cena', 'pre_entrenamiento', 'post_entrenamiento']],
                        'calories' => ['type' => 'integer'],
                        'protein' => ['type' => 'number'],
                        'carbs' => ['type' => 'number'],
                        'fat' => ['type' => 'number'],
                        'prep_time' => ['type' => 'integer', 'description' => 'Minutos de preparación.'],
                        'servings' => ['type' => 'integer'],
                        'ingredients' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'quantity' => ['type' => 'string'],
                                    'unit' => ['type' => 'string'],
                                ],
                                'required' => ['name'],
                            ],
                        ],
                        'instructions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Pasos de preparación en orden.',
                        ],
                    ],
                    'required' => ['title', 'meal_type', 'ingredients', 'instructions'],
                ],
            ],
        ];

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'propose_diet_plan',
                'description' => 'Arma una PROPUESTA de plan de dieta nuevo para un cliente, usando recetas ya existentes en el catálogo (no lo crea ni asigna todavía). El coach tiene que aprobarla. Buscá los nombres de receta lo más parecido posible a las del catálogo; si una no existe, se omite y se avisa.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_merge($clientArg, [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'goal' => ['type' => 'string', 'enum' => ['perdida_peso', 'ganancia_muscular', 'mantenimiento', 'rendimiento']],
                        'target_calories' => ['type' => 'integer'],
                        'days' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'day_of_week' => ['type' => 'string', 'enum' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']],
                                    'meals' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'meal_type' => ['type' => 'string', 'enum' => ['desayuno', 'almuerzo', 'merienda', 'cena', 'pre_entrenamiento', 'post_entrenamiento']],
                                                'recipe_title' => ['type' => 'string', 'description' => 'Nombre de la receta, debe coincidir (aprox) con una del catálogo.'],
                                            ],
                                            'required' => ['meal_type', 'recipe_title'],
                                        ],
                                    ],
                                ],
                                'required' => ['day_of_week', 'meals'],
                            ],
                        ],
                    ]),
                    'required' => array_merge($clientRequired, ['title', 'days']),
                ],
            ],
        ];

        $tools[] = [
            'type' => 'function',
            'function' => [
                'name' => 'propose_diet_plan_edit',
                'description' => 'Arma una PROPUESTA de edición sobre el plan de dieta ACTIVO actual de un cliente (no lo modifica todavía). Mandá la versión completa de los días/comidas como debería quedar después del cambio (no un diff). El coach tiene que aprobarla. Si el cliente no tiene plan activo, avisale y sugerile usar propose_diet_plan en cambio.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_merge($clientArg, [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'goal' => ['type' => 'string', 'enum' => ['perdida_peso', 'ganancia_muscular', 'mantenimiento', 'rendimiento']],
                        'target_calories' => ['type' => 'integer'],
                        'days' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'day_of_week' => ['type' => 'string', 'enum' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']],
                                    'meals' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'meal_type' => ['type' => 'string', 'enum' => ['desayuno', 'almuerzo', 'merienda', 'cena', 'pre_entrenamiento', 'post_entrenamiento']],
                                                'recipe_title' => ['type' => 'string'],
                                            ],
                                            'required' => ['meal_type', 'recipe_title'],
                                        ],
                                    ],
                                ],
                                'required' => ['day_of_week', 'meals'],
                            ],
                        ],
                    ]),
                    'required' => array_merge($clientRequired, ['title', 'days']),
                ],
            ],
        ];

        return $tools;
    }

    private static function daysSchema(): array
    {
        return [
            'type' => 'array',
            'description' => 'Días de entrenamiento de la rutina (versión completa y final).',
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
        ];
    }

    /**
     * Ejecuta una tool call del modelo.
     *
     * @return array{content: string, pending: ?array, resolvedClient: ?User}
     */
    public static function execute(string $name, array $args, User $coach, ?User $client): array
    {
        if ($name === 'search_client') {
            return ['content' => static::searchClient($args, $coach), 'pending' => null, 'resolvedClient' => null];
        }

        // Resolver cliente: el fijado en la conversación, o vía client_query (chat general)
        $resolvedClient = $client;

        if (! $resolvedClient && ! empty($args['client_query'])) {
            $matches = static::findClients($args['client_query'], $coach);

            if ($matches->isEmpty()) {
                return ['content' => "No encontré ningún cliente que coincida con \"{$args['client_query']}\".", 'pending' => null, 'resolvedClient' => null];
            }

            if ($matches->count() > 1) {
                $names = $matches->map(fn ($c) => "{$c->name} ({$c->email})")->implode(', ');

                return ['content' => "Hay más de un cliente que coincide con \"{$args['client_query']}\": {$names}. Pedile al coach que aclare cuál.", 'pending' => null, 'resolvedClient' => null];
            }

            $resolvedClient = $matches->first();
        }

        if (! $resolvedClient && in_array($name, ['send_notification', 'propose_routine', 'propose_routine_edit', 'propose_diet_plan', 'propose_diet_plan_edit'])) {
            return ['content' => 'No sé a qué cliente aplica esta acción. Usá search_client o pedile al coach que aclare el nombre.', 'pending' => null, 'resolvedClient' => null];
        }

        return match ($name) {
            'send_notification' => ['content' => static::sendNotification($args, $coach, $resolvedClient), 'pending' => null, 'resolvedClient' => $resolvedClient],
            'propose_routine' => static::buildRoutineProposal($args, $resolvedClient, edit: false),
            'propose_routine_edit' => static::buildRoutineProposal($args, $resolvedClient, edit: true),
            'propose_recipe' => static::buildRecipeProposal($args),
            'propose_diet_plan' => static::buildDietPlanProposal($args, $resolvedClient, $coach, edit: false),
            'propose_diet_plan_edit' => static::buildDietPlanProposal($args, $resolvedClient, $coach, edit: true),
            default => ['content' => "Herramienta desconocida: {$name}", 'pending' => null, 'resolvedClient' => $resolvedClient],
        };
    }

    public static function findClients(string $query, User $coach)
    {
        $query = trim($query);

        return User::query()
            ->where('role', 'client')
            ->when($coach->role !== 'super_admin', fn ($q) => $q->where('gym_id', $coach->gym_id))
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"))
            ->orderBy('name')
            ->get();
    }

    private static function searchClient(array $args, User $coach): string
    {
        $matches = static::findClients($args['query'] ?? '', $coach);

        if ($matches->isEmpty()) {
            return "No encontré clientes que coincidan con \"{$args['query']}\".";
        }

        return 'Coincidencias: '.$matches->map(fn ($c) => "{$c->name} ({$c->email})")->implode(', ').'.';
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

    /**
     * Arma (sin guardar nada) la propuesta de rutina/edición y un resumen legible.
     */
    private static function buildRoutineProposal(array $args, User $client, bool $edit): array
    {
        $title = trim($args['title'] ?? '');
        $days = $args['days'] ?? [];

        if ($title === '' || empty($days)) {
            return ['content' => 'Error: falta el título o los días de la rutina.', 'pending' => null, 'resolvedClient' => $client];
        }

        if ($edit) {
            $activeAssignment = Assignment::query()
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->whereNull('end_date')
                ->latest('assigned_at')
                ->first();

            if (! $activeAssignment) {
                return ['content' => "{$client->name} no tiene una rutina activa para editar. Usá propose_routine para crear una nueva.", 'pending' => null, 'resolvedClient' => $client];
            }
        }

        $lines = [];
        $lines[] = ($edit ? 'Edición propuesta para la rutina activa de ' : 'Rutina nueva propuesta para ')."{$client->name}: \"{$title}\".";

        foreach ($days as $day) {
            $exNames = collect($day['exercises'] ?? [])->map(fn ($e) => trim($e['exercise_title'] ?? ''))->filter()->implode(', ');
            $lines[] = "- Día {$day['day_number']} \"{$day['title']}\": {$exNames}";
        }

        $lines[] = 'Le mostré la propuesta al coach para que la revise y apruebe. Todavía no se guardó nada.';

        return [
            'content' => implode("\n", $lines),
            'pending' => [
                'type' => $edit ? 'routine_edit' : 'routine_create',
                'client_id' => $client->id,
                'client_name' => $client->name,
                'args' => $args,
            ],
            'resolvedClient' => $client,
        ];
    }

    private static function buildRecipeProposal(array $args): array
    {
        $title = trim($args['title'] ?? '');

        if ($title === '' || empty($args['ingredients']) || empty($args['instructions'])) {
            return ['content' => 'Error: falta el título, ingredientes o instrucciones de la receta.', 'pending' => null, 'resolvedClient' => null];
        }

        $lines = [];
        $lines[] = "Receta nueva propuesta: \"{$title}\".";
        $lines[] = 'Ingredientes: '.collect($args['ingredients'])->map(fn ($i) => trim($i['name'] ?? ''))->filter()->implode(', ').'.';
        $lines[] = 'Le mostré la propuesta al coach para que la revise y apruebe. Todavía no se guardó nada.';

        return [
            'content' => implode("\n", $lines),
            'pending' => [
                'type' => 'recipe_create',
                'client_id' => null,
                'client_name' => null,
                'args' => $args,
            ],
            'resolvedClient' => null,
        ];
    }

    private static function buildDietPlanProposal(array $args, User $client, User $coach, bool $edit = false): array
    {
        $title = trim($args['title'] ?? '');
        $days = $args['days'] ?? [];

        if ($title === '' || empty($days)) {
            return ['content' => 'Error: falta el título o los días del plan de dieta.', 'pending' => null, 'resolvedClient' => $client];
        }

        if ($edit) {
            $activeAssignment = DietAssignment::query()
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->whereNull('end_date')
                ->latest('assigned_at')
                ->first();

            if (! $activeAssignment) {
                return ['content' => "{$client->name} no tiene un plan de dieta activo para editar. Usá propose_diet_plan para crear uno nuevo.", 'pending' => null, 'resolvedClient' => $client];
            }
        }

        $lines = [];
        $lines[] = ($edit ? 'Edición propuesta para el plan de dieta activo de ' : 'Plan de dieta nuevo propuesto para ')."{$client->name}: \"{$title}\".";

        foreach ($days as $day) {
            $meals = collect($day['meals'] ?? [])->map(fn ($m) => trim($m['recipe_title'] ?? ''))->filter()->implode(', ');
            $lines[] = "- {$day['day_of_week']}: {$meals}";
        }

        $lines[] = 'Le mostré la propuesta al coach para que la revise y apruebe. Todavía no se guardó nada.';

        return [
            'content' => implode("\n", $lines),
            'pending' => [
                'type' => $edit ? 'diet_plan_edit' : 'diet_plan_create',
                'client_id' => $client->id,
                'client_name' => $client->name,
                'args' => $args,
            ],
            'resolvedClient' => $client,
        ];
    }

    /**
     * El coach aprobó la propuesta: acá se ejecuta de verdad (crea/edita/asigna).
     */
    public static function confirmAction(array $pending, User $coach): string
    {
        return match ($pending['type']) {
            'routine_create' => static::createAndAssignRoutine($pending['args'], $coach, User::findOrFail($pending['client_id'])),
            'routine_edit' => static::editActiveRoutine($pending['args'], $coach, User::findOrFail($pending['client_id'])),
            'recipe_create' => static::createRecipe($pending['args'], $coach),
            'diet_plan_create' => static::createAndAssignDietPlan($pending['args'], $coach, User::findOrFail($pending['client_id'])),
            'diet_plan_edit' => static::editActiveDietPlan($pending['args'], $coach, User::findOrFail($pending['client_id'])),
            default => 'No se pudo confirmar: tipo de acción desconocido.',
        };
    }

    private static function editActiveDietPlan(array $args, User $coach, User $client): string
    {
        $activeAssignment = DietAssignment::query()
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        if (! $activeAssignment) {
            return "{$client->name} ya no tiene un plan de dieta activo (puede que haya cambiado desde que se armó la propuesta). No se aplicó ningún cambio.";
        }

        $plan = $activeAssignment->dietPlan;
        $days = $args['days'] ?? [];
        $missingRecipes = [];

        DB::transaction(function () use ($plan, $args, $days, $coach, &$missingRecipes) {
            $plan->update([
                'title' => trim($args['title'] ?? $plan->title),
                'description' => $args['description'] ?? $plan->description,
                'goal' => $args['goal'] ?? $plan->goal,
                'target_calories' => $args['target_calories'] ?? $plan->target_calories,
            ]);

            // Reemplaza días/comidas por la versión nueva y completa.
            $plan->days()->each(function ($day) {
                $day->recipes()->delete();
                $day->delete();
            });

            foreach ($days as $dayData) {
                $day = $plan->days()->create([
                    'day_of_week' => $dayData['day_of_week'],
                    'notes' => $dayData['notes'] ?? null,
                ]);

                $order = 1;
                foreach ($dayData['meals'] ?? [] as $mealData) {
                    $recipeTitle = trim($mealData['recipe_title'] ?? '');
                    if ($recipeTitle === '') continue;

                    $recipe = Recipe::query()
                        ->where(fn ($q) => $q->where('is_global', true)->orWhereHas('creator', fn ($cq) => $cq->where('gym_id', $coach->gym_id)))
                        ->where('title', 'like', '%'.$recipeTitle.'%')
                        ->first();

                    if (! $recipe) {
                        $missingRecipes[] = $recipeTitle;
                        continue;
                    }

                    $day->recipes()->create([
                        'recipe_id' => $recipe->id,
                        'meal_type' => $mealData['meal_type'],
                        'order' => $order++,
                        'servings' => $mealData['servings'] ?? 1,
                    ]);
                }
            }
        });

        $summary = "Plan de dieta activo de {$client->name} (\"{$plan->title}\") actualizado.";

        if (! empty($missingRecipes)) {
            $summary .= ' Ojo: no encontré en el catálogo estas recetas, así que las omití: '.implode(', ', array_unique($missingRecipes)).'.';
        }

        return $summary;
    }

    private static function resolveExercise(string $exerciseTitle, User $coach, array &$missing): ?Exercise
    {
        $exerciseTitle = trim($exerciseTitle);
        if ($exerciseTitle === '') return null;

        $exercise = Exercise::query()
            ->where(fn ($q) => $q->where('is_global', true)->orWhere('gym_id', $coach->gym_id))
            ->where('title', 'like', '%'.$exerciseTitle.'%')
            ->first();

        if (! $exercise) {
            $missing[] = $exerciseTitle;
        }

        return $exercise;
    }

    private static function createAndAssignRoutine(array $args, User $coach, User $client): string
    {
        $title = trim($args['title'] ?? '');
        $days = $args['days'] ?? [];
        $missingExercises = [];

        $routine = DB::transaction(function () use ($title, $args, $days, $coach, $client, &$missingExercises) {
            $routine = Routine::create([
                'gym_id' => $coach->gym_id,
                'coach_id' => $coach->id,
                'title' => $title,
                'description' => $args['description'] ?? null,
            ]);

            static::writeRoutineDays($routine, $days, $coach, $missingExercises);

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

    private static function editActiveRoutine(array $args, User $coach, User $client): string
    {
        $activeAssignment = Assignment::query()
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        if (! $activeAssignment) {
            return "{$client->name} ya no tiene una rutina activa (puede que haya cambiado desde que se armó la propuesta). No se aplicó ningún cambio.";
        }

        $routine = $activeAssignment->routine;
        $days = $args['days'] ?? [];
        $missingExercises = [];

        DB::transaction(function () use ($routine, $args, $days, $coach, &$missingExercises) {
            $routine->update([
                'title' => trim($args['title'] ?? $routine->title),
                'description' => $args['description'] ?? $routine->description,
            ]);

            // Reemplaza días/ejercicios por la versión nueva y completa.
            $routine->days()->each(function ($day) {
                $day->exercises()->delete();
                $day->delete();
            });

            static::writeRoutineDays($routine, $days, $coach, $missingExercises);
        });

        $summary = "Rutina activa de {$client->name} (\"{$routine->title}\") actualizada con ".count($days).' día(s).';

        if (! empty($missingExercises)) {
            $summary .= ' Ojo: no encontré en la biblioteca estos ejercicios, así que los omití: '.implode(', ', array_unique($missingExercises)).'.';
        }

        return $summary;
    }

    private static function writeRoutineDays(Routine $routine, array $days, User $coach, array &$missingExercises): void
    {
        foreach ($days as $dayData) {
            $day = $routine->days()->create([
                'day_number' => $dayData['day_number'] ?? 1,
                'title' => $dayData['title'] ?? 'Día',
            ]);

            $order = 1;
            foreach ($dayData['exercises'] ?? [] as $exData) {
                $exercise = static::resolveExercise($exData['exercise_title'] ?? '', $coach, $missingExercises);

                if (! $exercise) continue;

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
    }

    private static function createRecipe(array $args, User $coach): string
    {
        $recipe = DB::transaction(function () use ($args, $coach) {
            $recipe = Recipe::create([
                'title' => trim($args['title'] ?? ''),
                'description' => $args['description'] ?? null,
                'meal_type' => $args['meal_type'] ?? 'almuerzo',
                'calories' => $args['calories'] ?? null,
                'protein' => $args['protein'] ?? null,
                'carbs' => $args['carbs'] ?? null,
                'fat' => $args['fat'] ?? null,
                'prep_time' => $args['prep_time'] ?? null,
                'servings' => $args['servings'] ?? 1,
                'is_global' => $coach->role === 'super_admin',
                'created_by_id' => $coach->id,
            ]);

            $order = 1;
            foreach ($args['ingredients'] ?? [] as $ing) {
                if (trim($ing['name'] ?? '') === '') continue;
                $recipe->ingredients()->create([
                    'name' => $ing['name'],
                    'quantity' => $ing['quantity'] ?? null,
                    'unit' => $ing['unit'] ?? null,
                    'order' => $order++,
                ]);
            }

            $step = 1;
            foreach ($args['instructions'] ?? [] as $instruction) {
                if (trim($instruction) === '') continue;
                $recipe->instructions()->create([
                    'step' => $step++,
                    'instruction' => $instruction,
                ]);
            }

            return $recipe;
        });

        return "Receta \"{$recipe->title}\" creada y agregada al catálogo.";
    }

    private static function createAndAssignDietPlan(array $args, User $coach, User $client): string
    {
        $title = trim($args['title'] ?? '');
        $days = $args['days'] ?? [];
        $missingRecipes = [];

        $plan = DB::transaction(function () use ($title, $args, $days, $coach, $client, &$missingRecipes) {
            $plan = DietPlan::create([
                'gym_id' => $coach->gym_id,
                'coach_id' => $coach->id,
                'title' => $title,
                'description' => $args['description'] ?? null,
                'goal' => $args['goal'] ?? 'mantenimiento',
                'target_calories' => $args['target_calories'] ?? null,
            ]);

            foreach ($days as $dayData) {
                $day = $plan->days()->create([
                    'day_of_week' => $dayData['day_of_week'],
                    'notes' => $dayData['notes'] ?? null,
                ]);

                $order = 1;
                foreach ($dayData['meals'] ?? [] as $mealData) {
                    $recipeTitle = trim($mealData['recipe_title'] ?? '');
                    if ($recipeTitle === '') continue;

                    $recipe = Recipe::query()
                        ->where(fn ($q) => $q->where('is_global', true)->orWhereHas('creator', fn ($cq) => $cq->where('gym_id', $coach->gym_id)))
                        ->where('title', 'like', '%'.$recipeTitle.'%')
                        ->first();

                    if (! $recipe) {
                        $missingRecipes[] = $recipeTitle;
                        continue;
                    }

                    $day->recipes()->create([
                        'recipe_id' => $recipe->id,
                        'meal_type' => $mealData['meal_type'],
                        'order' => $order++,
                        'servings' => $mealData['servings'] ?? 1,
                    ]);
                }
            }

            DietAssignment::query()
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->whereNull('end_date')
                ->update(['status' => 'completed', 'end_date' => now()->toDateString()]);

            DietAssignment::create([
                'gym_id' => $coach->gym_id,
                'diet_plan_id' => $plan->id,
                'client_id' => $client->id,
                'assigned_by_id' => $coach->id,
                'assigned_at' => now(),
                'start_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            return $plan;
        });

        $summary = "Plan de dieta \"{$plan->title}\" creado y asignado a {$client->name} como activo.";

        if (! empty($missingRecipes)) {
            $summary .= ' Ojo: no encontré en el catálogo estas recetas, así que las omití: '.implode(', ', array_unique($missingRecipes)).'.';
        }

        return $summary;
    }
}
