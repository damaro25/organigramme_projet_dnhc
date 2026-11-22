{{--
    Visionneuse de pièces jointes, ouverte par l'événement « voir-fichier »
    (données : PieceJointe::pourVisionneuse()). PDF et images s'affichent dans la page ;
    les documents Word, Excel, PowerPoint… se téléchargent.
--}}
<div x-data="{ ouvert: false, f: {} }"
     x-effect="document.body.dataset.visionneuse = ouvert ? 'ouverte' : ''"
     @voir-fichier.window="f = $event.detail; ouvert = true; $nextTick(() => $refs.fermer.focus())"
     @keydown.escape.window="ouvert = false"
     x-show="ouvert" x-cloak x-transition.opacity
     class="sous-menu fixed bottom-0 right-0 z-superpose flex items-center justify-center bg-dnh-950/70 p-2 sm:p-6"
     @click.self="ouvert = false">
    <div class="flex h-full w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-flottant" role="dialog" aria-modal="true" :aria-label="'Aperçu de ' + f.nom">
        <div class="flex items-center gap-3 border-b border-fond-bord px-4 py-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-dnh-50 font-titre text-[10px] font-bold uppercase text-dnh-800" x-text="(f.nom ?? '').split('.').pop().slice(0, 4)"></span>
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-sm font-semibold text-dnh-950" x-text="f.nom"></h2>
                <p class="text-xs text-slate-500" x-text="f.taille"></p>
            </div>
            <a x-show="f.type !== 'autre'" :href="f.apercu" target="_blank" rel="noopener" class="hidden rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 sm:inline-block">Ouvrir dans un onglet</a>
            <a :href="f.telechargement" class="rounded-md bg-dnh-950 px-3 py-1.5 text-xs font-medium text-white hover:bg-dnh-800">Télécharger</a>
            <button type="button" x-ref="fermer" @click="ouvert = false" class="rounded p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Fermer l'aperçu">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
            </button>
        </div>
        <div class="min-h-0 flex-1 bg-slate-100">
            <template x-if="ouvert && f.type === 'pdf'">
                <iframe :src="f.apercu" class="h-full w-full" :title="f.nom"></iframe>
            </template>
            <template x-if="ouvert && f.type === 'image'">
                <div class="flex h-full items-center justify-center overflow-auto p-4"><img :src="f.apercu" :alt="f.nom" class="max-h-full max-w-full rounded shadow"></div>
            </template>
            <template x-if="ouvert && f.type === 'autre'">
                <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                    <p class="text-sm text-slate-700">L'aperçu n'est pas disponible pour ce format (Word, Excel, PowerPoint…).</p>
                    <a :href="f.telechargement" class="rounded-md bg-dnh-950 px-4 py-2 text-sm font-medium text-white hover:bg-dnh-800">Télécharger le fichier</a>
                </div>
            </template>
        </div>
    </div>
</div>
