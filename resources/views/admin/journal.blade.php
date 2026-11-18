<x-app-layout titre="Journal des modifications">
    <x-slot name="header">
        <div>
            <h1 class="titre-page">Journal des modifications</h1>
            <p class="text-sm text-slate-500">Qui a modifié quelle photo, quel projet ou quelle étape, et quand, avec l'ancienne et la nouvelle valeur.</p>
        </div>
    </x-slot>

    <form method="GET" class="carte mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div>
            <label for="type" class="etiquette">Type</label>
            <select id="type" name="type" class="champ">
                <option value="">Tous</option>
                @foreach ($types as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(($filtres['type'] ?? null) === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="utilisateur" class="etiquette">Utilisateur</label>
            <select id="utilisateur" name="utilisateur" class="champ">
                <option value="">Tous</option>
                @foreach ($utilisateurs as $u)
                    <option value="{{ $u->id }}" @selected((int) ($filtres['utilisateur'] ?? 0) === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="du" class="etiquette">Du</label>
            <input type="date" id="du" name="du" value="{{ $filtres['du'] ?? '' }}" class="champ">
        </div>
        <div>
            <label for="au" class="etiquette">Au</label>
            <input type="date" id="au" name="au" value="{{ $filtres['au'] ?? '' }}" class="champ">
            @error('au')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2">
            <button class="btn-primaire">Filtrer</button>
            @if (array_filter($filtres))
                <a href="{{ route('admin.journal') }}" class="btn-secondaire">Effacer</a>
            @endif
        </div>
    </form>

    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead class="bg-slate-50">
                <tr><th class="w-36">Date</th><th class="w-44">Auteur</th><th>Action</th><th>Modifications</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($activites as $a)
                    <tr class="align-top">
                        <td class="whitespace-nowrap text-slate-600">
                            {{ $a->created_at->format('d/m/Y') }}<br>
                            <span class="text-xs text-slate-400">{{ $a->created_at->format('H:i') }} · {{ $a->created_at->diffForHumans() }}</span>
                        </td>
                        <td>{{ $a->causer?->name ?? 'Système' }}</td>
                        <td>
                            <p class="font-medium text-slate-900">{{ $a->description }}</p>
                            <p class="text-xs text-slate-500">{{ $journal->sujet($a) }}</p>
                        </td>
                        <td>
                            @php $changements = $journal->changements($a); @endphp
                            @if ($changements->isEmpty())
                                <span class="text-slate-400">—</span>
                            @else
                                <dl class="space-y-1 text-sm">
                                    @foreach ($changements as $c)
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <dt class="font-medium text-slate-600">{{ $c['champ'] }} :</dt>
                                            <dd class="flex flex-wrap items-center gap-2">
                                                @if ($c['photo'])
                                                    @if ($c['ancien'] === \App\Services\JournalService::PHOTO_SUPPRIMEE)<span class="rounded bg-slate-100 px-1.5 text-xs text-slate-500">photo remplacée depuis</span>@elseif ($c['ancien'])<img src="{{ $c['ancien'] }}" alt="Ancienne photo" class="h-10 w-10 rounded-full object-cover opacity-70 ring-1 ring-slate-200">@else<span class="text-xs text-slate-400">avatar par défaut</span>@endif
                                                    <span class="text-slate-400" aria-hidden="true">→</span>
                                                    @if ($c['nouveau'] === \App\Services\JournalService::PHOTO_SUPPRIMEE)<span class="rounded bg-slate-100 px-1.5 text-xs text-slate-500">photo remplacée depuis</span>@elseif ($c['nouveau'])<img src="{{ $c['nouveau'] }}" alt="Nouvelle photo" class="h-10 w-10 rounded-full object-cover ring-2 ring-dnh-500">@else<span class="text-xs text-slate-400">avatar par défaut</span>@endif
                                                @else
                                                    @unless ($c['creation'])
                                                        <del class="rounded bg-red-50 px-1.5 text-red-800 decoration-red-300">{{ $c['ancien'] ?? 'vide' }}</del>
                                                        <span class="text-slate-400" aria-hidden="true">→</span>
                                                    @endunless
                                                    <ins class="rounded bg-green-50 px-1.5 text-green-800 no-underline">{{ $c['nouveau'] ?? 'vide' }}</ins>
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">Aucune modification enregistrée pour ces critères.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $activites->links() }}</div>
</x-app-layout>
