@php
    $creation = ! $projet->exists;
    $peutModifier = $creation ? auth()->user()->can('create', App\Models\Projet::class) : auth()->user()->can('update', $projet);
    $selection = old('affectations', $projet->agents?->map(fn ($a) => ['agent_id' => $a->id, 'role' => $a->pivot->role])->values()->all() ?? []);
    $selection = array_map(fn ($s) => ['agent_id' => (int) $s['agent_id'], 'role' => $s['role']], $selection);
    $listeAgents = $agents->map(fn ($a) => ['id' => $a->id, 'nom' => $a->nom_complet, 'poste' => $a->poste, 'structure' => $a->structure?->libelle_court, 'photo' => $a->miniature_url])->values();
@endphp
<x-app-layout :titre="$creation ? 'Nouveau projet' : $projet->titre">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="titre-page">
                {{ $creation ? 'Nouveau projet' : $projet->titre }}
                @if (! $creation && $projet->est_en_retard)
                    <span class="ml-2 rounded bg-red-600 px-2 py-0.5 align-middle text-xs font-semibold text-white">⚠ En retard</span>
                @endif
                @if ($projet->archive)<span class="ml-2 rounded bg-slate-200 px-2 py-0.5 align-middle text-xs">Archivé</span>@endif
            </h1>
            <a href="{{ route('admin.projets.index') }}" class="text-sm text-slate-600 hover:underline">← Retour à la liste</a>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-5">
        <form method="POST" enctype="multipart/form-data" action="{{ $creation ? route('admin.projets.store') : route('admin.projets.update', $projet) }}"
              class="carte space-y-4 p-5 xl:col-span-2">
            @csrf
            @unless ($creation) @method('PUT') @endunless
            <fieldset @disabled(! $peutModifier) class="space-y-4">
                <div>
                    <label for="titre" class="etiquette">Titre *</label>
                    <input id="titre" name="titre" value="{{ old('titre', $projet->titre) }}" required maxlength="255" class="champ">
                    <x-input-error :messages="$errors->get('titre')" class="mt-1" />
                </div>
                <div>
                    <label for="description" class="etiquette">Description</label>
                    <textarea id="description" name="description" rows="3" class="champ">{{ old('description', $projet->description) }}</textarea>
                </div>
                <div>
                    <label for="structure_id" class="etiquette">Structure *</label>
                    <select id="structure_id" name="structure_id" required class="champ">
                        <option value="">— Choisir —</option>
                        @foreach ($structures as $s)
                            <option value="{{ $s->id }}" @selected(old('structure_id', $projet->structure_id) == $s->id)>{{ $s->nom }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('structure_id')" class="mt-1" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="date_debut" class="etiquette">Début</label>
                        <input id="date_debut" type="date" name="date_debut" value="{{ old('date_debut', $projet->date_debut?->toDateString()) }}" class="champ">
                    </div>
                    <div>
                        <label for="date_fin_prevue" class="etiquette">Échéance</label>
                        <input id="date_fin_prevue" type="date" name="date_fin_prevue" value="{{ old('date_fin_prevue', $projet->date_fin_prevue?->toDateString()) }}" class="champ">
                        <x-input-error :messages="$errors->get('date_fin_prevue')" class="mt-1" />
                    </div>
                    <div>
                        <label for="statut" class="etiquette">Statut *</label>
                        <select id="statut" name="statut" class="champ">
                            @foreach ($statuts as $s)
                                <option value="{{ $s->value }}" @selected(old('statut', $projet->statut?->value) === $s->value)>{{ $s->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Affectation des agents --}}
                <div x-data="affectation({ agents: @js($listeAgents), selection: @js($selection) })">
                    <p class="etiquette">Agents affectés</p>
                    <ul class="mt-2 space-y-2">
                        <template x-for="(s, i) in selection" :key="s.agent_id">
                            <li class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1.5">
                                <img :src="agent(s.agent_id).photo" alt="" class="h-7 w-7 rounded-full object-cover">
                                <span class="min-w-0 flex-1 text-sm"><span class="font-medium" x-text="agent(s.agent_id).nom"></span>
                                    <span class="block truncate text-xs text-slate-500" x-text="agent(s.agent_id).poste + ' · ' + (agent(s.agent_id).structure ?? '')"></span></span>
                                <input type="hidden" :name="`affectations[${i}][agent_id]`" :value="s.agent_id">
                                <select :name="`affectations[${i}][role]`" x-model="s.role" class="rounded-md border-slate-300 py-1 text-xs" aria-label="Rôle">
                                    <option value="responsable">Responsable</option>
                                    <option value="membre">Membre</option>
                                </select>
                                <button type="button" @click="retirer(i)" class="rounded p-1 text-slate-400 hover:text-red-600" aria-label="Retirer">✕</button>
                            </li>
                        </template>
                    </ul>
                    <div class="relative mt-2">
                        <input type="search" x-model="recherche" @focus="ouvert = true" @click.outside="ouvert = false" placeholder="Rechercher un agent à affecter…" class="champ" aria-label="Rechercher un agent">
                        <ul x-show="ouvert && resultats.length" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                            <template x-for="a in resultats" :key="a.id">
                                <li><button type="button" @click="ajouter(a)" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-dnh-50">
                                    <img :src="a.photo" alt="" class="h-6 w-6 rounded-full object-cover"><span x-text="a.nom"></span>
                                    <span class="truncate text-xs text-slate-500" x-text="a.poste + ' · ' + (a.structure ?? '')"></span></button></li>
                            </template>
                        </ul>
                    </div>
                    <x-input-error :messages="$errors->get('affectations')" class="mt-1" />
                </div>

                {{-- Pièces jointes --}}
                <div>
                    <p class="etiquette">Pièces jointes (PDF, rapports…)</p>
                    @if (! $creation && $projet->piecesJointes->isNotEmpty())
                        <ul class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200">
                            @foreach ($projet->piecesJointes as $pj)
                                <li class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                                    <a href="{{ route('admin.projets.pieces-jointes.show', [$projet, $pj]) }}" class="truncate text-dnh-700 hover:underline">📎 {{ $pj->nom }}</a>
                                    @if ($pj->apercu_possible)
                                        <a href="{{ route('admin.projets.pieces-jointes.apercu', [$projet, $pj]) }}" target="_blank" rel="noopener" class="shrink-0 text-xs text-dnh-700 hover:underline">Voir</a>
                                    @endif
                                    <span class="shrink-0 text-xs text-slate-500">{{ $pj->taille_lisible }}</span>
                                    @if ($peutModifier)
                                        <button type="submit" form="supprimer-pj-{{ $pj->id }}" class="shrink-0 text-xs text-red-700 hover:underline">Supprimer</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <input type="file" name="pieces_jointes[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-dnh-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-dnh-800">
                    <p class="mt-1 text-xs text-slate-500">10 Mo maximum par fichier.</p>
                    <x-input-error :messages="$errors->get('pieces_jointes.*')" class="mt-1" />
                </div>
            </fieldset>

            @if ($peutModifier)
                <div class="border-t border-slate-200 pt-4">
                    <button class="btn-primaire">{{ $creation ? 'Créer le projet' : 'Enregistrer' }}</button>
                </div>
            @endif
        </form>

        <div class="space-y-6 xl:col-span-3">
            @if ($creation)
                <div class="carte p-6 text-sm text-slate-500">Les étapes pourront être ajoutées une fois le projet créé.</div>
            @else
                <livewire:admin.etapes-projet :projet="$projet" />

                {{-- Actions --}}
                <section class="carte flex flex-wrap items-center gap-2 p-5">
                    @if ($projet->valide_le)
                        <p class="mr-auto text-sm text-green-700">✔ Validé par {{ $projet->validateur?->name }} le {{ $projet->valide_le->format('d/m/Y') }}</p>
                    @else
                        <p class="mr-auto text-sm text-slate-500">Non encore validé par la direction.</p>
                    @endif
                    @can('valider', $projet)
                        <form method="POST" action="{{ route('admin.projets.valider', $projet) }}">@csrf @method('PATCH')<button class="btn-secondaire">Valider le projet</button></form>
                    @endcan
                    @can('dupliquer', $projet)
                        <form method="POST" action="{{ route('admin.projets.dupliquer', $projet) }}">@csrf<button class="btn-secondaire" title="Créer un nouveau projet à partir de celui-ci">Dupliquer comme modèle</button></form>
                    @endcan
                    @can('archiver', $projet)
                        <form method="POST" action="{{ route('admin.projets.archiver', $projet) }}">@csrf @method('PATCH')<button class="btn-secondaire">{{ $projet->archive ? 'Désarchiver' : 'Archiver' }}</button></form>
                    @endcan
                    @can('delete', $projet)
                        <form method="POST" action="{{ route('admin.projets.destroy', $projet) }}" onsubmit="return confirm('Supprimer définitivement ce projet, ses étapes et ses pièces jointes ?')">@csrf @method('DELETE')<button class="btn-danger">Supprimer</button></form>
                    @endcan
                </section>

                @foreach ($projet->piecesJointes as $pj)
                    <form id="supprimer-pj-{{ $pj->id }}" method="POST" action="{{ route('admin.projets.pieces-jointes.destroy', [$projet, $pj]) }}" onsubmit="return confirm('Supprimer cette pièce jointe ?')" class="hidden">@csrf @method('DELETE')</form>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
