<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
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

                {{-- Case à cocher : ouvre la validation (date, commentaire, pièces jointes) ; décoche après confirmation. --}}
                <button type="button" @disabled(! $this->peutCocher)
                        @if ($etape->est_terminee)
                            wire:click="basculer({{ $etape->id }})" wire:confirm="Marquer l'étape « {{ $etape->libelle }} » comme non terminée ? Les pièces jointes sont conservées."
                        @else
                            wire:click="ouvrirValidation({{ $etape->id }})"
                        @endif
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

                    {{-- Pièces jointes de l'étape (justificatifs) --}}
                    @if ($etape->piecesJointes->isNotEmpty())
                        <ul class="mt-2 flex flex-wrap gap-2" aria-label="Pièces jointes de l'étape">
                            @foreach ($etape->piecesJointes as $pj)
                                <li wire:key="pj-{{ $pj->id }}" class="group flex max-w-xs items-center gap-2 rounded-md border border-fond-bord bg-white py-1 pl-1 pr-2 text-xs shadow-sm">
                                    <a href="{{ route('admin.projets.pieces-jointes.apercu', [$projet, $pj]) }}" target="_blank" rel="noopener"
                                       class="flex min-w-0 items-center gap-2 rounded hover:text-dnh-700" title="{{ $pj->est_visualisable ? 'Ouvrir' : 'Télécharger' }} {{ $pj->nom }}">
                                        @if ($pj->est_image)
                                            <img src="{{ route('admin.projets.pieces-jointes.apercu', [$projet, $pj]) }}" alt="" loading="lazy" class="h-8 w-8 shrink-0 rounded object-cover ring-1 ring-fond-bord">
                                        @else
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-dnh-50 font-titre text-[10px] font-bold uppercase text-dnh-800">{{ \Illuminate\Support\Str::of($pj->nom)->afterLast('.')->limit(4, '') }}</span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-slate-800">{{ $pj->nom }}</span>
                                            <span class="block text-[11px] text-slate-500">{{ $pj->taille_lisible }} · {{ $pj->est_visualisable ? 'Ouvrir' : 'Télécharger' }}</span>
                                        </span>
                                    </a>
                                    <a href="{{ route('admin.projets.pieces-jointes.show', [$projet, $pj]) }}" class="shrink-0 rounded p-1 text-slate-400 hover:bg-dnh-50 hover:text-dnh-700" title="Télécharger" aria-label="Télécharger {{ $pj->nom }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.69L6.53 8.72a.75.75 0 00-1.06 1.06l4 4a.75.75 0 001.06 0l4-4a.75.75 0 10-1.06-1.06l-2.72 2.72V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                                    </a>
                                    @if ($this->peutCocher)
                                        <button type="button" wire:click="supprimerPieceJointe({{ $pj->id }})" wire:confirm="Supprimer la pièce jointe « {{ $pj->nom }} » ?"
                                                class="shrink-0 rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Supprimer" aria-label="Supprimer {{ $pj->nom }}">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($etape->est_terminee && $this->peutCocher && $enValidation !== $etape->id)
                        <button type="button" wire:click="ouvrirValidation({{ $etape->id }})" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-dnh-700 hover:text-dnh-950 hover:underline">
                            📎 Joindre des pièces
                        </button>
                    @endif

                    {{-- Formulaire de validation : date, commentaire et pièces jointes --}}
                    @if ($enValidation === $etape->id)
                        <form wire:submit="valider" class="mt-3 rounded-md border border-dnh-200 bg-dnh-50/60 p-4">
                            <p class="font-titre text-sm font-semibold uppercase tracking-wider text-dnh-950">
                                {{ $etape->est_terminee ? 'Joindre des pièces à l\'étape' : 'Valider l\'étape' }}
                            </p>

                            @unless ($etape->est_terminee)
                                <div class="mt-3 grid gap-3 sm:grid-cols-[auto,1fr]">
                                    <div>
                                        <label for="validation-date-{{ $etape->id }}" class="etiquette">Date de réalisation</label>
                                        <input id="validation-date-{{ $etape->id }}" type="date" wire:model="validation.date_realisation" max="{{ now()->toDateString() }}" class="champ">
                                        @error('validation.date_realisation') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="validation-commentaire-{{ $etape->id }}" class="etiquette">Commentaire <span class="font-normal text-slate-500">(facultatif)</span></label>
                                        <input id="validation-commentaire-{{ $etape->id }}" type="text" wire:model="validation.commentaire" maxlength="1000" class="champ">
                                        @error('validation.commentaire') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endunless

                            {{-- Fichiers : chaque sélection ou dépôt s'ajoute à la liste, pour joindre plusieurs
                                 fichiers à la fois, y compris venant de dossiers différents. --}}
                            <div class="mt-3"
                                 x-data="{
                                    envoi: false, progression: 0, survol: false,
                                    deposer(fichiers) {
                                        if (! fichiers.length) return;
                                        this.envoi = true; this.progression = 0;
                                        this.$wire.uploadMultiple('nouveauxFichiers', fichiers,
                                            () => { this.envoi = false },
                                            () => { this.envoi = false },
                                            (e) => { this.progression = e.detail.progress });
                                    },
                                 }"
                                 x-on:livewire-upload-start="envoi = true; progression = 0"
                                 x-on:livewire-upload-finish="envoi = false; $refs.champ.value = ''"
                                 x-on:livewire-upload-error="envoi = false; $refs.champ.value = ''"
                                 x-on:livewire-upload-progress="progression = $event.detail.progress">
                                <p class="etiquette">
                                    Pièces jointes <span class="font-normal text-slate-500">({{ $etape->est_terminee ? 'au moins une' : 'facultatif' }}, {{ \App\Livewire\Admin\EtapesProjet::MAX_FICHIERS }} au plus, 10 Mo chacune)</span>
                                </p>
                                <label for="validation-fichiers-{{ $etape->id }}"
                                       x-on:dragover.prevent="survol = true" x-on:dragleave.prevent="survol = false"
                                       x-on:drop.prevent="survol = false; deposer([...$event.dataTransfer.files])"
                                       :class="survol ? 'border-dnh-500 bg-white' : 'border-dnh-200 bg-white/60 hover:border-dnh-400'"
                                       class="mt-1 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed px-4 py-5 text-center transition">
                                    <svg class="h-7 w-7 text-dnh-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                                    <span class="text-sm font-medium text-dnh-950">Glissez vos fichiers ici, ou <span class="text-dnh-700 underline">parcourez</span></span>
                                    <span class="text-xs text-slate-500">Sélectionnez-en plusieurs à la fois (Ctrl ou Maj) ; chaque nouvelle sélection s'ajoute à la liste.</span>
                                    <input id="validation-fichiers-{{ $etape->id }}" x-ref="champ" type="file" multiple wire:model="nouveauxFichiers"
                                           accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods" class="sr-only">
                                </label>
                                <div x-show="envoi" x-cloak class="mt-2 h-1.5 overflow-hidden rounded-full bg-dnh-100" role="progressbar" :aria-valuenow="progression" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full bg-energie transition-all" :style="`width: ${progression}%`"></div>
                                </div>
                                @error('fichiers') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                @error('fichiers.*') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                @error('nouveauxFichiers.*') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror

                                @if ($fichiers)
                                    <p class="mt-2 text-xs font-medium text-dnh-950">{{ count($fichiers) }} fichier(s) prêt(s) à joindre</p>
                                    <ul class="mt-1 space-y-1 text-xs">
                                        @foreach ($fichiers as $i => $fichier)
                                            <li wire:key="fichier-{{ $i }}-{{ $fichier->getFilename() }}" class="flex items-center justify-between gap-2 rounded bg-white px-2 py-1 ring-1 ring-fond-bord">
                                                <span class="truncate">📎 {{ $fichier->getClientOriginalName() }} <span class="text-slate-500">· {{ \Illuminate\Support\Number::fileSize($fichier->getSize(), precision: 1) }}</span></span>
                                                <button type="button" wire:click="retirerFichier({{ $i }})" class="shrink-0 text-slate-400 hover:text-red-600" aria-label="Retirer {{ $fichier->getClientOriginalName() }}">✕</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button class="btn-primaire py-1.5" wire:loading.attr="disabled" wire:target="nouveauxFichiers,valider">
                                    <span wire:loading.remove wire:target="valider">{{ $etape->est_terminee ? 'Joindre' : 'Valider l\'étape' }}</span>
                                    <span wire:loading wire:target="valider">Enregistrement…</span>
                                </button>
                                <button type="button" wire:click="fermerValidation" class="btn-secondaire py-1.5">Annuler</button>
                            </div>
                        </form>
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
</div>
