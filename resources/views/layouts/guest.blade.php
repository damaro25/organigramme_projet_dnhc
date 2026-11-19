<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Connexion – Direction Nationale des Hydrocarbures</title>

        <!-- Scripts -->
        @include('partials.icone-onglet')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-fond font-sans text-slate-800 antialiased">
        <div class="flex min-h-full">
            {{-- Volet d'identité (grand écran) : fond marine du logo, emblème en filigrane. --}}
            <aside class="relative hidden w-[44%] max-w-2xl flex-col justify-between overflow-hidden bg-dnh-950 p-12 text-white lg:flex">
                <x-logo-dnh variante="embleme" class="pointer-events-none absolute -right-24 top-1/2 h-[34rem] w-auto -translate-y-1/2 text-white opacity-[0.035]" aria-hidden="true" />
                <a href="/" class="relative block max-w-sm">
                    <x-logo-dnh class="h-auto w-full text-white" />
                </a>
                <div class="relative max-w-md">
                    <p class="font-titre text-sm font-semibold uppercase tracking-[0.2em] text-energie">Espace d'administration</p>
                    <p class="mt-3 font-titre text-4xl font-bold uppercase leading-tight tracking-wide">Organigramme et suivi des projets</p>
                    <p class="mt-4 text-base leading-relaxed text-dnh-200">Personnel de la Direction, fiches des agents, avancement des projets et tableau de bord.</p>
                </div>
                <div class="relative">
                    <x-tricolore class="w-24" />
                    <p class="mt-3 text-xs text-dnh-200">République de Guinée · Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</p>
                </div>
            </aside>

            {{-- Formulaire --}}
            <main class="flex flex-1 flex-col">
                {{-- Bandeau d'identité (petit écran) --}}
                <div class="bg-dnh-950 px-6 py-5 lg:hidden">
                    <a href="/" class="mx-auto block max-w-[16rem]"><x-logo-dnh class="h-auto w-full text-white" /></a>
                </div>
                <x-tricolore class="lg:hidden" />

                <div class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6">
                    <div class="w-full max-w-md">
                        <div class="mb-6 flex flex-col items-center text-center">
                            <img src="{{ \App\Support\Armoiries::url() }}" alt="Armoiries de la République de Guinée" class="h-16 w-auto object-contain">
                            <p class="mt-3 font-titre text-sm font-bold uppercase tracking-wider text-dnh-950">République de Guinée</p>
                            <p class="text-xs italic text-slate-500">Travail – Justice – Solidarité</p>
                        </div>

                        <div class="carte overflow-hidden">
                            <div class="h-1 bg-dnh-950" aria-hidden="true"></div>
                            <div class="px-6 py-7 sm:px-8">
                                <h1 class="mb-6 font-titre text-2xl font-bold uppercase tracking-wider text-dnh-950">Espace d'administration</h1>
                                {{ $slot }}
                            </div>
                        </div>

                        <p class="mt-6 text-center">
                            <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="text-xs text-slate-600 hover:text-dnh-700 hover:underline">Portail officiel du Gouvernement ↗<span class="sr-only"> (nouvel onglet)</span></a>
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
