<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titre ?? 'Organigramme' }} – Direction Nationale des Hydrocarbures</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/organigramme.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="flex h-full flex-col">
        <header class="bg-dnh-900 text-white shadow">
            <div class="flex items-center gap-4 px-4 py-3 lg:px-6">
                {{-- Emplacement du logo du Ministère : remplacer public/images/logo-ministere.svg --}}
                <img src="{{ asset('images/logo-ministere.svg') }}" alt="Logo du Ministère" class="h-12 w-12 shrink-0 rounded-full bg-white p-0.5">
                <div class="min-w-0">
                    <p class="truncate text-xs uppercase tracking-wider text-dnh-200">Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</p>
                    <h1 class="truncate text-lg font-bold sm:text-xl">Direction Nationale des Hydrocarbures</h1>
                </div>
                <nav class="ml-auto flex shrink-0 items-center gap-2 text-sm">
                    @auth
                        <a href="{{ url('/admin') }}" class="rounded-md bg-white/10 px-3 py-2 font-medium hover:bg-white/20">Administration</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-white/10 px-3 py-2 font-medium hover:bg-white/20">Connexion</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="relative min-h-0 flex-1">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
