# VisionFit: web comercial y membresías

La web pública y la app usan el mismo Laravel. `/` presenta el servicio; `/login` conserva el acceso. No se agregó un hosting ni una dependencia de pago. La web está renderizada en servidor, sin un framework de JavaScript adicional.

## Operación
- `/planes`: precios en ARS. La fuente de precios, cupos y nombres es `config/commerce.php`.
- `/empezar/{plan}`: prueba de software de 14 días sin tarjeta, alta de un espacio y su propietario. No cobra al finalizar.
- `/mi-suscripcion`: plan, cupo, cobro recurrente, historial, actualización y cancelación. Solo su propietario modifica la suscripción.
- `/gestion-comercial`: solicitudes con estados y últimas 50 suscripciones. Solo super_admin, con enlace en el panel.
- Asesorías: coordinar por contacto, crear al alumno en el espacio de Nahuel y preparar su plan en Gestión comercial. El cliente contrata desde Mi suscripción.
- `/baja`, `/arrepentimiento`, `/privacidad/solicitud`: solicitudes sin login ni consentimiento adicional obligatorio; referencia inmediata. Revisarlas diariamente y cumplir los plazos aplicables.
- Los formularios se guardan aunque falle el correo. Con mailer `log` o `array` no se pretende entregar email. Con un transporte real se intenta enviar referencia al solicitante y aviso al inbox; los errores se registran sin datos sensibles. Verificar entrega real antes de vender.

## Datos del titular y configuración
Completar en el entorno de producción, nunca con ejemplos ficticios ni tokens en Git:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMINIO-REAL
COMMERCE_PUBLIC_URL=https://DOMINIO-REAL
COMMERCE_INDEXABLE=true
LEGAL_OPERATOR_NAME=IDENTIDAD-LEGAL-REAL
LEGAL_CONTACT_EMAIL=EMAIL-REAL
LEGAL_OPERATOR_ADDRESS=DOMICILIO-REAL
LEGAL_TAX_ID=IDENTIFICACION-FISCAL-SI-CORRESPONDE
LEGAL_PRIVACY_VERSION=2026-10-23
LEGAL_TERMS_VERSION=2026-10-23
COMMERCE_INBOX_EMAIL=EMAIL-PARA-CONSULTAS
COACHING_GYM_ID=ID-DEL-ESPACIO-DE-ASESORIAS
GOOGLE_SITE_VERIFICATION=TOKEN-DE-SEARCH-CONSOLE-SI-SE-UTILIZA
MERCADOPAGO_ACCESS_TOKEN=CREDENCIAL-PRIVADA
MERCADOPAGO_WEBHOOK_SECRET=SECRETO-DE-FIRMA
```
Configurar también el remitente y transporte de correo de Laravel. Sin identidad/contacto legal, token y secreto de firma, el checkout no se habilita. La prueba y el contacto siguen funcionando. No se creó una cuenta de Mercado Pago ni se ejecutaron cobros reales en desarrollo.

## Mercado Pago
En la cuenta del vendedor configurar `/webhooks/mercadopago` con HTTPS y los tópicos `subscription_preapproval`, `subscription_authorized_payment` y `payment`. Solo esta ruta se excluye de CSRF: exige firma HMAC y consulta el recurso con la credencial del vendedor.

El importe proviene del registro guardado, nunca del navegador. La autorización recurrente no acredita pago. Solo pagos aprobados, asociados mediante una factura del proveedor a la suscripción y con importe y moneda correctos, generan vigencia. El ID de pago es único y el período deriva de su fecha: reentregas no suman meses. Reintegros/contracargos actualizan vigencia al notificarse. La versión `date_last_updated` del proveedor evita que respuestas antiguas reviertan una devolución o aprobación más reciente. El usuario puede actualizar el estado para reconciliar notificaciones faltantes.

La cancelación se confirma solo cuando el proveedor la acepta y conserva el período pagado. Hay siete días de gracia tras el último período abonado, nunca una prueba infinita. Un cambio de plan se solicita por contacto y requiere coordinación y aceptación de las nuevas condiciones. No cambiar importes en Mercado Pago sin actualizar el contrato y su registro en VisionFit.

## Compatibilidad y acceso
Solo los nuevos espacios tienen `billing_managed=true`: no se impuso un vencimiento a los gimnasios existentes. Cupos en altas QR y creación/edición del panel, con transacción y bloqueo de espacio. El propietario ocupa una plaza de profesional. Deshabilitar un acceso conserva historial y libera plaza. Antes de eliminar al pagador o su espacio, cancelar la renovación externa para evitar cobros huérfanos.

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
- https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview
- https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls
- https://www.argentina.gob.ar/aaip/datospersonales/derechos
- https://www.argentina.gob.ar/normativa/nacional/disposici%C3%B3n-954-2025-417152/texto
- https://www.boletinoficial.gov.ar/detalleAviso/primera/338248/20260206
- https://www.mercadopago.com.ar/developers/es/reference/online-payments/subscriptions/create-preapproval/post
- https://www.mercadopago.com.ar/developers/es/reference/online-payments/subscriptions/get-authorized-payment/get
- https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-orders/resources/notifications/webhooks
- https://www.mercadopago.com.ar/developers/es/docs/subscriptions/additional-content/payment-management
