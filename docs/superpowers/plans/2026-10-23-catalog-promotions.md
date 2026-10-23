# Catálogo y promociones Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Editar precios y una promoción desde el panel raíz y mejorar la lectura de títulos.

**Architecture:** `CommercialPricing` deriva productos y promociones de la configuración comercial y calcula centavos. `CommercialCatalogWriter` guarda exclusivamente el catálogo bajo bloqueo/versionado y audita. Controlador y vistas sirven el formulario privado, los precios públicos y el interruptor.

**Tech Stack:** Laravel, Blade, MySQL/SQLite de pruebas, CSS existente; sin dependencias nuevas.

**Spec:** `docs/superpowers/specs/2026-10-23-catalog-promotions-design.md`

## Global Constraints

- Solo `super_admin`; CSRF y throttle en escrituras. ARS positivos hasta 1.000.000; porcentaje entero 1–99.
- Promoción desactivada inicialmente; respeta alcance; estado deseado explícito; conservar acuerdos y cobros existentes.
- No modificar datos comerciales reales para verificar. GitHub despliega sin autenticación Railway; sin navegador por la restricción previa.

## Review Focus

- Formularios obsoletos y clic duplicado no revierten un descuento ni pisan ediciones recientes.
- Importes muy pequeños, decimales y redondeo no muestran ni cobran un precio diferente.
- Entrada anidada desconocida, texto HTML y roles ajenos no publican datos indebidos.
- Cambios del catálogo no modifican el importe histórico, el próximo período ya acordado ni las credenciales de cobro.
- Alcances parciales, rutina puntual y navegación privada no anuncian descuentos inexistentes.

### Task 1: Catálogo editable y promoción coherente

**Files:** Crear `app/Services/CommercialPricing.php`, `app/Services/CommercialCatalogWriter.php`, `app/Http/Controllers/CommercialCatalogController.php`, `resources/views/marketing/commercial/pricing.blade.php`, `resources/views/marketing/promotion.blade.php`, `resources/views/marketing/one-off-routine.blade.php`, `tests/Feature/CommercialPricingTest.php`. Modificar catálogo, importes, controladores de altas/acuerdos, rutas y vistas de precios/gestión.

**Interfaces:** Consumes `CommercialSettings::values()` y singleton 1; `CommercialOperation` para auditoría. Produces `CommercialPricing::items(): array`, `promotion(): array`, `version(): int`; cada producto tiene `base_price_cents`, `price_cents`, `discount_percent`. `CommercialCatalog::plans()` conserva sus campos; agrega `oneOffRoutine()` y `promotion()`. `CommercialAmounts::format(int $cents): string` presenta ARS. `CommercialCatalogWriter::save(User $actor, array $data): void` y `setEnabled(User $actor, bool $enabled, int $version): void` validan, bloquean y auditan.

- [ ] Escribir pruebas HTTP reales: permisos de tres roles/huéspedes; guardar siete precios y reflejo inmediato público; promoción 10 % solo en entrenador (20.000,50 → 18.000,45), otras categorías intactas; un clic desactiva y conserva detalles; inyección/campos ilegales; porcentajes y precios inválidos; versión obsoleta; preservar legal/recordatorios; altas y acuerdos nuevos con centavos; mantener importes de suscripciones existentes; banner solo público; rutina puntual y HTML escapado. Deben fallar por rutas/comportamiento ausentes.
- [ ] Ejecutar `php artisan test --compact --filter=CommercialPricingTest`. Expected: pruebas rojas por función ausente.
- [ ] Implementar interfaces, formularios y presentaciones descritas; precios base y finales usan el mismo catálogo. No cambiar registros existentes en la actualización del catálogo.
- [ ] Ejecutar `php artisan test --compact --filter='CommercialPricingTest|CommercialConfigurationTest|ManualMembershipPlansTest|ManualMembershipPaymentsTest|CommercialSubscriptionsTest'`. Expected: todas verdes.
- [ ] Commit `feat: editar catálogo y promociones desde el panel` y registrar resultado en ledger.

### Task 2: Tipografía, integración y publicación

**Files:** Modificar `public/css/visionfit/marketing.css` y navegación comercial/administradora; crear `docs/catalog-promotions.md`.

**Interfaces:** Consumes las clases de banners, tarjetas y formularios de Task 1. No cambia la API de precios.

- [ ] Abrir tracking y líneas de encabezados, usar tamaños fluidos móviles, espaciado de banner y formularios, manteniendo los saltos con espacios existentes. Revisión de CSS/HTML, sin prueba que solo replique declaraciones.
- [ ] Documentar uso, centavos, alcance, preservación de membresías, límites de verificación y decisiones.
- [ ] Ejecutar `php artisan test --compact`, `node --test tests/js/*.test.*`, `npm run build`, `vendor/bin/pint --test --dirty`, `php artisan view:cache`, `git diff --check`. Expected: todas las pruebas y comandos pasan.
- [ ] Commit `fix: mejorar lectura de títulos y documentar ofertas` y registrar resultado.
- [ ] Una revisión independiente de la rama completa, corregir hallazgos importantes con RED→GREEN; publicar por PR y verificar CI, árbol exacto, dos despliegues y HTTP público/privado.
