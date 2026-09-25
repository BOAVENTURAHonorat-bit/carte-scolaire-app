<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajouter un administrateur
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
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

                <p class="text-sm text-gray-500 mb-6">
                    Crée un compte pour un membre du personnel (secrétaire, surveillant...) ou un autre DG.
                    Le compte est créé directement, sans te déconnecter.
                </p>

                <form method="POST" action="{{ route('admins.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nom complet" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required autofocus />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                    </div>

                    <div>
                        <x-input-label for="role" value="Rôle" />
                        <select id="role" name="role" required
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="secretaire" @selected(old('role') === 'secretaire')>Secrétaire</option>
                            <option value="surveillant" @selected(old('role') === 'surveillant')>Surveillant</option>
                            <option value="dg" @selected(old('role') === 'dg')>DG</option>
                            @if (Auth::user()->isDev())
                                <option value="dev" @selected(old('role') === 'dev')>Dev</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <x-input-label for="password" value="Mot de passe" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>
                            Créer le compte
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
