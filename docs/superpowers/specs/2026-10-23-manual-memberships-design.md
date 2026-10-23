# VisionFit: operación de membresías con cobro manual

El usuario pidió terminar lo pendiente y preguntó si conviene empezar cobrando manualmente. La decisión de implementación es lanzar cobros manuales confirmados por el superadministrador, conservando la integración existente de Mercado Pago para una activación posterior. Continúa autorizado publicar mediante GitHub sin autenticar Railway.

## Alcance y decisiones
- Mantener Laravel/Blade, el catálogo de seis planes en ARS y la web existente. Sin dependencias nuevas.
- Superadministrador: configurar identidad/contacto/instrucciones de transferencia y espacio de asesorías; asignar planes a cuentas existentes; registrar cobros completos y reintegros ya realizados; programar cambios de plan; cancelar o reanudar membresías manuales; consultar movimientos y exportar registros.
- Propietario: ver instrucciones para pagar, estado, vencimiento, historial y comprobantes internos privados. Los alumnos incluidos en una licencia no pagan otra membresía.
- El registro de un cobro no mueve dinero. Debe confirmarse después de comprobar el ingreso. Un reintegro se registra después de realizarlo por fuera de VisionFit. Ningún botón simula una operación bancaria o una factura fiscal.
- Cada cobro cubre 1–12 meses completos al precio guardado. El período empieza al vencer la vigencia pagada si aún es futura, o al confirmar el cobro si venció. Sin pagos parciales de cuotas ni cobros proporcionales en esta etapa.
- Cambios de plan: entran en la próxima renovación, sin alterar lo pagado. Misma clase software/coaching. Un plan menor exige que alumnos y profesionales habilitados entren en su cupo; el cupo pendiente limita altas hasta aplicarse o retirarse.
- Reintegros parciales conservan el período. Un reintegro total retira ese período; otros períodos aprobados conservan sus fechas, sin habilitar huecos entre ellos. Las reglas de prueba de 14 días y gracia de 7 días se conservan.
- Los espacios anteriores permanecen exentos hasta una asignación explícita y su primer cobro; nunca se suspenden ni se cobran por una migración de código.
- Las suscripciones vinculadas a Mercado Pago no admiten cobros manuales, cambios de plan manuales ni reintegros fingidos sobre pagos del proveedor. El checkout voluntario desde modo manual se bloquea mientras exista un período manual futuro pagado para evitar superponer cobros.
- Datos legales e instrucciones se configuran desde el panel. Credenciales privadas de Mercado Pago y SMTP permanecen en el entorno, nunca en formularios, registros ni Git.

## Enfoques considerados
1. Cobrar y llevar todo en planilla: rápido, pero duplica registros y facilita errores de acceso.
2. Cobrar manualmente y gestionar en VisionFit: elegido como decisión de implementación; permite empezar con pocos clientes y deja los vencimientos controlados.
3. Automatizar cobros y fiscalidad de inmediato: requiere cuentas, credenciales y validación externa que no están disponibles. La integración existente se preserva.

## Seguridad y trazabilidad
- Solo super_admin puede escribir operaciones comerciales o configuración. El propietario solo ve sus movimientos; no basta compartir un gimnasio.
- CSRF, throttling, validación de importes exactos en centavos y moneda ARS. Idempotencia por UUID y huella de la solicitud; la misma clave con otro contenido se rechaza.
- Transacciones y bloqueo por suscripción. Referencias de transferencia duplicadas se rechazan. Cobros originales no se editan ni borran; correcciones mediante reintegro y nuevo registro.
- Bitácora con actor, acción, referencia y resultado. Reintegros acumulados nunca superan el cobro.
- Evitar borrados accidentales de cuentas/espacios con historial comercial. Las solicitudes de privacidad requieren revisión y tratamiento de datos por el titular.
- HTML escapado y CSV protegido contra fórmulas; cuentas, comprobantes y registros con noindex y cache privado/no-store.

## Verificación y límites
Pruebas de permisos, repetición, importes erróneos, cupos, fechas de fin de mes, huecos por reintegros, compatibilidad con Mercado Pago y cuentas anteriores. Suite completa, JS, vistas, revisión independiente y publicación verificable por GitHub/HTTP. Sin cobros, reintegros ni envíos reales de prueba en producción. La emisión fiscal queda en ARCA y se puede anotar su referencia; no se afirma cumplimiento jurídico integral ni ausencia absoluta de incidentes.
