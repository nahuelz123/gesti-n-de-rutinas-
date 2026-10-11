<?php

namespace Tests\Feature;

use App\Models\FreeMealLog;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MealPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('almuerzo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='
        ));
    }

    public function test_photo_generates_reviewable_estimate_and_saves_only_after_confirmation(): void
    {
        $gym = Gym::create(['name' => 'Gym nutrición']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        config()->set('services.gemini.key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'name' => 'Pollo con arroz', 'quantity_grams' => 350, 'calories' => 520,
                'protein' => 42, 'carbs' => 54, 'fat' => 12, 'note' => 'Aceite no visible',
                'included_foods' => 'Pollo servido en el plato y arroz, sin bebida',
            ])]]]]],
        ])]);

        $this->actingAs($client)->post(route('client.nutrition.photo.analyze'), [
            'photo' => $this->photo(), 'processing_consent' => '1',
            'meal_scope' => 'Comí el pollo y el arroz del plato, no la bebida',
        ])->assertRedirect(route('client.nutrition.photo.show'))
            ->assertSessionHas('meal-photo-estimate', fn (array $data) => $data['calories'] === 520.0
                && $data['included_foods'] === 'Pollo servido en el plato y arroz, sin bebida');

        Http::assertSent(fn ($request) => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            'Comí el pollo y el arroz del plato, no la bebida'
        ));
        $this->actingAs($client)->get(route('client.nutrition.photo.show'))
            ->assertSee('Pollo servido en el plato y arroz, sin bebida');

        $this->assertDatabaseCount('free_meal_logs', 0);

        $this->actingAs($client)->post(route('client.nutrition.photo.confirm'), [
            'name' => 'Pollo con arroz', 'quantity_grams' => 400, 'calories' => 600,
            'protein' => 45, 'carbs' => 60, 'fat' => 14,
        ])->assertRedirect(route('client.nutrition.index'));

        $this->assertDatabaseHas('free_meal_logs', [
            'client_id' => $client->id, 'custom_name' => 'Pollo con arroz',
            'quantity_grams' => 400, 'calories' => 600,
        ]);
    }

    public function test_photo_requires_consent_and_cannot_be_confirmed_without_an_estimate(): void
    {
        $gym = Gym::create(['name' => 'Gym nutrición']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $this->actingAs($client)->post(route('client.nutrition.photo.analyze'), [
            'photo' => $this->photo(),
        ])->assertSessionHasErrors('processing_consent');

        $this->actingAs($client)->post(route('client.nutrition.photo.confirm'), [
            'name' => 'Comida', 'quantity_grams' => 300, 'calories' => 300,
            'protein' => 10, 'carbs' => 30, 'fat' => 5,
        ])->assertStatus(419);

        $this->assertSame(0, FreeMealLog::count());
        Http::assertNothingSent();
    }

    public function test_an_existing_photo_estimate_still_opens_after_the_new_breakdown_is_added(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client)->withSession(['meal-photo-estimate' => [
            'name' => 'Pizza de muzzarella', 'note' => '', 'quantity_grams' => 400,
            'calories' => 1040, 'protein' => 44, 'carbs' => 132, 'fat' => 40,
        ]])->get(route('client.nutrition.photo.show'))
            ->assertOk()->assertSee('Pizza de muzzarella');
    }
}
