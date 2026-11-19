{{-- Layout de l'espace d'administration (utilisé via <x-app-layout>). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($titre) ? $titre.' – ' : '' }}Administration – DNH</title>
    @include('partials.icone-onglet')
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="h-full bg-fond font-sans text-slate-800 antialiased">
@php
    $u = auth()->user();
    $menu = [
        ['Tableau de bord', 'admin.tableau-de-bord', 'admin.tableau-de-bord', true, 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['Organigramme', 'admin.organigramme', 'admin.organigramme*', true, 'M10 3h4v4h-1v3h6v4h1v4h-4v-4h1v-2h-4v2h1v4h-4v-4h1v-2H7v2h1v4H4v-4h1v-4h6V7h-1z'],
        ['Agents', 'admin.agents.index', 'admin.agents.*', $u->can('viewAny', \App\Models\Agent::class), 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0z'],
        ['Projets', 'admin.projets.index', 'admin.projets.*', $u->can('viewAny', \App\Models\Projet::class), 'M4 4h16v4H4zm0 6h16v10H4zm3 3v4h2v-4zm4-1v5h2v-5zm4 2v3h2v-3z'],
        ['Structures', 'admin.structures.index', 'admin.structures.*', $u->can('viewAny', \App\Models\Structure::class), 'M4 21V7l8-4 8 4v14h-6v-6h-4v6z'],
        ['Utilisateurs', 'admin.utilisateurs.index', 'admin.utilisateurs.*', $u->can('viewAny', \App\Models\User::class), 'M16 11a3 3 0 100-6 3 3 0 000 6zM8 11a3 3 0 100-6 3 3 0 000 6zm0 2c-2.7 0-8 1.3-8 4v3h16v-3c0-2.7-5.3-4-8-4zm8 0c-.3 0-.7 0-1.1.1 1.2.9 2.1 2.1 2.1 3.9v3h7v-3c0-2.7-5.3-4-8-4z'],
        ['Journal des modifications', 'admin.journal', 'admin.journal*', $u->can('voir_journal') && Route::has('admin.journal'), 'M6 2h9l5 5v15H6zm8 1v5h5M9 12h8M9 16h8'],
    ];
    $nonLues = $u->unreadNotifications()->count();
@endphp
<div x-data="{ menu: false }" class="flex min-h-full">
    {{-- Menu latéral --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="menu = false"></div>
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-dnh-900 text-white transition-transform lg:sticky lg:top-0 lg:h-screen">
        <x-tricolore />
        <a href="{{ route('organigramme') }}" class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
            {{-- Armoiries sur fond blanc, avec une marge libre (charte graphique). --}}
            <span class="shrink-0 rounded bg-white p-1.5">
                <img src="{{ \App\Support\Armoiries::url() }}" alt="Armoiries de la République de Guinée" class="h-9 w-auto object-contain">
            </span>
            <span class="leading-tight"><span class="block text-[10px] uppercase tracking-wider text-dnh-200">République de Guinée</span><span class="block text-sm font-bold">DNH</span><span class="block text-xs text-dnh-200">Administration</span></span>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Menu d'administration">
            @foreach ($menu as [$libelle, $route, $motif, $visible, $icone])
                @continue(! $visible)
                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-white/15 text-white' => request()->routeIs($motif), 'text-dnh-100 hover:bg-white/10' => ! request()->routeIs($motif)])>
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                    {{ $libelle }}
                </a>
            @endforeach
        </nav>
        <a href="{{ route('organigramme') }}" class="mx-3 mb-4 rounded-lg border border-white/20 px-3 py-2 text-center text-xs text-dnh-100 hover:bg-white/10">← Voir l'organigramme public</a>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Barre supérieure --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-fond-bord bg-white px-4 py-3 lg:px-8">
            <button type="button" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden" @click="menu = true" aria-label="Ouvrir le menu">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">{{ $header ?? '' }}</div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-slate-100">
                        <img src="{{ $u->agent?->miniature_url ?? asset(\App\Models\Agent::AVATAR_DEFAUT) }}" alt="" class="h-8 w-8 rounded-full object-cover">
                        <span class="hidden text-left sm:block">
                            <span class="block font-medium leading-tight">{{ $u->name }}</span>
                            <span class="block text-xs text-slate-500">{{ \App\Http\Requests\Admin\UserRequest::ROLES[$u->getRoleNames()->first()] ?? '' }}</span>
                        </span>
                        @if ($nonLues)
                            <span class="rounded-full bg-red-600 px-1.5 text-xs font-bold text-white" title="Notifications non lues">{{ $nonLues }}</span>
                        @endif
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('admin.notifications')">Notifications @if ($nonLues) ({{ $nonLues }}) @endif</x-dropdown-link>
                    <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Se déconnecter</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8">
            @if ($errors->any() && ! isset($masquerErreurs))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold">Le formulaire contient des erreurs :</p>
                    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </main>

        <x-pied-officiel class="lg:px-8" />
    </div>
</div>

{{-- Messages de confirmation --}}
<div x-data="notifications" x-init="ajouter(@js(session('succes')))" @notification.window="ajouter($event.detail.message ?? $event.detail[0]?.message)"
     class="pointer-events-none fixed bottom-4 right-4 z-50 flex flex-col gap-2" aria-live="polite">
    <template x-for="n in liste" :key="n.id">
        <div x-transition class="pointer-events-auto rounded-lg bg-slate-900 px-4 py-3 text-sm text-white shadow-lg" x-text="n.message"></div>
    </template>
</div>

@livewireScriptConfig
</body>
</html>
