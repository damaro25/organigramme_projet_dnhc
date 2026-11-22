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
<body class="avec-menu-lateral h-full bg-fond font-sans text-slate-800 antialiased">
@php
    $u = auth()->user();
    $menu = [
        ['Tableau de bord', 'admin.tableau-de-bord', 'admin.tableau-de-bord', $u->can('acceder_admin'), 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['Organigramme', 'admin.organigramme', 'admin.organigramme*', $u->can('acceder_admin') && $u->peutModifierDesAgents(), 'M10 3h4v4h-1v3h6v4h1v4h-4v-4h1v-2h-4v2h1v4h-4v-4h1v-2H7v2h1v4H4v-4h1v-4h6V7h-1z'],
        ['Agents', 'admin.agents.index', 'admin.agents.*', $u->can('viewAny', \App\Models\Agent::class), 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0z'],
        ['Projets', 'admin.projets.index', 'admin.projets.*', $u->can('acceder_admin') && $u->can('viewAny', \App\Models\Projet::class), 'M4 4h16v4H4zm0 6h16v10H4zm3 3v4h2v-4zm4-1v5h2v-5zm4 2v3h2v-3z'],
        ['Structures', 'admin.structures.index', 'admin.structures.*', $u->can('viewAny', \App\Models\Structure::class), 'M4 21V7l8-4 8 4v14h-6v-6h-4v6z'],
        ['Utilisateurs', 'admin.utilisateurs.index', 'admin.utilisateurs.*', $u->can('viewAny', \App\Models\User::class), 'M16 11a3 3 0 100-6 3 3 0 000 6zM8 11a3 3 0 100-6 3 3 0 000 6zm0 2c-2.7 0-8 1.3-8 4v3h16v-3c0-2.7-5.3-4-8-4zm8 0c-.3 0-.7 0-1.1.1 1.2.9 2.1 2.1 2.1 3.9v3h7v-3c0-2.7-5.3-4-8-4z'],
        ['Journal des modifications', 'admin.journal', 'admin.journal*', $u->can('voir_journal') && Route::has('admin.journal'), 'M6 2h9l5 5v15H6zm8 1v5h5M9 12h8M9 16h8'],
        ['Styleguide', 'admin.styleguide', 'admin.styleguide', $u->can('gerer_utilisateurs'), 'M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 3.5a3.5 3.5 0 107 0 3.5 3.5 0 00-7 0'],
    ];
    $nonLues = $u->unreadNotifications()->count();
@endphp
<div x-data="{ menu: false }" class="flex min-h-full">
    {{-- Menu latéral --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-menu-fond bg-slate-900/40 lg:hidden" @click="menu = false"></div>
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-menu flex w-64 flex-col bg-primary-950 text-white transition-transform lg:sticky lg:top-0 lg:h-screen"
           aria-label="Menu latéral">
        <x-tricolore class="h-1" />
        <a href="{{ route('admin.tableau-de-bord') }}" class="block border-b border-white/10 px-5 pb-4 pt-5">
            <x-logo-dnh class="w-full max-w-[210px]" />
            <span class="mt-3 block font-display text-[11px] font-medium uppercase tracking-[0.12em] text-primary-200">République de Guinée · Administration</span>
        </a>
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Menu d'administration">
            @foreach ($menu as [$libelle, $route, $motif, $visible, $icone])
                @continue(! $visible)
                @php($actif = request()->routeIs($motif))
                <a href="{{ route($route) }}" @if ($actif) aria-current="page" @endif
                   @class(['flex items-center gap-3 rounded-md border-l-4 px-3 py-2 font-display text-[15px] font-medium uppercase tracking-titre transition',
                        'border-accent-500 bg-primary-800 text-white' => $actif,
                        'border-transparent text-primary-100 hover:bg-white/10 hover:text-white' => ! $actif])>
                    <svg @class(['h-5 w-5 shrink-0', 'text-accent-500' => $actif]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                    {{ $libelle }}
                </a>
            @endforeach
        </nav>
        <a href="{{ route('organigramme') }}" class="mx-3 mb-4 rounded-md border border-white/20 px-3 py-2 text-center font-display text-xs font-medium uppercase tracking-titre text-primary-100 hover:bg-white/10 hover:text-white">← Organigramme public</a>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Barre supérieure --}}
        <header data-menu-haut class="sticky top-0 z-entete flex items-center gap-3 border-b border-fond-bord bg-white px-4 py-3 lg:px-8">
            <button type="button" class="rounded-md p-1.5 text-primary-950 hover:bg-primary-50 lg:hidden" @click="menu = true" aria-label="Ouvrir le menu" :aria-expanded="menu">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">{{ $header ?? '' }}</div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-primary-50" aria-label="Menu du compte">
                        <img src="{{ $u->agent?->miniature_url ?? asset(\App\Models\Agent::AVATAR_DEFAUT) }}" alt="" class="h-8 w-8 rounded-full object-cover">
                        <span class="hidden text-left sm:block">
                            <span class="block font-semibold leading-tight text-primary-950">{{ $u->name }}</span>
                            <span class="block text-xs text-slate-600">{{ \App\Http\Requests\Admin\UserRequest::ROLES[$u->getRoleNames()->first()] ?? '' }}</span>
                        </span>
                        @if ($nonLues)
                            <span class="rounded-full bg-accent-500 px-1.5 text-xs font-bold text-primary-950" title="Notifications non lues">{{ $nonLues }}</span>
                        @endif
                    </button>
                </x-slot>
                <x-slot name="content">
                    @can('acceder_admin')
                        <x-dropdown-link :href="route('admin.notifications')">Notifications @if ($nonLues) ({{ $nonLues }}) @endif</x-dropdown-link>
                    @endcan
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
                <x-alert type="danger" titre="Le formulaire contient des erreurs" class="mb-4">
                    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </x-alert>
            @endif
            {{ $slot }}
        </main>

        <x-pied-officiel />
    </div>
</div>

{{-- Messages de confirmation --}}
<div x-data="notifications" x-init="ajouter(@js(session('succes')))" @notification.window="ajouter($event.detail.message ?? $event.detail[0]?.message)"
     class="pointer-events-none fixed bottom-4 right-4 z-superpose flex flex-col gap-2" aria-live="polite">
    <template x-for="n in liste" :key="n.id">
        <div x-transition class="pointer-events-auto rounded-md border-l-4 border-accent-500 bg-primary-950 px-4 py-3 text-sm text-white shadow-lg" role="status" x-text="n.message"></div>
    </template>
</div>

@livewireScriptConfig
    <x-visionneuse />
@include('partials.couches-menu')
</body>
</html>
