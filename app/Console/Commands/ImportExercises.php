<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

class ImportExercises extends Command
{
    protected $signature = 'exercises:import {--fresh : Borra los ejercicios globales existentes antes de importar}';

    protected $description = 'Importa la biblioteca de ejercicios globales desde database/data/exercises_import.json';

    public function handle(): int
    {
        $path = database_path('data/exercises_import.json');

        if (! file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $exercises = json_decode(file_get_contents($path), true);

        if (! is_array($exercises)) {
            $this->error('El archivo JSON no se pudo leer correctamente.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $deleted = Exercise::query()->where('is_global', true)->delete();
            $this->info("Se eliminaron {$deleted} ejercicios globales existentes.");
        }

        $bar = $this->output->createProgressBar(count($exercises));
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($exercises as $item) {
            $exercise = Exercise::query()->updateOrCreate(
                [
                    'title' => $item['title'],
                    'is_global' => true,
                ],
                [
                    'gym_id' => null,
                    'muscle_group' => $item['muscle_group'],
                    'description' => $item['description'],
                    'tips' => $item['attribution'] ?? null,
                    'video_url' => null,
                    'gif_url' => $item['gif_url'],
                    'created_by_id' => null,
                ]
            );

            $exercise->wasRecentlyCreated ? $created++ : $updated++;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo. Creados: {$created} — Actualizados: {$updated}");

        return self::SUCCESS;
    }
}
