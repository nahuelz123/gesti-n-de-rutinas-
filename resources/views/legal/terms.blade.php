<x-layouts.legal title="Condiciones del servicio">
    <p>VisionFit es una herramienta para que cada gimnasio organice perfiles, rutinas, seguimiento del entrenamiento, nutrición y comunicación con sus clientes. El gimnasio gestiona la prestación del entrenamiento y el acceso de sus profes.</p>
    <section>
        <h2 class="text-xl font-semibold text-white">Cuenta y uso</h2>
        <p>Usá datos de contacto correctos, mantené tu contraseña privada y avisá al gimnasio si detectás un acceso que no reconocés. El acceso puede depender de que tu gimnasio mantenga activa tu cuenta.</p>
        <p>Los profes son responsables de revisar que las rutinas y planes sean adecuados para cada persona. El lector de fotos y las propuestas de IA pueden interpretar mal una indicación: el profe debe revisar y corregir cada propuesta antes de guardarla o asignarla.</p>
    </section>
    <section>
        <h2 class="text-xl font-semibold text-white">Salud, entrenamiento e IA</h2>
        <p>La aplicación organiza información de entrenamiento y nutrición, pero no brinda diagnóstico, tratamiento ni atención de emergencia. Consultá a un profesional de salud si tenés una lesión, una condición médica o dudas sobre si podés hacer un ejercicio.</p>
        <p>La IA es opcional y puede equivocarse. No sigas una respuesta que te cause dolor o que contradiga una indicación profesional. El permiso para enviar información personal al proveedor de IA se gestiona por separado desde Mi cuenta.</p>
    </section>
    <section>
        <h2 class="text-xl font-semibold text-white">Planes, cobros y cancelación</h2>
        <p>El catálogo distingue licencias de software para entrenadores o gimnasios y asesorías de entrenamiento personal. La licencia del profesional incluye los accesos de sus alumnos dentro del cupo del plan. La asesoría personal incluye el acceso a la app para ese servicio.</p>
        <p>Los precios publicados se expresan en pesos argentinos. Antes de autorizar una contratación se informa el plan, importe y frecuencia. La prueba de software de catorce días no solicita tarjeta ni autoriza cobros al terminar. En la modalidad manual, el pago se hace por el medio acordado y se registra después de verificar el ingreso. Cada cobro cubre entre uno y doce meses completos al precio acordado; no se debita ni se renueva automáticamente. Si se habilita el cobro recurrente de Mercado Pago, requiere tu autorización en su sitio. Podés detener la continuidad desde Mi suscripción. El acceso se mantiene hasta finalizar el período abonado, salvo un reintegro o una circunstancia que justifique otra medida.</p>
        <p>Los cambios de precio se comunican antes de aplicarlos a una renovación futura, con una anticipación propuesta de treinta días. Los cambios de plan acordados se aplican al próximo período que se abone, sin modificar meses ya pagados ni cobrar diferencias proporcionales. Alcanzar el cupo no elimina alumnos: requiere archivar accesos o acordar otro plan. Una capacitación adicional, migración masiva o desarrollo específico no está incluido salvo acuerdo expreso.</p>
        <p>Podés registrar una <a class="text-amber-300 underline" href="{{ url('/baja') }}">baja de servicio</a> o ejercer el <a class="text-amber-300 underline" href="{{ url('/arrepentimiento') }}">derecho de arrepentimiento</a> desde la web pública, sin crear una cuenta adicional. Estas condiciones no limitan los derechos del consumidor establecidos por la normativa aplicable. Los registros internos de VisionFit y los comprobantes del procesador de pagos no son facturas fiscales. El operador debe emitir la documentación fiscal que corresponda por su circuito de facturación; en VisionFit puede informar su referencia.</p>
    </section>
    <section>
        <h2 class="text-xl font-semibold text-white">Disponibilidad y cambios</h2>
        <p>El operador puede actualizar funciones para mantener el servicio. Si un cambio afecta estas condiciones o el uso de datos, se publicará una versión nueva.</p>
        <p>Para consultas sobre tu cuenta o el servicio, contactá a tu gimnasio o escribí al contacto de plataforma indicado en la política de privacidad.</p>
    </section>
    @if (! config('legal.operator_name') || ! config('legal.contact_email'))
        <div class="rounded-xl border border-amber-700 bg-amber-950/40 p-4 text-amber-100">
            Antes de ofrecer el servicio comercialmente, completá el nombre y correo legal del operador en la configuración de producción.
        </div>
    @endif
</x-layouts.legal>
