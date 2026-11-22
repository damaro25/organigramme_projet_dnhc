{{-- Un fichier dans le panneau de l'agent (variable Alpine « f », voir FicheAgentController::fichier). --}}
<li class="flex items-center gap-1.5 text-xs">
    <span aria-hidden="true" x-text="f.icone"></span>
    <a :href="f.visualisable ? f.url : f.telechargement" :target="f.visualisable ? '_blank' : null" rel="noopener"
       @click="if (f.visualisable) { $event.preventDefault(); $dispatch('voir-fichier', { url: f.url, telechargement: f.telechargement, nom: f.nom, image: f.image }) }"
       class="min-w-0 truncate text-dnh-700 hover:underline" :title="(f.visualisable ? 'Voir ' : 'Télécharger ') + f.nom" x-text="f.nom"></a>
    <span class="shrink-0 text-slate-400" x-text="f.taille"></span>
    <a :href="f.telechargement" class="shrink-0 rounded px-1 text-slate-500 hover:bg-slate-100" title="Télécharger" :aria-label="'Télécharger ' + f.nom">⤓</a>
</li>
