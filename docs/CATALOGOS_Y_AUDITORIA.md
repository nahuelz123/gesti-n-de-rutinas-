# Auditoría e incorporación de catálogos

## Instalación

Después de ejecutar las migraciones, ejecutar `php artisan db:seed` (en una base ya poblada, preferir `php artisan db:seed --class=GlobalCatalogSeeder`, `--class=FoodItemSeeder`, `--class=CuratedFoodSeeder`, `--class=RecipeSeeder` y `--class=SimpleRecipeSeeder` por separado). No ejecutar `migrate:fresh` en producción.

El catálogo incluye 1324 entradas en `database/data/exercises_import.json` (con 6 títulos repetidos), además de los ejercicios base; las imágenes externas del archivo no se cargan automáticamente. Incluye 42 alimentos originales y 56 traducciones seleccionadas con macros, más 13 recetas originales y hasta 12 recetas simples con macros calculados por porción. El total real depende de coincidencias previas y de los alimentos disponibles.

Los 56 alimentos adicionales provienen de una selección de `Open-food-calories` de Tom09s (MIT), archivo `data/Open-food-calories.json`, consultado el 24/09/2026: https://github.com/chpalitom09-bot/Open-food-calories. Se conservaron entradas con macros completos, confianza marcada como `high`, traducción manual clara y energía razonablemente compatible con sus macros. La fuente etiqueta algunas entradas como CIQUAL, pero esto no certifica que cada valor provenga directamente de CIQUAL; los datos son orientativos. La licencia original se conserva en `database/data/OPEN_FOOD_CALORIES_LICENSE.txt`.

## Foto de rutina

Configurar `GEMINI_API_KEY` en el entorno. En **Rutinas → Crear → Cargar rutina desde foto**, subir JPG/PNG/WebP de hasta 8 MB. La imagen se elimina tras procesarla. Gemini devuelve un borrador editable; los ejercicios sin coincidencia exacta quedan sin seleccionar y su nombre leído aparece en Notas. Los valores ilegibles quedan vacíos y bloquean el guardado por validación. El profesor revisa todos los campos y pulsa **Crear**. Para asignar la rutina al alumno, usar **Asignaciones** y elegir el alumno del mismo gimnasio. Nunca se asigna automáticamente desde la foto.

## Hallazgos y próximos pasos

1. Se corrigió el registro de comidas: un usuario podía enviar por ID un alimento privado de otro gimnasio aunque no apareciera en la búsqueda. Revisar otros endpoints que acepten IDs de recursos entre gimnasios.
2. `ExerciseSeeder` y `RecipeSeeder` contienen ejemplos o valores estimados: revisar sus macros, raciones e indicaciones con un profesional antes de tratarlos como pautas nutricionales.
3. El importador anterior `exercises:import` guarda GIF externos con atribución que menciona Gym visual. Validar derechos de redistribución antes de activar esa importación o mostrar las imágenes en producción.
4. El catálogo de ejercicios tiene nombres principalmente en inglés; incorporar alias en español y matching supervisado facilitaría la lectura de fotos manuscritas.
5. Los recursos Filament permiten editar rutinas del gimnasio; si cada profesor debe controlar solo sus rutinas, restringir consultas y políticas también por `coach_id`.
6. Falta ejecutar pruebas automatizadas y migraciones en un entorno con PHP y dependencias: este workspace no incluye PHP ni `vendor/`.
