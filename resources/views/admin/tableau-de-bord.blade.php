<x-app-layout titre="Tableau de bord">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="titre-page">Tableau de bord</h1>
                @if ($perimetre)
                    <p class="text-sm text-slate-500">Périmètre : {{ $perimetre === 'vos projets' ? 'vos projets' : $perimetre.' et les structures en dessous' }}</p>
                @endif
            </div>
            <a href="{{ route('organigramme.pdf') }}" class="btn-secondaire py-1.5">⤓ Organigramme en PDF</a>
        </div>
    </x-slot>

    {{-- Filtres --}}
    <form method="GET" class="carte mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="division" class="etiquette">Division</label>
            <select id="division" name="division" class="champ" onchange="this.form.section.value=''; this.form.submit()">
                <option value="">Toutes</option>
                @foreach ($listeDivisions as $d)<option value="{{ $d->id }}" @selected(($filtres['division'] ?? null) == $d->id)>{{ $d->nom }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="section" class="etiquette">Section</label>
            <select id="section" name="section" class="champ" onchange="this.form.submit()">
                <option value="">Toutes</option>
                @foreach ($listeSections->when($filtres['division'] ?? null, fn ($c, $d) => $c->where('parent_id', $d)) as $s)
                    <option value="{{ $s->id }}" @selected(($filtres['section'] ?? null) == $s->id)>{{ $s->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="agent_id" class="etiquette">Agent</label>
            <select id="agent_id" name="agent_id" class="champ" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach ($listeAgents as $a)<option value="{{ $a->id }}" @selected(($filtres['agent_id'] ?? null) == $a->id)>{{ $a->nom_complet }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="statut" class="etiquette">Statut</label>
            <select id="statut" name="statut" class="champ" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach (App\Enums\StatutProjet::cases() as $s)<option value="{{ $s->value }}" @selected(($filtres['statut'] ?? '') === $s->value)>{{ $s->libelle() }}</option>@endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="btn-primaire">Filtrer</button>
            @if (array_filter($filtres))<a href="{{ route('admin.tableau-de-bord') }}" class="btn-secondaire">Réinitialiser</a>@endif
        </div>
    </form>

    {{-- Indicateurs --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-7">
        <div class="carte p-5 xl:col-span-2">
            <p class="text-xs font-medium uppercase text-slate-500">Taux d'exécution global de la DNH</p>
            <p class="mt-1 text-4xl font-bold text-dnh-800">{{ $tauxGlobal ?? 0 }} %</p>
            <div class="mt-3"><x-admin-barre-taux :taux="$tauxGlobal" /></div>
            <p class="mt-2 text-xs text-slate-500">{{ $total }} projet(s) en cours de suivi</p>
        </div>
        <a href="{{ route('admin.projets.index', ['retard' => 1]) }}" class="carte p-5 hover:bg-red-50">
            <p class="text-xs font-medium uppercase text-slate-500">En retard</p>
            <p class="mt-1 text-4xl font-bold text-red-700">{{ $enRetard->count() }}</p>
        </a>
        @foreach ($parStatut as $ligne)
            <a href="{{ route('admin.projets.index', ['statut' => $ligne['statut']->value]) }}" class="carte p-5 hover:bg-slate-50">
                <p class="text-xs font-medium uppercase text-slate-500">{{ $ligne['statut']->libelle() }}</p>
                <p class="mt-1 text-4xl font-bold text-slate-900">{{ $ligne['nombre'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        {{-- Comparaison des divisions --}}
        <section class="carte p-5">
            <h2 class="mb-4 font-semibold">Comparaison des divisions</h2>
            <div class="h-72" x-data="graphiqueDivisions({
                    libelles: @js($divisions->map(fn ($d) => $d['structure']->sigle ?: $d['structure']->nom)),
                    taux: @js($divisions->map(fn ($d) => $d['taux'] ?? 0)),
                    nombres: @js($divisions->pluck('nombre')),
                    couleurs: @js($divisions->pluck('couleur')),
                })">
                <canvas x-ref="canvas" role="img" aria-label="Taux d'exécution moyen et nombre de projets par division"></canvas>
            </div>
            <table class="tableau mt-4">
                <thead><tr><th>Division</th><th>Projets</th><th>En retard</th><th>Taux moyen</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($divisions as $d)
                        <tr>
                            <td><span class="mr-2 inline-block h-3 w-3 rounded-sm align-middle" style="background: {{ $d['couleur'] }}"></span>{{ $d['structure']->nom }}</td>
                            <td>{{ $d['nombre'] }}</td>
                            <td class="{{ $d['retards'] ? 'font-semibold text-red-700' : '' }}">{{ $d['retards'] }}</td>
                            <td><x-admin-barre-taux :taux="$d['taux']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- Classement des sections --}}
        <section class="carte p-5">
            <h2 class="mb-4 font-semibold">Classement des sections par taux d'exécution</h2>
            <ol class="space-y-2">
                @forelse ($sections as $i => $s)
                    <li class="flex items-center gap-3">
                        <span class="w-6 text-right text-sm font-bold text-slate-400">{{ $i + 1 }}</span>
                        <span class="h-8 w-1.5 rounded" style="background: {{ $s['couleur'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $s['structure']->nom }}</p>
                            <p class="text-xs text-slate-500">{{ $s['division']?->sigle }} · {{ $s['nombre'] }} projet(s){{ $s['retards'] ? ' · '.$s['retards'].' en retard' : '' }}</p>
                        </div>
                        <x-admin-barre-taux :taux="$s['taux']" />
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucun projet pour ces filtres.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Projets en retard --}}
    @if ($enRetard->isNotEmpty())
        <section class="carte mt-6">
            <h2 class="border-b border-slate-200 px-5 py-3 font-semibold text-red-800">⚠ Projets en retard</h2>
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead class="bg-slate-50"><tr><th>Projet</th><th>Structure</th><th>Responsable</th><th>Échéance</th><th>Taux</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($enRetard as $p)
                            <tr>
                                <td><a href="{{ route('admin.projets.edit', $p) }}" class="font-medium text-dnh-700 hover:underline">{{ $p->titre }}</a></td>
                                <td>{{ $p->structure?->libelle_court }}</td>
                                <td>{{ $p->responsables->pluck('nom_complet')->join(', ') }}</td>
                                <td class="font-semibold text-red-700">{{ $p->date_fin_prevue->format('d/m/Y') }}</td>
                                <td><x-admin-barre-taux :taux="$p->taux_execution" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @if (auth()->user()->agent)
            <a href="{{ route('admin.agents.edit', auth()->user()->agent) }}" class="carte flex items-center gap-4 p-4 hover:bg-dnh-50">
                <img src="{{ auth()->user()->agent->miniature_url }}" alt="" class="h-12 w-12 rounded-full object-cover">
                <span><span class="block font-semibold">Ma fiche agent</span><span class="text-sm text-slate-500">Consulter ma fiche et modifier ma photo</span></span>
            </a>
        @endif
        @if ($mesProjets->isNotEmpty())
            <section class="carte">
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
    </div>
</x-app-layout>
