<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Connexion – Direction Nationale des Hydrocarbures</title>

        <!-- Scripts -->
        @include('partials.icone-onglet')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-tricolore class="fixed inset-x-0 top-0 z-10" />
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 pb-6 sm:pt-6 bg-fond">
            <a href="/">
                <x-bandeau-republique variante="centre" />
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="mt-6 text-xs text-slate-500 hover:text-dnh-700 hover:underline">Portail officiel du Gouvernement ↗</a>
        </div>
    </body>
</html>
