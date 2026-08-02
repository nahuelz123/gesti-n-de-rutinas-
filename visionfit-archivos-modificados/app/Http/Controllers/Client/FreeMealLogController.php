<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\FreeMealLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FreeMealLogController extends Controller
{
    /**
     * Búsqueda en vivo del catálogo de alimentos (usada por el JS del formulario).
     */
    public function searchFoods(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $user = $request->user();

        $items = FoodItem::query()
            ->where(fn ($q) => $q->where('is_global', true)
                ->orWhereHas('creator', fn ($cq) => $cq->where('gym_id', $user->gym_id)))
            ->when($query !== '', fn ($q) => $q->where('name', 'like', '%'.$query.'%'))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'category', 'calories_per_100g', 'protein_per_100g', 'carbs_per_100g', 'fat_per_100g']);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'food_item_id' => ['nullable', 'integer', 'exists:food_items,id'],
            'custom_name' => ['nullable', 'string', 'max:120', 'required_without:food_item_id'],
            'quantity_grams' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            // Solo se piden si es un alimento "custom" que no está en el catálogo
            'custom_calories_per_100g' => ['required_without:food_item_id', 'nullable', 'numeric', 'min:0', 'max:900'],
            'custom_protein_per_100g' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'custom_carbs_per_100g' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'custom_fat_per_100g' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $grams = (float) $data['quantity_grams'];
        $factor = $grams / 100;

        if (! empty($data['food_item_id'])) {
            $foodItem = FoodItem::query()->findOrFail($data['food_item_id']);
            $macros = $foodItem->macrosFor($grams);

            $log = FreeMealLog::create([
                'client_id' => $user->id,
                'food_item_id' => $foodItem->id,
                'custom_name' => null,
                'quantity_grams' => $grams,
                'calories' => $macros['calories'],
                'protein' => $macros['protein'],
                'carbs' => $macros['carbs'],
                'fat' => $macros['fat'],
                'logged_date' => today(),
                'logged_at' => now(),
            ]);
        } else {
            $log = FreeMealLog::create([
                'client_id' => $user->id,
                'food_item_id' => null,
                'custom_name' => $data['custom_name'],
                'quantity_grams' => $grams,
                'calories' => round(($data['custom_calories_per_100g'] ?? 0) * $factor, 1),
                'protein' => round(($data['custom_protein_per_100g'] ?? 0) * $factor, 1),
                'carbs' => round(($data['custom_carbs_per_100g'] ?? 0) * $factor, 1),
                'fat' => round(($data['custom_fat_per_100g'] ?? 0) * $factor, 1),
                'logged_date' => today(),
                'logged_at' => now(),
            ]);
        }

        return back()->with('success', "Registrado: {$log->displayName()} ({$grams}g).");
    }

    public function destroy(Request $request, FreeMealLog $freeMealLog)
    {
        abort_unless($freeMealLog->client_id === $request->user()->id, 403);

        $freeMealLog->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
