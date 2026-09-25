<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Élèves
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('importErrors') && count(session('importErrors')))
                <div class="mb-4 p-4 rounded-md bg-amber-50 text-amber-800 text-sm">
                    <p class="font-medium mb-1">Lignes ignorées :</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach (session('importErrors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-700 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 sm:px-6 py-4 text-left">
                        <span class="text-sm font-medium text-gray-900">Importer des élèves (CSV ou PDF)</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="px-4 sm:px-6 pb-6 border-t border-gray-100 pt-4">
                        <a href="{{ route('students.import.template') }}" class="inline-block text-sm text-indigo-600 hover:text-indigo-900 mb-1">
                            Télécharger le modèle CSV
                        </a>
                        <p class="text-xs text-gray-500 mb-4">Colonnes : nom, prénom, date de naissance, classe, téléphone, et jusqu'à 3 accompagnateurs (nom/prénom).</p>

                        <form method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" class="space-y-4"
                            x-data="{
                                fileName: '',
                                photoNames: [],
                                removePhoto(index) {
                                    const dt = new DataTransfer();
                                    Array.from(this.$refs.photosInput.files).forEach((f, i) => { if (i !== index) dt.items.add(f); });
                                    this.$refs.photosInput.files = dt.files;
                                    this.photoNames.splice(index, 1);
                                },
                            }"
                        >
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">1. Liste des élèves (CSV ou PDF)</label>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <input type="file" name="file" accept=".csv,text/csv,.pdf,application/pdf" required
                                        x-ref="fileInput"
                                        @change="fileName = $event.target.files[0]?.name || ''"
                                        class="block text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <button type="button" x-show="fileName" x-cloak
                                        @click="$refs.fileInput.value = ''; fileName = '';"
                                        class="text-xs font-medium text-red-600 hover:text-red-800">
                                        ✕ Retirer
                                    </button>
                                </div>
                                <p x-show="fileName" x-cloak class="mt-1 text-xs text-gray-500">
                                    Fichier sélectionné : <span class="font-medium" x-text="fileName"></span> — cliquez sur "Choisir un fichier" pour le remplacer, ou "Retirer" pour l'enlever.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">2. Photos de la classe (optionnel, plusieurs fichiers)</label>
                                <input type="file" name="photos[]" accept="image/*" multiple
                                    x-ref="photosInput"
                                    @change="photoNames = Array.from($event.target.files).map(f => f.name)"
                                    class="block text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">
                                    Chaque photo est attribuée en priorité à l'élève dont le nom apparaît dans le nom du fichier
                                    (ex : <span class="font-mono">KOUASSI_Aya.jpg</span> ou <span class="font-mono">Aya KOUASSI.jpg</span>) —
                                    l'ordre de sélection n'a pas d'importance pour celles-là. S'il reste ensuite autant de photos non reconnues
                                    que d'élèves encore sans photo, elles sont attribuées <strong>dans l'ordre</strong> (même position que dans la liste importée).
                                    En cas de nombres différents, rien n'est deviné au hasard : les photos concernées sont signalées.
                                </p>

                                <ul x-show="photoNames.length > 0" x-cloak class="mt-2 space-y-1 max-w-md">
                                    <template x-for="(name, index) in photoNames" :key="index + '-' + name">
                                        <li class="flex items-center justify-between gap-2 text-xs bg-gray-50 border border-gray-100 rounded px-2 py-1">
                                            <span class="truncate" x-text="name"></span>
                                            <button type="button" @click="removePhoto(index)" class="shrink-0 text-red-600 hover:text-red-800 font-medium">✕</button>
                                        </li>
                                    </template>
                                </ul>
                                <button type="button" x-show="photoNames.length > 0" x-cloak
                                    @click="$refs.photosInput.value = ''; photoNames = [];"
                                    class="mt-1 text-xs font-medium text-red-600 hover:text-red-800">
                                    ✕ Tout retirer
                                </button>
                            </div>

                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none transition">
                                Importer la classe
                            </button>
                        </form>
                    </div>
                </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('dashboard') }}" data-autosubmit="450" class="p-4 sm:p-6 border-b border-gray-200 flex flex-wrap items-end gap-4 transition-opacity">
                    <div class="flex-1 min-w-[200px] relative">
                        <label for="q" class="block text-xs font-medium text-gray-500 uppercase mb-1">Rechercher un élève</label>
                        <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Nom ou prénom..." autocomplete="off"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-[11px] text-gray-400">La recherche se lance automatiquement pendant la frappe.</p>
                    </div>

                    <div class="min-w-[220px]">
                        <label for="class_id" class="block text-xs font-medium text-gray-500 uppercase mb-1">Classe</label>
                        <select id="class_id" name="class_id"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>
                                    {{ $class->level }}{{ $class->section ? ' '.$class->section : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-[160px]">
                        <label for="status" class="block text-xs font-medium text-gray-500 uppercase mb-1">Statut</label>
                        <select id="status" name="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les statuts</option>
                            <option value="active" @selected(request('status') === 'active')>Actif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactif</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none transition">
                            Filtrer
                        </button>
                        @if (request('q') || request('class_id') || request('status'))
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
                        @endif
                    </div>
                </form>

                <div class="px-4 sm:px-6 py-2 bg-gray-50 flex items-center justify-between flex-wrap gap-2">
                    <span class="text-sm text-gray-500">
                        {{ $students->total() }} élève{{ $students->total() > 1 ? 's' : '' }}
                        @if ($students->lastPage() > 1)
                            — page {{ $students->currentPage() }} / {{ $students->lastPage() }}
                        @endif
                    </span>
                    @if (request('class_id') && $students->total() > 0)
                        <a href="{{ route('classes.cards', ['schoolClass' => request('class_id'), 'side' => 'recto']) }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                            PDF Recto (classe)
                        </a>
                        <a href="{{ route('classes.cards', ['schoolClass' => request('class_id'), 'side' => 'verso']) }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                            PDF Verso (classe)
                        </a>
                    @endif
                </div>

                {{-- Vue mobile : cartes empilées, plus lisibles qu'un tableau qui déborde --}}
                <div class="sm:hidden divide-y divide-gray-100">
                    @forelse ($students as $student)
                        <div class="p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $student->last_name }} {{ $student->first_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->schoolClass->level }}{{ $student->schoolClass->section ? ' '.$student->schoolClass->section : '' }}</div>
                                </div>
                                @if ($student->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactif</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('students.card.preview', $student) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Voir</a>
                                @if (Auth::user()->hasFullAccess() || Auth::user()->isSecretaire())
                                    <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">Modifier</a>
                                @endif
                                <a href="{{ route('students.card', $student) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition">Carte PDF</a>
                                @if (Auth::user()->hasFullAccess())
                                    <form method="POST" action="{{ route('students.toggle-status', $student) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition">{{ $student->status === 'active' ? 'Désactiver' : 'Activer' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline" onsubmit="return confirm('Supprimer définitivement la carte de {{ $student->first_name }} {{ $student->last_name }} ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">Aucun élève ne correspond à cette recherche.</div>
                    @endforelse
                </div>

                <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            @if (Auth::user()->hasFullAccess())
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Déplacer vers</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $student->last_name }} {{ $student->first_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $student->schoolClass->level }}{{ $student->schoolClass->section ? ' '.$student->schoolClass->section : '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if ($student->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <a href="{{ route('students.card.preview', $student) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            Voir
                                        </a>
                                        @if (Auth::user()->hasFullAccess() || Auth::user()->isSecretaire())
                                            <a href="{{ route('students.edit', $student) }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                                Modifier
                                            </a>
                                        @endif
                                        <a href="{{ route('students.card', $student) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            Carte PDF
                                        </a>
                                        @if (Auth::user()->hasFullAccess())
                                            <form method="POST" action="{{ route('students.toggle-status', $student) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" /></svg>
                                                    {{ $student->status === 'active' ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline"
                                                onsubmit="return confirm('Supprimer définitivement la carte de {{ $student->first_name }} {{ $student->last_name }} ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                @if (Auth::user()->hasFullAccess())
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" action="{{ route('students.update', $student) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="last_name" value="{{ $student->last_name }}">
                                            <input type="hidden" name="first_name" value="{{ $student->first_name }}">
                                            <input type="hidden" name="birth_date" value="{{ $student->birth_date?->format('Y-m-d') }}">
                                            <input type="hidden" name="parent_phone" value="{{ $student->parent_phone }}">
                                            <select name="school_class_id" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach ($classes as $class)
                                                    <option value="{{ $class->id }}" @selected($class->id === $student->school_class_id)>
                                                        {{ $class->level }}{{ $class->section ? ' '.$class->section : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Déplacer</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Aucun élève ne correspond à cette recherche.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                @if ($students->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
                        {{ $students->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
