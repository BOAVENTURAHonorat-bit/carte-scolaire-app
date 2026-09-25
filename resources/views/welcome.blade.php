<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $school->name ?? config('app.name', 'Carte Scolaire') }} — Cartes de sécurité scolaires</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <style>
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body class="antialiased bg-white text-gray-900" x-data="{ loginOpen: {{ $errors->any() ? 'true' : 'false' }} }">

        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-30">
            <div class="max-w-7xl mx-auto px-6 sm:px-10 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/flags/pere.png') }}" alt="Logo" class="w-11 h-11 rounded-full object-cover ring-2 ring-white/70">
                    <span class="font-semibold text-white drop-shadow">{{ $school->name ?? config('app.name') }}</span>
                </div>

                @auth
                    <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm rounded-md bg-white text-indigo-700 font-medium hover:bg-indigo-50 transition shadow">
                        Mon espace
                    </a>
                @else
                    <button type="button" @click="loginOpen = true" class="px-4 py-2 text-sm rounded-md bg-white text-indigo-700 font-medium hover:bg-indigo-50 transition shadow">
                        Se connecter
                    </button>
                @endauth
            </div>
        </header>

        <!-- Hero -->
        <section class="relative h-[92vh] min-h-[560px] flex items-center overflow-hidden">
            <img src="{{ asset('images/home/classroom.jpg') }}" alt="Élèves en classe"
                class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/60 to-gray-900/30"></div>

            <div class="relative max-w-7xl mx-auto px-6 sm:px-10 w-full">
                <p class="text-amber-300 font-semibold tracking-wide uppercase text-sm mb-3">
                    {{ $school->school_type ?? 'Complexe Scolaire Catholique' }}
                </p>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight max-w-3xl">
                    {{ $school->name ?? 'Cartes de sécurité scolaires' }}
                </h1>
                <p class="mt-5 text-lg text-gray-200 max-w-xl">
                    De la Maternelle au Primaire (CI au CM2), nous accompagnons chaque enfant dans son parcours —
                    et sécurisons chaque sortie grâce à la carte de sécurité de l'élève.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-6 py-3 rounded-md bg-amber-400 text-gray-900 font-semibold hover:bg-amber-300 transition shadow-lg">
                            Accéder à mon espace
                        </a>
                    @else
                        <button type="button" @click="loginOpen = true" class="px-6 py-3 rounded-md bg-amber-400 text-gray-900 font-semibold hover:bg-amber-300 transition shadow-lg">
                            Se connecter
                        </button>
                    @endauth
                    <a href="#niveaux" class="px-6 py-3 rounded-md bg-white/10 text-white font-medium border border-white/30 hover:bg-white/20 transition backdrop-blur-sm">
                        Découvrir l'école
                    </a>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="relative -mt-12 z-10">
            <div class="max-w-5xl mx-auto px-6 sm:px-10">
                <div class="bg-white rounded-xl shadow-xl grid grid-cols-2 sm:grid-cols-4 divide-x divide-gray-100 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="text-3xl font-extrabold text-indigo-700">{{ $studentCount }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Élèves inscrits</div>
                    </div>
                    <div class="p-6 text-center">
                        <div class="text-3xl font-extrabold text-indigo-700">{{ $classCount }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Classes</div>
                    </div>
                    <div class="p-6 text-center">
                        <div class="text-3xl font-extrabold text-indigo-700">2</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Cycles : Maternelle & Primaire</div>
                    </div>
                    <div class="p-6 text-center">
                        <div class="text-3xl font-extrabold text-indigo-700">100%</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Élèves avec carte de sécurité</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Niveaux -->
        <section id="niveaux" class="py-24 px-6 sm:px-10">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm mb-2">Nos niveaux</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">De la Maternelle au CM2</h2>
                    <p class="mt-4 text-gray-600">
                        Un parcours complet et structuré, pensé pour l'épanouissement de chaque enfant.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="group rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow bg-white">
                        <div class="relative h-64 overflow-hidden">
                            <img src="{{ asset('images/home/maternelle-kids.jpg') }}" alt="Élèves de la maternelle" loading="lazy" decoding="async"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                            <span class="absolute bottom-4 left-4 px-3 py-1 rounded-full bg-amber-400 text-gray-900 text-xs font-bold uppercase">
                                Maternelle 1 &amp; 2
                            </span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Section Maternelle</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                Premiers apprentissages, éveil et socialisation dans un cadre chaleureux et sécurisé,
                                réparti en Maternelle 1 et Maternelle 2 (sections A et B).
                            </p>
                        </div>
                    </div>

                    <div class="group rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow bg-white">
                        <div class="relative h-64 overflow-hidden">
                            <img src="{{ asset('images/home/writing.jpg') }}" alt="Élèves du primaire" loading="lazy" decoding="async"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                            <span class="absolute bottom-4 left-4 px-3 py-1 rounded-full bg-indigo-500 text-white text-xs font-bold uppercase">
                                CI au CM2
                            </span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Section Primaire</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                Un enseignement rigoureux du CI au CM2 (sections A et B), pour bâtir des bases solides
                                et préparer sereinement la suite du parcours scolaire.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vie scolaire (galerie) -->
        <section class="py-24 px-6 sm:px-10 bg-gray-50">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm mb-2">La vie à l'école</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Des enfants épanouis, chaque jour</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <img src="{{ asset('images/home/smiling-boys.jpg') }}" alt="Élèves souriants" loading="lazy" decoding="async"
                        class="col-span-2 row-span-2 h-full w-full object-cover rounded-xl shadow-md aspect-square md:aspect-auto">
                    <img src="{{ asset('images/home/table-activity.jpg') }}" alt="Activité en classe" loading="lazy" decoding="async"
                        class="w-full h-full object-cover rounded-xl shadow-md aspect-square">
                    <img src="{{ asset('images/home/maternelle-kids.jpg') }}" alt="Repas des petits" loading="lazy" decoding="async"
                        class="w-full h-full object-cover rounded-xl shadow-md aspect-square">
                    <img src="{{ asset('images/home/writing.jpg') }}" alt="Élève qui écrit" loading="lazy" decoding="async"
                        class="w-full h-full object-cover rounded-xl shadow-md aspect-square">
                    <img src="{{ asset('images/home/classroom.jpg') }}" alt="Salle de classe" loading="lazy" decoding="async"
                        class="w-full h-full object-cover rounded-xl shadow-md aspect-square">
                </div>
            </div>
        </section>

        <!-- Pourquoi la plateforme -->
        <section class="py-24 px-6 sm:px-10">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm mb-2">Sécurité &amp; suivi</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">La sécurité de votre enfant, notre priorité</h2>
                </div>

                <div class="grid sm:grid-cols-3 gap-8">
                    <div class="text-center px-4">
                        <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12c0 4.556-3.04 8.4-7.2 9.62a1.5 1.5 0 01-1.6 0C7.04 20.4 4 16.556 4 12V6.545c0-.71.44-1.35 1.11-1.596L11.11 2.7a1.5 1.5 0 011.78 0l5.999 2.25A1.72 1.72 0 0121 6.545V12z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Carte de sécurité individuelle</h3>
                        <p class="text-sm text-gray-600">
                            Chaque élève dispose d'une carte avec photo et contact du parent, exigée à chaque sortie.
                        </p>
                    </div>
                    <div class="text-center px-4">
                        <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-1a3 3 0 10-3-3" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Accompagnateurs identifiés</h3>
                        <p class="text-sm text-gray-600">
                            Jusqu'à 3 personnes autorisées par élève, avec photo, pour un retrait en toute confiance.
                        </p>
                    </div>
                    <div class="text-center px-4">
                        <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Suivi numérique simplifié</h3>
                        <p class="text-sm text-gray-600">
                            Le personnel de l'école accède en un instant à la liste des élèves, par classe et par statut.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact / Footer -->
        <footer class="bg-gray-900 text-gray-300">
            <div class="max-w-6xl mx-auto px-6 sm:px-10 py-16 grid sm:grid-cols-3 gap-10">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/flags/pere.png') }}" alt="Logo" class="w-10 h-10 rounded-full object-cover">
                        <span class="font-semibold text-white">{{ $school->name ?? config('app.name') }}</span>
                    </div>
                    <p class="text-sm text-gray-400">
                        {{ $school->school_type ?? 'Complexe Scolaire Catholique' }} — Maternelle &amp; Primaire.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm uppercase tracking-wide">Contact</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        @if ($school?->address)
                            <li>{{ $school->address }}</li>
                        @endif
                        @if ($school?->phone)
                            <li>Tél : {{ $school->phone }}</li>
                        @endif
                        @if ($school?->email)
                            <li>{{ $school->email }}</li>
                        @endif
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm uppercase tracking-wide">Accès</h4>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-block px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-500 transition">
                            Mon espace
                        </a>
                    @else
                        <button type="button" @click="loginOpen = true" class="inline-block px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-500 transition">
                            Se connecter
                        </button>
                    @endauth
                </div>
            </div>
            <div class="border-t border-white/10 py-5 px-6 sm:px-10 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <p class="text-xs text-gray-500">
                    &copy; {{ date('Y') }} {{ $school->name ?? config('app.name') }} — Cartes de sécurité scolaires.
                </p>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span>Site réalisé en partenariat avec</span>
                    <img src="{{ asset('images/cortex-logo.png') }}" alt="CORTEX" class="h-6 w-auto opacity-90">
                </div>
            </div>
        </footer>

        @guest
            <!-- Popup de connexion -->
            <div x-show="loginOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4"
                style="display: none;">
                <div class="absolute inset-0 bg-gray-900/50" @click="loginOpen = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm p-6" @click.outside="loginOpen = false">
                    <button type="button" @click="loginOpen = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="flex flex-col items-center mb-4">
                        <img src="{{ asset('images/flags/pere.png') }}" alt="Logo" class="w-14 h-14 rounded-full object-cover mb-2">
                        <h2 class="text-lg font-semibold text-gray-900">Connexion</h2>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" id="popup-login-form">
                        @csrf

                        <div>
                            <x-input-label for="popup_email" :value="__('Email')" />
                            <x-text-input id="popup_email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" list="email-history" />
                            <datalist id="email-history"></datalist>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="popup_password" :value="__('Password')" />
                            <x-text-input id="popup_password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="block mt-4">
                            <label for="popup_remember_me" class="inline-flex items-center">
                                <input id="popup_remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            @if (Route::has('password.request'))
                                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif

                            <x-primary-button>
                                {{ __('Log in') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                (function () {
                    var KEY = 'loginEmailHistory';
                    var list = document.getElementById('email-history');
                    var input = document.getElementById('popup_email');
                    var form = document.getElementById('popup-login-form');

                    function getHistory() {
                        try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
                    }

                    function renderHistory() {
                        list.innerHTML = '';
                        getHistory().forEach(function (email) {
                            var opt = document.createElement('option');
                            opt.value = email;
                            list.appendChild(opt);
                        });
                    }

                    function remember(email) {
                        email = (email || '').trim().toLowerCase();
                        if (!email) return;
                        var history = getHistory().filter(function (e) { return e !== email; });
                        history.unshift(email);
                        localStorage.setItem(KEY, JSON.stringify(history.slice(0, 5)));
                    }

                    renderHistory();
                    form.addEventListener('submit', function () { remember(input.value); });
                })();
            </script>
        @endguest
    </body>
</html>
