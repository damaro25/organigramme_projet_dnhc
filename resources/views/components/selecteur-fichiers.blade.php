{{--
    Sélecteur de plusieurs fichiers pour un formulaire classique (non Livewire).

    Un <input type="file" multiple> natif remplace sa sélection à chaque ouverture :
    l'utilisateur qui choisit ses fichiers en plusieurs fois n'en envoie que le dernier
    lot. Ici, chaque choix (bouton ou glisser-déposer) s'ajoute à la liste, chaque
    fichier peut être retiré, et les formats, tailles et nombre sont vérifiés avant
    l'envoi. La vraie liste envoyée reste celle du champ natif (DataTransfer).
    Logique : Alpine.data('selecteurFichiers') dans resources/js/admin.js.
--}}
@props(['name' => 'pieces_jointes[]', 'id' => 'pieces-jointes'])

@use('App\Models\PieceJointe')
@php($tailleMax = PieceJointe::tailleMaxEffective())

<div x-data="selecteurFichiers({
        extensions: @js(PieceJointe::EXTENSIONS),
        tailleMax: {{ $tailleMax }},
        tailleMaxLisible: @js(PieceJointe::tailleMaxLisible()),
        nombreMax: {{ PieceJointe::NOMBRE_MAX }},
     })" {{ $attributes->merge(['class' => 'mt-2']) }}>

    <input type="file" id="{{ $id }}" name="{{ $name }}" multiple accept="{{ PieceJointe::accept() }}"
           x-ref="champ" @change="ajouter($event.target.files)" class="sr-only">

    <label for="{{ $id }}"
           @dragover.prevent="survol = true" @dragleave.prevent="survol = false" @drop.prevent="survol = false; ajouter($event.dataTransfer.files)"
           :class="survol ? 'border-dnh-500 bg-dnh-50' : 'border-slate-300 bg-white hover:border-dnh-500'"
           class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed px-4 py-5 text-center text-sm transition">
        <svg class="h-6 w-6 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
        <span><span class="font-medium text-dnh-700">Ajouter des fichiers</span> ou glissez-les ici</span>
        <span class="text-xs text-slate-500">PDF, Word, Excel, PowerPoint ou photos · {{ PieceJointe::tailleMaxLisible() }} maximum par fichier · {{ PieceJointe::NOMBRE_MAX }} fichiers maximum</span>
    </label>

    <ul x-show="fichiers.length" x-cloak class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white" aria-live="polite">
        <template x-for="(f, i) in fichiers" :key="f.name + f.size + f.lastModified">
            <li class="flex items-center gap-2 px-3 py-2 text-sm">
                <span class="min-w-0 flex-1 truncate" x-text="f.name"></span>
                <span class="shrink-0 text-xs text-slate-500" x-text="taille(f.size)"></span>
                <button type="button" @click="retirer(i)" class="shrink-0 rounded px-1.5 text-slate-400 hover:bg-red-50 hover:text-red-700" :aria-label="'Retirer ' + f.name" title="Retirer">✕</button>
            </li>
        </template>
    </ul>
    <p x-show="fichiers.length" x-cloak class="mt-1 text-xs text-slate-500"><span x-text="fichiers.length"></span> fichier(s) prêt(s), envoyé(s) à l'enregistrement.</p>

    <template x-for="erreur in erreurs" :key="erreur">
        <p class="mt-1 text-xs text-red-600" x-text="erreur"></p>
    </template>
</div>

