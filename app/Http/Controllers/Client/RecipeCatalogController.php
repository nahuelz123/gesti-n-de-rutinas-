<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class RecipeCatalogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $mealType = $request->query('meal_type');
        $sort = $request->query('sort', 'recientes');

        $query = Recipe::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('is_global', true)
                    ->orWhereHas('creator', fn (Builder $cq) => $cq->where('gym_id', $user->gym_id));
            });

        if ($mealType) {
            $query->where('meal_type', $mealType);
        }

        match ($sort) {
            'proteina' => $query->orderByDesc('protein'),
            'calorias' => $query->orderBy('calories'),
            default => $query->latest(),
        };

        $recipes = $query->paginate(12)->withQueryString();

        return view('client.recipes.index', [
            'recipes' => $recipes,
            'mealType' => $mealType,
            'sort' => $sort,
            'mealTypeLabels' => NutritionCalculator::$mealTypeLabels,
        ]);
    }

    public function show(Request $request, Recipe $recipe)
    {
        $user = $request->user();

        $canView = $recipe->is_global || $recipe->creator?->gym_id === $user->gym_id;

        abort_unless($canView, 403);

        $recipe->load(['ingredients', 'instructions']);

        return view('client.recipes.show', [
            'recipe' => $recipe,
        ]);
    }
}
