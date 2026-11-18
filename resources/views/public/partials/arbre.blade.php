{{-- Arbre, barre d'outils et panneau latéral, partagés entre la page publique et l'administration. --}}
<div class="{{ $classe ?? 'absolute inset-0' }} flex flex-col"
     x-data="organigramme({ urlDonnees: @js($urlDonnees), urlFiche: @js(route('agents.fiche', '__ID__')), admin: @js($admin ?? false) })"
     @keydown.escape.window="fermerFiche()">

    {{-- Barre d'outils --}}
    <div class="z-20 flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-4 py-2 lg:px-6">
        <div class="relative w-full sm:w-80">
            <label for="recherche-agent" class="sr-only">Rechercher un agent</label>
            <input id="recherche-agent" type="search" x-model="recherche" @input.debounce.150ms="chercher()"
                   @keydown.arrow-down.prevent="indexSuggestion = Math.min(indexSuggestion + 1, suggestions.length - 1)"
                   @keydown.arrow-up.prevent="indexSuggestion = Math.max(indexSuggestion - 1, 0)"
                   @keydown.enter.prevent="choisir(suggestions[indexSuggestion])"
                   placeholder="Rechercher un agent (nom, poste, section)…" autocomplete="off"
                   class="w-full rounded-lg border-slate-300 pl-9 text-sm focus:border-dnh-500 focus:ring-dnh-500">
            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.45 4.39l3.08 3.08a.75.75 0 11-1.06 1.06l-3.08-3.08A7 7 0 012 9z" clip-rule="evenodd"/></svg>
            <ul x-show="suggestions.length" x-cloak @click.outside="suggestions = []"
                class="absolute z-30 mt-1 max-h-80 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                <template x-for="(s, i) in suggestions" :key="s.agentId">
                    <li>
                        <button type="button" @click="choisir(s)" @mouseenter="indexSuggestion = i"
                                :class="i === indexSuggestion ? 'bg-dnh-50' : ''"
                                class="flex w-full items-center gap-3 px-3 py-2 text-left">
                            <img :src="s.photo" alt="" class="h-8 w-8 rounded-full object-cover">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium" x-text="s.nom"></span>
                                <span class="block truncate text-xs text-slate-500" x-text="s.poste + ' · ' + s.sigle"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" @click="zoomer(1)" class="btn-outil" title="Zoom avant" aria-label="Zoom avant">+</button>
            <button type="button" @click="zoomer(-1)" class="btn-outil" title="Zoom arrière" aria-label="Zoom arrière">−</button>
            <button type="button" @click="ajuster()" class="btn-outil px-3" title="Ajuster à l'écran">Ajuster</button>
            <button type="button" @click="toutDeplier()" class="btn-outil px-3">Tout déplier</button>
            <button type="button" @click="toutReplier()" class="btn-outil px-3">Replier</button>
            <a href="{{ route('organigramme.pdf') }}" class="btn-outil px-3" title="Télécharger l'organigramme complet en PDF (A3 paysage)">
                <svg class="mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.69L6.53 8.72a.75.75 0 00-1.06 1.06l4 4a.75.75 0 001.06 0l4-4a.75.75 0 10-1.06-1.06l-2.72 2.72V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>PDF
            </a>
        </div>

        <ul class="ml-auto hidden flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600 lg:flex" aria-label="Légende">
            @foreach ($divisions as $division)
                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm" style="background: {{ $division->couleur }}"></span>{{ $division->nom }}</li>
            @endforeach
            <li class="flex items-center gap-1.5"><span class="w-6 border-t-2 border-dashed border-slate-500"></span>Lien fonctionnel</li>
        </ul>
    </div>

    {{-- Arbre --}}
    <div class="relative min-h-0 flex-1">
        <div x-ref="arbre" class="h-full w-full"></div>
        <div x-show="chargement" class="absolute inset-0 flex items-center justify-center text-slate-500">Chargement de l'organigramme…</div>
        <div x-show="erreur" x-cloak class="absolute inset-0 flex items-center justify-center text-red-700" x-text="erreur"></div>
    </div>

    {{-- Panneau latéral --}}
    @include('public.partials.panneau-agent')
</div>
