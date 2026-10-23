<x-layouts.legal title="Política de privacidad">
    <p>Esta política explica qué información usan VisionFit y el gimnasio al que pertenecés. El gimnasio administra la relación de entrenamiento y decide qué datos solicita; {{ $operatorName }} brinda la plataforma para gestionarlos.</p>

    <section>
        <h2 class="text-xl font-semibold text-white">Responsable y contacto</h2>
        @if ($gym)
            <p>Gimnasio: <strong>{{ $gym->name }}</strong>.</p>
            @if ($gym->privacy_contact_name || $gym->privacy_contact_email)
                <p>Contacto del gimnasio para consultas de privacidad: {{ $gym->privacy_contact_name ?: $gym->name }}@if($gym->privacy_contact_email) · <a class="text-amber-300 underline" href="mailto:{{ $gym->privacy_contact_email }}">{{ $gym->privacy_contact_email }}</a>@endif</p>
            @else
                <p>El contacto de privacidad de este gimnasio todavía no está cargado. Pedíselo al administrador del gimnasio.</p>
            @endif
        @else
            <p>El gimnasio al que pertenecés es el contacto para consultas sobre los datos de tu entrenamiento y salud.</p>
        @endif
        <p>Operador de la plataforma: {{ config('legal.operator_name') ?: 'identidad legal pendiente de configurar' }}.
            @if ($contactEmail)
                Contacto: <a class="text-amber-300 underline" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
            @else
                El correo de contacto legal de la plataforma está pendiente de configurar.
            @endif
        </p>
        @if(config('legal.operator_address'))<p>Domicilio del operador: {{ config('legal.operator_address') }}.</p>@endif
        @if(config('legal.tax_id'))<p>Identificación fiscal: {{ config('legal.tax_id') }}.</p>@endif
        <p>También podés contactar al área de atención de VisionFit por el <a class="text-amber-300 underline" href="{{ url('/contacto') }}">formulario de contacto</a> o registrar una <a class="text-amber-300 underline" href="{{ url('/privacidad/solicitud') }}">solicitud sobre tus datos</a>.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Datos que se usan</h2>
        <ul class="list-disc space-y-2 pl-6">
            <li>Cuenta y contacto: nombre, correo electrónico, gimnasio y datos de acceso.</li>
            <li>Perfil de entrenamiento: edad, objetivos, nivel de actividad, ejercicios, series, repeticiones, cargas e historial de progreso.</li>
            <li>Información de salud que decidas cargar, como lesiones u observaciones médicas, y mediciones corporales.</li>
            <li>Registros de comidas, recetas, mensajes con el coach y conversaciones con asistentes de IA.</li>
            <li>Cuando un profe usa la lectura de rutinas, el archivo que selecciona para transcribir.</li>
        </ul>
        <p>Los datos de perfil, salud y progreso se usan para operar la cuenta, mostrar rutinas y planes, registrar avances y permitir que el equipo autorizado del gimnasio te acompañe.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Uso de inteligencia artificial</h2>
        <p>El chat de IA para clientes es opcional. Solo se activa después de que lo autorices; podés retirar ese permiso desde Mi cuenta. Si lo usás, tus mensajes y el contexto necesario de tu perfil, rutina, dieta y progreso se envían al proveedor configurado ({{ $aiProviderHost }}) para generar una respuesta.</p>
        <p>El asistente del profe solo puede usar los datos de un cliente que haya dado ese permiso. El chat general del profe funciona sin seleccionar un cliente. Las respuestas pueden contener errores y no son diagnóstico ni indicación médica.</p>
        <p>Si un profe carga una foto o un documento de rutina, el archivo o su texto se envía a {{ $photoProviderHost }} para transcribirlo. VisionFit no conserva el archivo después del procesamiento. El profe recibe un borrador editable y debe revisar ejercicios, series y repeticiones; nunca se asigna automáticamente.</p>
        <p>Si registrás una comida desde una foto, la imagen se envía a {{ $photoProviderHost }} para estimar sus alimentos y calorías. La foto no se conserva; solo se guarda el registro que revises y confirmes. Las cantidades y macros son aproximados.</p>
        <p>En las fotos, evitá incluir nombres, diagnósticos u otra información que no haga falta para leer la rutina. El tratamiento que haga cada proveedor externo se rige además por sus propias condiciones.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Avisos de descanso opcionales</h2>
        <p>Si activás las notificaciones, guardamos cifrada la suscripción de ese navegador y programamos los avisos de descanso. El servicio de notificaciones del navegador (Apple, Google o Mozilla) recibe mensajes genéricos, sin ejercicios, pesos ni datos de salud. Podés retirar el permiso desde los ajustes del navegador o del teléfono.</p>
        <p>Los registros de avisos se eliminan después de siete días y las suscripciones sin uso después de noventa días. El cierre de sesión intenta dar de baja los avisos de ese dispositivo. La entrega, el sonido y la vibración dependen de la conexión y de los ajustes del sistema.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Acceso, almacenamiento y conservación</h2>
        <p>Los entrenadores y administradores autorizados de tu gimnasio pueden consultar la información necesaria para prestarte el servicio. Los datos se alojan en la infraestructura que el operador tenga configurada, actualmente Railway para la aplicación, y se transmiten a los proveedores de IA solo en las funciones indicadas.</p>
        <p>Las conversaciones con la IA se conservan en el historial de la cuenta hasta que uses la opción para borrarlas. Los demás registros permanecen asociados a la cuenta mientras se presta el servicio. Para consultar, corregir o pedir la baja/eliminación de información, contactá al gimnasio y al operador de la plataforma.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Web pública, consultas y contratación</h2>
        <p>Si completás un formulario comercial, de baja, arrepentimiento o privacidad, guardamos tu nombre, email, el servicio o plan indicado, el mensaje, la fecha y una referencia de gestión. Se usan para responder y tramitar lo solicitado; no te incorporan a publicidad ni a una lista de marketing. No incluyas datos de salud ni de tarjetas en estos formularios.</p>
        <p>Las consultas quedan disponibles para la administración autorizada de VisionFit. Podés solicitar su acceso, corrección o eliminación desde <a class="text-amber-300 underline" href="{{ url('/privacidad/solicitud') }}">el formulario de privacidad</a>. Las solicitudes comerciales se conservan mientras se gestionan y se revisan para su eliminación cuando dejan de ser necesarias; los comprobantes y registros sujetos a conservación obligatoria siguen el plazo aplicable.</p>
        <p>Si contratás un plan con cobro online, Mercado Pago procesa los datos del medio de pago en su propio sitio. VisionFit conserva identificadores de suscripción y pagos, importes, moneda, estados y fechas para administrar el acceso y la renovación. VisionFit no guarda números completos de tarjeta ni códigos de seguridad.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-white">Tus opciones</h2>
        <p>Podés corregir los datos de perfil desde Mi cuenta, decidir si usás el asistente de IA y retirar ese permiso cuando quieras. Para ejercer otros derechos sobre tus datos, usá los contactos indicados arriba. Esta política debe revisarse según el contrato y la configuración real de cada gimnasio.</p>
    </section>
    <p>La Agencia de Acceso a la Información Pública es el órgano de control de la Ley 25.326. Podés consultar tus derechos y presentar un reclamo en <a class="text-amber-300 underline" href="https://www.argentina.gob.ar/aaip/datospersonales" rel="external">su sitio oficial</a>.</p>

    @if (! config('legal.operator_name') || ! $contactEmail || ($gym && ! $gym->privacy_contact_email))
        <div class="rounded-xl border border-amber-700 bg-amber-950/40 p-4 text-amber-100">
            Antes de ofrecer el servicio comercialmente, completá el nombre y correo legal del operador y el contacto de privacidad de cada gimnasio.
        </div>
    @endif
</x-layouts.legal>
