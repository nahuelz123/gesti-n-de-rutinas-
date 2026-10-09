<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FileAiExecutionTimeTest extends TestCase
{
    public function test_slow_analysis_outlives_the_original_php_limit_without_a_fatal_error(): void
    {
        $script = <<<'PHP'
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config()->set('services.gemini.key', 'test-key');
Illuminate\Support\Facades\Http::fake(function () {
    $start = microtime(true);
    $hash = 'test';
    while (microtime(true) - $start < 2) {
        $hash = hash('sha256', $hash);
    }
    return Illuminate\Support\Facades\Http::response([
        'candidates' => [['content' => ['parts' => [['text' => '{"title":"Rutina"}']]]]],
    ]);
});
set_time_limit(1);
fwrite(STDERR, json_encode([
    'timer_function_available' => function_exists('set_time_limit'),
    'timer_return' => set_time_limit(150),
    'timer_value' => ini_get('max_execution_time'),
])."\n");
set_time_limit(1);
$result = app(App\Services\FileAiReader::class)->read('Transcribir', null, null, 'Sentadilla 3x12');
echo json_encode(['result' => $result, 'restored_limit' => (int) ini_get('max_execution_time')]);
PHP;

        $process = new Process([PHP_BINARY, '-r', $script], base_path());
        $process->setTimeout(10)->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
        $this->assertSame([
            'result' => ['title' => 'Rutina'], 'restored_limit' => 1,
        ], json_decode($process->getOutput(), true));
    }

    public function test_timeout_restores_php_limit_and_returns_a_controlled_error(): void
    {
        $original = (int) ini_get('max_execution_time');
        config()->set('services.gemini.key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::failedConnection()]);

        try {
            app(\App\Services\FileAiReader::class)->read('Transcribir', null, null, 'Sentadilla 3x12');
            $this->fail('El timeout debe devolver un error controlado.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('tardó demasiado', $error->getMessage());
        }
        $this->assertSame($original, (int) ini_get('max_execution_time'));
    }
}
