<x-app-layout titre="Agents">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">Agents</h1>
            @can('create', App\Models\Agent::class)
                <a href="{{ route('admin.agents.create') }}" class="btn-primaire">+ Ajouter un agent</a>
            @endcan
        </div>
    </x-slot>

    <form method="GET" class="carte mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="recherche" class="etiquette">Recherche</label>
            <input id="recherche" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" placeholder="Nom, prénom ou poste" class="champ">
        </div>
        <div>
            <label for="division" class="etiquette">Division</label>
            <select id="division" name="division" class="champ" onchange="this.form.section.value=''; this.form.submit()">
                <option value="">Toutes</option>
                @foreach ($divisions as $d)
                    <option value="{{ $d->id }}" @selected(($filtres['division'] ?? null) == $d->id)>{{ $d->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="section" class="etiquette">Section</label>
            <select id="section" name="section" class="champ" onchange="this.form.submit()">
                <option value="">Toutes</option>
                @foreach ($sections->when($filtres['division'] ?? null, fn ($c, $d) => $c->where('parent_id', $d)) as $s)
                    <option value="{{ $s->id }}" @selected(($filtres['section'] ?? null) == $s->id)>{{ $s->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="etat" class="etiquette">État</label>
            <select id="etat" name="etat" class="champ" onchange="this.form.submit()">
                <option value="actifs" @selected(($filtres['etat'] ?? 'actifs') === 'actifs')>Actifs</option>
                <option value="inactifs" @selected(($filtres['etat'] ?? '') === 'inactifs')>Désactivés</option>
                <option value="tous" @selected(($filtres['etat'] ?? '') === 'tous')>Tous</option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <button class="btn-primaire">Filtrer</button>
            <a href="{{ route('admin.agents.index') }}" class="btn-secondaire">Réinitialiser</a>
        </div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead class="bg-slate-50"><tr><th>Agent</th><th>Poste</th><th>Structure</th><th>Supérieur</th><th>Contacts</th><th class="text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($agents as $agent)
                    <tr @class(['opacity-60' => ! $agent->actif])>
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ $agent->miniature_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $agent->nom_complet }}</p>
                                    @unless ($agent->actif)<span class="rounded bg-slate-200 px-1.5 text-xs">Désactivé</span>@endunless
                                    @if ($agent->est_responsable)<span class="rounded bg-dnh-100 px-1.5 text-xs text-dnh-800">Responsable</span>@endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $agent->poste }}</td>
                        <td>{{ $agent->structure?->libelle_court }}</td>
                        <td>{{ $agent->superieur?->nom_complet ?? '—' }}</td>
                        <td class="text-xs text-slate-600">{{ $agent->email }}<br>{{ $agent->telephone }}</td>
                        <td class="text-right">
                            @can('update', $agent)
                                <a href="{{ route('admin.agents.edit', $agent) }}" class="text-dnh-700 hover:underline">Modifier</a>
                            @elsecan('updatePhoto', $agent)
                                <a href="{{ route('admin.agents.edit', $agent) }}" class="text-dnh-700 hover:underline">Photo</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Aucun agent ne correspond aux filtres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $agents->links() }}</div>
</x-app-layout>
