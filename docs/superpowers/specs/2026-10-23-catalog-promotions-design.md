# Precios, promociones y títulos legibles

El súper administrador necesita cambiar los siete precios publicados (seis membresías y la rutina puntual), editar una oferta y activar o desactivar su descuento con un clic. La web tiene títulos con tracking de hasta −0,055 em y líneas demasiado cerradas; se aflojarán sin cambiar la identidad de VisionFit.

## Enfoque elegido

La solicitud ya autoriza completar y publicar cambios; se conserva Laravel y el panel existente. Esta elección de implementación es del agente. Un banner meramente decorativo dejaría importes incoherentes; editar archivos o variables seguiría requiriendo un despliegue. Se usará un catálogo persistido en la configuración comercial, con un único cálculo para web, nuevas altas y nuevos acuerdos de plan.

## Comportamiento

- Pantalla privada «Precios y promociones» enlazada desde el panel administrador y la navegación comercial; solo `super_admin` puede leer o escribir.
- Siete importes positivos en ARS, hasta 1.000.000, con dos decimales. Se almacenan y calculan centavos enteros. Los precios actuales siguen como valores iniciales.
- Una promoción editable: título (120 caracteres), detalle opcional (280), porcentaje entero de 1 a 99 y productos seleccionados. Inicialmente desactivada, con propuesta de 10 % y todos los productos seleccionados.
- Botones POST de un clic «Activar descuento» / «Desactivar descuento», con estado deseado explícito. Guardar detalles conserva el estado actual. CSRF y límites de frecuencia; nunca cambia estado mediante GET.
- Descuento real, redondeado al centavo más próximo (mitades hacia arriba), con precio final de al menos un centavo. Un precio que produciría cero se rechaza antes de publicar.
- Banner público con porcentaje, título, detalle y alcance; cada producto incluido muestra precio base, final y porcentaje. Incluye rutina puntual en su sección de asesorías y en planes. Sin banner en pantallas privadas o legales.
- Altas nuevas y nuevos acuerdos de renovación toman el precio efectivo; cobros, membresías y períodos ya acordados conservan sus importes. La misma membresía puede actualizarse con el flujo existente de cambio de plan para la próxima renovación.
- Se informa en la web que la promoción aplica a nuevas contrataciones. No se impone fecha de fin: permanece activa hasta que el operador la desactive.
- Escrituras atómicas bajo bloqueo de la configuración, versión para rechazar formularios obsoletos y auditoría del actor/cambios. Se preservan los datos legales, las instrucciones y la configuración de recordatorios. Invalidación de caché tras guardar.
- Títulos: tracking más abierto, interlineado de al menos 1,16 en encabezados grandes y 1,25 en tarjetas, tamaños fluidos en móvil y saltos que permitan separar palabras. Se conserva el color y el contenido.

## Límites y validación

No se crean cupones por cliente, promociones múltiples, automatización de vencimientos ni cambios masivos de membresías existentes. No se activará un descuento real ni se alterarán precios productivos de prueba. No se autentica Railway. Se publicará por GitHub y se comprobarán ambos servicios y las rutas HTTP. La restricción previa de navegador impide certificar una inspección visual; se revisan CSS, vistas y contenido servido.
