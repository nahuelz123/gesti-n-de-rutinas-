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

        $this->actingAs($coach)->post(route('routines.photo.store'), ['photo' => $photo, 'photo_processing_consent' => '1'])
            ->assertRedirect(RoutineResource::getUrl('create'))
            ->assertSessionHas('routine-photo-draft', fn (array $draft) =>
                $draft['days'][0]['exercises'][0]['exercise_id'] === $exercise->id
            );

        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('assignments', 0);
        $this->assertDatabaseHas('user_consents', [
            'user_id' => $coach->id,
            'scope' => 'routine_photo_upload',
            'version' => config('legal.versions.routine_photo_upload'),
        ]);

        Livewire::actingAs($coach)->test(CreateRoutine::class)
            ->assertSet('data.title', 'Piernas');
    }

    public function test_photo_upload_requires_explicit_processing_consent(): void
    {
        $gym = Gym::create(['name' => 'Gym foto']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $photo = UploadedFile::fake()->createWithContent('rutina.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='
        ));

        $this->actingAs($coach)->post(route('routines.photo.store'), ['photo' => $photo])
            ->assertSessionHasErrors('photo_processing_consent');

        $this->assertDatabaseMissing('user_consents', [
            'user_id' => $coach->id,
            'scope' => 'routine_photo_upload',
        ]);
    }

    public function test_client_cannot_upload_routines(): void
    {
        $gym = Gym::create(['name' => 'Gym foto']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        $this->actingAs($client)->get(route('routines.photo.show'))->assertForbidden();
        $this->actingAs($client)->post(route('routines.photo.store'))->assertForbidden();
    }
    public function test_pdf_word_and_excel_create_reviewable_drafts_only(): void
    {
        $gym = Gym::create(['name' => 'Gym documentos']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        Exercise::create(['title' => 'Sentadilla', 'is_global' => true, 'muscle_group' => 'piernas']);
        config()->set('services.gemini.key', 'test-key');

        $routine = ['title' => 'Piernas', 'days' => [
            ['title' => 'Día 1', 'exercises' => [['name' => 'Sentadilla', 'sets' => 3, 'reps' => '12']]],
        ]];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($routine)]]]]],
        ])]);

        $files = [
            'pdf' => '%PDF-1.4'."\n".'1 0 obj<</Type/Catalog>>endobj',
            'docx' => $this->officeZip(['word/document.xml' =>
                '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Sentadilla 3x12</w:t></w:r></w:p></w:body></w:document>']),
            'xlsx' => $this->officeZip([
                'xl/sharedStrings.xml' => '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Sentadilla</t></si></sst>',
                'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row><c t="s"><v>0</v></c><c><v>3</v></c><c><v>12</v></c></row></sheetData></worksheet>',
            ]),
        ];

        foreach ($files as $extension => $bytes) {
            $file = UploadedFile::fake()->createWithContent('rutina.'.$extension, $bytes);
            $this->actingAs($coach)->post(route('routines.photo.store'), [
                'photo' => $file, 'photo_processing_consent' => '1',
            ])->assertRedirect(RoutineResource::getUrl('create'))
                ->assertSessionHas('routine-photo-draft', fn (array $draft) => $draft['title'] === 'Piernas');
        }

        Http::assertSentCount(3);
        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_invalid_word_archive_is_rejected_without_creating_a_draft(): void
    {
        $gym = Gym::create(['name' => 'Gym documentos']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        config()->set('services.gemini.key', 'test-key');

        $file = UploadedFile::fake()->createWithContent('rutina.docx', $this->officeZip([
            'unrelated.xml' => '<data/>',
        ]));

        $this->actingAs($coach)->post(route('routines.photo.store'), [
            'photo' => $file, 'photo_processing_consent' => '1',
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('routines', 0);
    }

    /** @param array<string, string> $entries */
    private function officeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'routine-test-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }

}
