# Administrar precios y promociones de VisionFit

Entrá como súper administrador al panel y elegí **Precios y promociones**, o abrí `/gestion-comercial/precios`.

1. Editá los precios base de entrenadores, gimnasios, asesorías y rutina puntual. Todos están en ARS; admiten centavos. Guardá con **Guardar precios y detalles**. Se actualiza la web sin desplegar código.
2. Elegí el título, el texto, el porcentaje y los productos de la oferta. Guardar conserva el estado activo o desactivado.
3. Pulsá **Activar descuento** para publicar el cartel y los precios descontados. El mismo botón pasa a **Desactivar descuento**. Cada acción requiere un clic y usa la configuración guardada.
4. Usá **Ver precios publicados** para abrir la web en otra pestaña. Los productos incluidos muestran base, porcentaje y final; los demás conservan su precio.

La promoción comienza desactivada. El valor inicial de 10 % es una propuesta editable; no se aplica a clientes hasta que el operador la activa. Puede incluir todos los productos o solo algunos y permanece vigente hasta desactivarla. El cartel se publica en las páginas comerciales, sin agregarse a pantallas privadas o políticas.

El precio mostrado se calcula en centavos enteros y se redondea al centavo más próximo, con mitades hacia arriba. El mismo importe se guarda en las nuevas altas y en los nuevos acuerdos de plan. Se rechaza cualquier oferta que termine en menos de un centavo o que, por redondeo, no genere un ahorro real.

Cambiar el catálogo **no modifica una membresía existente**, sus cobros o un período acordado. Si querés actualizarla, acordá el precio con el titular y usá **Plan para la próxima renovación** en su gestión. Elegir el mismo plan toma el nuevo precio publicado; los períodos pagados conservan su valor.

Solo el súper administrador tiene acceso. Las acciones usan POST con CSRF y límites de frecuencia. Se registra quién cambió los valores, antes/después y la versión. Si otra pestaña guardó cambios primero, se rechaza el formulario viejo y se ofrece recargarlo. El estado explícito y la versión impiden que un doble clic invierta la promoción. Los datos legales, instrucciones de transferencia y recordatorios se conservan.

## Decisiones y límites

- Se amplió el catálogo existente en Laravel. No se agregaron dependencias ni se necesitó autenticación de Railway. Si se esperaban cupones individuales, múltiples ofertas o fechas automáticas, requieren una ampliación; esta promoción se gestiona manualmente.
- Se limita cada precio a ARS 1.000.000. Las columnas monetarias actuales y los cobros de hasta 12 meses admiten ese rango; valores mayores requieren ampliar esas columnas. No se cambiaron tablas productivas para esta función.
- Los centavos se muestran también en las vistas de suscripción y gestión, para que el valor visible coincida con el importe que se registra.
- Los títulos tienen interlineado y espaciado entre letras más abiertos, y tamaños fluidos en móvil. La restricción previa de navegador permite verificar código/HTML/HTTP, pero impide certificar una inspección visual real en dispositivos.
- No se activan ofertas ni se modifican precios reales como prueba de publicación. Las escrituras, los permisos y los cálculos se verifican con datos aislados; producción se comprueba mediante CI, despliegues y rutas HTTP.

## Confirmación de importes

Las altas, asignaciones y cambios de plan incluyen una referencia firmada del catálogo mostrado. El servidor la comprueba contra los datos actuales bajo bloqueo dentro de la transacción. Si cambió el catálogo mientras completabas el formulario, no guarda otro importe: te pide recargar y confirmar. No se acepta un importe enviado por el navegador como precio del plan. Los formularios abiertos antes de esta actualización también deben recargarse.

## Revisión y alcance de la verificación

Una revisión independiente encontró la posibilidad de guardar un precio distinto al mostrado en un formulario abierto. Se corrigió con pruebas que primero reprodujeron el fallo en asignación, renovación y alta, además del rechazo de referencias ausentes/falsificadas. Se corrigieron también la publicidad de descuentos sin ahorro y la recuperación del formulario ante entradas anidadas inválidas, ambos con regresión roja y verde. Se trataron como problemas de funcionamiento: un descuento debe ahorrar y un error de validación debe devolver un editor utilizable.

No quedaron hallazgos menores diferidos. Decisiones de alcance de la revisión, en el orden en que se evaluaron:

1. Apariencia, desbordes y accesibilidad visual: la restricción de navegador sigue vigente. Se revisó CSS/HTML y se publican cambios; la apariencia final en cada dispositivo no se certificó.
2. Aceptación real de Mercado Pago: se conserva el precio almacenado de cada membresía y no se realizaron cobros externos. Los límites o la activación del proveedor requieren comprobar la cuenta del operador; los cobros manuales siguen disponibles.
3. Concurrencia MySQL y llenado de caché: se revisaron bloqueos/versiones y se valida el importe contra la fila actual antes de guardar. No se certificó una prueba de contención real; una página desactualizada puede pedir recargar.
4. Despliegue/CI/configuración/precios productivos: la publicación se comprueba por GitHub y HTTP; no se usan cuentas financieras reales ni se cambian importes para probar. No equivale a una prueba de escritura con la cuenta del operador.
5. Compra de rutina puntual: sigue el flujo de consulta y contratación acordada; agregar checkout automático sería otra función.
6. Operaciones simultáneas previas de membresías: se conservan sus bloqueos e idempotencia; no se rediseña ese módulo. Una necesidad nueva de coordinación requiere revisión específica.
7. Suite completa: el revisor repitió las pruebas del catálogo; la suite completa y la verificación tras los arreglos las ejecutó el implementador. No se presenta como una segunda revisión independiente.

Verificación final antes de publicar: **451 pruebas PHP, 2256 aserciones; 44 pruebas JavaScript; Vite, Pint, caché Blade, análisis sintáctico de CSS y diff sin errores**. Las seis regresiones de revisión se observaron primero fallar y luego pasar.
