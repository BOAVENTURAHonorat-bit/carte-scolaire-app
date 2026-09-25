<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Utilisateurs
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('generatedPassword'))
                <div class="mb-4 p-4 rounded-md bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                    <p class="font-medium mb-1">Nouveau mot de passe pour {{ session('generatedPassword')['user'] }} :</p>
                    <p class="font-mono text-base tracking-wide bg-white/70 inline-block px-3 py-1 rounded-md border border-amber-200">{{ session('generatedPassword')['password'] }}</p>
                    <p class="mt-1 text-xs text-amber-700">Transmets-le lui directement — il ne sera plus affiché après ce rechargement.</p>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                    <p class="text-sm text-gray-500">{{ $users->count() }} utilisateur{{ $users->count() > 1 ? 's' : '' }}</p>
                    <a href="{{ route('admins.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        + Nouvel utilisateur
                    </a>
                </div>

                <div class="sm:hidden divide-y divide-gray-100">
                    @forelse ($users as $user)
                        @php
                            $roleStyles = [
                                'dg' => 'bg-indigo-100 text-indigo-800',
                                'secretaire' => 'bg-blue-100 text-blue-800',
                                'surveillant' => 'bg-amber-100 text-amber-800',
                                'dev' => 'bg-gray-900 text-white',
                            ];
                            $roleLabels = [
                                'dg' => 'DG',
                                'secretaire' => 'Secrétaire',
                                'surveillant' => 'Surveillant',
                                'dev' => 'Dev',
                            ];
                        @endphp
                        <div class="p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $user->name }} @if ($user->id === Auth::id())<span class="text-xs text-gray-400">(toi)</span>@endif</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleStyles[$user->role] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $roleLabels[$user->role] ?? $user->role }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('admins.edit', $user) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">Modifier</a>
                                @if ($user->id !== Auth::id())
                                    @if (Auth::user()->isDev())
                                        <form method="POST" action="{{ route('admins.reset-password', $user) }}" class="inline" onsubmit="return confirm('Réinitialiser le mot de passe de {{ $user->name }} et le déconnecter immédiatement ?');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition">Réinitialiser mdp</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admins.destroy', $user) }}" class="inline" onsubmit="return confirm('Supprimer définitivement le compte de {{ $user->name }} ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">Aucun utilisateur.</div>
                    @endforelse
                </div>

                <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $user->name }}
                                    @if ($user->id === Auth::id())
                                        <span class="text-xs text-gray-400">(toi)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $roleStyles = [
                                            'dg' => 'bg-indigo-100 text-indigo-800',
                                            'secretaire' => 'bg-blue-100 text-blue-800',
                                            'surveillant' => 'bg-amber-100 text-amber-800',
                                            'dev' => 'bg-gray-900 text-white',
                                        ];
                                        $roleLabels = [
                                            'dg' => 'DG',
                                            'secretaire' => 'Secrétaire',
                                            'surveillant' => 'Surveillant',
                                            'dev' => 'Dev',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleStyles[$user->role] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $roleLabels[$user->role] ?? $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex flex-wrap items-center justify-end gap-1.5">
                                        <a href="{{ route('admins.edit', $user) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                            Modifier
                                        </a>
                                        @if ($user->id !== Auth::id())
                                            @if (Auth::user()->isDev())
                                                <form method="POST" action="{{ route('admins.reset-password', $user) }}" class="inline"
                                                    onsubmit="return confirm('Réinitialiser le mot de passe de {{ $user->name }} et le déconnecter immédiatement ?');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                                        Réinitialiser mdp
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admins.destroy', $user) }}" class="inline"
                                                onsubmit="return confirm('Supprimer définitivement le compte de {{ $user->name }} ?');">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Aucun utilisateur.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
