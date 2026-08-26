<x-layouts.client>

<div class="rw" x-data="{ openModal: false, mediaType: '', mediaUrl: '', modalTitle: '' }"
     @open-tutorial.window="openModal = true; mediaType = $event.detail.type; mediaUrl = $event.detail.url; modalTitle = $event.detail.title">

    <a class="back-link" href="{{ route('client.routines.history') }}">← Volver</a>

    <p class="pg-label">Detalle de rutina</p>
    <h1 class="pg-title">{{ $assignment->routine->title }}</h1>

    @if ($assignment->routine->description)
        <div class="routine-card">
            <div class="routine-card-desc">{{ $assignment->routine->description }}</div>
        </div>
    @endif

    <div class="routine-card" style="margin-bottom:12px;">
        <div class="routine-card-desc">
            Asignada el {{ $assignment->assigned_at?->format('d/m/Y') }} &nbsp;·&nbsp;
            <span class="status-badge {{ $assignment->status === 'active' ? 'status-active' : 'status-inactive' }}">
                {{ $assignment->status }}
            </span>
        </div>
    </div>
    
    <div style="margin-bottom: 24px;">
        <form action="{{ route('client.routines.replay', $assignment) }}" method="POST">
            @csrf
            <button type="submit" class="client-btn client-btn-primary" style="width:100%; min-height:44px;">
                Realizar rutina
            </button>
        </form>
    </div>

    @foreach ($assignment->routine->days as $day)
        <div class="day-block">
            <div class="day-header">
                <span class="day-badge">Día {{ $day->day_number }}</span>
                <span class="day-title">{{ $day->title }}</span>
            </div>

            @foreach ($day->exercises->sortBy('order') as $dx)
                <div class="ex-card">
                    <div class="ex-top">
                        <div class="ex-top-row">
                            <div class="ex-info" style="width:100%;">
                                <div class="ex-row">
                                    <div class="ex-name">{{ $dx->exercise->title }}</div>
                                </div>
                                <div class="ex-meta">
                                    <span class="ex-meta-item">Series <b>{{ $dx->sets }}</b></span>
                                    <span class="ex-meta-item">Reps <b>{{ $dx->reps }}</b></span>
                                    @if ($dx->rest)
                                        <span class="ex-meta-item">Descanso <b>{{ $dx->rest }}</b></span>
                                    @endif
                                </div>
                                @if ($dx->notes)
                                    <div class="ex-notes">{{ $dx->notes }}</div>
                                @endif
                                
                                <div style="display:flex; gap:10px; margin-top:10px;">
                                    @if ($dx->exercise->gif_url || $dx->exercise->video_url)
                                        @php
                                            $mType = $dx->exercise->video_url ? 'video' : 'gif';
                                            $mUrl = $dx->exercise->video_url ?? $dx->exercise->gif_url;
                                        @endphp
                                        <button type="button" 
                                            @click="$dispatch('open-tutorial', { type: '{{ $mType }}', url: '{{ $mUrl }}', title: '{{ addslashes($dx->exercise->title) }}' })" 
                                            class="btn-video" style="padding:6px 12px; flex:1; justify-content:center; display:flex;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                            Ver tutorial
                                        </button>
                                    @endif
                                    
                                    <a class="ex-link" href="{{ route('client.progress.exercise', $dx->exercise_id) }}" 
                                       style="padding:6px 12px; flex:1; justify-content:center; display:flex; align-items:center; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:var(--clr-text); font-size:12px; font-weight:600; text-decoration:none;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                                        Ver progreso
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- Tutorial Modal Alpine --}}
    <div x-show="openModal" 
         style="display:none;" 
         class="modal-overlay" 
         :class="{ 'open': openModal }"
         @click.self="openModal = false; mediaUrl = ''">
        
        <div class="modal-box" style="background: var(--clr-card); border: 1px solid var(--clr-border);">
            <div class="modal-header" style="border-bottom: 1px solid var(--clr-border);">
                <span class="modal-title" x-text="modalTitle" style="color:var(--clr-text);"></span>
                <button class="modal-close" @click="openModal = false; mediaUrl = ''" style="color:var(--clr-text-muted);">✕</button>
            </div>
            <div class="modal-body" style="padding:0; background:#000;">
                <template x-if="mediaType === 'video' && mediaUrl">
                    <iframe :src="'https://www.youtube.com/embed/' + (mediaUrl.match(/(?:v=|youtu\.be\/)([^&?\/]+)/) ? mediaUrl.match(/(?:v=|youtu\.be\/)([^&?\/]+)/)[1] : '') + '?autoplay=1'" allowfullscreen style="width:100%; aspect-ratio:16/9; border:none; display:block;"></iframe>
                </template>
                <template x-if="mediaType === 'gif' && mediaUrl">
                    <img :src="mediaUrl" style="width:100%; max-height:70vh; object-fit:contain; display:block; margin:0 auto;" />
                </template>
            </div>
        </div>
    </div>

</div>

</x-layouts.client>
