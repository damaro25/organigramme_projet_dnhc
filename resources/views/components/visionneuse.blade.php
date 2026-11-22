{{--
    Visionneuse de fichiers, placée une seule fois dans le gabarit d'administration.
    S'ouvre sur l'événement « voir-fichier » : { url, telechargement, nom, image }.
    PDF dans le lecteur intégré du navigateur, photos en image ; Échap ou clic en
    dehors pour fermer.
--}}
<div x-data="{ ouvert: false, f: {} }"
     @voir-fichier.window="f = $event.detail; ouvert = true"
     @keydown.escape.window="ouvert = false"
     x-show="ouvert" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-2 sm:p-6"
     @click.self="ouvert = false">
    <div class="flex h-full w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" role="dialog" aria-modal="true" :aria-label="'Aperçu de ' + f.nom">
        <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-2.5">
            <h2 class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800" x-text="f.nom"></h2>
            <a :href="f.url" target="_blank" rel="noopener" class="btn-secondaire py-1.5 text-xs">Ouvrir dans un onglet ↗</a>
            <a :href="f.telechargement" class="btn-primaire py-1.5 text-xs">Télécharger</a>
            <button type="button" @click="ouvert = false" class="rounded p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Fermer">✕</button>
        </div>
        <div class="min-h-0 flex-1 bg-slate-100">
            <template x-if="ouvert && f.image">
                <div class="flex h-full items-center justify-center overflow-auto p-4">
                    <img :src="f.url" :alt="f.nom" class="max-h-full max-w-full rounded shadow">
                </div>
            </template>
            <template x-if="ouvert && ! f.image">
                <iframe :src="f.url" :title="f.nom" class="h-full w-full border-0"></iframe>
            </template>
        </div>
    </div>
</div>
