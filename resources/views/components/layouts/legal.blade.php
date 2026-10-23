@props(['title'])
@php($meta = ['title' => $title.' | VisionFit', 'description' => 'Información de VisionFit sobre privacidad, uso de datos y condiciones del servicio.', 'path' => request()->getPathInfo()])
<x-layouts.marketing :meta="$meta" :noindex="request()->has('gym')">
    <div class="m-wrap m-reading m-guide">
        <span class="m-eyebrow">INFORMACIÓN VISIONFIT</span>
        <h1>{{ $title }}</h1>
        <article class="m-legal">{{ $slot }}</article>
    </div>
</x-layouts.marketing>
