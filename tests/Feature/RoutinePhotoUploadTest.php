<?php

namespace Tests\Feature;

use App\Filament\Resources\Routines\Pages\CreateRoutine;
use App\Filament\Resources\Routines\RoutineResource;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RoutinePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_creates_only_a_reviewable_draft(): void
    {
        $gym = Gym::create(['name' => 'Gym foto']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $exercise = Exercise::create(['title' => 'Sentadilla', 'is_global' => true, 'muscle_group' => 'piernas']);

        config()->set('services.gemini.key', 'test-key');
        $routine = [
            'title' => 'Piernas',
            'days' => [['title' => 'Día 1', 'exercises' => [['name' => 'Sentadilla', 'sets' => 3, 'reps' => '12']]]],
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($routine)]]]]],
        ])]);

        // PNG de 1x1: se procesa por el endpoint HTTP convencional, sin Livewire Upload.
        $photo = UploadedFile::fake()->createWithContent('rutina.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='
        ));

        $this->actingAs($coach)->post(route('routines.photo.store'), ['photo' => $photo])
            ->assertRedirect(RoutineResource::getUrl('create'))
            ->assertSessionHas('routine-photo-draft', fn (array $draft) =>
                $draft['days'][0]['exercises'][0]['exercise_id'] === $exercise->id
            );

        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('assignments', 0);

        Livewire::actingAs($coach)->test(CreateRoutine::class)
            ->assertSet('data.title', 'Piernas');
    }

    public function test_client_cannot_upload_routines(): void
    {
        $gym = Gym::create(['name' => 'Gym foto']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        $this->actingAs($client)->get(route('routines.photo.show'))->assertForbidden();
        $this->actingAs($client)->post(route('routines.photo.store'))->assertForbidden();
    }
}
