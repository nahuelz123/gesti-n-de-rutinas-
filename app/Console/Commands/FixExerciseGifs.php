<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

class FixExerciseGifs extends Command
{
    protected $signature = 'exercises:fix-gifs';

    protected $description = 'Reemplaza las URLs de gif_url rotas (wger.de) de los ejercicios originales por gifs que sí funcionan';

    public function handle(): int
    {
        $path = database_path('data/exercise_gif_fixes.json');

        if (! file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $fixes = json_decode(file_get_contents($path), true);

        $updated = 0;
        $notFound = [];

        foreach ($fixes as $title => $gifUrl) {
            $exercise = Exercise::query()->where('title', $title)->first();

            if (! $exercise) {
                $notFound[] = $title;
                continue;
            }

            $exercise->gif_url = $gifUrl;
            $exercise->save();
            $updated++;
        }

        $this->info("Listo. Actualizados: {$updated}");

        if (! empty($notFound)) {
            $this->warn('No se encontraron en tu base de datos (nombre no coincide exacto): '.implode(', ', $notFound));
        }

        return self::SUCCESS;
    }
}
