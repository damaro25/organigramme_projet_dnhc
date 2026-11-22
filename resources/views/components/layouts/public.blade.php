<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titre ?? 'Organigramme' }} – Direction Nationale des Hydrocarbures</title>
    @include('partials.icone-onglet')
    @vite(['resources/css/app.css', 'resources/js/organigramme.js'])
</head>
<body class="h-full bg-fond font-sans text-slate-800 antialiased">
    <div class="flex h-full flex-col">
        <header class="bg-white shadow-sm">
            <div class="flex items-center gap-4 px-4 py-2 lg:px-6">
                <x-bandeau-republique />
                <nav class="ml-auto flex shrink-0 items-center gap-2 text-sm">
                    @auth
                        <a href="{{ url('/admin') }}" class="rounded-md bg-dnh-700 px-3 py-2 font-medium text-white hover:bg-dnh-800">Administration</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-dnh-700 px-3 py-2 font-medium text-white hover:bg-dnh-800">Connexion</a>
                    @endauth
                </nav>
            </div>
            <x-tricolore />
        </header>

        <main class="relative min-h-0 flex-1">
            {{ $slot }}
        </main>

        <x-pied-officiel />
    </div>
    <x-visionneuse />
</body>
</html>
