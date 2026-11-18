<x-app-layout titre="Tableau de bord">
    <x-slot name="header"><h1 class="titre-page">Tableau de bord</h1></x-slot>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="carte p-5"><p class="text-xs font-medium uppercase text-slate-500">Taux d'exécution global</p><p class="mt-1 text-3xl font-bold text-dnh-800">{{ $tauxGlobal ?? 0 }} %</p></div>
        <div class="carte p-5"><p class="text-xs font-medium uppercase text-slate-500">Projets en retard</p><p class="mt-1 text-3xl font-bold text-red-700">{{ $enRetard }}</p></div>
        @foreach ($parStatut as $libelle => $nombre)
            <div class="carte p-5"><p class="text-xs font-medium uppercase text-slate-500">{{ $libelle }}</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $nombre }}</p></div>
        @endforeach
        <div class="carte p-5"><p class="text-xs font-medium uppercase text-slate-500">Agents actifs</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $nbAgents }}</p></div>
    </div>

    @if (auth()->user()->agent)
        <a href="{{ route('admin.agents.edit', auth()->user()->agent) }}" class="carte mt-6 flex items-center gap-4 p-4 hover:bg-dnh-50">
            <img src="{{ auth()->user()->agent->miniature_url }}" alt="" class="h-12 w-12 rounded-full object-cover">
            <span><span class="block font-semibold">Ma fiche agent</span><span class="text-sm text-slate-500">Consulter ma fiche et modifier ma photo</span></span>
        </a>
    @endif

    @if ($mesProjets->isNotEmpty())
        <section class="carte mt-6">
            <h2 class="border-b border-slate-200 px-5 py-3 font-semibold">Mes projets</h2>
            <ul class="divide-y divide-slate-100">
                @foreach ($mesProjets as $p)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <a href="{{ route('admin.projets.edit', $p) }}" class="font-medium text-dnh-700 hover:underline">{{ $p->titre }}</a>
                        <x-admin-barre-taux :taux="$p->taux_execution" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <p class="mt-6 text-sm text-slate-500">Les graphiques par division, le classement des sections et les filtres arriveront à l'étape 6.</p>
</x-app-layout>
