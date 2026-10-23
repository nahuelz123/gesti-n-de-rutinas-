# Avisos de vencimiento para la gestión manual

El usuario quiere asignar cuentas, cobrar personalmente y recibir avisos de vencimiento. Se conserva la gestión manual publicada y la autorización de terminar/publicar por GitHub sin autenticar Railway. La pregunta opcional sobre canal no tuvo respuesta; se eligió panel + correo configurable, sin atribuir al usuario una preferencia no confirmada.

- Lista privada del superadministrador: manuales que vencen en próximos 7 días calendario o vencieron en últimos 30; fuente paid_until o trial_ends_at cuando no hubo pago. Titular, espacio, plan y vínculo de gestión, con paginación.
- Excluir proveedor, bajas, cuentas deshabilitadas y espacios anteriores no incorporados al control comercial. Registrar una renovación actualiza la selección.
- Resumen opcional al correo de la cuenta root que lo activa explícitamente, nunca a clientes ni a una dirección arbitraria. Desactivado por defecto. Validar cuenta, rol y opt-in al entregar al transporte.
- Una vez por día/destinatario entre 09:00 y 21:00 de Argentina; hasta 100 filas y vínculo al resto. Claim SQL previo a contactar SMTP, sin reintentar automáticamente ese día ante fallo o interrupción; estado visible. Aceptación del transporte no certifica recepción en bandeja.
- Reutilizar el worker existente: cada 15 minutos comprueba activación y lanza un hijo asíncrono de hasta 60 segundos. El correo no bloquea el cronómetro. Otro hosting puede ejecutar memberships:remind por cron.
- Sin nuevas dependencias, servicios de alojamiento ni credenciales en DB/Git. No modificar pagos, fechas o accesos. No enviar correos reales de prueba ni crear una automatización de ChatGPT.

Alternativas: solo filtro al entrar (no llega fuera de la app), panel + correo configurable (elegido), Telegram/WhatsApp/push (requieren vinculación no aportada y quedan fuera).

Verificar calendario, exclusiones, permisos, renovación, preservación de otros ajustes, rol/destinatario actual, idempotencia, fallos y proceso no bloqueante. Revisión independiente, suite completa, JS/build/Blade/Pint y despliegue por GitHub. No afirmar prueba visual ni entrega real.
