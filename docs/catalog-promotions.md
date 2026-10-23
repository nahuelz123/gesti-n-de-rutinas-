# Administrar precios y promociones de VisionFit

Entrá como súper administrador al panel y elegí **Precios y promociones**, o abrí `/gestion-comercial/precios`.

1. Editá los precios base de entrenadores, gimnasios, asesorías y rutina puntual. Todos están en ARS; admiten centavos. Guardá con **Guardar precios y detalles**. Se actualiza la web sin desplegar código.
2. Elegí el título, el texto, el porcentaje y los productos de la oferta. Guardar conserva el estado activo o desactivado.
3. Pulsá **Activar descuento** para publicar el cartel y los precios descontados. El mismo botón pasa a **Desactivar descuento**. Cada acción requiere un clic y usa la configuración guardada.
4. Usá **Ver precios publicados** para abrir la web en otra pestaña. Los productos incluidos muestran base, porcentaje y final; los demás conservan su precio.

La promoción comienza desactivada. El valor inicial de 10 % es una propuesta editable; no se aplica a clientes hasta que el operador la activa. Puede incluir todos los productos o solo algunos y permanece vigente hasta desactivarla. El cartel se publica en las páginas comerciales, sin agregarse a pantallas privadas o políticas.

El precio mostrado se calcula en centavos enteros y se redondea al centavo más próximo, con mitades hacia arriba. El mismo importe se guarda en las nuevas altas y en los nuevos acuerdos de plan. Se rechaza cualquier oferta que termine en menos de un centavo.

Cambiar el catálogo **no modifica una membresía existente**, sus cobros o un período acordado. Si querés actualizarla, acordá el precio con el titular y usá **Plan para la próxima renovación** en su gestión. Elegir el mismo plan toma el nuevo precio publicado; los períodos pagados conservan su valor.

Solo el súper administrador tiene acceso. Las acciones usan POST con CSRF y límites de frecuencia. Se registra quién cambió los valores, antes/después y la versión. Si otra pestaña guardó cambios primero, se rechaza el formulario viejo y se ofrece recargarlo. El estado explícito y la versión impiden que un doble clic invierta la promoción. Los datos legales, instrucciones de transferencia y recordatorios se conservan.

## Decisiones y límites

- Se amplió el catálogo existente en Laravel. No se agregaron dependencias ni se necesitó autenticación de Railway. Si se esperaban cupones individuales, múltiples ofertas o fechas automáticas, requieren una ampliación; esta promoción se gestiona manualmente.
- Se limita cada precio a ARS 1.000.000. Las columnas monetarias actuales y los cobros de hasta 12 meses admiten ese rango; valores mayores requieren ampliar esas columnas. No se cambiaron tablas productivas para esta función.
- Los centavos se muestran también en las vistas de suscripción y gestión, para que el valor visible coincida con el importe que se registra.
- Los títulos tienen interlineado y espaciado entre letras más abiertos, y tamaños fluidos en móvil. La restricción previa de navegador permite verificar código/HTML/HTTP, pero impide certificar una inspección visual real en dispositivos.
- No se activan ofertas ni se modifican precios reales como prueba de publicación. Las escrituras, los permisos y los cálculos se verifican con datos aislados; producción se comprueba mediante CI, despliegues y rutas HTTP.
