<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RoutinePhotoReader
{
    /** @return array{title: string, description: string, days: array, unmatched: array} */
    public function read(string $bytes, string $mime, ?int $gymId): array
    {
        $key = config('services.gemini.key');
        if (! $key) {
            throw new RuntimeException('Configurá GEMINI_API_KEY para leer fotos de rutinas.');
        }

        $model = config('services.gemini.model');
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $model)) {
            throw new RuntimeException('El modelo de Gemini no es válido.');
        }

        $response = Http::timeout(50)->withHeaders(['x-goog-api-key' => $key])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [['parts' => [
                    ['text' => 'Transcribí la rutina manuscrita o impresa. No inventes ejercicios, series ni repeticiones. Si un dato no es legible, dejalo vacío. Devolvé JSON en español: {"title":"", "days":[{"title":"", "exercises":[{"name":"", "sets":0, "reps":"", "rest":"", "notes":""}]}]}. Cada día debe tener su título. Series es entero. Conservá indicaciones especiales en notes.'],
                    ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($bytes)]],
                ]]],
                'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini no pudo procesar la foto (HTTP '.$response->status().'). Intentá nuevamente.');
        }

        $raw = $response->json('candidates.0.content.parts.0.text');
        $parsed = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($parsed) || ! is_array($parsed['days'] ?? null) || count($parsed['days']) < 1 || count($parsed['days']) > 14) {
            throw new RuntimeException('La foto no produjo una rutina reconocible. Probá con una imagen más nítida.');
        }

        $catalog = Exercise::query()->where(fn ($q) => $q->where('is_global', true)->orWhere('gym_id', $gymId))
            ->get(['id', 'title'])->keyBy(fn ($exercise) => Str::lower(Str::ascii(trim($exercise->title))));
        $days = [];
        $missing = [];
        foreach ($parsed['days'] as $dayIndex => $day) {
            if (! is_array($day) || ! is_array($day['exercises'] ?? null) || count($day['exercises']) < 1 || count($day['exercises']) > 40) {
                throw new RuntimeException('Un día no tiene ejercicios válidos. Revisá la foto.');
            }
            $exercises = [];
            foreach ($day['exercises'] as $index => $item) {
                $name = trim((string) ($item['name'] ?? ''));
                $match = $catalog->get(Str::lower(Str::ascii($name)));
                if (! $match) {
                    $missing[] = $name ?: 'ejercicio ilegible';
                }
                $sets = filter_var($item['sets'] ?? null, FILTER_VALIDATE_INT);
                $reps = trim((string) ($item['reps'] ?? ''));
                if ($sets === false || $sets < 1 || $sets > 100 || $reps === '' || mb_strlen($reps) > 20) {
                    $missing[] = $name.' (series o repeticiones ilegibles)';
                }
                $exercises[] = [
                    'exercise_id' => $match?->id, 'sets' => $sets ?: null, 'reps' => $reps,
                    'rest' => Str::limit((string) ($item['rest'] ?? ''), 20, ''),
                    'notes' => trim(($match ? '' : "Leído en la foto: {$name}. Confirmar ejercicio. ").(string) ($item['notes'] ?? '')),
                    'order' => $index + 1,
                ];
            }
            $days[] = ['day_number' => $dayIndex + 1, 'title' => trim((string) ($day['title'] ?? '')) ?: 'Día '.($dayIndex + 1), 'exercises' => $exercises];
        }

        return ['title' => trim((string) ($parsed['title'] ?? '')) ?: 'Rutina desde foto',
            'description' => 'Borrador leído de una foto. Revisar cada día, ejercicio, serie y repetición antes de guardar.',
            'days' => $days, 'unmatched' => array_values(array_unique($missing))];
    }
}
