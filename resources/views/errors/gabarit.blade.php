{{--
    Pages d'erreur aux couleurs de la DNH. Volontairement autonomes : pas d'appel à la
    base de données ni à la session, pour s'afficher même quand l'application est en panne.
--}}
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $titre }} – Direction Nationale des Hydrocarbures</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full flex-col bg-fond font-sans text-slate-800 antialiased">
    <header class="bg-primary-950">
        <div class="mx-auto flex max-w-5xl items-center px-6 py-4">
            <a href="{{ url('/') }}"><x-logo-dnh class="w-[200px]" /></a>
        </div>
        <x-tricolore class="h-1" />
    </header>

    <main class="relative flex flex-1 items-center overflow-hidden">
        <img src="{{ asset('images/symbole-dnh-marine.svg') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -right-24 top-1/2 hidden w-[560px] max-w-none -translate-y-1/2 opacity-[0.04] md:block">
        <div class="relative mx-auto w-full max-w-5xl px-6 py-16">
            <p class="font-display text-7xl font-bold tracking-titre text-accent-500 sm:text-8xl">{{ $code }}</p>
            <h1 class="mt-2 font-display text-3xl font-semibold uppercase tracking-titre text-primary-950">{{ $titre }}</h1>
            <p class="mt-3 max-w-xl text-slate-600">{{ $message }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ url('/') }}" class="btn-primaire">Organigramme</a>
                @if ($retour ?? true)
                    <a href="javascript:history.back()" class="btn-secondaire">Page précédente</a>
                @endif
            </div>
        </div>
    </main>

    <x-pied-officiel />
</body>
</html>
