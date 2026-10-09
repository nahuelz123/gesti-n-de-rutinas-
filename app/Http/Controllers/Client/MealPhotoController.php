<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FreeMealLog;
use App\Services\FileAiReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MealPhotoController extends Controller
{
    public function show(Request $request): View
    {
        return view('client.nutrition.photo', ['estimate' => $request->session()->get('meal-photo-estimate')]);
    }

    public function analyze(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'processing_consent' => ['accepted'],
        ]);
        try {
            $photo = $data['photo'];
            $parsed = app(FileAiReader::class)->read(
                'Analizá solo la comida visible. Devolvé JSON en español con name (descripción breve), quantity_grams (gramos aproximados de toda la porción), calories (kcal aproximadas de toda la porción), protein, carbs, fat (gramos aproximados totales) y note (alimentos dudosos, aceite, salsas o ingredientes no visibles). No inventes precisión; si no se ve comida, devolvé {"error":"No se reconoce comida"}.',
                $photo->get(), $photo->getMimeType()
            );
            if (! is_array($parsed) || isset($parsed['error'])) {
                throw new RuntimeException('No pudimos reconocer una comida. Probá con otra foto.');
            }
            $estimate = [
                'name' => mb_substr(trim((string) ($parsed['name'] ?? '')), 0, 120),
                'note' => mb_substr(trim((string) ($parsed['note'] ?? '')), 0, 300),
            ];
            foreach (['quantity_grams', 'calories', 'protein', 'carbs', 'fat'] as $field) {
                $value = $parsed[$field] ?? null;
                if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > 5000) {
                    throw new RuntimeException('La estimación vino incompleta. Probá con otra foto.');
                }
                $estimate[$field] = round((float) $value, 1);
            }
            if ($estimate['name'] === '' || $estimate['quantity_grams'] < 1 || $estimate['calories'] < 1) {
                throw new RuntimeException('La estimación vino incompleta. Probá con otra foto.');
            }

            $request->user()->consents()->create([
                'scope' => 'meal_photo_analysis',
                'version' => config('legal.versions.meal_photo_analysis'),
                'granted_at' => now(),
            ]);
            $request->session()->put('meal-photo-estimate', $estimate);

            return redirect()->route('client.nutrition.photo.show');
        } catch (RuntimeException $error) {
            return back()->withErrors(['photo' => $error->getMessage()]);
        }
    }

    public function confirm(Request $request): RedirectResponse
    {
        abort_unless($request->session()->has('meal-photo-estimate'), 419);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'quantity_grams' => ['required', 'numeric', 'min:1', 'max:5000'],
            'calories' => ['required', 'numeric', 'min:1', 'max:5000'],
            'protein' => ['required', 'numeric', 'min:0', 'max:500'],
            'carbs' => ['required', 'numeric', 'min:0', 'max:500'],
            'fat' => ['required', 'numeric', 'min:0', 'max:500'],
        ]);

        FreeMealLog::create([
            'client_id' => $request->user()->id,
            'food_item_id' => null,
            'custom_name' => $data['name'],
            'quantity_grams' => $data['quantity_grams'],
            'calories' => $data['calories'],
            'protein' => $data['protein'],
            'carbs' => $data['carbs'],
            'fat' => $data['fat'],
            'logged_date' => today(),
            'logged_at' => now(),
        ]);
        $request->session()->forget('meal-photo-estimate');

        return redirect()->route('client.nutrition.index')->with('success', 'Comida registrada. Las calorías y macros son estimaciones.');
    }
}
