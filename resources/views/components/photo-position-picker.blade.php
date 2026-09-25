@props([
    'name',
    'positionXName',
    'positionYName',
    'initialX' => 50,
    'initialY' => 50,
    'existingUrl' => null,
    'ratio' => '0.84',
    'required' => false,
    'livePreviewTarget' => null,
])

<div x-data="photoPositioner({{ (int) $initialX }}, {{ (int) $initialY }}, {{ $existingUrl ? "'".addslashes($existingUrl)."'" : 'null' }})"
    @if ($livePreviewTarget)
        x-effect="$dispatch('card-photo-update', { target: '{{ $livePreviewTarget }}', url: photoUrl, posX: posX, posY: posY })"
    @endif
>
    <div class="flex items-start gap-4">
        <div
            x-ref="box"
            class="relative shrink-0 w-24 rounded-md border-2 border-dashed border-gray-300 bg-gray-50 overflow-hidden select-none touch-none"
            style="aspect-ratio: {{ $ratio }};"
            :class="photoUrl ? 'cursor-move' : ''"
            @mousedown="startDrag($event)"
            @mousemove.window="onDrag($event)"
            @mouseup.window="endDrag()"
            @touchstart="startDrag($event)"
            @touchmove.window="onDrag($event)"
            @touchend.window="endDrag()"
        >
            <img x-show="photoUrl" x-cloak :src="photoUrl" alt="Aperçu" class="w-full h-full object-cover pointer-events-none"
                :style="`object-position: ${posX}% ${posY}%`">
            <svg x-show="!photoUrl" class="w-8 h-8 text-gray-300 absolute inset-0 m-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <div x-show="photoUrl" x-cloak class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[8px] leading-tight text-center py-0.5 pointer-events-none">
                Glisser pour ajuster
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <input type="file" name="{{ $name }}" accept="image/*" {{ $required ? 'required' : '' }}
                x-ref="fileInput"
                @change="onFileChange($event)"
                class="block min-w-0 w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
            <p class="text-xs text-gray-500 mt-1" x-show="photoUrl" x-cloak>
                Glissez la photo dans le cadre pour bien cadrer le visage, puis enregistrez.
            </p>
            <div class="flex items-center gap-3 mt-1" x-show="photoUrl" x-cloak>
                <button type="button" @click="reset()" class="text-xs text-indigo-600 hover:text-indigo-800">
                    Recentrer
                </button>
                <button type="button" @click="clear()" class="text-xs text-red-600 hover:text-red-800">
                    ✕ Retirer la photo
                </button>
            </div>
        </div>
    </div>
    <input type="hidden" name="{{ $positionXName }}" :value="Math.round(posX)">
    <input type="hidden" name="{{ $positionYName }}" :value="Math.round(posY)">
</div>
