# VisionFit · experiencia de personaje

## Objetivo aprobado

Probar una interfaz futurista con un personaje 2D, mapa muscular por ejercicio y progreso con niveles y logros. El usuario autorizó implementarlo en `feature/avatar-gamificacion` y publicarlo exclusivamente en el entorno de pruebas para evaluarlo desde su celular.

## Alcance

- Inicio con resumen de actividad, personaje, siguiente entrenamiento y accesos a rutina, progreso y nutrición.
- Ficha de personaje con nivel, experiencia, logros, historial real y personalización local por cuenta: figura genérica, masculina o femenina; colores cosméticos desbloqueables.
- Mapa frontal y posterior propio en SVG, con músculos principales y secundarios cuando exista correspondencia conocida. Los grupos desconocidos no se inventan.
- El mapa del entrenamiento usa el ejercicio realmente seleccionado, incluidas las alternativas temporales.
- Demo pública sin sesión con datos ficticios identificados; la experiencia con datos reales sigue requiriendo sesión de cliente.
- Cambios detrás de `VISIONFIT_EXPERIENCE_ENABLED`, desactivado por defecto y activado solo en preview. Sin cambios de esquema ni copia de datos de producción.

## Reglas de progreso

- 100 XP por fecha local con al menos una serie válida propia. Varias series, más peso, repeticiones adicionales, repetir una sesión o editar una serie no aumentan esa recompensa diaria.
- Nivel = 1 + floor(XP / 500). El progreso del nivel es XP módulo 500.
- Excluir registros futuros y series sin repeticiones positivas o con cargas negativas. Carga nula válida para peso corporal.
- El ejercicio real guardado en el log prevalece; los registros anteriores sin ese campo usan el ejercicio original de su rutina.
- Mostrar días activos, semanas activas, series registradas, músculos trabajados y marcas registradas como datos; no estimar ganancia muscular, recuperación ni habilidades físicas.
- Logros de 1, 5 y 20 días de actividad, 4 semanas distintas activas y 5 grupos musculares. No penalizar días de descanso.
- Personalización local limitada a opciones permitidas y desbloqueos calculados por el servidor; no guardar sexo nuevo, fotografías ni reconocimiento facial.

## Diseño e interacción

Fondo azul casi negro, superficies mates, tipografía legible y acentos cian/lima. Personaje como centro visual con mapa de colores y selector frente/espalda. Controles de 44 px mínimo, foco visible, navegación por teclado y adaptación a movimiento reducido. Evitar dependencias nuevas, audio automático y animaciones continuas pesadas.

La pantalla de series conserva sus campos y cronómetro; el mapa es desplegable. La experiencia debe servir en 360 px de ancho sin desplazamiento horizontal. La demo permite cambiar vista, avatar y ejercicio, y simular completar una serie sin escribir en la base.

## Contratos entre componentes

`App\Support\MuscleMap::forExercise(Exercise $exercise): array` devuelve `primary`, `secondary`, `label`, `available`. IDs: chest, back, shoulders, biceps, triceps, forearms, abs, glutes, quadriceps, hamstrings, calves.

`MuscleMap::labels(): array` devuelve ID => nombre español.

`App\Services\TrainingExperience::forClient(User $client): array` devuelve `xp`, `level`, `level_xp`, `next_level_xp` (500), `level_percent`, `active_days`, `active_weeks`, `total_sets`, `today_sets`, `week_days`, `muscles` (lista de ID), `muscle_activity` (lista: id, label, sets), `achievements` (lista: id, title, description, unlocked, current, target), `recent_exercises` (lista: id, title, weight nullable, reps, logged_at).

Las vistas compartidas reciben `$experience`, `$isDemo`, `$displayName`, `$trainingUrl`, `$progressUrl`, `$nutritionUrl`, `$characterUrl`, `$avatarStorageKey`, `$workoutTitle`, `$workoutSubtitle`, `$initialMuscleMap`, `$demoExercises`.

## Verificación y entrega

Pruebas de aislamiento entre clientes, deduplicación diaria, límites de fecha y pesos, registro real de alternativas, grupos desconocidos, estado vacío, feature flag y demo sin datos reales. Pruebas JS de personalización y simulación. Revisar visualmente en navegador y ejecutar la suite existente. PR en borrador y URL de preview; no fusionar a main ni cambiar producción sin nueva aprobación.
