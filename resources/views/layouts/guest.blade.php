<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Connexion – Direction Nationale des Hydrocarbures</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100">
            <a href="/" class="flex flex-col items-center gap-2 text-center">
                <img src="{{ asset('images/logo-ministere.svg') }}" alt="Logo du Ministère" class="h-20 w-20">
                <span class="text-xs uppercase tracking-wider text-slate-500">Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</span>
                <span class="text-lg font-bold text-dnh-900">Direction Nationale des Hydrocarbures</span>
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
