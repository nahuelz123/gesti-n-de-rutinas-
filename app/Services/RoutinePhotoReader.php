<?php

namespace App\Services;

use App\Models\Exercise;
use App\Support\ExerciseNames;
use Illuminate\Support\Str;
use RuntimeException;

class RoutinePhotoReader
{
    /** @return array{title: string, description: string, days: array, unmatched: array} */
    public function read(string $bytes, string $mime, ?int $gymId): array
    {
        $documentText = in_array($mime, ['docx', 'xlsx'], true) ? $this->extractOfficeText($bytes, $mime) : null;
        if ($mime === 'application/pdf' && ! str_starts_with($bytes, '%PDF-')) {
            throw new RuntimeException('El archivo PDF no es válido.');
        }

        $parsed = app(FileAiReader::class)->read(
            'Transcribí la rutina manuscrita o impresa. No inventes ejercicios, series ni repeticiones. Si un dato no es legible, dejalo vacío. Devolvé JSON en español: {"title":"", "days":[{"title":"", "exercises":[{"name":"", "sets":0, "reps":"", "rest":"", "notes":""}]}]}. Cada día debe tener su título. Series es entero. Conservá indicaciones especiales en notes.',
            $bytes, $mime, $documentText
        );
        if (! is_array($parsed) || ! is_array($parsed['days'] ?? null) || count($parsed['days']) < 1 || count($parsed['days']) > 14) {
            throw new RuntimeException('El archivo no produjo una rutina reconocible. Probá con una imagen más nítida.');
        }

        $catalog = Exercise::query()->visibleToGym($gymId)->get(['id', 'title']);
        $days = [];
        $missing = [];
        foreach ($parsed['days'] as $dayIndex => $day) {
            if (! is_array($day) || ! is_array($day['exercises'] ?? null) || count($day['exercises']) < 1 || count($day['exercises']) > 40) {
                throw new RuntimeException('Un día no tiene ejercicios válidos. Revisá el archivo.');
            }
            $exercises = [];
            foreach ($day['exercises'] as $index => $item) {
                $name = trim((string) ($item['name'] ?? ''));
                $match = ExerciseNames::uniqueMatch($catalog, $name);
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
                    'notes' => trim(($match ? '' : "Leído en el archivo: {$name}. Confirmar ejercicio. ").(string) ($item['notes'] ?? '')),
                    'order' => $index + 1,
                ];
            }
            $days[] = ['day_number' => $dayIndex + 1, 'title' => trim((string) ($day['title'] ?? '')) ?: 'Día '.($dayIndex + 1), 'exercises' => $exercises];
        }

        return ['title' => trim((string) ($parsed['title'] ?? '')) ?: 'Rutina importada',
            'description' => 'Borrador importado de un archivo. Revisar cada día, ejercicio, serie y repetición antes de guardar.',
            'days' => $days, 'unmatched' => array_values(array_unique($missing))];
    }
    private function extractOfficeText(string $bytes, string $type): string
    {
        $path = tempnam(sys_get_temp_dir(), 'routine-');
        if ($path === false) {
            throw new RuntimeException('No se pudo leer el documento.');
        }

        try {
            file_put_contents($path, $bytes);
            $zip = new \ZipArchive;
            if ($zip->open($path) !== true) {
                throw new RuntimeException('El archivo de Office no es válido.');
            }

            try {
                $entries = $type === 'docx'
                    ? ['word/document.xml']
                    : array_merge(['xl/sharedStrings.xml'], array_map(
                        static fn (int $index): string => "xl/worksheets/sheet{$index}.xml",
                        range(1, 14)
                    ));
                $shared = [];
                $lines = [];
                foreach ($entries as $entry) {
                    $stat = $zip->statName($entry);
                    if ($stat === false) {
                        continue;
                    }
                    if ($stat['size'] > 2_000_000) {
                        throw new RuntimeException('El documento es demasiado grande para interpretar.');
                    }
                    $xml = $zip->getFromName($entry);
                    if ($xml === false) {
                        throw new RuntimeException('No se pudo leer el documento.');
                    }
                    $document = new \DOMDocument;
                    if (! @$document->loadXML($xml, LIBXML_NONET)) {
                        throw new RuntimeException('El documento contiene datos inválidos.');
                    }
                    $xpath = new \DOMXPath($document);
                    if ($type === 'docx') {
                        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                        foreach ($xpath->query('//w:p') as $paragraph) {
                            $words = [];
                            foreach ($xpath->query('.//w:t', $paragraph) as $text) {
                                $words[] = $text->textContent;
                            }
                            $lines[] = implode('', $words);
                        }
                    } else {
                        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                        if ($entry === 'xl/sharedStrings.xml') {
                            foreach ($xpath->query('//x:si') as $string) {
                                $shared[] = $string->textContent;
                            }
                            continue;
                        }
                        $lines[] = basename($entry, '.xml');
                        foreach ($xpath->query('//x:sheetData/x:row') as $row) {
                            $cells = [];
                            foreach ($xpath->query('./x:c', $row) as $cell) {
                                $value = $xpath->query('./x:v', $cell)->item(0)?->textContent
                                    ?? $xpath->query('./x:is', $cell)->item(0)?->textContent ?? '';
                                if ($cell->getAttribute('t') === 's') {
                                    $value = $shared[(int) $value] ?? '';
                                }
                                $cells[] = $value;
                            }
                            $lines[] = implode(' | ', $cells);
                        }
                    }
                }
                if (($type === 'docx' && $zip->locateName('word/document.xml') === false)
                    || ($type === 'xlsx' && $zip->locateName('xl/worksheets/sheet1.xml') === false)) {
                    throw new RuntimeException('El documento no tiene una estructura Word o Excel válida.');
                }
                $text = trim(implode("\n", $lines));
                if ($text === '') {
                    throw new RuntimeException('El documento no contiene texto legible. Si es un escaneo, exportalo a PDF.');
                }

                return mb_substr($text, 0, 100_000);
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($path);
        }
    }

}
