{{--
    Liste de pièces jointes avec « Voir » (visionneuse, pour les PDF et les photos)
    et « Télécharger ». Suppression facultative :
      - suppression="formulaire" : bouton relié au formulaire caché supprimer-pj-{id} ;
      - suppression="livewire"   : appelle retirerFichier(id) sur le composant Livewire ;
      - :peut-supprimer="fn ($pj) => …" pour décider fichier par fichier.
--}}
@props(['pieces', 'projet', 'suppression' => null, 'peutSupprimer' => null, 'compact' => false])

<ul {{ $attributes->merge(['class' => 'divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white']) }}>
    @foreach ($pieces as $pj)
        @php
            $apercu = route('admin.projets.pieces-jointes.apercu', [$projet, $pj]);
            $telechargement = route('admin.projets.pieces-jointes.show', [$projet, $pj]);
            $supprimable = $suppression && (! $peutSupprimer || $peutSupprimer($pj));
        @endphp
        <li wire:key="pj-{{ $pj->id }}" @class(['flex items-center gap-2 px-3', 'py-1.5 text-xs' => $compact, 'py-2 text-sm' => ! $compact])>
            <span aria-hidden="true">{{ $pj->icone }}</span>
            @if ($pj->est_visualisable)
                {{-- Liens et non boutons : ils restent actifs dans un <fieldset disabled> (lecture seule). --}}
                <a href="{{ $apercu }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate text-dnh-700 hover:underline"
                   @click.prevent="$dispatch('voir-fichier', @js(['url' => $apercu, 'telechargement' => $telechargement, 'nom' => $pj->nom, 'image' => $pj->est_image]))"
                   title="Voir {{ $pj->nom }}">{{ $pj->nom }}</a>
            @else
                <a href="{{ $telechargement }}" class="min-w-0 flex-1 truncate text-dnh-700 hover:underline" title="Télécharger {{ $pj->nom }}">{{ $pj->nom }}</a>
            @endif
            <span class="hidden shrink-0 text-slate-500 sm:inline">{{ $pj->taille_lisible }}</span>
            @if (! $compact && $pj->auteur)
                <span class="hidden shrink-0 text-xs text-slate-400 lg:inline">{{ $pj->auteur->name }}, {{ $pj->created_at->format('d/m/Y') }}</span>
            @endif

            @if ($pj->est_visualisable)
                <a href="{{ $apercu }}" target="_blank" rel="noopener" class="shrink-0 rounded px-1.5 py-0.5 font-medium text-dnh-700 hover:bg-dnh-50"
                   @click.prevent="$dispatch('voir-fichier', @js(['url' => $apercu, 'telechargement' => $telechargement, 'nom' => $pj->nom, 'image' => $pj->est_image]))">Voir</a>
            @endif
            <a href="{{ $telechargement }}" class="shrink-0 rounded px-1.5 py-0.5 text-slate-600 hover:bg-slate-100" title="Télécharger" aria-label="Télécharger {{ $pj->nom }}">⤓</a>

            @if ($supprimable)
                @if ($suppression === 'livewire')
                    <button type="button" wire:click="retirerFichier({{ $pj->id }})" wire:confirm="Supprimer « {{ $pj->nom }} » ?"
                            class="shrink-0 rounded px-1.5 py-0.5 text-red-700 hover:bg-red-50" aria-label="Supprimer {{ $pj->nom }}" title="Supprimer">✕</button>
                @else
                    <button type="submit" form="supprimer-pj-{{ $pj->id }}" class="shrink-0 rounded px-1.5 py-0.5 text-red-700 hover:bg-red-50" aria-label="Supprimer {{ $pj->nom }}" title="Supprimer">✕</button>
                @endif
            @endif
        </li>
    @endforeach
</ul>
