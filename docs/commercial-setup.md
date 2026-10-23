# VisionFit: web comercial y membresías

La web pública y la app usan el mismo Laravel. `/` presenta el servicio; `/login` conserva el acceso. No se agregó un hosting ni una dependencia de pago. La web está renderizada en servidor, sin un framework de JavaScript adicional.

## Inicio recomendado: cobro manual, gestión en VisionFit

1. Entrar como `super_admin` en `/gestion-comercial/configuracion`. Completar identidad, contacto, domicilio y datos fiscales reales; cargar las instrucciones de pago (titular y alias/CBU que corresponda, sin contraseñas ni credenciales) y elegir el espacio de asesorías. Estos datos no secretos se guardan en la app y actualizan las políticas y las instrucciones del cliente. Los campos vacíos conservan los valores del entorno.
2. Crear la cuenta del cliente en Usuarios. Para licencias, elegir al administrador de un espacio; para asesorías, un cliente del espacio configurado. En `/gestion-comercial/membresias`, preparar la membresía y acordar el plan. Los alumnos incluidos en una licencia no pagan otra suscripción.
3. Cobrar por transferencia, efectivo u otro medio acordado. Comprobar el ingreso en la cuenta real: una captura o un mensaje no confirma fondos.
4. Abrir la membresía y registrar el importe exacto, entre uno y doce meses completos, medio, fecha y referencia completa de la transferencia. Confirmar que el dinero ya ingresó. La app registra el cobro, habilita el período, aplica cupos y conserva quién hizo la operación. No ejecuta transferencias ni cobros.
5. El titular consulta `/mi-suscripcion`: instrucciones, plan, vencimiento, historial y comprobantes internos imprimibles. Estos comprobantes son privados y **no son facturas fiscales**. Emitir la factura por el circuito fiscal del operador y anotar después su referencia si corresponde.
6. Revisar el filtro «Vencen en 7 días» y coordinar la renovación por el canal acordado. No se prometen recordatorios automáticos por correo. Exportar los cobros/reintegros del período en CSV privado para conciliación.

### Reglas operativas
- El período comienza al confirmar el cobro, o al finalizar el período ya pagado si su vencimiento es futuro. La fecha del ingreso documenta el movimiento, sin retrotraer automáticamente el acceso. Se usa el calendario de Argentina; fin de mes sin desbordes (31/01 → 28/02).
- Cada mes usa el precio guardado en la membresía. No se admiten cuotas parciales, prorrateos, descuentos ni créditos informales que habiliten acceso.
- Un UUID de operación evita repetir el mismo registro al reenviar el formulario; la misma clave con otro contenido se rechaza. Las referencias de transferencias son únicas. Los cobros originales no se editan ni se borran.
- Cambios de plan: se acuerdan y programan para el próximo período abonado, con su precio. No cambian los meses pagados. Un período futuro prepagado no adelanta los beneficios del nuevo plan. No se cambia entre licencia y asesoría. Una reducción exige archivar accesos que excedan el cupo y limita nuevas altas mientras esté pendiente.
- La baja conserva el período pagado y detiene la continuidad; antes de registrar otro cobro hay que reanudarla. El titular puede solicitarla desde su cuenta. No genera una devolución bancaria.
- Reintegrar primero por el medio acordado y luego registrar fecha, importe y motivo. Un reintegro parcial conserva el período; uno total retira ese período. Otros períodos aprobados mantienen sus fechas y no habilitan huecos anteriores. El acumulado no puede superar el cobro. Anotar o corregir la referencia fiscal deja una nueva entrada de auditoría y no modifica importes.
- Solo `super_admin` administra dinero, planes o configuración. El propietario ve sus comprobantes; los demás miembros del espacio no ven sus finanzas. Cuentas y espacios con historial comercial no se borran accidentalmente desde la app: tratar las solicitudes de privacidad mediante el procedimiento del operador.
- Las licencias nuevas tienen prueba de 14 días sin tarjeta en `/empezar/{plan}` y siete días de gracia tras el período pago si no se cancelaron. No se debita nada al terminar la prueba. Un espacio anterior mantiene su acceso hasta la asignación explícita y el primer cobro confirmado; después se controla su vigencia y cupo.
- La tabla ofrece filtros y paginación. CSV de hasta 366 días, cobros brutos y reintegros negativos en filas separadas, texto protegido contra fórmulas de planilla. La exportación no incluye notas de salud.

### Web, consultas y atención
- `/planes`: seis planes en ARS; nombres, precios y cupos provienen de `config/commerce.php`.
- `/gestion-comercial`: solicitudes de contacto, baja, arrepentimiento y privacidad; `/gestion-comercial/membresias`: gestión de membresías; enlaces disponibles en el panel de superadministrador.
- `/baja`, `/arrepentimiento`, `/privacidad/solicitud`: formularios públicos sin login, con referencia inmediata. Revisarlos diariamente y atenderlos según corresponda.
- Los formularios se guardan aunque falle el correo. Con mailer `log` o `array` no se entrega email. Un transporte real intenta enviar referencia al solicitante y aviso al inbox; verificar la entrega antes de prometer avisos. No se probaron envíos reales en desarrollo.

## Configuración del entorno y activación futura

