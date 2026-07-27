<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\PexelsClient;
use Illuminate\Console\Command;

class FetchRecipePhotos extends Command
{
    protected $signature = 'recipes:fetch-photos {--all : Reemplaza incluso las que ya tienen foto}';

    protected $description = 'Busca en Pexels una foto real para cada receta según su nombre y la guarda en photo_url';

    /**
     * Mapeo título en español -> término de búsqueda en inglés (da resultados mucho mejores en Pexels).
     */
    private array $searchTerms = [
        'Avena con banana y miel' => 'oatmeal banana honey bowl breakfast',
        'Tostadas con palta y huevo' => 'avocado toast with egg',
        'Yogur con granola y frutos rojos' => 'yogurt granola berries bowl',
        'Pollo grillado con arroz y vegetales' => 'grilled chicken rice vegetables plate',
        'Carne magra con batata y ensalada' => 'steak sweet potato salad plate',
        'Ensalada de atún y garbanzos' => 'tuna chickpea salad bowl',
        'Licuado de banana y avena' => 'banana oat smoothie glass',
        'Tostado de jamón y queso light' => 'ham cheese toasted sandwich',
        'Salmón al horno con puré de calabaza' => 'baked salmon pumpkin puree plate',
        'Tortilla de vegetales' => 'vegetable omelette plate',
        'Pollo al curry con vegetales salteados' => 'chicken curry stir fry vegetables',
        'Batido pre-entreno de banana y café' => 'banana coffee smoothie glass',
        'Batido post-entreno de proteína y frutos rojos' => 'protein berry smoothie glass',
    ];

    public function handle(PexelsClient $pexels): int
    {
        $query = Recipe::query();

        if (! $this->option('all')) {
            $query->whereNull('photo_url');
        }

        $recipes = $query->get();

        if ($recipes->isEmpty()) {
            $this->info('No hay recetas para actualizar (usá --all para reemplazar todas).');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($recipes->count());
        $bar->start();

        $updated = 0;
        $failed = [];

        foreach ($recipes as $recipe) {
            $searchTerm = $this->searchTerms[$recipe->title] ?? $recipe->title;

            $photoUrl = $pexels->searchPhoto($searchTerm);

            if ($photoUrl) {
                $recipe->photo_url = $photoUrl;
                $recipe->save();
                $updated++;
            } else {
                $failed[] = $recipe->title;
            }

            $bar->advance();
            usleep(300000); // 0.3s entre pedidos, para no pasarnos del rate limit gratis de Pexels
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo. Actualizadas: {$updated}");

        if (! empty($failed)) {
            $this->warn('No se pudo conseguir foto para: '.implode(', ', $failed));
        }

        return self::SUCCESS;
    }
}
