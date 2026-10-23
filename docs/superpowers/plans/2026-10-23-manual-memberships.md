# Manual Membership Operations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar una operación comercial manual completa, privada y auditable que permita empezar a vender VisionFit sin credenciales de cobro automático.
**Architecture:** Extender los modelos comerciales existentes con procedencia del cobro, configuración no secreta, bitácora y reintegros. Servicios separados para movimientos y planes, controladores protegidos y vistas Blade; Mercado Pago conserva su flujo verificado.
**Tech Stack:** Laravel/PHP 8.2+, Blade, SQL, PHPUnit, CSS local.
**Spec:** docs/superpowers/specs/2026-10-23-manual-memberships-design.md

## Global Constraints
- Seis planes ARS existentes; prueba 14 días y gracia 7 días. Meses completos 1–12; sin prorrateo.
- Solo super_admin registra dinero o configura la operación. No ejecutar movimientos bancarios reales.
- Propietario ve sus comprobantes; otros miembros del espacio no ven sus datos financieros.
- No convertir suscripciones de proveedor ni imponer cobro a espacios anteriores silenciosamente.
- Sin credenciales nuevas en DB/Git, sin dependencias nuevas y publicación por GitHub sin autenticar Railway.

## Review Focus
- Reenvío con UUID duplicado y distinto contenido no registra dos períodos ni cambia el primero.
- Reintegros simultáneos o repetidos no superan el importe ni habilitan huecos futuros.
- Una baja no renueva al volver a cobrar sin reanudar; períodos pagados conservan su acceso.
- Un cambio a menor cupo no puede eludirse con altas, reactivaciones o cambios de rol.
- Configuración y exportación no exponen secretos, datos ajenos ni fórmulas ejecutables.

### Task 1: Configuración y esquema operativo
**Files:** migration 2026_10_23_000003, CommercialSettings model/middleware/controller/view, CommercialOperation and CommercialRefund models, bootstrap/app.php, routes/web.php; tests/Feature/CommercialConfigurationTest.php.
**Interfaces:** CommercialSettings::values(): array y saveValues(array): void; CommercialConfiguration aplica solo claves permitidas a config del request. Nuevas columnas de suscripción billing_mode y próximo plan; pagos con UUID público, source, método, referencia, fecha, reintegrado y referencia fiscal. Bitácora result/request_hash y reintegros persistentes.
- [ ] RED: usuario normal no configura; super_admin guarda datos y las políticas/instrucciones los consumen escapados; claves de tokens enviadas no se guardan. Config vacía conserva valores del entorno.
- [ ] GREEN: esquema aditivo, datos existentes con provider_id quedan mercadopago, resto manual; configuración editable sin nuevas credenciales.
- [ ] Verificar `php vendor/bin/phpunit tests/Feature/CommercialConfigurationTest.php`; Expected: PASS. Commit.

### Task 2: Cobros, reintegros y trazabilidad
**Files:** CommercialOperations, ManualMembershipPayments, CommercialPayment/Subscription models; tests/Feature/ManualMembershipPaymentsTest.php.
**Interfaces:** CommercialOperations::execute(CommercialSubscription, User, string $id, string $action, array $payload, Closure): CommercialOperation; ManualMembershipPayments::record(CommercialSubscription, User, array): CommercialPayment y refund(CommercialPayment, User, array): CommercialRefund.
- [ ] RED: cobro de 14900 ARS el 31/01 cubre hasta 28/02; renovación conserva el período futuro; 0, negativo, monto incorrecto, no confirmación y 13 meses se rechazan. UUID repetido devuelve el mismo registro; contenido diferente y transferencia repetida se rechazan. Reintegros parciales y totales, límite acumulado, propiedad y exclusión de proveedor; hueco antes de un período futuro no concede acceso.
- [ ] GREEN: centavos exactos, transacción/bloqueos, bitácora, referencia única, períodos independientes y protección de registros originales.
- [ ] Verificar `php vendor/bin/phpunit tests/Feature/ManualMembershipPaymentsTest.php tests/Feature/CommercialSubscriptionsTest.php tests/Feature/CommercialReviewRegressionsTest.php`; Expected: PASS. Commit.

### Task 3: Altas y cambios de plan manuales
**Files:** ManualMembershipPlans, CommercialMemberWriter, User/Gym deletion guards, MercadoPagoSubscriptions; tests/Feature/ManualMembershipPlansTest.php.
**Interfaces:** ManualMembershipPlans::assign(User $owner, User $actor, string $plan, string $operationId): CommercialSubscription; schedule(CommercialSubscription, User, array): void; cancel/resume(CommercialSubscription, User, array): void. record aplica próximo plan solo en la renovación y toma su precio del catálogo.
- [ ] RED: asignar licencia existente conserva acceso hasta primer cobro; asesoría solo para cliente del espacio configurado; duplicados rechazados. Cambio software/coaching cruzado y cupos insuficientes rechazados; cupo pendiente protege altas y se aplica al renovar. Cancelar conserva período; cobrar cancelada exige reanudación. Proveedor no admite operaciones manuales ni checkout sobre período manual vigente. Borrar cuentas con movimientos no elimina su historia.
- [ ] GREEN: servicios de planes, integración con escritor y cancelación del propietario, límites pendientes y bloqueo de borrado accidental.
- [ ] Verificar `php vendor/bin/phpunit tests/Feature/ManualMembershipPlansTest.php tests/Feature/ManualMembershipPaymentsTest.php tests/Feature/CommercialSubscriptionsTest.php`; Expected: PASS. Commit.

### Task 4: Panel comercial, comprobantes y entrega
**Files:** CommercialManagementController, CommercialReceiptController, nuevas vistas marketing/commercial/*, subscription.blade.php, enlaces del panel, CSS y docs/commercial-setup.md; tests/Feature/CommercialManagementTest.php.
**Interfaces:** GET /gestion-comercial/membresias y /{subscription}; POST cobros/reintegros/plan/baja/reanudar/altas; GET /mi-suscripcion/comprobantes/{payment:public_id}; GET exportación CSV privada. Acciones redirigen con resultado; ninguna afirma que transfirió dinero.
- [ ] RED: permisos en cada operación/exportación; recibo solo propietario/root; filtros por nombre/estado/vencimiento y paginación; CSV neutraliza fórmulas. Formularios registran un cobro y muestran nuevo período, devolución y auditoría; cliente ve instrucciones y referencia fiscal sin exponer registros ajenos.
- [ ] GREEN: paneles, acciones, recibos imprimibles, exportación, configuración y manual operativo. Ampliar prepare de despliegue para verificar nuevo esquema.
- [ ] Verificar suite PHP completa, JS, Pint de cambios, vistas y diff; Expected: PASS. Una revisión independiente completa, una pasada de correcciones importantes con regresiones. Publicar PR, esperar CI, integrar y comprobar despliegue/HTTP. No afirmar pruebas visuales si el navegador sigue bloqueado ni cobros reales.
