{{-- Gabarit des pages d'erreur, aux couleurs de la DNH. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') – @yield('titre') – Direction Nationale des Hydrocarbures</title>
    @include('partials.icone-onglet')
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-dnh-950 font-sans text-white antialiased">
    <div class="relative flex min-h-full flex-col overflow-hidden">
        <x-logo-dnh variante="embleme" class="pointer-events-none absolute -right-32 top-1/2 h-[40rem] w-auto -translate-y-1/2 text-white opacity-[0.04]" aria-hidden="true" />
        <header class="relative px-6 py-6 sm:px-10">
            <a href="{{ url('/') }}" class="inline-block w-56 sm:w-72"><x-logo-dnh class="h-auto w-full text-white" /></a>
        </header>
        <main class="relative flex flex-1 items-center px-6 pb-16 sm:px-10">
            <div class="max-w-xl">
                <p class="font-titre text-8xl font-bold leading-none text-energie sm:text-9xl">@yield('code')</p>
                <h1 class="mt-4 font-titre text-3xl font-bold uppercase tracking-wider sm:text-4xl">@yield('titre')</h1>
                <p class="mt-4 text-base leading-relaxed text-dnh-200">@yield('message')</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-5 py-2.5 font-titre text-[15px] font-semibold uppercase tracking-wider text-dnh-950 transition hover:bg-energie focus-visible:ring-offset-dnh-950">Retour à l'organigramme</a>
                    <a href="javascript:history.back()" class="inline-flex items-center gap-2 rounded-md border border-white/25 px-5 py-2.5 font-titre text-[15px] font-semibold uppercase tracking-wider text-white transition hover:bg-white/10 focus-visible:ring-offset-dnh-950">Page précédente</a>
                </div>
            </div>
        </main>
        <x-tricolore class="relative" />
    </div>
</body>
</html>