La identidad y las instrucciones se completan desde el panel privado. No inventar datos del titular. Mantener estas variables y todas las credenciales en el entorno de producción, nunca en Git:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMINIO-REAL
COMMERCE_PUBLIC_URL=https://DOMINIO-REAL
COMMERCE_INDEXABLE=true
LEGAL_PRIVACY_VERSION=2026-10-23
LEGAL_TERMS_VERSION=2026-10-23
COMMERCE_INBOX_EMAIL=EMAIL-PARA-CONSULTAS
GOOGLE_SITE_VERIFICATION=TOKEN-DE-SEARCH-CONSOLE-SI-SE-UTILIZA
```

El entorno puede seguir definiendo `LEGAL_OPERATOR_NAME`, `LEGAL_CONTACT_EMAIL`, `LEGAL_OPERATOR_ADDRESS`, `LEGAL_TAX_ID` y `COACHING_GYM_ID` como respaldo. Los valores no vacíos del panel prevalecen. Configurar remitente y transporte de correo de Laravel por el circuito habitual.

Para habilitar posteriormente Mercado Pago, agregar `MERCADOPAGO_ACCESS_TOKEN` y `MERCADOPAGO_WEBHOOK_SECRET`, además de una identidad y contacto reales. Sin ellos el checkout queda deshabilitado; la gestión manual, prueba y contacto funcionan. El panel **no** permite guardar tokens, secretos de firma ni contraseñas SMTP. No se creó una cuenta del proveedor ni se ejecutaron cobros reales.

## Mercado Pago
En la cuenta del vendedor configurar `/webhooks/mercadopago` con HTTPS y los tópicos `subscription_preapproval`, `subscription_authorized_payment` y `payment`. Solo esta ruta se excluye de CSRF: exige firma HMAC y consulta el recurso con la credencial del vendedor.

El importe proviene del registro guardado, nunca del navegador. La autorización recurrente no acredita pago. Solo pagos aprobados, asociados mediante una factura del proveedor a la suscripción y con importe y moneda correctos, generan vigencia. El ID de pago es único y el período deriva de su fecha: reentregas no suman meses. Reintegros/contracargos actualizan vigencia al notificarse. La versión `date_last_updated` del proveedor evita que respuestas antiguas reviertan una devolución o aprobación más reciente. El usuario puede actualizar el estado para reconciliar notificaciones faltantes.

La cancelación se confirma solo cuando el proveedor la acepta y conserva el período pagado. Hay siete días de gracia tras el último período abonado, nunca una prueba infinita. La gestión manual se bloquea en membresías vinculadas al proveedor. Un checkout hacia Mercado Pago se bloquea mientras exista un período manual futuro pagado o un cambio de plan pendiente, para evitar superposiciones. Un cambio de plan del proveedor se solicita por contacto y requiere coordinación y aceptación de las nuevas condiciones. No cambiar importes en Mercado Pago sin actualizar el contrato y su registro en VisionFit.

## Compatibilidad y acceso
Los espacios nuevos tienen `billing_managed=true`. Un espacio anterior solo pasa al esquema comercial mediante asignación explícita y primer cobro confirmado; la migración no le impone un vencimiento. Cupos en altas QR y creación/edición del panel, con transacción y bloqueo de espacio. El propietario ocupa una plaza de profesional. Deshabilitar un acceso conserva historial y libera plaza. No eliminar el historial financiero. Además, cancelar cualquier renovación externa antes de tramitar una baja definitiva evita cobros huérfanos.

## SEO y privacidad
- Sitemap comercial y guías en `/sitemap.xml`; robots excluye rutas privadas.
- Títulos, descripciones, canonical sin parámetros, Open Graph y JSON-LD veraz. Imagen de compartir: `/images/visionfit-social.png`.
- `X-Robots-Tag: noindex, nofollow` en cuentas, paneles, altas, pagos, confirmaciones y demos. Robots no sustituye la autorización.
- Sin píxeles, analítica, cookies publicitarias ni fuentes externas. Aviso esencial persistente en localStorage con respaldo de pestaña.
- Verificar el dominio en Search Console y enviar el sitemap desde esa cuenta. No se creó una verificación ficticia y no se garantizan posiciones ni plazos de indexación.
- Completar y revisar identidad, domicilio, contacto, datos fiscales y textos con la operación real. La factura fiscal, atención al consumidor y gestión de solicitudes/reintegros requieren al titular: publicar una política no sustituye esas tareas ni una revisión jurídica.

## Despliegue
`railway.toml` agrega únicamente el comando previo `php artisan visionfit:prepare`, sin cambiar el build ni el comando de arranque de cada servicio. Aplica migraciones aditivas con un bloqueo SQL compartido entre web y worker, independiente de APP_NAME, y rechaza la publicación si el esquema comercial queda incompleto. Usa la tabla `cache_locks` ya presente en la base de la app existente; una instalación desde cero debe ejecutar sus migraciones iniciales antes de usar ese bloqueo.

Reconstruir cachés con el proceso existente. Confirmar `/up`, `/`, planes, contacto, sitemap, robots, políticas y login. Probar alta controlada, pago de prueba y rechazado, reentrega del mismo webhook y cancelación confirmada por el proveedor. Separar credenciales y datos de prueba de producción.

## Fuentes consultadas (23 octubre 2026)
- https://www.arca.gob.ar/monotributo/ayuda/facturacion.asp
- https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview
- https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls
- https://www.argentina.gob.ar/aaip/datospersonales/derechos
- https://www.argentina.gob.ar/normativa/nacional/disposici%C3%B3n-954-2025-417152/texto
- https://www.boletinoficial.gov.ar/detalleAviso/primera/338248/20260206
- https://www.mercadopago.com.ar/developers/es/reference/online-payments/subscriptions/create-preapproval/post
- https://www.mercadopago.com.ar/developers/es/reference/online-payments/subscriptions/get-authorized-payment/get
- https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-orders/resources/notifications/webhooks
- https://www.mercadopago.com.ar/developers/es/docs/subscriptions/additional-content/payment-management
