@if ($parParent->has($parentId))
    <ul data-freres @class(['space-y-1', 'ml-6 mt-1 border-l border-slate-200 pl-3' => $parentId !== $racine])>
        @foreach ($parParent->get($parentId) as $s)
            @php($modifiable = auth()->user()->can('update', $s))
            <li data-id="{{ $s->id }}">
                <div class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                    @if ($modifiable)
                        <button type="button" class="poignee cursor-grab text-slate-400" title="Glisser pour réordonner" aria-label="Déplacer">⠿</button>
                    @else
                        <span class="w-[1ch]" aria-hidden="true"></span>
                    @endif
                    <span class="h-4 w-4 shrink-0 rounded" style="background: {{ $s->couleur_effective }}" title="{{ $s->couleur ?? 'Couleur héritée de la division' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $s->nom }} @if ($s->sigle)<span class="text-slate-500">({{ $s->sigle }})</span>@endif</p>
                        <p class="text-xs text-slate-500">
                            {{ $s->type->libelle() }}@if ($s->groupe) · {{ $s->groupe->libelle() }}@endif
                            @if ($s->type_lien->value === 'fonctionnel') · <span class="italic">lien fonctionnel (pointillés)</span>@endif
                            · {{ $s->agents_count }} agent(s)
                        </p>
                    </div>
                    @can('create', \App\Models\Structure::class)
                        <a href="{{ route('admin.structures.create', ['parent_id' => $s->id]) }}" class="hidden text-xs text-slate-600 hover:underline sm:inline">+ Sous-structure</a>
                    @endcan
                    @if ($modifiable)
                        <a href="{{ route('admin.structures.edit', $s) }}" class="text-sm text-dnh-700 hover:underline">Modifier</a>
                    @endif
                </div>
                @include('admin.structures.partials.branche', ['parentId' => $s->id, 'racine' => $racine])
            </li>
        @endforeach
    </ul>
@endif
