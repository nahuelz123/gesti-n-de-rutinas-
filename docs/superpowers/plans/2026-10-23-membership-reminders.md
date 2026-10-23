# Membership Reminders Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Tareas implementadas directamente y una revisión independiente final.

**Goal:** Gestión manual con avisos privados y resumen diario opcional al operador.
**Architecture:** Consulta única, ajustes permitidos en SQL, claim diario persistente y comando independiente del worker de descansos.
**Tech Stack:** Laravel 12, PHP 8.2+, Blade, SQL, PHPUnit, Process.
**Spec:** docs/superpowers/specs/2026-10-23-membership-reminders-design.md

## Global Constraints
Próximos 7 días/últimos 30, calendario argentino, manuales activas/gestión incorporada. Solo root configura y recibe en su cuenta. Correo desactivado por defecto, intento diario 09–21, límite100. Sin nuevas dependencias/servicios ni autenticación Railway; no envío real de prueba.

## Review Focus
- Una renovación o una baja anterior al resumen no debe generar un aviso desactualizado en la consulta nueva.
- Guardar datos legales no desactiva avisos ni cambiar avisos pierde instrucciones de pago.
- Cambiar el rol/eliminar la cuenta receptora impide el envío de información comercial a un destinatario sin autorización.
- Dos procesos o un reinicio no producen dos intentos diarios; fallo o timeout no se presentan como entrega confirmada.
- El worker de descansos continúa disponible aunque el proceso de correo tarde, falle o exceda su plazo.

### Task 1: Lista privada y configuración
- [x] RED: permisos, calendario7/8 y30/31, pruebas, proveedor/baja/legacy, renovación, destino propio y conservación de ajustes.
- [x] GREEN: CommercialMembershipReminders::query(): Builder con reminder_due_at; GET /gestion-comercial/recordatorios; POST /gestion-comercial/recordatorios/configuracion; mezcla de ajustes bajo bloqueo y UI privada.
- [x] Verificación original 20 pruebas129 aserciones de avisos/configuración/panel. Cobertura preservada en la entrega.

### Task 2: Entrega y publicación
- [x] RED/GREEN: claim SQL único día/root, configuración CLI, mailable, comando memberships:remind, hijo asíncrono60s cada15min y compatibilidad con --once.
- [x] Verificación original410 PHP1966 aserciones, JS44, Vite/Pint/Blade. Prueba adicional real de hijo CLI sin correo.
- [x] Revisión independiente completa, dos hallazgos importantes; reproducir RED antes de corregir, una pasada de correcciones sin re-review.
- [ ] Suite completa actual, publicación GitHub, CI, ambos despliegues y HTTP. Sin registros financieros/correos reales ni prueba visual.

