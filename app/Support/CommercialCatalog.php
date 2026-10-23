<?php

namespace App\Support;

use App\Services\CommercialPricing;

class CommercialCatalog
{
    public static function plans(): array
    {
        return array_intersect_key(app(CommercialPricing::class)->items(), config('commerce.plans'));
    }

    public static function oneOffRoutine(): array
    {
        return app(CommercialPricing::class)->items()['rutina-puntual'];
    }

    public static function promotion(): array
    {
        return app(CommercialPricing::class)->promotion();
    }

    public static function plan(string $code): array
    {
        abort_unless(isset(self::plans()[$code]), 404);

        return self::plans()[$code] + ['code' => $code];
    }

    public static function faqs(): array
    {
        return [
            ['¿VisionFit es una app o una web?', 'Es una aplicación web que podés usar desde el celular o la computadora. No necesitás descargarla de una tienda. El acceso a tu cuenta está separado de esta web informativa.'],
            ['¿Los alumnos pagan una segunda suscripción?', 'No. El entrenador o gimnasio paga la licencia y habilita a sus alumnos dentro del cupo de su plan. Las asesorías personales de Nahuel son un servicio distinto y ya incluyen el acceso a la app.'],
            ['¿Puedo probar el sistema antes de pagar?', 'Los planes de software incluyen 14 días de prueba sin tarjeta. Al terminar, elegís si querés contratar. La prueba no genera un débito automático.'],
            ['¿Qué puedo registrar durante el entrenamiento?', 'Podés consultar la rutina asignada y registrar series, repeticiones y cargas. También hay registro de cardio, pasos y progreso. Los cálculos de gasto energético son estimaciones.'],
            ['¿El sistema crea y asigna rutinas sin revisión?', 'No. El profesional decide la planificación y revisa cualquier borrador generado con ayuda de IA. Las funciones de IA que usan datos personales requieren autorización separada.'],
            ['¿La asesoría incluye una dieta?', 'La asesoría publicada se centra en entrenamiento. Un plan de alimentación personalizado requiere un profesional de nutrición y un acuerdo específico.'],
            ['¿Cómo se paga y cómo cancelo?', 'Podés abonar por transferencia u otro medio acordado. En Mi suscripción ves las instrucciones y el período confirmado después de verificar el ingreso. No hay débito automático por un cobro manual. Si se habilita Mercado Pago, su renovación requiere tu autorización. Podés dar de baja la continuidad desde tu cuenta y también tenés enlaces públicos de baja y arrepentimiento.'],
            ['¿Es un sistema de caja o de molinetes?', 'La propuesta actual se enfoca en rutinas, seguimiento, comunicación y experiencia del alumno. Consultanos por necesidades particulares antes de contratar; la integración con molinetes o la facturación fiscal no se ofrecen como funciones incluidas.'],
            ['¿Qué sucede si necesito más alumnos?', 'El panel muestra tu cupo. Podés solicitar el cambio de plan; se informa el importe y la fecha antes de confirmarlo. No se borran tus alumnos al alcanzar el límite.'],
        ];
    }

    public static function guides(): array
    {
        return [
            'como-registrar-tu-progreso' => [
                'title' => 'Cómo registrar tu progreso en el gimnasio',
                'description' => 'Qué anotar en cada serie y cómo usar el historial para conversar con tu entrenador.',
                'sections' => [
                    ['Anotá lo que realmente hiciste', 'Registrá el ejercicio o la variante, las series, las repeticiones y la carga que utilizaste. Si reemplazaste una máquina, dejá asentado el cambio: dos variantes diferentes no siempre se pueden comparar directamente.'],
                    ['Compará sesiones parecidas', 'Más carga con menos repeticiones no demuestra por sí sola una mejora. Mirá varias sesiones, compará el mismo ejercicio y tené en cuenta las indicaciones de técnica y esfuerzo de tu profesional.'],
                    ['Dale contexto al seguimiento', 'Una sesión aislada no cuenta toda la historia. Compartí con tu entrenador las dificultades, descansos o cambios de rutina para que pueda interpretar el registro y decidir los ajustes.'],
                ],
            ],
            'organizar-rutinas-de-tus-alumnos' => [
                'title' => 'Cómo organizar las rutinas de tus alumnos',
                'description' => 'Un flujo práctico para planificar, asignar y revisar entrenamientos sin perder el historial.',
                'sections' => [
                    ['Partí de una base y personalizala', 'Las plantillas ahorran trabajo repetido. Revisá la selección de ejercicios, el equipo disponible, el nivel de experiencia y los objetivos antes de asignar una rutina.'],
                    ['Separá planificación de ejecución', 'La rutina indica el trabajo previsto; el registro muestra lo realizado. Conservá ambos para que los cambios posteriores no alteren lo que el alumno ya anotó.'],
                    ['Reservá un momento para revisar', 'Acordá una frecuencia de revisión y un canal de consultas. Un registro claro ayuda a dedicar el tiempo a las decisiones del entrenamiento en lugar de buscar mensajes dispersos.'],
                ],
            ],
        ];
    }

    public static function pages(): array
    {
        return [
            'home' => ['path' => '/', 'title' => 'Rutinas, seguimiento y asesorías online | VisionFit', 'description' => 'Entrená con un plan y seguí tu progreso. VisionFit conecta alumnos, entrenadores y gimnasios con rutinas, registros y asesorías online.'],
            'personal' => ['path' => '/asesorias-online', 'title' => 'Asesorías de entrenamiento online | VisionFit', 'description' => 'Planificación personalizada y seguimiento de entrenamiento con Nahuel. Conocé las modalidades, qué incluyen y cómo empezar.'],
            'coach' => ['path' => '/software-entrenadores-personales', 'title' => 'Software para entrenadores personales en Argentina | VisionFit', 'description' => 'Organizá rutinas y acompañá el progreso de tus alumnos con VisionFit. Planes para entrenadores y prueba de 14 días sin tarjeta.'],
            'gym' => ['path' => '/software-gimnasios', 'title' => 'App de rutinas y seguimiento para gimnasios | VisionFit', 'description' => 'Una app para las rutinas, los profesores y el progreso de tus alumnos. Conocé los planes de VisionFit para gimnasios.'],
            'plans' => ['path' => '/planes', 'title' => 'Planes y precios en pesos argentinos | VisionFit', 'description' => 'Compará los planes de VisionFit para entrenadores y gimnasios, y las asesorías personales. Precios mensuales en pesos argentinos.'],
            'faq' => ['path' => '/preguntas-frecuentes', 'title' => 'Preguntas frecuentes sobre VisionFit', 'description' => 'Respuestas sobre la app, rutinas, asesorías, prueba gratuita, alumnos, cobros y cancelación de VisionFit.'],
            'resources' => ['path' => '/recursos', 'title' => 'Recursos sobre rutinas y seguimiento | VisionFit', 'description' => 'Guías prácticas para registrar tu progreso y organizar las rutinas de tus alumnos con una planificación clara.'],
            'contact' => ['path' => '/contacto', 'title' => 'Contacto y demostración de VisionFit', 'description' => 'Consultá por una asesoría o coordiná una demostración de VisionFit para tu gimnasio o servicio de entrenamiento.'],
        ];
    }
}
