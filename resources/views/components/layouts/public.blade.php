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
        <header class="bg-primary-950 text-white">
            <div class="flex items-center gap-6 px-4 py-3 lg:px-6">
                <a href="{{ route('organigramme') }}" class="shrink-0" aria-label="Accueil : organigramme de la DNH">
                    <x-logo-dnh class="w-[190px] sm:w-[230px]" />
                </a>
                <x-bandeau-republique class="hidden border-l border-white/15 pl-6 md:block" />
                <nav class="ml-auto flex shrink-0 items-center gap-2" aria-label="Compte">
                    @auth
                        @can('acceder_admin')
                            <a href="{{ url('/admin') }}" class="btn-accent py-1.5">Administration</a>
                        @else
                            <a href="{{ route('profile.edit') }}" class="btn-base border border-white/40 py-1.5 text-white hover:bg-white/10">Mon profil</a>
                        @endcan
                    @else
                        <a href="{{ route('login') }}" class="btn-accent py-1.5">Connexion</a>
                    @endauth
                </nav>
            </div>
            <x-tricolore class="h-1" />
        </header>

        <main class="relative min-h-0 flex-1">
            {{ $slot }}
        </main>

        <x-pied-officiel />
    </div>
    <x-visionneuse />
</body>
</html>
