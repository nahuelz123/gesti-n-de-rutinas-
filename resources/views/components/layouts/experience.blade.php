@props(['isDemo' => false, 'displayName' => 'Atleta'])

<x-layouts.client :is-demo="$isDemo" :display-name="$displayName" :is-character-page="true">
    {{ $slot }}
</x-layouts.client>
