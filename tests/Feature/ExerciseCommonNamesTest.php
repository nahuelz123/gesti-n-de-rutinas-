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
        $genericCurl = Exercise::create(['title' => 'Curl femoral', 'muscle_group' => 'piernas', 'is_global' => true]);
        Livewire::actingAs($coach)
            ->test(\App\Filament\Resources\Exercises\Pages\ListExercises::class)
            ->searchTable('sillon de cuadriceps')
            ->assertCanSeeTableRecords([$extension])
            ->assertCanNotSeeTableRecords([$curl])
            ->searchTable('camilla de isquios')
            ->assertCanSeeTableRecords([$curl, $genericCurl])
            ->assertCanNotSeeTableRecords([$extension]);
    }


    public function test_import_does_not_substitute_a_different_exercise_variant(): void
    {
        Exercise::create(['title' => 'Zancadas caminando', 'muscle_group' => 'piernas', 'is_global' => true]);
        Exercise::create(['title' => 'Barbell squat', 'muscle_group' => 'piernas', 'is_global' => true]);
        config()->set('services.gemini.key', 'test-key');
        $routine = ['title' => 'Piernas', 'days' => [[
            'title' => 'Día 1', 'exercises' => [
                ['name' => 'Reverse lunge', 'sets' => 3, 'reps' => '12'],
                ['name' => 'Bodyweight squat', 'sets' => 3, 'reps' => '10'],
            ],
        ]]];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($routine)]]]]],
        ])]);
        $draft = app(RoutinePhotoReader::class)->read('image', 'image/png', null);
        $this->assertNull($draft['days'][0]['exercises'][0]['exercise_id']);
        $this->assertNull($draft['days'][0]['exercises'][1]['exercise_id']);
        $this->assertContains('Reverse lunge', $draft['unmatched']);
        $this->assertContains('Bodyweight squat', $draft['unmatched']);
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

    public function test_excel_import_maps_deep_squat_to_the_generic_squat(): void
    {
        $squat = Exercise::create(['title' => 'Sentadilla', 'muscle_group' => 'piernas', 'is_global' => true]);
        Exercise::create(['title' => 'Barbell full squat', 'muscle_group' => 'piernas', 'is_global' => true]);
        config()->set('services.gemini.key', 'test-key');
        $routine = ['title' => 'Piernas', 'days' => [[
            'title' => 'Día 1', 'exercises' => [
                ['name' => 'Sentadilla profunda', 'sets' => 3, 'reps' => '10'],
            ],
        ]]];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($routine)]]]]],
        ])]);
        $draft = app(RoutinePhotoReader::class)->read('xlsx bytes', 'image/png', null);
        $this->assertSame($squat->id, $draft['days'][0]['exercises'][0]['exercise_id']);
        $this->assertSame([], $draft['unmatched']);
    }

    public function test_deep_squat_does_not_choose_an_equipment_variant_when_generic_squat_is_missing(): void
    {
        Exercise::create(['title' => 'Barbell full squat', 'muscle_group' => 'piernas', 'is_global' => true]);
        $catalog = Exercise::query()->get(['id', 'title']);
        $this->assertNull(\App\Support\ExerciseNames::uniqueMatch($catalog, 'Sentadilla profunda'));
    }

    public function test_photo_import_resolves_named_gym_variants_without_changing_equipment(): void
    {
        $smithBench = Exercise::create(['title' => 'Smith bench press', 'muscle_group' => 'pecho', 'is_global' => true]);
        $smithShoulder = Exercise::create(['title' => 'Press militar en Smith', 'muscle_group' => 'hombros', 'is_global' => true]);
        $jm = Exercise::create(['title' => 'JM press', 'muscle_group' => 'triceps', 'is_global' => true]);
        $laterals = Exercise::create(['title' => 'Elevaciones laterales', 'muscle_group' => 'hombros', 'is_global' => true]);
        Exercise::create(['title' => 'Barbell bench press', 'muscle_group' => 'pecho', 'is_global' => true]);
        Exercise::create(['title' => 'Smith seated shoulder press', 'muscle_group' => 'hombros', 'is_global' => true]);
        $catalog = Exercise::query()->get(['id', 'title']);

        $this->assertSame($smithBench->id, \App\Support\ExerciseNames::uniqueMatch($catalog, 'plano en smit')?->id);
        $this->assertSame($smithShoulder->id, \App\Support\ExerciseNames::uniqueMatch($catalog, 'press militar en smit')?->id);
        $this->assertSame($jm->id, \App\Support\ExerciseNames::uniqueMatch($catalog, 'KAS press')?->id);
        $this->assertSame($jm->id, \App\Support\ExerciseNames::uniqueMatch($catalog, 'J press')?->id);
        $this->assertSame($laterals->id, \App\Support\ExerciseNames::uniqueMatch($catalog, 'vuelos laterales')?->id);
    }
}
