@php($routine = \App\Support\CommercialCatalog::oneOffRoutine())
<div class="m-note m-one-off">
    <h3>¿Buscás una planificación puntual?</h3>
    <p>Rutina personalizada. Incluye evaluación inicial y planificación de cuatro semanas.</p>
    @include('marketing.price', ['item' => $routine, 'frequency' => '· pago único'])
    <a href="{{ url('/contacto?plan=rutina-puntual') }}">Consultar disponibilidad →</a>
</div>
