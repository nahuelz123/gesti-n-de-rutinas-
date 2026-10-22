# Registro de actividad y gasto estimado

Los valores provienen del **Compendio de Actividades Físicas para adultos 2024**. Consulta: 20 de octubre de 2026.

- [Definición de MET y condiciones de uso comercial](https://pacompendium.com/): un MET estándar equivale aproximadamente a 1 kcal/kg/hora; uso comercial permitido con atribución, sin cambiar los valores MET.
- [Acondicionamiento](https://pacompendium.com/conditioning-exercise/): elíptico, fuerza, remo, escaladora y soga.
- [Bicicleta](https://pacompendium.com/bicycling/), [caminata](https://pacompendium.com/walking/) y [carrera](https://pacompendium.com/running/).

`ActivityCatalog` conserva cada código y MET sin mezclarlos. Las opciones distinguen el esfuerzo o ritmo; los valores no se interpolan a partir del peso levantado.

## Cálculo

Se presentan **calorías activas netas**: `(MET - 1) × peso corporal en kg × duración en horas`. Restar un MET descuenta el gasto de reposo del mismo intervalo. El número no representa el gasto energético total diario, un déficit calórico ni un objetivo alimentario. Es una estimación poblacional, no una medición individual. La aplicación no modifica las metas de alimentación.

Cada registro conserva duración, peso corporal, MET, código de actividad, categoría y versión `compendium2024-net-v1`. Los totales históricos suman esas estimaciones guardadas; un cambio posterior de peso no reescribe el pasado. Se redondea al presentar, tras sumar. Sin registro se muestra ausencia de datos, no actividad inferida.

La fuerza se carga por duración de sesión con sus descansos habituales, excluyendo cardio y pausas largas. Las series ya guardadas **no suman calorías por separado**, porque sus tiempos no miden la duración real de la sesión. El cardio se registra por separado para no sumarlo otra vez dentro de fuerza.

Los datos están asociados al cliente autenticado, sin acceso cruzado entre cuentas. El token del formulario evita duplicar una misma entrega por doble clic o reintento. Hay edición y borrado lógico; la demo no escribe registros.
