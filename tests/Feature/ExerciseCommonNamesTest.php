<?php

namespace Tests\Feature;

use App\Filament\Resources\Routines\Pages\CreateRoutine;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\User;
use App\Services\RoutinePhotoReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ExerciseCommonNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_routine_selector_finds_common_names_and_preserves_gym_visibility(): void
    {
        $gym = Gym::create(['name' => 'Gym búsqueda']);
        $otherGym = Gym::create(['name' => 'Otro gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $titles = [
            'estocadas' => 'Zancadas caminando',
            'sentadilla' => 'Barbell squat',
            'sillon de cuadriceps' => 'Lever leg extension',
            'camilla de isquios' => 'Lever lying leg curl',
            'bulgara' => 'Sentadilla búlgara',
        ];
        $exercises = [];
        foreach ($titles as $search => $title) {
            $exercises[$search] = Exercise::create([
                'title' => $title, 'muscle_group' => 'piernas', 'is_global' => true,
            ]);
        }
        $private = Exercise::create([
            'title' => 'Sillón de cuádriceps privado', 'muscle_group' => 'piernas',
            'gym_id' => $otherGym->id, 'is_global' => false,
        ]);
        $deleted = Exercise::create([
            'title' => 'Sillón de cuádriceps eliminado', 'muscle_group' => 'piernas', 'is_global' => true,
        ]);
        $deleted->delete();

        $page = Livewire::actingAs($coach)->test(CreateRoutine::class)->fillForm([
            'title' => 'Prueba', 'days' => [[
                'title' => 'Piernas', 'exercises' => [['sets' => 3, 'reps' => '12']],
            ]],
        ]);
        $field = collect($page->instance()->form->getFlatFields())
            ->first(fn ($field) => $field->getName() === 'exercise_id');
        $this->assertNotNull($field);
        foreach ($exercises as $search => $exercise) {
            $results = $field->getSearchResults($search);
            $this->assertArrayHasKey($exercise->id, $results, $search);
            $this->assertArrayNotHasKey($private->id, $results);
            $this->assertArrayNotHasKey($deleted->id, $results);
        }
        $this->assertSame([], $field->getSearchResults('%'));
    }


    public function test_catalog_search_accepts_complete_common_names(): void
    {
        $gym = Gym::create(['name' => 'Gym catálogo']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $extension = Exercise::create(['title' => 'Lever leg extension', 'muscle_group' => 'piernas', 'is_global' => true]);
        $curl = Exercise::create(['title' => 'Lever lying leg curl', 'muscle_group' => 'piernas', 'is_global' => true]);
        Livewire::actingAs($coach)
            ->test(\App\Filament\Resources\Exercises\Pages\ListExercises::class)
            ->searchTable('sillon de cuadriceps')
            ->assertCanSeeTableRecords([$extension])
            ->assertCanNotSeeTableRecords([$curl])
            ->searchTable('camilla de isquios')
            ->assertCanSeeTableRecords([$curl])
            ->assertCanNotSeeTableRecords([$extension]);
    }

    public function test_import_matches_unique_common_names_but_leaves_ambiguous_variants_for_review(): void
    {
        $extension = Exercise::create(['title' => 'Extensiones de cuádriceps', 'muscle_group' => 'piernas', 'is_global' => true]);
        Exercise::create(['title' => 'Zancadas caminando', 'muscle_group' => 'piernas', 'is_global' => true]);
        Exercise::create(['title' => 'Dumbbell lunge', 'muscle_group' => 'piernas', 'is_global' => true]);
        config()->set('services.gemini.key', 'test-key');
        $routine = ['title' => 'Piernas', 'days' => [[
            'title' => 'Día 1', 'exercises' => [
                ['name' => 'Sillón de cuádriceps', 'sets' => 3, 'reps' => '12'],
                ['name' => 'Estocadas', 'sets' => 3, 'reps' => '10'],
            ],
        ]]];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($routine)]]]]],
        ])]);
        $draft = app(RoutinePhotoReader::class)->read('image', 'image/png', null);
        $this->assertSame($extension->id, $draft['days'][0]['exercises'][0]['exercise_id']);
        $this->assertNull($draft['days'][0]['exercises'][1]['exercise_id']);
        $this->assertContains('Estocadas', $draft['unmatched']);
        $this->assertDatabaseCount('routines', 0);
    }
}
