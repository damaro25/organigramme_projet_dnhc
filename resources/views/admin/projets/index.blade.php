<x-app-layout titre="Projets">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">Projets</h1>
            @can('create', App\Models\Projet::class)
                <a href="{{ route('admin.projets.create') }}" class="btn-primaire">+ Nouveau projet</a>
            @endcan
        </div>
    </x-slot>

    <form method="GET" class="carte mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label for="recherche" class="etiquette">Recherche</label>
            <input id="recherche" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" placeholder="Titre du projet" class="champ">
        </div>
        <div>
            <label for="statut" class="etiquette">Statut</label>
            <select id="statut" name="statut" class="champ">
                <option value="">Tous</option>
                @foreach (App\Enums\StatutProjet::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filtres['statut'] ?? '') === $s->value)>{{ $s->libelle() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="structure_id" class="etiquette">Division / section</label>
            <select id="structure_id" name="structure_id" class="champ">
                <option value="">Toutes</option>
                @foreach ($structures as $s)
                    <option value="{{ $s->id }}" @selected(($filtres['structure_id'] ?? null) == $s->id)>{{ $s->type->value === 'section' ? '— ' : '' }}{{ $s->nom }}</option>
                @endforeach
            </select>
        </div>
        @if ($agents->isNotEmpty())
            <div>
                <label for="agent_id" class="etiquette">Agent</label>
                <select id="agent_id" name="agent_id" class="champ">
                    <option value="">Tous</option>
                    @foreach ($agents as $a)
                        <option value="{{ $a->id }}" @selected(($filtres['agent_id'] ?? null) == $a->id)>{{ $a->nom_complet }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex flex-col justify-end gap-1 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="retard" value="1" @checked($filtres['retard'] ?? false) class="rounded border-slate-300 text-red-600"> En retard</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="archive" value="1" @checked($filtres['archive'] ?? false) class="rounded border-slate-300"> Archivés</label>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
            <button class="btn-primaire">Filtrer</button>
            <a href="{{ route('admin.projets.index') }}" class="btn-secondaire">Réinitialiser</a>
        </div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead class="bg-slate-50"><tr><th>Projet</th><th>Statut</th><th>Taux d'exécution</th><th>Responsable</th><th>Échéance</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($projets as $projet)
                    <tr @class(['bg-red-50/60' => $projet->est_en_retard])>
                        <td>
                            <a href="{{ route('admin.projets.edit', $projet) }}" class="font-medium text-dnh-800 hover:underline">{{ $projet->titre }}</a>
                            <p class="text-xs text-slate-500">{{ $projet->structure?->nom }}</p>
                        </td>
                        <td><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $projet->statut->classes() }}">{{ $projet->statut->libelle() }}</span>
                            @if ($projet->valide_le)<span class="ml-1 text-xs text-green-700" title="Validé par la direction">✔ validé</span>@endif</td>
                        <td><x-admin-barre-taux :taux="$projet->taux_execution" /></td>
                        <td>{{ $projet->responsables->pluck('nom_complet')->join(', ') ?: '—' }}</td>
                        <td class="whitespace-nowrap">
                            {{ $projet->date_fin_prevue?->format('d/m/Y') ?? '—' }}
                            @if ($projet->est_en_retard)
                                <span class="ml-1 rounded bg-red-600 px-1.5 py-0.5 text-xs font-semibold text-white" title="Échéance dépassée et taux inférieur à 100 %">⚠ En retard</span>
                            @endif
                        </td>
                        <td class="text-right"><a href="{{ route('admin.projets.edit', $projet) }}" class="text-dnh-700 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Aucun projet ne correspond aux filtres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $projets->links() }}</div>
</x-app-layout>
