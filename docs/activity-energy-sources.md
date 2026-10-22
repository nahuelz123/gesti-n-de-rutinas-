# Registro de actividad y gasto estimado

Los valores provienen del **Compendio de Actividades Físicas para adultos 2024**. Consulta: 22 de octubre de 2026.

- [Definición de MET y condiciones de uso comercial](https://pacompendium.com/): un MET estándar equivale aproximadamente a 1 kcal/kg/hora; uso comercial permitido con atribución, sin cambiar los valores MET.
- [Acondicionamiento](https://pacompendium.com/conditioning-exercise/): elíptico, fuerza, remo, escaladora y soga.
- [Bicicleta](https://pacompendium.com/bicycling/), [caminata](https://pacompendium.com/walking/) y [carrera](https://pacompendium.com/running/).

`ActivityCatalog` conserva cada código y MET sin mezclarlos. Las opciones distinguen el esfuerzo o ritmo; los valores no se interpolan a partir del peso levantado.

## Cálculo

Se presentan **calorías activas netas**: `(MET - 1) × peso corporal en kg × duración en horas`. Restar un MET descuenta el gasto de reposo del mismo intervalo. El número no representa el gasto energético total diario, un déficit calórico ni un objetivo alimentario. Es una estimación poblacional, no una medición individual. La aplicación no modifica las metas de alimentación.

Cada actividad guardada conserva duración, peso corporal, MET, código de actividad, categoría y versión `compendium2024-net-v1`. Un cambio posterior de peso no reescribe las actividades guardadas a mano. Se redondea al presentar, tras sumar. Sin actividades ni series registradas se muestra ausencia de datos.

## Pasos adicionales

`walk_steps` usa la caminata de referencia del código 17170 (3 MET, sin modificar el valor del Compendio). Convierte los pasos a minutos mediante `ceil(pasos / 100)`, con un rango de 1 a 60.000 pasos. La referencia de 100 pasos por minuto es una heurística poblacional de intensidad moderada, estudiada por [Tudor-Locke et al., CADENCE-Adults, 2019](https://pmc.ncbi.nlm.nih.gov/articles/PMC6337834/). No permite inferir el ritmo real de una persona ni convierte el total diario en tiempo medido. La pantalla declara este supuesto y ofrece elegir caminata por minutos cuando se conoce su duración y ritmo.

Estos registros conservan `step_count` y la versión `steps100-compendium2024-net-v1`. Sus calorías ya forman parte del subtotal de cardio. La persona debe confirmar que los pasos excluyen las caminatas y corridas que ya cargó: la app no puede detectar el solapamiento sin datos de intervalos. No se debe cargar de nuevo el total del podómetro si ya se registraron partes de ese recorrido.

## Fuerza desde las series

`AutomaticStrengthEnergy` genera una estimación de lectura, sin insertar actividades. Usa las series válidas del cliente dentro de la semana consultada; descarta registros futuros, negativos, sin repeticiones válidas y duplicados de la misma serie/fecha. Excluye los ejercicios realmente realizados cuyo grupo es cardio (incluidos reemplazos), para no convertirlos en fuerza. Conserva el historial de asignaciones archivadas. Separa los días de rutina por asignación para no inventar descanso entre sesiones.

El tiempo **modelado, no medido** suma 3 segundos por repetición y el descanso de la rutina entre las series registradas, excluyendo el último descanso de cada día de rutina. Usa 90 segundos cuando no hay un descanso interpretable, respeta el cero explícito y limita cada pausa a 5 minutos. El cronómetro conserva su comportamiento previo de 90 segundos como valor inicial cuando el descanso es cero o falta. Redondea el total diario hacia arriba al minuto, con tope de 600 minutos. Son supuestos de producto para ofrecer una aproximación; el Compendio no valida esta conversión de series a duración. Aplica luego 3,5 MET (02054) y el último peso corporal válido registrado hasta la fecha de actividad. Los kilos levantados no se convierten directamente a calorías.

Sin peso corporal disponible para esa fecha, las series se muestran pendientes de cálculo. No se usa una medición futura para completar el pasado. Corregir las series o las mediciones puede cambiar esta estimación automática, a diferencia de los registros guardados a mano.

La fuerza también puede cargarse manualmente por el **tiempo total del día**, con sus descansos habituales, excluyendo cardio y pausas largas. Cuando existe fuerza manual para una fecha, su suma reemplaza toda la estimación automática de ese día; nunca se suman ambas. Eliminar la última actividad manual de fuerza vuelve a mostrar el modelo automático. Los totales diarios/semanales aplican la misma regla.

## Feedback de progreso

`ProgressFeedback` muestra días y series registrados en los últimos 7 días y busca comparaciones en los últimos 28. Compara la mejor serie de las dos fechas más recientes para el mismo ejercicio realmente realizado, respetando reemplazos y sin mezclar variantes. Solo presenta como mejora comparable más repeticiones con la misma carga o más carga con las mismas repeticiones. No diagnostica técnica, fatiga ni composición corporal; no interpreta bajar de peso como algo bueno para todos los objetivos. El feedback es determinista y no hace llamadas a IA.

Los datos están asociados al cliente autenticado, sin acceso cruzado entre cuentas. El token del formulario evita duplicar una misma entrega por doble clic o reintento. Hay edición y borrado lógico; la demo no escribe registros.
