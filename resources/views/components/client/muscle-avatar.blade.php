@props([
    'primary' => [],
    'secondary' => [],
    'label' => 'Mapa muscular',
    'available' => true,
    'storageKey' => 'visionfit-avatar:guest',
    'level' => 1,
    'inheritState' => false,
    'compact' => false,
    'showControls' => true,
])

<div
    {{ $attributes->class(['vf-muscle-avatar', 'vf-muscle-avatar--compact' => $compact]) }}
    @unless($inheritState)
        x-data="visionFitExperience({ storageKey: @js($storageKey), level: @js($level), initialMuscleMap: @js(['primary' => $primary, 'secondary' => $secondary, 'label' => $label, 'available' => $available]) })"
    @endunless
    :data-theme="theme"
    x-id="['muscle-map']"
>
    @if($showControls)
        <div class="vf-avatar-toolbar">
            <span class="vf-eyebrow">Mapa muscular <span class="vf-live-dot" aria-hidden="true"></span></span>
            <div class="vf-segmented" role="group" aria-label="Vista del personaje">
                <button type="button" :aria-pressed="view === 'front'" :class="{ 'is-selected': view === 'front' }" @click="setView('front')">Frente</button>
                <button type="button" :aria-pressed="view === 'back'" :class="{ 'is-selected': view === 'back' }" @click="setView('back')">Espalda</button>
            </div>
        </div>
    @endif

    <div class="vf-avatar-stage">
        <span class="vf-stage-coordinate vf-stage-coordinate--top" aria-hidden="true">VF / HUMAN FRAME</span>
        <span class="vf-stage-coordinate vf-stage-coordinate--bottom" aria-hidden="true" x-text="view === 'front' ? '01 — ANTERIOR' : '02 — POSTERIOR'">01 — ANTERIOR</span>
        <div class="vf-avatar-orbit" aria-hidden="true"></div>

        <svg class="vf-avatar-figure" viewBox="0 0 300 480" role="img" :aria-labelledby="$id('muscle-map')" :data-avatar="avatar" focusable="false">
            <title :id="$id('muscle-map')" x-text="'Mapa muscular de ' + mapLabel + ', vista ' + (view === 'front' ? 'frontal' : 'posterior')">Mapa muscular</title>
            <ellipse class="vf-avatar-ground" cx="150" cy="456" rx="77" ry="10"/>
            <path class="vf-avatar-centerline" d="M150 5V469"/>

            <g x-show="view === 'front'" class="vf-avatar-body">
                {{-- A stylized original mannequin. Each plate follows one anatomical group. --}}
                <path class="vf-avatar-neck" d="m137 73-3 22-19 10 35 26 35-26-19-10-3-22Z"/>
                <path class="vf-avatar-shell" d="M112 102 92 109 79 131 72 174 59 213 50 257 54 270 64 269 76 232 87 209 96 175 108 148 116 187 111 224 106 252 110 305 109 339 115 389 116 430 105 441 108 452 136 452 141 442 140 391 149 339 150 296 151 339 160 391 159 442 164 452 192 452 195 441 184 430 185 389 191 339 190 305 194 252 189 224 184 187 192 148 204 175 213 209 224 232 236 269 246 270 250 257 241 213 228 174 221 131 208 109 188 102 168 95 150 110 132 95Z"/>

                <g class="vf-avatar-head">
                    <path class="vf-head-generic" d="m133 29 17-6 17 6 9 17-3 27-13 14h-20l-13-14-3-27Z"/>
                    <path class="vf-head-male" d="m132 29 18-5 18 5 8 18-3 25-11 17h-24l-11-17-3-25Z"/>
                    <path class="vf-head-female" d="M127 46q0-22 23-22t23 22l-2 25-12 17h-18l-12-17Z"/>
                    <path class="vf-avatar-face" d="m132 49 18 4 18-4-2 11-16 6-16-6Z"/>
                    <path class="vf-avatar-visor" d="m134 52 16 3 16-3"/>
                    <path class="vf-avatar-detail" d="m143 76 7 2 7-2M150 27v12"/>
                </g>

                <g class="vf-avatar-torso">
                    <path class="vf-muscle" :class="muscleClass('chest')" d="m112 111 35 3v32l-30 8-12-14Z"/>
                    <path class="vf-muscle" :class="muscleClass('chest')" d="m188 111-35 3v32l30 8 12-14Z"/>
                    <path class="vf-avatar-sternum" d="M150 113v45m-26 1 26 8 26-8"/>
                    <g class="vf-muscle" :class="muscleClass('abs')">
                        <path d="m130 161 17 7v16l-18-4Zm23 7 17-7 1 19-18 4Z"/>
                        <path d="m129 185 18 4v17l-17-4Zm24 4 18-4-1 17-17 4Z"/>
                        <path d="m130 207 17 4v18l-15-8Zm23 4 17-4-2 14-15 8Z"/>
                    </g>
                    <path class="vf-avatar-oblique" d="m116 161 9 5-1 43 10 24-17-13-3-28Zm68 0-9 5 1 43-10 24 17-13 3-28Z"/>
                    <path class="vf-avatar-pelvis" d="m116 227 20 12h28l20-12 2 23-16 26-20-14-20 14-16-26Z"/>
                    <path class="vf-avatar-core" d="m145 236 5-3 5 3v12l-5 3-5-3Z"/>
                </g>

                @foreach([false, true] as $mirror)
                    <g @if($mirror) transform="translate(300 0) scale(-1 1)" @endif>
                        <path class="vf-muscle vf-avatar-shoulder" :class="muscleClass('shoulders')" d="m94 113 15-7-7 30-12 16-9-8 5-19Z"/>
                        <path class="vf-muscle" :class="muscleClass('biceps')" d="m88 153 10-9-4 29-8 27-10 7-1-12 6-27Z"/>
                        <path class="vf-muscle" :class="muscleClass('forearms')" d="m73 212 11-7-9 28-11 25-6-2 5-26Z"/>
                        <path class="vf-avatar-joint" d="m55 264 8 2-4 14-6 3-4-6Z"/>
                        <path class="vf-muscle" :class="muscleClass('quadriceps')" d="m115 258 15 19 13-7-2 45-12 22-12-5-4-32Z"/>
                        <path class="vf-avatar-knee" d="m117 341 11 2 9-5-3 15-8 7-8-6Z"/>
                        <path class="vf-avatar-shin" d="m119 362 9 5 5-5-2 31 3 35-12 2-2-36Z"/>
                        <path class="vf-avatar-boot" d="m120 434 14-1 2 13h-24l-2-5Z"/>
                        <path class="vf-avatar-detail" d="m113 287 8 9m-1 86 7-4m-57-145 6-2"/>
                    </g>
                @endforeach
            </g>

            <g x-show="view === 'back'" x-cloak class="vf-avatar-body">
                <path class="vf-avatar-neck" d="m137 73-3 22-19 10 35 26 35-26-19-10-3-22Z"/>
                <path class="vf-avatar-shell" d="M112 102 92 109 79 131 72 174 59 213 50 257 54 270 64 269 76 232 87 209 96 175 108 148 116 187 111 224 106 252 110 305 109 339 115 389 116 430 105 441 108 452 136 452 141 442 140 391 149 339 150 296 151 339 160 391 159 442 164 452 192 452 195 441 184 430 185 389 191 339 190 305 194 252 189 224 184 187 192 148 204 175 213 209 224 232 236 269 246 270 250 257 241 213 228 174 221 131 208 109 188 102 168 95 150 110 132 95Z"/>
                <g class="vf-avatar-head">
                    <path class="vf-head-generic" d="m133 29 17-6 17 6 9 17-3 27-13 14h-20l-13-14-3-27Z"/>
                    <path class="vf-head-male" d="m132 29 18-5 18 5 8 18-3 25-11 17h-24l-11-17-3-25Z"/>
                    <path class="vf-head-female" d="M127 46q0-22 23-22t23 22l-2 25-12 17h-18l-12-17Z"/>
                    <path class="vf-avatar-detail" d="M150 29v49m-15-36 15 6 15-6m-32 21 17 5 17-5"/>
                    <path class="vf-avatar-visor" d="M142 76h16"/>
                </g>
                <g class="vf-avatar-torso">
                    <path class="vf-muscle" :class="muscleClass('back')" d="m132 102 15 11v55l-19-9-20-34 6-14Zm36 0-15 11v55l19-9 20-34-6-14Z"/>
                    <path class="vf-muscle" :class="muscleClass('back')" d="m113 149 15 15 19 11v44l-20-11-11-22Zm74 0-15 15-19 11v44l20-11 11-22Z"/>
                    <path class="vf-avatar-sternum" d="M150 105v124m-9-86h18m-18 12h18m-18 12h18m-18 12h18m-18 12h18"/>
                    <path class="vf-avatar-pelvis" d="m116 216 20 8h28l20-8 2 24-36 24-36-24Z"/>
                    <path class="vf-muscle" :class="muscleClass('glutes')" d="m116 237 19-6 12 8v33l-18 8-16-18Zm68 0-19-6-12 8v33l18 8 16-18Z"/>
                </g>
                @foreach([false, true] as $mirror)
                    <g @if($mirror) transform="translate(300 0) scale(-1 1)" @endif>
                        <path class="vf-muscle vf-avatar-shoulder" :class="muscleClass('shoulders')" d="m94 113 15-7-7 30-12 16-9-8 5-19Z"/>
                        <path class="vf-muscle" :class="muscleClass('triceps')" d="m88 153 10-9-4 29-8 27-10 7-1-12 6-27Z"/>
                        <path class="vf-muscle" :class="muscleClass('forearms')" d="m73 212 11-7-9 28-11 25-6-2 5-26Z"/>
                        <path class="vf-avatar-joint" d="m55 264 8 2-4 14-6 3-4-6Z"/>
                        <path class="vf-muscle" :class="muscleClass('hamstrings')" d="m115 273 15 12 13-6-2 36-12 22-12-5-4-32Z"/>
                        <path class="vf-avatar-knee" d="m117 341 11 2 9-5-3 12-8 5-8-3Z"/>
                        <path class="vf-muscle" :class="muscleClass('calves')" d="m118 358 10 2 8-4 1 20-7 27-9-7-4-20Z"/>
                        <path class="vf-avatar-shin" d="m123 401 7 5 3-4 1 26-12 2Z"/>
                        <path class="vf-avatar-boot" d="m120 434 14-1 2 13h-24l-2-5Z"/>
                    </g>
                @endforeach
            </g>
        </svg>
        <span class="vf-avatar-marker vf-avatar-marker--left" aria-hidden="true"><i></i> <span x-text="view === 'front' ? 'FRENTE' : 'ESPALDA'">FRENTE</span></span>
        <span class="vf-avatar-marker vf-avatar-marker--right" aria-hidden="true">2D <i></i></span>
    </div>

    <div class="vf-map-legend" aria-label="Referencias del mapa muscular">
        <span><i class="vf-legend-primary" aria-hidden="true"></i> Principal</span>
        <span><i class="vf-legend-secondary" aria-hidden="true"></i> Secundario</span>
    </div>
    <p class="vf-map-unavailable" x-show="!mapAvailable" @if($available && count($primary) + count($secondary) > 0) x-cloak @endif>Este ejercicio todavía no tiene un mapa muscular. Tus series se registran igual.</p>
</div>
