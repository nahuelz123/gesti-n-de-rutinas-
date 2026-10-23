# Avisos de vencimiento: uso y límites

La gestión manual conserva la asignación de cuentas y planes, cobros confirmados, períodos y cupos. Ahora tiene una lista privada en `/gestion-comercial/recordatorios` con los próximos7 días y los últimos30 días vencidos. Renovar actualiza la lista; bajas, proveedor y espacios anteriores no incorporados quedan fuera. Los vencidos más antiguos siguen en el filtro general.

Como superadministrador, podés activar el resumen diario para el correo de tu propia cuenta desde esa pantalla. Está desactivado por defecto y requiere un transporte real y la URL HTTPS configurada en el worker. No se envía a clientes ni se admite una dirección arbitraria. Se revalida la cuenta, rol, destinatario y activación al entregar el mensaje.

El worker existente comprueba cada15 minutos, entre09:00 y21:00 de Argentina, y ejecuta el comando de correo en un proceso independiente de hasta60 segundos. No agrega otro servicio ni espera a SMTP en el cronómetro. Un intento diario por destinatario, hasta100 filas y vínculo al resto. Si falla o se interrumpe, no se reintenta automáticamente ese día; el panel sigue disponible. La aceptación del transporte no garantiza llegada a la bandeja.

## Verificación

417 pruebas PHP y1986 aserciones,44 pruebas JavaScript, build Vite, Pint, Blade y diff. Revisión independiente con dos hallazgos importantes corregidos mediante siete regresiones RED→GREEN: revocaciones/cambio de correo durante la preparación del mensaje y transporte efectivo seleccionado por MAIL_URL. Sin hallazgos menores diferidos. También se comprobó un hijo CLI asíncrono real con transporte log, sin envío.

No se enviaron correos reales ni se configuraron credenciales del operador. No se certificó apariencia visual desde un navegador ni concurrencia en varias conexiones MySQL. La publicación se verifica por GitHub/HTTP, sin autenticación de Railway.

## Decisiones tomadas

1. Panel y correo opcional al no recibir una elección de canal: permite usar la lista de inmediato; Telegram, WhatsApp y push no están habilitados.
2. Máximo un intento diario sin reenvío automático: evita duplicados después de una interrupción; un fallo espera al día siguiente.
3. Reutilizar el worker mediante un hijo limitado: protege los cronómetros y evita otro servicio; requiere mantener ese worker activo y su correo configurado.
4. Credenciales/entrega final se validan al activar la cuenta real: no se inventa configuración ni se envían pruebas reales; el operador debe comprobar su servicio.
5. Revisión del despliegue separada de la revisión de código: GitHub/HTTP prueban la publicación; no se ejercitan cuentas root o datos financieros reales en producción.
6. Sin prueba visual por la restricción anterior del navegador: se verifica código y respuestas; pueden quedar detalles de presentación/impresión.
7. Canales Telegram/WhatsApp/push siguen fuera del alcance: necesitan vinculación y elección; solo se incluye panel y correo configurable.
8. El correo refleja su momento de captura: renovaciones posteriores se consultan en la app; un mensaje entregado al transporte no puede retirarse. La revocación anterior sí se comprueba bajo bloqueo.

Procedimiento ampliado: `commercial-setup.md`.
