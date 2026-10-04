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
