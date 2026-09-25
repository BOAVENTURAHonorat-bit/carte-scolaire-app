<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajouter un élève
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start"
                x-data="cardServerPreview(@js(route('students.preview-card')))"
                x-init="refresh()"
                @input="schedule()"
                @change="schedule()"
                @card-photo-update.window="schedule()"
            >
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg card-hover p-6">

                    @if (session('status'))
                        <div class="mb-4 p-4 rounded-md bg-green-50 text-green-700 text-sm">
                            {{ session('status') }}
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

                    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-6" data-card-form>
                        @csrf

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Informations de l'élève (recto)</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="last_name" value="Nom" />
                                    <x-text-input id="last_name" name="last_name" class="block mt-1 w-full" :value="old('last_name')" required />
                                </div>
                                <div>
                                    <x-input-label for="first_name" value="Prénom(s)" />
                                    <x-text-input id="first_name" name="first_name" class="block mt-1 w-full" :value="old('first_name')" required />
                                </div>
                                <div>
                                    <x-input-label for="birth_date" value="Date de naissance" />
                                    <x-text-input id="birth_date" name="birth_date" type="date" class="block mt-1 w-full" :value="old('birth_date')" required />
                                    <p class="text-xs text-gray-500 mt-1">L'âge est calculé automatiquement sur la carte.</p>
                                </div>
                                <div>
                                    <x-input-label for="school_class_id" value="Section / Classe" />
                                    <select id="school_class_id" name="school_class_id" required
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Choisir une classe --</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>
                                                {{ $class->level }}{{ $class->section ? ' '.$class->section : '' }} ({{ $class->school->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="parent_phone" value="Téléphone du parent" />
                                    <x-text-input id="parent_phone" name="parent_phone" class="block mt-1 w-full" :value="old('parent_phone')" />
                                    <p class="text-xs text-gray-500 mt-1">Utilisé pour vous permettre de contacter le parent, et affiché sur la carte.</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="photo" value="Photo de l'élève" />
                                <x-photo-position-picker
                                    name="photo"
                                    position-x-name="photo_position_x"
                                    position-y-name="photo_position_y"
                                    ratio="0.84"
                                    live-preview-target="student"
                                    required
                                />
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Accompagnateurs (verso — jusqu'à 3 photos)</h3>
                            <p class="text-sm text-gray-500 mb-4">Toute personne autorisée à récupérer l'élève. Au moins un accompagnateur est recommandé.</p>

                            @for ($i = 0; $i < 3; $i++)
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4 p-4 border rounded-md">
                                    <div>
                                        <x-input-label :for="'guardian_last_'.$i" value="Nom" />
                                        <x-text-input :id="'guardian_last_'.$i" name="guardians[{{ $i }}][last_name]" class="block mt-1 w-full" :value="old('guardians.'.$i.'.last_name')" />
                                    </div>
                                    <div>
                                        <x-input-label :for="'guardian_first_'.$i" value="Prénom" />
                                        <x-text-input :id="'guardian_first_'.$i" name="guardians[{{ $i }}][first_name]" class="block mt-1 w-full" :value="old('guardians.'.$i.'.first_name')" />
                                    </div>

                                    <div class="sm:col-span-4">
                                        <x-input-label :for="'guardian_photo_'.$i" value="Photo" />
                                        <x-photo-position-picker
                                            :name="'guardians['.$i.'][photo]'"
                                            :position-x-name="'guardians['.$i.'][photo_position_x]'"
                                            :position-y-name="'guardians['.$i.'][photo_position_y]'"
                                            ratio="1.15"
                                            :live-preview-target="'guardian-'.$i"
                                        />
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                Enregistrer l'élève
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <x-card-live-preview />
            </div>
        </div>
    </div>
</x-app-layout>
