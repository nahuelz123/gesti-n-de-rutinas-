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
