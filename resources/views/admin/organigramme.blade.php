<x-app-layout titre="Organigramme">
    <x-slot name="header">
        <h1 class="titre-page">Organigramme <span class="ml-2 font-sans text-sm font-normal normal-case tracking-normal text-slate-500">Cliquez sur ✏️ pour changer la photo ou le poste sans quitter l'arbre.</span></h1>
    </x-slot>

    <div class="carte relative h-[calc(100vh-9rem)] overflow-hidden">
        @include('public.partials.arbre', ['urlDonnees' => route('admin.organigramme.donnees'), 'admin' => true, 'classe' => 'absolute inset-0'])
    </div>

    {{-- Modification rapide d'un nœud --}}
    <div x-data="editionNoeud({ urlPhoto: @js(route('admin.agents.photo.store', '__ID__')), urlPoste: @js(route('admin.agents.poste', '__ID__')), avatarDefaut: @js(asset(\App\Models\Agent::AVATAR_DEFAUT)) })"
         @editer-agent.window="ouvrir($event.detail)" @keydown.escape.window="ouvert = false">
        <div x-show="ouvert" x-cloak class="sous-menu fixed bottom-0 right-0 z-superpose flex items-center justify-center bg-slate-900/50 p-4" @click.self="ouvert = false">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="titre-edition">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h2 id="titre-edition" class="font-semibold" x-text="agent?.nom"></h2>
                    <button type="button" @click="ouvert = false" class="rounded p-1 text-slate-500 hover:bg-slate-100" aria-label="Fermer">✕</button>
                </div>
                <template x-if="ouvert && agent">
                    <div class="space-y-6 p-5">
                        <template x-if="agent.droits.photo">
                            <div x-data="editeurPhoto({ urlEnvoi: url(urlPhoto), urlSuppression: url(urlPhoto), photo: agent.photo, avatarDefaut, aPhoto: !agent.photo.includes('avatar-defaut') })"
                                 @photo-modifiee="window.dispatchEvent(new CustomEvent('organigramme-recharger'))">
                                @include('admin.agents.partials.editeur-photo-corps')
                            </div>
                        </template>
                        <template x-if="agent.droits.poste">
                            <form @submit.prevent="enregistrerPoste()" class="space-y-2">
                                <label for="poste-rapide" class="etiquette">Poste</label>
                                <div class="flex gap-2">
                                    <input id="poste-rapide" type="text" x-model="poste" maxlength="255" required class="champ mt-0">
                                    <button class="btn-primaire" :disabled="envoi">Enregistrer</button>
                                </div>
                                <p x-show="erreur" class="text-sm text-red-600" x-text="erreur"></p>
                            </form>
                        </template>
                        <a x-show="agent.droits.url" :href="agent.droits.url" class="inline-block text-sm text-dnh-700 hover:underline">Ouvrir la fiche complète de l'agent →</a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
