# Checklist de lanzamiento comercial

## Configuración de Railway

- [ ] Configurar APP_ENV=production, APP_DEBUG=false, APP_URL=https://..., APP_KEY y SESSION_SECURE_COOKIE=true.
- [ ] Confirmar base de datos persistente y ejecutar migraciones con php artisan migrate --force.
- [ ] Configurar correo real. MAIL_MAILER=log sirve para desarrollo, no para recuperación de contraseñas en producción.
- [ ] Configurar LEGAL_OPERATOR_NAME y LEGAL_CONTACT_EMAIL; revisar las políticas con asesoría legal.
- [ ] Completar el contacto de privacidad de cada gimnasio antes de habilitar su QR de alta.
- [ ] Para logos, montar un volumen persistente en storage/app/public o configurar GYM_LOGO_DISK=s3 y las credenciales/bucket. Los archivos locales efímeros pueden perderse al recrear el contenedor.
- [ ] Programar backups automáticos de la base y probar una restauración antes de incorporar gimnasios.

## IA y datos

- [ ] Verificar GEMINI_API_KEY, DEEPSEEK_API_KEY y DEEPSEEK_BASE_URL en Railway, y configurar límites de gasto.
- [ ] Revisar las condiciones de tratamiento y retención de Google Gemini y del host definido en DEEPSEEK_BASE_URL.
- [ ] Probar que la foto no se guarda, que el borrador siempre requiere revisión del profe y que clientes sin permiso no aparecen en las herramientas del asistente.
- [ ] Probar que retirar el permiso de IA desde Mi cuenta bloquea nuevas solicitudes con contexto personal.

## Verificación antes de vender

- [ ] Probar en móvil: alta QR, inicio de sesión, recuperar contraseña, registrar entrenamiento, progreso, chat y lectura de foto.
- [ ] Revisar composer audit y npm audit; resolver vulnerabilidades críticas o altas antes de abrir acceso comercial.
- [ ] Confirmar límites de uso, soporte, precio, cancelación y contrato de tratamiento de datos por gimnasio. Estos puntos dependen de decisiones comerciales y no están definidos por el código.
