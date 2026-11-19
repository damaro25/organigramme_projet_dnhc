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
    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-dnh-950/50 backdrop-blur-sm lg:hidden" @click="menu = false"></div>
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col overflow-hidden bg-dnh-950 text-white transition-transform lg:sticky lg:top-0 lg:h-screen">
        {{-- Emblème en filigrane, très discret, en bas du menu. --}}
        <x-logo-dnh variante="embleme" class="pointer-events-none absolute -bottom-10 -right-16 h-56 w-auto text-white opacity-[0.04]" aria-hidden="true" />
        <a href="{{ route('organigramme') }}" class="relative block border-b border-white/10 px-5 pb-4 pt-5" title="Voir l'organigramme public">
            <x-logo-dnh class="h-auto w-full text-white" />
            <span class="mt-3 flex items-center gap-2 font-titre text-[11px] font-semibold uppercase tracking-[0.18em] text-dnh-200">
                <span class="h-px flex-1 bg-white/15"></span>Administration<span class="h-px flex-1 bg-white/15"></span>
            </span>
        </a>
        <nav class="relative flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Menu d'administration">
            @foreach ($menu as [$libelle, $route, $motif, $visible, $icone])
                @continue(! $visible)
                <a href="{{ route($route) }}" @if (request()->routeIs($motif)) aria-current="page" @endif @class(['group relative flex items-center gap-3 rounded-md px-3 py-2.5 font-titre text-[15px] font-semibold uppercase tracking-wider transition focus-visible:ring-offset-dnh-950',
                        'bg-white/10 text-white' => request()->routeIs($motif), 'text-dnh-200 hover:bg-white/5 hover:text-white' => ! request()->routeIs($motif)])>
                    {{-- Repère de l'élément actif : la couleur de la flamme. --}}
                    <span @class(['absolute inset-y-1.5 left-0 w-1 rounded-r bg-energie transition', 'opacity-100' => request()->routeIs($motif), 'opacity-0' => ! request()->routeIs($motif)]) aria-hidden="true"></span>
                    <svg @class(['h-5 w-5 shrink-0', 'text-energie' => request()->routeIs($motif), 'text-dnh-300 group-hover:text-white' => ! request()->routeIs($motif)]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                    {{ $libelle }}
                </a>
            @endforeach
        </nav>
        <div class="relative">
            <a href="{{ route('organigramme') }}" class="mx-3 mb-3 flex items-center justify-center gap-2 rounded-md border border-white/15 px-3 py-2 font-titre text-[13px] font-semibold uppercase tracking-wider text-dnh-100 transition hover:border-white/30 hover:bg-white/5 hover:text-white focus-visible:ring-offset-dnh-950">← Organigramme public</a>
            <x-tricolore />
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Barre supérieure --}}
        <header class="sticky top-0 z-20 flex min-h-16 items-center gap-3 border-b border-fond-bord bg-white/95 px-4 py-3 backdrop-blur lg:px-8">
            <button type="button" class="rounded-md p-1.5 text-dnh-900 hover:bg-dnh-50 lg:hidden" @click="menu = true" aria-label="Ouvrir le menu" :aria-expanded="menu.toString()">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">{{ $header ?? '' }}</div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-md px-2 py-1 text-sm transition hover:bg-dnh-50" aria-label="Menu du compte{{ $nonLues ? ' – '.$nonLues.' notification(s) non lue(s)' : '' }}">
                        <img src="{{ $u->agent?->miniature_url ?? asset(\App\Models\Agent::AVATAR_DEFAUT) }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-dnh-100">
                        <span class="hidden text-left sm:block">
                            <span class="block font-semibold leading-tight text-dnh-950">{{ $u->name }}</span>
                            <span class="block text-xs text-slate-500">{{ \App\Http\Requests\Admin\UserRequest::ROLES[$u->getRoleNames()->first()] ?? '' }}</span>
                        </span>
                        @if ($nonLues)
                            <span class="rounded-full bg-energie px-1.5 text-xs font-bold text-dnh-950" title="Notifications non lues">{{ $nonLues }}</span>
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

        <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            @if ($errors->any() && ! isset($masquerErreurs))
                <div class="mb-6 rounded-md border border-red-200 border-l-4 border-l-red-600 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
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
        <div x-transition class="pointer-events-auto flex items-center gap-3 rounded-md border-l-4 border-energie bg-dnh-950 px-4 py-3 text-sm text-white shadow-flottant">
            <svg class="h-5 w-5 shrink-0 text-energie" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.86-9.86a.75.75 0 00-1.22-.88l-3.24 4.53-1.6-1.6a.75.75 0 10-1.06 1.06l2.22 2.22a.75.75 0 001.14-.1l3.76-5.23z" clip-rule="evenodd"/></svg>
            <span x-text="n.message"></span>
        </div>
    </template>
</div>

@livewireScriptConfig
</body>
</html>
