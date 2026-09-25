<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Photos par lot — {{ $schoolClass->level }}{{ $schoolClass->section ? ' '.$schoolClass->section : '' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-md bg-red-50 text-red-700 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg card-hover p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Sélectionnez plusieurs photos à la fois : la 1<sup>ère</sup> photo sera attribuée au 1<sup>er</sup> élève de la liste ci-dessous, la 2<sup>ème</sup> au 2<sup>ème</sup>, etc.
                    Nommez ou triez vos fichiers dans cet ordre avant de les sélectionner.
                </p>

                <form method="POST" action="{{ route('students.photos.bulk-store', $schoolClass) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-4">
                    @csrf
                    <input type="file" name="photos[]" accept="image/*" multiple required
                        class="block text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none transition">
                        Attribuer dans l'ordre
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg card-hover">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($students as $index => $student)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $index + 1 }}</td>
                                <td class="px-6 py-3">
                                    @if ($student->photo_path)
                                        <div x-data="{
                                                posX: {{ (int) ($student->photo_position_x ?? 50) }},
                                                posY: {{ (int) ($student->photo_position_y ?? 50) }},
                                                dragging: false, startX: 0, startY: 0, startPosX: 0, startPosY: 0,
                                                saving: false, saved: false,
                                                point(e) { return e.touches && e.touches.length ? e.touches[0] : e; },
                                                startDrag(e) {
                                                    e.preventDefault(); this.dragging = true;
                                                    const p = this.point(e);
                                                    this.startX = p.clientX; this.startY = p.clientY;
                                                    this.startPosX = this.posX; this.startPosY = this.posY;
                                                },
                                                onDrag(e) {
                                                    if (!this.dragging) return;
                                                    const p = this.point(e);
                                                    const rect = this.$refs.box.getBoundingClientRect();
                                                    const dx = p.clientX - this.startX; const dy = p.clientY - this.startY;
                                                    this.posX = Math.min(100, Math.max(0, this.startPosX - (dx / rect.width) * 100));
                                                    this.posY = Math.min(100, Math.max(0, this.startPosY - (dy / rect.height) * 100));
                                                },
                                                endDrag() { if (!this.dragging) return; this.dragging = false; this.save(); },
                                                recenter() { this.posX = 50; this.posY = 50; this.save(); },
                                                save() {
                                                    this.saving = true; this.saved = false;
                                                    fetch('{{ route('students.photo-position', $student) }}', {
                                                        method: 'PATCH',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                            Accept: 'application/json',
                                                        },
                                                        body: JSON.stringify({ photo_position_x: Math.round(this.posX), photo_position_y: Math.round(this.posY) }),
                                                    })
                                                        .then((r) => { if (!r.ok) throw new Error('save failed'); this.saving = false; this.saved = true; setTimeout(() => (this.saved = false), 1500); })
                                                        .catch(() => { this.saving = false; alert('Erreur lors de l\'enregistrement.'); });
                                                },
                                            }"
                                        >
                                            <div x-ref="box"
                                                class="relative w-[123px] h-[150px] rounded-md border overflow-hidden select-none touch-none cursor-move"
                                                :style="`background-image:url('{{ \Illuminate\Support\Facades\Storage::url($student->photo_path) }}'); background-size:cover; background-repeat:no-repeat; background-position: ${posX}% ${posY}%`"
                                                @mousedown="startDrag($event)" @mousemove.window="onDrag($event)" @mouseup.window="endDrag()"
                                                @touchstart="startDrag($event)" @touchmove.window="onDrag($event)" @touchend.window="endDrag()"
                                            >
                                                <div x-show="saving || saved" x-cloak class="absolute bottom-0 inset-x-0 text-white text-[10px] text-center py-0.5 pointer-events-none"
                                                    :class="saved ? 'bg-green-600/80' : 'bg-black/50'">
                                                    <span x-show="saving">Enregistrement...</span>
                                                    <span x-show="saved" x-cloak>Enregistré &check;</span>
                                                </div>
                                            </div>
                                            <button type="button" @click="recenter()" class="text-xs text-indigo-600 hover:text-indigo-900 mt-1">Recentrer</button>
                                        </div>
                                    @else
                                        <div class="w-[123px] h-[150px] rounded-md border-2 border-dashed border-gray-300 bg-gray-50"></div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $student->last_name }} {{ $student->first_name }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('students.edit', $student) }}" class="text-xs text-indigo-600 hover:text-indigo-900">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Aucun élève dans cette classe.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
