<?php

namespace Tests\Feature;

use App\Filament\Resources\Routines\RoutineResource;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NvidiaPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gemini.key', null);
        config()->set('services.deepseek.key', 'existing-nvidia-key');
        config()->set('services.deepseek.base_url', 'https://integrate.api.nvidia.com/v1/chat/completions');
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('foto.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='
        ));
    }

    public function test_existing_nvidia_key_reads_routine_without_gemini(): void
    {
        $gym = Gym::create(['name' => 'Gym NVIDIA']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $exercise = Exercise::create(['title' => 'Sentadilla', 'is_global' => true, 'muscle_group' => 'piernas']);
        $draft = ['title' => 'Piernas', 'days' => [
            ['title' => 'Día 1', 'exercises' => [['name' => 'Sentadilla', 'sets' => 3, 'reps' => '12']]],
        ]];
        Http::fake(['integrate.api.nvidia.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($draft)], 'finish_reason' => 'stop']],
        ])]);

        $this->actingAs($coach)->post(route('routines.photo.store'), [
            'photo' => $this->photo(), 'photo_processing_consent' => '1',
        ])->assertRedirect(RoutineResource::getUrl('create'))
            ->assertSessionHas('routine-photo-draft', fn (array $data) =>
                $data['days'][0]['exercises'][0]['exercise_id'] === $exercise->id
            );
        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('assignments', 0);
        Http::assertSent(fn ($request) =>
            $request->hasHeader('Authorization', 'Bearer existing-nvidia-key')
            && $request['model'] === 'nvidia/nemotron-nano-12b-v2-vl'
            && str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/png;base64,')
        );
        $this->get(route('routines.photo.show'))->assertSee('NVIDIA')->assertDontSee('Google Gemini');
        $this->get(route('legal.privacy'))->assertSee('NVIDIA')->assertDontSee('Google Gemini');
    }

    public function test_existing_nvidia_key_estimates_food_without_saving_it(): void
    {
        $gym = Gym::create(['name' => 'Gym NVIDIA']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        Http::fake(['integrate.api.nvidia.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'name' => 'Arroz', 'quantity_grams' => 200, 'calories' => 260,
                'protein' => 5, 'carbs' => 56, 'fat' => 1, 'note' => 'Porción aproximada',
            ])], 'finish_reason' => 'stop']],
        ])]);

        $this->actingAs($client)->post(route('client.nutrition.photo.analyze'), [
            'photo' => $this->photo(), 'processing_consent' => '1',
        ])->assertRedirect(route('client.nutrition.photo.show'))
            ->assertSessionHas('meal-photo-estimate', fn (array $data) => $data['calories'] === 260.0);
        $this->assertDatabaseCount('free_meal_logs', 0);
        $this->get(route('client.nutrition.photo.show'))->assertSee('NVIDIA')->assertDontSee('Google Gemini');
    }

    public function test_pdf_pages_are_read_with_existing_nvidia_key(): void
    {
        $gym = Gym::create(['name' => 'Gym PDF']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        Exercise::create(['title' => 'Sentadilla', 'is_global' => true, 'muscle_group' => 'piernas']);
        Http::fake(['integrate.api.nvidia.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'title' => 'PDF piernas', 'days' => [
                    ['title' => 'Día 1', 'exercises' => [['name' => 'Sentadilla', 'sets' => 3, 'reps' => '12']]],
                ],
            ])], 'finish_reason' => 'stop']],
        ])]);

        $this->actingAs($coach)->post(route('routines.photo.store'), [
            'photo' => UploadedFile::fake()->createWithContent('rutina.pdf', $this->pdf(2)),
            'photo_processing_consent' => '1',
        ])->assertRedirect(RoutineResource::getUrl('create'))
            ->assertSessionHas('routine-photo-draft');
        $this->assertDatabaseCount('routines', 0);
        Http::assertSent(fn ($request) =>
            count($request['messages'][0]['content']) === 3
            && str_starts_with($request['messages'][0]['content'][1]['image_url']['url'], 'data:image/jpeg;base64,')
        );
    }

    public function test_large_pdf_is_rejected_without_partial_reading(): void
    {
        $gym = Gym::create(['name' => 'Gym PDF']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        Http::fake();

        $this->actingAs($coach)->post(route('routines.photo.store'), [
            'photo' => UploadedFile::fake()->createWithContent('rutina.pdf', $this->pdf(6)),
            'photo_processing_consent' => '1',
        ])->assertSessionHasErrors('photo')->assertSessionMissing('routine-photo-draft');
        Http::assertNothingSent();
    }

    public function test_provider_failure_does_not_create_a_food_estimate(): void
    {
        $gym = Gym::create(['name' => 'Gym NVIDIA']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        Http::fake(['integrate.api.nvidia.com/*' => Http::response([], 403)]);

        $this->actingAs($client)->post(route('client.nutrition.photo.analyze'), [
            'photo' => $this->photo(), 'processing_consent' => '1',
        ])->assertSessionHasErrors('photo')->assertSessionMissing('meal-photo-estimate');
        $this->assertDatabaseCount('free_meal_logs', 0);
    }

    public function test_pending_analysis_is_polled_without_resubmitting_the_photo(): void
    {
        $gym = Gym::create(['name' => 'Gym NVIDIA']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $requestId = 'af79a140-2c41-4f48-bd1e-6a6131e32a4e';
        Http::fake([
            'integrate.api.nvidia.com/v1/chat/completions' => Http::response([], 202, ['NVCF-REQID' => $requestId]),
            'integrate.api.nvidia.com/v1/status/'.$requestId => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'name' => 'Arroz', 'quantity_grams' => 200, 'calories' => 260,
                    'protein' => 5, 'carbs' => 56, 'fat' => 1, 'note' => '',
                ])], 'finish_reason' => 'stop']],
            ]),
        ]);

        $this->actingAs($client)->post(route('client.nutrition.photo.analyze'), [
            'photo' => $this->photo(), 'processing_consent' => '1',
        ])->assertRedirect(route('client.nutrition.photo.show'))
            ->assertSessionHas('meal-photo-estimate');
        $this->assertDatabaseCount('free_meal_logs', 0);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) =>
            $request->method() === 'GET'
            && $request->url() === 'https://integrate.api.nvidia.com/v1/status/'.$requestId
            && $request->hasHeader('Authorization', 'Bearer existing-nvidia-key')
        );
    }

    private function pdf(int $pages): string
    {
        $font = $pages + 3;
        $kids = [];
        for ($i = 0; $i < $pages; $i++) {
            $kids[] = ($i + 3).' 0 R';
        }
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Count '.$pages.' /Kids ['.implode(' ', $kids).'] >>',
        ];
        for ($i = 0; $i < $pages; $i++) {
            $content = $font + $i + 1;
            $objects[$i + 3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 600] /Resources << /Font << /F1 {$font} 0 R >> >> /Contents {$content} 0 R >>";
        }
        $objects[$font] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        for ($i = 0; $i < $pages; $i++) {
            $stream = 'BT /F1 12 Tf 30 550 Td (Sentadilla 3x12) Tj ET';
            $objects[$font + $i + 1] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
        }
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    public function test_invalid_provider_credentials_are_not_sent_to_nvidia(): void
    {
        config()->set('services.deepseek.base_url', 'https://api.deepseek.com/chat/completions');
        Http::fake();
        $gym = Gym::create(['name' => 'Gym NVIDIA']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);

        $this->actingAs($coach)->post(route('routines.photo.store'), [
            'photo' => $this->photo(), 'photo_processing_consent' => '1',
        ])->assertSessionHasErrors('photo')->assertSessionMissing('routine-photo-draft');
        Http::assertNothingSent();
    }
}
