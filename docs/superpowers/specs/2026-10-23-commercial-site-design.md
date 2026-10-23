# VisionFit: sitio comercial y membresías

## Encargo y decisiones
El usuario pidió empezar y terminar la web comercial completa, conectada con la app, con políticas, cookies, SEO y membresías. Autorizó resolver el diseño sin más rondas de preguntas. Se amplía el Laravel existente: otro hosting o un front separado duplicaría autenticación, formularios y mantenimiento. La alternativa temporal de links externos de cobro no controla el acceso y no es la solución elegida.

## Experiencia
- Inicio público; asesorías, entrenadores y gimnasios con contenido específico; planes; preguntas frecuentes; recursos útiles; contacto y demo. Acceso visible a la app.
- Mantener rojo, negro y blanco, con buen contraste, navegación por teclado y móvil. Sin testimonios, resultados, clientes ni credenciales inventadas. Las vistas de producto ilustrativas se identifican.
- Catálogo en ARS: entrenador 10 alumnos $14.900 / 30 alumnos $24.900; gimnasio 100 alumnos y 3 profesionales $34.900 / 300 y 10 $59.900; asesoría $69.900 / premium $109.900; rutina puntual $29.900 por solicitud. Precios editables en configuración.
- Las consultas se guardan en la base y se administran por super_admin. Contacto, baja, arrepentimiento y privacidad son públicos y dan un número de solicitud sin exponer datos de otras personas. Los avisos por email dependen del correo configurado; nunca se finge su envío.
- El software tiene prueba de 14 días sin tarjeta. Un entrenador o gimnasio crea su espacio y cuenta administradora; una cuenta ya asociada no cambia de gimnasio silenciosamente. Las asesorías requieren coordinación y disponibilidad del entrenador.
- Suscripción dentro de la app: plan, uso, vigencia, cobros, renovación y cancelación. Cobro en checkout alojado de Mercado Pago; nunca se guardan tarjetas. No activar cobros sin identidad/contacto legal y credenciales reales. Los clientes existentes mantienen su acceso actual.
- Un débito aprobado verificado habilita un período; la mera autorización recurrente o URL de retorno no acredita pago. Eventos repetidos no duplican períodos. Los errores del proveedor no se convierten en cobros aprobados ni cancelaciones exitosas.
- Cancelar detiene renovaciones y conserva el período pagado; rechazos dan siete días de gracia tras vencer el período pagado. La baja pública se registra sin login; la gestión autenticada puede detener la renovación automáticamente.
- Solo el propietario de la suscripción puede modificarla; super_admin consulta y administra solicitudes. El alumno de un gimnasio no paga una segunda licencia.

## Privacidad y SEO
No instalar publicidad, analítica ni píxeles. El aviso esencial persistente existente se conserva y la política explica localStorage. Formularios mínimos, sin pedir salud; aceptación de privacidad y términos donde corresponde. Incorporar contactos comerciales, pagos y solicitudes a la política, manteniendo las explicaciones reales de IA y notificaciones.

Metadatos únicos, canonical sin parámetros sobre APP_URL, Open Graph, JSON-LD veraz, sitemap XML solo público, robots y noindex de cuentas/pagos/paneles/demos. Páginas renderizadas por servidor, CSS y JS locales, sin fuentes externas. Configuración opcional de verificación de Search Console. No prometer posiciones en Google ni cumplimiento legal absoluto.

## Límites de puesta en marcha
No inventar email, domicilio, CUIT, dominio ni credenciales del titular. Publicar funciones disponibles y solicitudes operativas; el checkout real queda cerrado hasta completar esos datos. Documentar configuración y comprobación en producción. La facturación fiscal es independiente del comprobante de pago.

## Validación
Pruebas de páginas públicas, enlaces y formularios; SEO y privacidad de rutas; autorización, pruebas gratuitas, checkout sin configuración, webhooks repetidos/falsos, importes y cancelación. Ejecutar suite PHP/JS, build y caché de vistas, revisar escritorio/móvil si el entorno lo permite, revisión independiente y publicar por GitHub para conservar el despliegue existente.
