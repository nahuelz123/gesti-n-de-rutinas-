# VisionFit Commercial Site Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar el sitio comercial público y la administración de consultas y suscripciones conectada con la app.
**Architecture:** Laravel/Blade y Filament existentes, catálogo configurable y formularios persistentes. Checkout externo de Mercado Pago y confirmación de pagos en servidor; ninguna tarjeta ni autorización se simula.
**Tech Stack:** PHP 8.2+, Laravel, Blade, CSS/JS locales, MySQL/SQLite, PHPUnit y Node.
**Spec:** docs/superpowers/specs/2026-10-23-commercial-site-design.md

## Global Constraints
- Mantener rojo, negro y blanco; sin dependencias nuevas ni datos comerciales inventados.
- No instalar publicidad, analítica ni píxeles.
- Prueba software de 14 días sin tarjeta; gracia de 7 días tras vencer el período pagado.
- Preservar cuentas y acceso de gimnasios existentes.
- No activar checkout sin datos legales y credenciales reales.

## Review Focus
- Un formulario público no debe exponer solicitudes ajenas ni aceptar roles o estados del navegador.
- Un comprador no debe modificar otra suscripción ni trasladarse de gimnasio silenciosamente.
- Un webhook repetido, falso o con monto incorrecto no debe habilitar períodos.
- Un proveedor caído no debe producir un falso pago o una falsa cancelación.
- Las cuentas, pagos y datos privados no deben aparecer en el sitemap ni indexarse.

### Task 1: Sitio y catálogo
**Files:** config/commerce.php, app/Support/CommercialCatalog.php, app/Http/Controllers/MarketingController.php, resources/views/marketing/*, public/css/visionfit/marketing.css, routes/web.php; tests/Feature/MarketingSiteTest.php.
**Interfaces:** catálogo plans(): array y plan(string): array; MarketingController páginas públicas consume catálogo y produce HTML/SEO.
- [ ] RED: GET / es 200 con título y acceso; páginas de audiencias y planes muestran precios y enlaces válidos; sitemap excluye cuentas; canonical elimina query.
- [ ] GREEN: implementar catálogo, contenido y diseño responsive, metadatos, sitemap y robots.
- [ ] Verificar MarketingSiteTest y crear commit.

### Task 2: Consultas y privacidad
**Files:** commercial_inquiries migration/model; CommercialInquiryController; CommercialAdminController; vistas de formulario, confirmación y administración; políticas existentes; tests/Feature/CommercialInquiriesTest.php.
**Interfaces:** POST /contacto, /baja, /arrepentimiento y /privacidad/solicitud guardan solicitud y muestran referencia; solo super_admin ve el listado.
- [ ] RED: consentimiento obligatorio, mensajes escapados, referencia creada, honeypot no guarda, consumidores sin login y otro rol obtiene403.
- [ ] GREEN: persistencia, validación, rate limit, panel con estados y actualización de textos legales.
- [ ] Verificar pruebas específicas y crear commit.

### Task 3: Suscripciones y cobro
**Files:** migraciones y modelos Subscription/SubscriptionPayment; MembershipService; MercadoPagoSubscriptions; controladores de alta, facturación y webhook; vistas y enlaces; tests/Feature/CommercialSubscriptionsTest.php.
**Interfaces:** POST /empezar/{plan} crea prueba para software; GET /mi-suscripcion; POST checkout/cancel; POST /webhooks/mercadopago verifica firma y recurso del proveedor.
- [ ] RED: prueba gratuita crea espacio aislado, no cambia cuentas existentes; autorización de propietario; sin configuración no cobra; pagos verificados habilitan una sola vez, rechazos o importes incorrectos no; cancelación respeta vigencia y error del proveedor.
- [ ] GREEN: servicios y vistas; acceso/cupos solo para nuevas membresías administradas, sin suspender clientes legados; documentación de variables y comandos.
- [ ] Verificar tests específicos y suite completa, crear commit.

### Task 4: Revisión y entrega
**Files:** docs/commercial-setup.md, prueba y fuentes de QA.
- [ ] Ejecutar PHPUnit completo, JS, build, Pint de cambios, caché de vistas y revisión de enlaces internos.
- [ ] QA responsive con navegador si el preview soporta Laravel; registrar limitaciones sin inventar validación visual.
- [ ] Una revisión independiente completa; corregir hallazgos importantes con regresiones.
- [ ] Subir PR por GitHub, esperar CI, integrar en main y verificar despliegue y páginas públicas. Identificar datos externos pendientes con precisión.
