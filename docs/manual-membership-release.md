# Entrega: membresías con cobro manual

VisionFit permite comenzar con cobros por transferencia, efectivo u otro medio acordado y administrar la vigencia dentro de la app. Registrar un movimiento no ejecuta una operación bancaria.

## Acceso y uso

- `/gestion-comercial/configuracion`: identidad real del servicio, contacto, instrucciones de pago y espacio de asesorías. Solo superadministrador; sin credenciales privadas.
- `/gestion-comercial/membresias`: altas, períodos, cambios al próximo ciclo, bajas/reanudaciones, cobros confirmados, reintegros ya realizados, referencia fiscal e historial. Filtros y CSV privados.
- `/mi-suscripcion`: instrucciones, plan, vigencia, cancelación e historial del titular. Los comprobantes son internos e imprimibles, no facturas fiscales.

Los seis planes y sus cupos se conservan. Los gimnasios anteriores mantienen su acceso hasta asignarles un plan y confirmar su primer pago. La prueba nueva no genera débitos automáticos. Para operar, completar primero los datos reales, cobrar y verificar el ingreso; recién entonces registrar el cobro.

## Verificación y revisión

- 390 pruebas PHP, 1861 aserciones; 44 pruebas JavaScript. Compilación Vite, Pint de los cambios, compilación de vistas y verificación del diff satisfactorias.
- Revisión independiente completa del cambio, sin hallazgos críticos ni menores pendientes. Tres hallazgos importantes corregidos con regresiones que fallaron primero y luego pasaron: orden común de bloqueo para cambios de cupo; programación del siguiente plan después de comenzar un período prepagado; compatibilidad de versiones UTC del proveedor con registros anteriores.
- SQLite verifica el orden/intención de los bloqueos y los escenarios funcionales. No se ejecutó una prueba concurrente de varias conexiones MySQL/PostgreSQL.
- Sin pruebas bancarias, reintegros, envíos de correo ni datos financieros ficticios en producción. No se certifica apariencia visual o impresión desde un navegador.

## Decisiones y sus límites

1. Cambios de plan en la próxima renovación, sin prorrateos: evita diferencias proporcionales durante el inicio manual; no permite cobrar una ampliación inmediata a mitad del ciclo.
2. Acciones HTTP de cobros y reintegros implementadas junto a sus servicios para verificar permisos reales desde el comienzo: adelantó ese trabajo respecto del panel; no cambió la funcionalidad acordada.
3. Un plan futuro prepagado tiene fecha de inicio y cada pago conserva su plan: evita adelantar beneficios; requiere conservar ese estado adicional. Los beneficios se aplican por fecha sin depender de una tarea programada.
4. El titular puede cancelar su continuidad con auditoría: conserva la baja desde su cuenta; solo el superadministrador registra dinero y configuración.
5. La referencia de una factura externa puede anotarse o corregirse después: deja historial de la modificación; el importe y período originales no se editan.
6. Cuentas bancarias, activación del vendedor y correo real dependen del operador: se verifica la publicación por GitHub/HTTP sin autenticar Railway; el operador debe completar y comprobar sus propias cuentas antes de prometer cobros o entregas automáticas.
7. Se respeta la restricción del navegador y se verifica contenido HTTP, código y pruebas: pueden quedar detalles visuales o de impresión que esas verificaciones no detectan.
8. Comprobantes internos y políticas no sustituyen facturación fiscal ni revisión de la operación real: el operador emite la documentación y completa su identidad; no se inventan datos legales.
9. Prorrateos, recordatorios automáticos y cambios manuales sobre suscripciones del proveedor quedan fuera del lanzamiento manual: las renovaciones necesitan seguimiento del operador y las gestiones del proveedor se coordinan por su circuito.

Menores diferidos por la revisión: ninguno.

El procedimiento detallado está en [commercial-setup.md](commercial-setup.md).

Actualización posterior: avisos de vencimiento y resumen diario configurable amplían el alcance inicial. Ver commercial-setup.md; entrega real requiere configuración del operador.
