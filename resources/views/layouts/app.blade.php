<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div id="nprogress-bar" style="--brand-primary: {{ $navSchool?->primary_color ?? '#4f46e5' }}; --brand-secondary: {{ $navSchool?->secondary_color ?? '#6366f1' }};"></div>
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow relative overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, {{ $navSchool?->primary_color ?? '#1E3A8A' }}, {{ $navSchool?->secondary_color ?? '#E05A5A' }});"></div>
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="page-fade-in">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200 mt-8 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-center gap-3 text-sm text-gray-500">
                    <span>Réalisé en partenariat avec</span>
                    <img src="{{ asset('images/cortex-logo.png') }}" alt="CORTEX" class="h-9 w-auto">
                </div>
            </footer>
        </div>
    </body>
</html>
