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
        {{-- En-tête : bandeau institutionnel (armoiries sur fond blanc, charte du
             Gouvernement), puis barre marine aux couleurs de la DNH. --}}
        <header data-menu-haut class="relative z-entete">
            <div class="hidden items-center justify-between gap-4 bg-white px-4 py-1.5 lg:flex lg:px-6">
                <span class="flex items-center gap-2 text-[11px] text-slate-600">
                    <img src="{{ \App\Support\Armoiries::url() }}" alt="Armoiries de la République de Guinée" class="h-6 w-auto object-contain">
                    <span class="font-titre font-bold uppercase tracking-wider text-dnh-950">République de Guinée</span>
                    <span class="italic">Travail – Justice – Solidarité</span>
                </span>
                <span class="text-[11px] uppercase tracking-wide text-slate-500">Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</span>
            </div>
            <div class="bg-dnh-950 text-white">
                <div class="flex items-center gap-4 px-4 py-3 lg:px-6">
                    <a href="{{ route('organigramme') }}" class="flex min-w-0 items-center gap-4 rounded focus-visible:ring-offset-dnh-950">
                        <x-logo-dnh class="hidden h-11 w-auto shrink-0 text-white sm:block" />
                        <x-logo-dnh variante="embleme" class="h-10 w-auto shrink-0 text-white sm:hidden" />
                        <span class="hidden h-9 w-px bg-white/20 md:block" aria-hidden="true"></span>
                        <span class="min-w-0 leading-tight">
                            <span class="block truncate font-titre text-lg font-semibold uppercase tracking-wider sm:text-xl">{{ $titre ?? 'Organigramme' }}</span>
                            <span class="block truncate text-xs text-dnh-200">Personnel et suivi des projets</span>
                        </span>
                    </a>
                    <nav class="ml-auto flex shrink-0 items-center gap-2 text-sm">
                        @auth
                            <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 font-titre text-[15px] font-semibold uppercase tracking-wider text-dnh-950 transition hover:bg-energie focus-visible:ring-offset-dnh-950">Administration</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 font-titre text-[15px] font-semibold uppercase tracking-wider text-dnh-950 transition hover:bg-energie focus-visible:ring-offset-dnh-950">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>Connexion
                            </a>
                        @endauth
                    </nav>
                </div>
            </div>
            <x-tricolore />
        </header>

        <main class="relative min-h-0 flex-1">
            {{ $slot }}
        </main>

        <x-pied-officiel />
    </div>
@include('partials.couches-menu')
</body>
</html>
