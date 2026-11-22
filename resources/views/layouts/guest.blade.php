<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Connexion – Direction Nationale des Hydrocarbures</title>

        @include('partials.icone-onglet')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-fond font-sans text-slate-800 antialiased">
        {{-- Écran partagé : identité de la DNH à gauche (en haut sur mobile), formulaire à droite. --}}
        <div class="flex min-h-full flex-col lg:flex-row">
            <aside class="relative flex flex-col overflow-hidden bg-primary-950 text-white lg:w-[46%]">
                <x-tricolore class="h-1" />
                {{-- Filigrane : le symbole de la DNH, très léger (4 %), loin du texte. --}}
                <img src="{{ asset('images/symbole-dnh-blanc.svg') }}" alt="" aria-hidden="true"
                     class="pointer-events-none absolute -bottom-24 -right-28 hidden w-[620px] max-w-none opacity-[0.04] lg:block">

                <div class="relative flex flex-1 flex-col justify-center gap-8 px-6 py-8 sm:px-10 lg:px-14 lg:py-16">
                    <a href="{{ route('organigramme') }}" aria-label="Organigramme de la Direction Nationale des Hydrocarbures">
                        <x-logo-dnh class="w-[240px] sm:w-[300px] lg:w-[380px]" />
                    </a>
                    <div class="hidden max-w-md lg:block">
                        <p class="font-display text-3xl font-semibold uppercase leading-tight tracking-titre">Organigramme et suivi des projets</p>
                        <p class="mt-3 text-primary-200">Espace de travail des agents de la Direction Nationale des Hydrocarbures.</p>
                    </div>
                    <x-bandeau-republique class="hidden lg:block" />
                </div>
            </aside>

            <main class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="carte px-6 py-7 shadow-lg sm:px-8">
                        {{ $slot }}
                    </div>
                    <p class="mt-6 flex flex-wrap justify-between gap-2 text-xs text-slate-600">
                        <a href="{{ route('organigramme') }}" class="hover:text-primary-950 hover:underline">← Organigramme public</a>
                        <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="hover:text-primary-950 hover:underline">Portail officiel du Gouvernement ↗</a>
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>
