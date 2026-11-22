<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div>
            <h2 class="text-base titre-section text-slate-900">Étapes du projet</h2>
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

                    {{-- Justificatifs de l'étape --}}
                    @if ($etape->piecesJointes->isNotEmpty())
                        <x-liste-fichiers :pieces="$etape->piecesJointes" :projet="$projet" compact class="mt-2"
                                          suppression="livewire" :peut-supprimer="fn ($pj) => $this->peutRetirer($pj)" />
                    @endif

                    @if ($etape->est_terminee && $this->peutCocher)
                        <div class="mt-2" x-data="{ envoi: false, progression: 0 }"
                             x-on:livewire-upload-start="envoi = true" x-on:livewire-upload-finish="envoi = false"
                             x-on:livewire-upload-error="envoi = false" x-on:livewire-upload-progress="progression = $event.detail.progress">
                            @if (empty($fichiers[$etape->id]))
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-dashed border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-dnh-700 hover:border-dnh-500 hover:bg-dnh-50">
                                    <span aria-hidden="true">📎</span> Joindre des justificatifs
                                    <input type="file" multiple wire:model="fichiers.{{ $etape->id }}" accept="{{ \App\Models\PieceJointe::accept() }}" class="sr-only">
                                </label>
                                <span x-show="envoi" x-cloak class="ml-2 text-xs text-slate-500">Envoi… <span x-text="progression"></span> %</span>
                            @else
                                <div class="rounded-md border border-dnh-200 bg-dnh-50 px-3 py-2 text-xs">
                                    <p class="font-medium text-slate-700">{{ count($fichiers[$etape->id]) }} fichier(s) à joindre :</p>
                                    <ul class="mt-1 list-inside list-disc text-slate-600">
                                        @foreach ($fichiers[$etape->id] as $f)
                                            <li class="truncate">{{ $f->getClientOriginalName() }} <span class="text-slate-400">({{ \App\Models\PieceJointe::octetsLisibles($f->getSize()) }})</span></li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-2 flex gap-2">
                                        <button type="button" wire:click="joindre({{ $etape->id }})" wire:loading.attr="disabled" wire:target="joindre({{ $etape->id }})" class="btn-primaire py-1 text-xs">
                                            <span wire:loading.remove wire:target="joindre({{ $etape->id }})">Joindre</span>
                                            <span wire:loading wire:target="joindre({{ $etape->id }})">Enregistrement…</span>
                                        </button>
                                        <button type="button" wire:click="viderSelection({{ $etape->id }})" class="btn-secondaire py-1 text-xs">Annuler</button>
                                    </div>
                                </div>
                            @endif
                            @error("fichiers.{$etape->id}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @foreach ($errors->get("fichiers.{$etape->id}.*") as $messages)
                                @foreach ($messages as $message) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @endforeach
                            @endforeach
                            <p class="mt-1 text-[11px] text-slate-400">PDF, Word, Excel, PowerPoint ou photos · {{ \App\Models\PieceJointe::tailleMaxLisible() }} maximum par fichier</p>
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
</div>
