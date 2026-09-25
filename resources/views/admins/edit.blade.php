<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg card-hover p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 rounded-md bg-red-50 text-red-700 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admins.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nom complet" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $user->name)" required autofocus />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email', $user->email)" required />
                    </div>

                    <div>
                        <x-input-label for="role" value="Rôle" />
                        <select id="role" name="role" required
                            @if ($user->id === Auth::id()) disabled @endif
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="secretaire" @selected(old('role', $user->role) === 'secretaire')>Secrétaire</option>
                            <option value="surveillant" @selected(old('role', $user->role) === 'surveillant')>Surveillant</option>
                            <option value="dg" @selected(old('role', $user->role) === 'dg')>DG</option>
                            @if (Auth::user()->isDev())
                                <option value="dev" @selected(old('role', $user->role) === 'dev')>Dev</option>
                            @endif
                        </select>
                        @if ($user->id === Auth::id())
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <p class="text-xs text-gray-500 mt-1">Tu ne peux pas changer ton propre rôle.</p>
                        @endif
                    </div>

                    <div class="border-t pt-4">
                        <x-input-label for="password" value="Nouveau mot de passe" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" />
                        <p class="text-xs text-gray-500 mt-1">Laisser vide pour conserver le mot de passe actuel.</p>
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmer le nouveau mot de passe" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" />
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admins.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Retour à la liste</a>
                        <x-primary-button>
                            Enregistrer
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
