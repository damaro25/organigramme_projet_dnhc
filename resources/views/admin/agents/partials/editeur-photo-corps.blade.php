{{-- Contenu de l'éditeur de photo (composant Alpine « editeurPhoto »). --}}
<div class="space-y-3">
    <div class="flex items-center gap-4" x-show="!apercu">
        <img :src="photo" alt="Photo actuelle" class="h-24 w-24 rounded-full object-cover ring-4 ring-dnh-100">
        <div class="flex-1">
            <label @dragover.prevent="survol = true" @dragleave.prevent="survol = false"
                   @drop.prevent="survol = false; choisir($event.dataTransfer.files)"
                   :class="survol ? 'border-dnh-500 bg-dnh-50' : 'border-slate-300'"
                   class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-4 text-center text-sm text-slate-600 transition hover:border-dnh-400">
                <span>Glissez une photo ici ou <span class="font-semibold text-dnh-700 underline">Parcourir</span></span>
                <span class="text-xs text-slate-400">JPG, PNG ou WEBP · 2 Mo maximum</span>
                <input x-ref="fichier" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="choisir($event.target.files)">
            </label>
            <button type="button" x-show="aPhoto" @click="supprimer()" :disabled="envoi" class="mt-2 text-sm text-red-700 hover:underline">Supprimer la photo</button>
        </div>
    </div>

    {{-- Aperçu et recadrage carré --}}
    <div x-show="apercu" x-cloak class="space-y-3">
        <p class="text-sm text-slate-600">Ajustez le cadrage, puis enregistrez.</p>
        <div class="max-h-80 overflow-hidden rounded-lg bg-slate-900"><img x-ref="image" :src="apercu" alt="Aperçu à recadrer" class="block max-w-full"></div>
        <div class="flex gap-2">
            <button type="button" @click="enregistrer()" :disabled="envoi" class="btn-primaire">
                <span x-show="!envoi">Enregistrer la photo</span><span x-show="envoi">Envoi…</span>
            </button>
            <button type="button" @click="annuler()" :disabled="envoi" class="btn-secondaire">Annuler</button>
        </div>
    </div>

    <p x-show="erreur" x-cloak class="text-sm text-red-600" x-text="erreur" role="alert"></p>
</div>
