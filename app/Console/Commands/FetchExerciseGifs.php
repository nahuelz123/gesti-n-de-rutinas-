<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Exercise;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchExerciseGifs extends Command
{
    protected $signature   = 'exercises:fetch-gifs {--download : Solo descarga el JSON} {--apply : Solo aplica el JSON ya descargado}';
    protected $description = 'Descarga GIFs de ExerciseDB y los aplica a los ejercicios';

    private string $baseUrl   = 'https://exercisedb.dev/api/v1';
    private string $cacheFile = 'exercisedb_cache.json';

    private array $translations = [
        'Press banca con barra'                => 'barbell bench press',
        'Press banca inclinado con mancuernas' => 'dumbbell incline bench press',
        'Press banca declinado'                => 'barbell decline bench press',
        'Aperturas con mancuernas'             => 'dumbbell fly',
        'Cruces en polea'                      => 'cable crossover',
        'Flexiones de brazos'                  => 'push-up',
        'Dominadas pronas'                     => 'pull-up',
        'Jalón al pecho'                       => 'cable lat pulldown',
        'Remo con barra'                       => 'barbell bent over row',
        'Remo con mancuerna a una mano'        => 'dumbbell bent over row',
        'Remo en polea baja'                   => 'cable seated row',
        'Pullover en polea'                    => 'cable pullover',
        'Sentadilla con barra'                 => 'barbell squat',
        'Sentadilla frontal'                   => 'barbell front squat',
        'Prensa de piernas'                    => 'sled leg press',
        'Zancadas caminando'                   => 'dumbbell walking lunge',
        'Extensiones de cuádriceps'            => 'lever leg extension',
        'Curl femoral'                         => 'lever lying leg curl',
        'Hip thrust con barra'                 => 'barbell hip thrust',
        'Puente de glúteos'                    => 'glute bridge',
        'Patada de glúteo en polea'            => 'cable glute kickback',
        'Sentadilla sumo'                      => 'barbell sumo squat',
        'Peso muerto rumano'                   => 'barbell romanian deadlift',
        'Abducción de cadera (máquina)'        => 'lever hip abduction',
        'Press militar'                        => 'barbell overhead press',
        'Press con mancuernas'                 => 'dumbbell shoulder press',
        'Elevaciones laterales'                => 'dumbbell lateral raise',
        'Elevaciones frontales'                => 'dumbbell front raise',
        'Pájaros / posteriores con mancuernas' => 'dumbbell rear lateral raise',
        'Face pull'                            => 'cable face pull',
        'Curl bíceps con barra'                => 'barbell curl',
        'Curl martillo'                        => 'dumbbell hammer curl',
        'Curl inclinado'                       => 'dumbbell incline curl',
        'Curl concentrado'                     => 'dumbbell concentration curl',
        'Curl en polea'                        => 'cable curl',
        'Curl predicador'                      => 'barbell preacher curl',
        'Fondos en paralelas'                  => 'dip',
        'Press cerrado'                        => 'barbell close-grip bench press',
        'Extensión de tríceps en polea'        => 'cable triceps pushdown',
        'Extensión por encima de la cabeza'    => 'dumbbell tricep overhead extension',
        'Patada de tríceps con mancuerna'      => 'dumbbell kickback',
        'Rompecráneos (extensión en banco)'    => 'barbell skull crusher',
        'Plancha'                              => 'plank',
        'Crunch'                               => 'crunch',
        'Elevación de piernas'                 => 'hanging leg raise',
        'Russian twist'                        => 'russian twist',
        'Ab wheel'                             => 'ab wheel rollout',
        'Crunch en polea'                      => 'cable crunch',
        'Soga'                                 => 'jump rope',
        'Peso muerto convencional'             => 'barbell deadlift',
        'Kettlebell swing'                     => 'kettlebell swing',
        'Burpees'                              => 'burpee',
        'Thrusters'                            => 'barbell thruster',
    ];

    public function handle(): int
    {
        $onlyDownload = $this->option('download');
        $onlyApply    = $this->option('apply');

        if (!$onlyApply) {
            $this->download();
        }

        if (!$onlyDownload) {
            $this->apply();
        }

        return self::SUCCESS;
    }

    private function download(): void
    {
        $cachePath = storage_path('app/' . $this->cacheFile);

        // Si ya existe el cache, preguntar si re-descargar
        if (file_exists($cachePath)) {
            $existing = json_decode(file_get_contents($cachePath), true);
            $this->info('Cache existente con ' . count($existing) . ' ejercicios.');
            if (!$this->confirm('¿Re-descargar desde la API?', false)) {
                return;
            }
        }

        $this->info('Descargando todos los ejercicios de ExerciseDB...');
        $all    = [];
        $offset = 0;
        $limit  = 100;
        $total  = 1500;

        $bar = $this->output->createProgressBar(ceil($total / $limit));
        $bar->start();

        do {
            $response = Http::timeout(15)->get("{$this->baseUrl}/exercises", [
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            if ($response->status() === 429) {
                $bar->clear();
                $this->warn("Rate limit en offset={$offset}. Esperando 20s...");
                sleep(20);
                $response = Http::timeout(15)->get("{$this->baseUrl}/exercises", [
                    'limit'  => $limit,
                    'offset' => $offset,
                ]);
            }

            if (!$response->successful()) {
                $bar->clear();
                $this->warn("Error HTTP {$response->status()} en offset={$offset}. Guardando lo que tenemos...");
                break;
            }

            $body = $response->json();
            $page = $body['data'] ?? [];

            if (empty($page)) break;

            $all = array_merge($all, $page);
            $bar->advance();
            $offset += $limit;

            sleep(2);

        } while (count($page) === $limit && $offset < $total);

        $bar->finish();
        $this->newLine();

        // Guardar cache
        file_put_contents($cachePath, json_encode($all));
        $this->info('Cache guardado: ' . count($all) . ' ejercicios en storage/app/' . $this->cacheFile);
    }

    private function apply(): void
    {
        $cachePath = storage_path('app/' . $this->cacheFile);

        if (!file_exists($cachePath)) {
            $this->error('No hay cache. Corré primero: php artisan exercises:fetch-gifs --download');
            return;
        }

        $all = json_decode(file_get_contents($cachePath), true);
        $this->info('Usando cache con ' . count($all) . ' ejercicios.');

        // Indexar por nombre lowercase
        $indexed = [];
        foreach ($all as $ex) {
            $indexed[strtolower($ex['name'])] = $ex['gifUrl'];
        }

        $exercises = Exercise::whereNull('gif_url')->get();
        $this->info("Ejercicios sin GIF: {$exercises->count()}");

        $updated = 0;
        $failed  = [];

        foreach ($exercises as $exercise) {
            $searchTerm = $this->translations[$exercise->title] ?? null;

            if (!$searchTerm) {
                $failed[] = $exercise->title . ' (sin traducción)';
                continue;
            }

            $search = strtolower($searchTerm);

            // 1. Exacto
            $gifUrl = $indexed[$search] ?? null;

            // 2. Buscar si algún nombre de la API contiene las palabras clave
            if (!$gifUrl) {
                $words = array_filter(explode(' ', $search), fn($w) => strlen($w) > 2);
                foreach ($indexed as $name => $url) {
                    $hits = 0;
                    foreach ($words as $word) {
                        if (str_contains($name, $word)) $hits++;
                    }
                    if ($hits >= count($words)) {
                        $gifUrl = $url;
                        $this->line("  ~ Match parcial: {$exercise->title} → {$name}");
                        break;
                    }
                }
            }

            if ($gifUrl) {
                $exercise->update(['gif_url' => $gifUrl]);
                $this->line("✓ {$exercise->title}");
                $updated++;
            } else {
                $failed[] = $exercise->title;
            }
        }

        $this->newLine();
        $this->info("✓ Actualizados: {$updated}");

        if (count($failed)) {
            $this->warn("✗ Sin GIF: " . count($failed));
            foreach ($failed as $f) $this->line("  - {$f}");
        }
    }
}