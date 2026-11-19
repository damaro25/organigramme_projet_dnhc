<div class="rounded-xl border border-slate-200 bg-white shadow-sm"
     x-data="{ apercu: null }" x-on:keydown.escape.window="apercu = null">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Étapes du projet</h2>
            <p class="text-xs text-slate-500">Le taux d'exécution est pondéré par le poids de chaque étape.</p>
        </div>
        @php($couleur = \App\Models\Projet::couleurPourTaux($this->taux))
        <div class="flex w-full items-center gap-3 sm:w-72">
            <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $this->taux }}" aria-valuemin="0" aria-valuemax="100">
                <div @class(['h-full rounded-full transition-all duration-500', 'bg-red-600' => $couleur === 'rouge', 'bg-orange-500' => $couleur === 'orange', 'bg-green-600' => $couleur === 'vert'])
                     style="width: {{ $this->taux }}%"></div>
            </div>
            <span class="w-12 text-right text-lg font-bold text-slate-900">{{ $this->taux }}%</span>
        </div>
    </div>

    <ol x-data x-init="
            @if ($this->peutGerer)
            new window.Sortable($el, {
                handle: '.poignee', animation: 150, ghostClass: 'bg-dnh-50',
                onEnd: () => $wire.reordonner([...$el.querySelectorAll(':scope > li')].map((li) => li.dataset.id)),
            })
            @endif
        " class="divide-y divide-slate-100">
        @forelse ($this->etapes as $etape)
            <li wire:key="etape-{{ $etape->id }}" data-id="{{ $etape->id }}"
                @class(['flex items-start gap-3 px-5 py-3', 'bg-green-50/50' => $etape->est_terminee])>
                @if ($this->peutGerer)
                    <button type="button" class="poignee mt-1 cursor-grab text-slate-400 hover:text-slate-600" title="Glisser pour réordonner" aria-label="Déplacer l'étape">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm6 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM7 11.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm6 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM7 19a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm6 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
                    </button>
                @endif

                {{-- Case à cocher : coche verte immédiate --}}
                <button type="button" wire:click="basculer({{ $etape->id }})" @disabled(! $this->peutCocher)
                        @class(['mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2 transition',
                            'border-green-600 bg-green-600 text-white' => $etape->est_terminee,
                            'border-slate-300 bg-white hover:border-green-500' => ! $etape->est_terminee,
                            'cursor-not-allowed opacity-60' => ! $this->peutCocher])
                        title="{{ $etape->est_terminee ? 'Marquer comme non terminée' : 'Marquer comme terminée' }}"
                        aria-pressed="{{ $etape->est_terminee ? 'true' : 'false' }}">
                    @if ($etape->est_terminee)
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    @endif
                </button>

                <div class="min-w-0 flex-1">
                    @if ($enEdition === $etape->id)
                        <form wire:submit="enregistrer" class="flex flex-wrap items-start gap-2">
                            <div class="min-w-48 flex-1">
                                <input type="text" wire:model="edition.libelle" class="w-full rounded-md border-slate-300 text-sm" aria-label="Libellé">
                                @error('edition.libelle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="number" min="1" max="100" wire:model="edition.poids" class="w-20 rounded-md border-slate-300 text-sm" aria-label="Poids">
                                @error('edition.poids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button class="btn-primaire py-1.5">Enregistrer</button>
                            <button type="button" wire:click="annuler" class="btn-secondaire py-1.5">Annuler</button>
                        </form>
                    @else
                        <p @class(['text-sm', 'font-medium text-slate-900' => $etape->est_terminee, 'text-slate-700' => ! $etape->est_terminee])>
                            {{ $etape->libelle }}
                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500" title="Poids de l'étape">poids {{ $etape->poids }}</span>
                        </p>
                    @endif

                    @if ($etape->est_terminee)
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-semibold text-green-700">✔ Terminée le</span>
                            <input type="date" wire:model="cochage.{{ $etape->id }}.date_realisation" wire:change="enregistrerCochage({{ $etape->id }})"
                                   max="{{ now()->toDateString() }}" @disabled(! $this->peutCocher)
                                   class="rounded-md border-slate-300 py-1 text-xs" aria-label="Date de réalisation">
                            <input type="text" wire:model="cochage.{{ $etape->id }}.commentaire" wire:change="enregistrerCochage({{ $etape->id }})"
                                   placeholder="Commentaire (facultatif)" @disabled(! $this->peutCocher)
                                   class="min-w-48 flex-1 rounded-md border-slate-300 py-1 text-xs" aria-label="Commentaire">
                            @if ($etape->validateur)
                                <span class="text-slate-400">par {{ $etape->validateur->name }}</span>
                            @endif
                        </div>
                        @error("cochage.{$etape->id}.date_realisation") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @endif

                    {{-- Pièces jointes justificatives de l'étape --}}
                    @if ($etape->piecesJointes->isNotEmpty())
                        <ul class="mt-2 flex flex-wrap gap-2" aria-label="Pièces jointes de l'étape">
                            @foreach ($etape->piecesJointes as $pj)
                                @php($telechargement = route('admin.projets.pieces-jointes.show', [$projet, $pj]))
                                <li wire:key="pj-{{ $pj->id }}" class="group flex max-w-full items-center gap-1.5 rounded-lg border border-slate-200 bg-white py-1 pl-1 pr-2 text-xs shadow-sm">
                                    @if ($pj->est_image)
                                        <img src="{{ route('admin.projets.pieces-jointes.apercu', [$projet, $pj]) }}" alt="" loading="lazy" class="h-7 w-7 shrink-0 rounded object-cover">
                                    @else
                                        <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded text-[9px] font-bold uppercase',
                                            'bg-red-50 text-red-700' => $pj->est_pdf, 'bg-slate-100 text-slate-600' => ! $pj->est_pdf])>
                                            {{ \Illuminate\Support\Str::limit(pathinfo($pj->nom, PATHINFO_EXTENSION) ?: 'doc', 4, '') }}
                                        </span>
                                    @endif
                                    @if ($pj->apercu_possible)
                                        <button type="button" class="truncate text-left text-dnh-700 hover:underline" title="Visualiser {{ $pj->nom }}"
                                                x-on:click="apercu = @js(['url' => route('admin.projets.pieces-jointes.apercu', [$projet, $pj]), 'telechargement' => $telechargement, 'nom' => $pj->nom, 'image' => $pj->est_image])">
                                            {{ $pj->nom }}
                                        </button>
                                    @else
                                        <a href="{{ $telechargement }}" class="truncate text-dnh-700 hover:underline" title="Télécharger {{ $pj->nom }}">{{ $pj->nom }}</a>
                                    @endif
                                    <span class="shrink-0 text-slate-400" title="Ajouté par {{ $pj->auteur?->name ?? '—' }} le {{ $pj->created_at->format('d/m/Y') }}">{{ $pj->taille_lisible }}</span>
                                    <a href="{{ $telechargement }}" class="shrink-0 text-slate-400 hover:text-dnh-700" title="Télécharger" aria-label="Télécharger {{ $pj->nom }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.69L6.28 8.47a.75.75 0 00-1.06 1.06l4.25 4.25a.75.75 0 001.06 0l4.25-4.25a.75.75 0 10-1.06-1.06l-2.97 2.97V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                                    </a>
                                    @if ($this->peutGerer || ($this->peutCocher && $pj->ajoute_par === auth()->id()))
                                        <button type="button" wire:click="supprimerPieceJointe({{ $pj->id }})" wire:confirm="Supprimer la pièce jointe « {{ $pj->nom }} » ?"
                                                class="shrink-0 text-slate-400 hover:text-red-600" title="Supprimer" aria-label="Supprimer {{ $pj->nom }}">✕</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($etape->est_terminee && $this->peutCocher)
                        <div class="mt-2" x-data="{ progression: 0 }"
                             x-on:livewire-upload-start="progression = 1"
                             x-on:livewire-upload-progress="progression = $event.detail.progress"
                             x-on:livewire-upload-error="progression = 0"
                             x-on:livewire-upload-finish="progression = 0">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-dashed border-slate-300 px-2 py-1 text-xs text-slate-600 hover:border-dnh-500 hover:text-dnh-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.62 4.38a2.75 2.75 0 00-3.89 0l-6.3 6.3a1.75 1.75 0 102.47 2.47l5.25-5.25a.75.75 0 111.06 1.06L8.96 14.2a3.25 3.25 0 11-4.6-4.6l6.31-6.3a4.25 4.25 0 016.01 6.01l-6.3 6.3a.75.75 0 01-1.07-1.06l6.31-6.3a2.75 2.75 0 000-3.88z" clip-rule="evenodd"/></svg>
                                Joindre des fichiers
                                <input type="file" multiple class="sr-only"
                                       accept="{{ collect(\App\Models\PieceJointe::EXTENSIONS_ETAPE)->map(fn ($e) => '.'.$e)->implode(',') }}"
                                       wire:model="fichiers.{{ $etape->id }}"
                                       x-on:livewire-upload-finish="$wire.joindre({{ $etape->id }}).then(() => $el.value = '')">
                            </label>
                            <span x-show="progression > 0" x-cloak class="ml-2 text-xs text-slate-500">Envoi… <span x-text="progression + ' %'"></span></span>
                            <span wire:loading wire:target="joindre({{ $etape->id }})" class="ml-2 text-xs text-slate-500">Enregistrement…</span>
                            <p class="mt-1 text-[11px] text-slate-400">PDF, Word, Excel, PowerPoint ou images · {{ \App\Livewire\Admin\EtapesProjet::MAX_FICHIERS }} fichiers de 10 Mo maximum à la fois.</p>
                            @foreach (collect($errors->get("fichiers.{$etape->id}"))->merge(collect($errors->get("fichiers.{$etape->id}.*"))->flatten())->unique() as $message)
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($this->peutGerer && $enEdition !== $etape->id)
                    <div class="flex shrink-0 gap-1">
                        <button type="button" wire:click="editer({{ $etape->id }})" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-dnh-700" title="Modifier">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.6 3.4a2 2 0 012.8 2.8l-8.7 8.7-3.7.9.9-3.7 8.7-8.7z"/></svg>
                        </button>
                        <button type="button" wire:click="supprimer({{ $etape->id }})" wire:confirm="Supprimer l'étape « {{ $etape->libelle }} » ?" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Supprimer">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.44c-.8.08-1.58.17-2.37.29a.75.75 0 10.22 1.48l.15-.02.84 10.52A2.75 2.75 0 007.58 19h4.84a2.75 2.75 0 002.74-2.54L16 5.94l.15.02a.75.75 0 10.22-1.48A41 41 0 0014 4.19v-.44A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.67.03 2.5.08v-.33c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.33C8.33 4.03 9.16 4 10 4z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif
            </li>
        @empty
            <li class="px-5 py-6 text-sm text-slate-500">Aucune étape pour l'instant.</li>
        @endforelse
    </ol>

    @if ($this->peutGerer)
        <form wire:submit="ajouter" class="flex flex-wrap items-start gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
            <div class="min-w-56 flex-1">
                <label for="nouvelle-etape" class="sr-only">Nouvelle étape</label>
                <input id="nouvelle-etape" type="text" wire:model="nouvelleEtape.libelle" placeholder="Nouvelle étape…" class="w-full rounded-md border-slate-300 text-sm">
                @error('nouvelleEtape.libelle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-slate-600">Poids
                    <input type="number" min="1" max="100" wire:model="nouvelleEtape.poids" class="w-20 rounded-md border-slate-300 text-sm">
                </label>
                @error('nouvelleEtape.poids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primaire">Ajouter l'étape</button>
        </form>
    @endif
    {{-- Visionneuse des pièces jointes (PDF et images) --}}
    <template x-teleport="body">
        <div x-show="apercu" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4"
             x-on:click.self="apercu = null" role="dialog" aria-modal="true" :aria-label="apercu?.nom">
            <div class="flex h-full max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-2">
                    <p class="truncate text-sm font-medium text-slate-900" x-text="apercu?.nom"></p>
                    <div class="flex shrink-0 items-center gap-2">
                        <a :href="apercu?.url" target="_blank" rel="noopener" class="btn-secondaire py-1 text-xs">Ouvrir dans un onglet</a>
                        <a :href="apercu?.telechargement" class="btn-secondaire py-1 text-xs">Télécharger</a>
                        <button type="button" x-on:click="apercu = null" class="rounded p-1 text-slate-500 hover:bg-slate-100" aria-label="Fermer">✕</button>
                    </div>
                </div>
                <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-100">
                    <template x-if="apercu && apercu.image">
                        <img :src="apercu.url" :alt="apercu.nom" class="max-h-full max-w-full object-contain">
                    </template>
                    <template x-if="apercu && ! apercu.image">
                        <iframe :src="apercu.url" :title="apercu.nom" class="h-full w-full border-0"></iframe>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
