{{-- Panneau latéral : fiche de l'agent et liste de ses projets (données chargées en JSON). --}}
<div x-show="panneau.ouvert" x-cloak class="fixed inset-0 z-40 bg-slate-900/30 lg:hidden" @click="fermerFiche()"></div>
<aside x-show="panneau.ouvert" x-cloak
       x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
       class="fixed inset-y-0 right-0 z-50 flex w-full max-w-lg flex-col bg-white shadow-2xl"
       role="dialog" aria-modal="true" aria-label="Fiche de l'agent">

    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Fiche de l'agent</h2>
        <a x-show="panneau.donnees?.agent" :href="@js(route('agents.fiche.pdf', '__ID__')).replace('__ID__', panneau.donnees?.agent?.id)"
           class="ml-auto mr-2 inline-flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.69L6.53 8.72a.75.75 0 00-1.06 1.06l4 4a.75.75 0 001.06 0l4-4a.75.75 0 10-1.06-1.06l-2.72 2.72V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
            Fiche PDF
        </a>
        <button type="button" @click="fermerFiche()" class="rounded-md p-1 text-slate-500 hover:bg-slate-100" aria-label="Fermer">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto">
        <p x-show="panneau.chargement" class="p-6 text-sm text-slate-500">Chargement…</p>
        <p x-show="panneau.donnees?.erreur" class="p-6 text-sm text-red-700" x-text="panneau.donnees?.erreur"></p>

        <template x-if="panneau.donnees && !panneau.donnees.erreur">
            <div>
                {{-- Identité --}}
                <div class="flex gap-4 bg-dnh-50 px-5 py-5">
                    <img :src="panneau.donnees.agent.photo" :alt="'Photo de ' + panneau.donnees.agent.nom" class="h-24 w-24 shrink-0 rounded-full object-cover ring-4 ring-white">
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-slate-900" x-text="panneau.donnees.agent.nom"></p>
                        <p class="text-sm font-medium text-dnh-800" x-text="panneau.donnees.agent.poste"></p>
                        <p class="text-sm text-slate-600" x-text="panneau.donnees.agent.structure"></p>
                        <p class="text-xs text-slate-500" x-show="panneau.donnees.agent.structureParente" x-text="panneau.donnees.agent.structureParente"></p>
                        <div class="mt-2 space-y-0.5 text-sm">
                            <a x-show="panneau.donnees.agent.email" :href="'mailto:' + panneau.donnees.agent.email" class="block truncate text-dnh-700 hover:underline" x-text="panneau.donnees.agent.email"></a>
                            <a x-show="panneau.donnees.agent.telephone" :href="'tel:' + panneau.donnees.agent.telephone" class="block text-slate-700" x-text="panneau.donnees.agent.telephone"></a>
                        </div>
                    </div>
                </div>

                {{-- Onglets : ses projets / projets de sa structure (chefs) --}}
                <div x-show="panneau.donnees.structure" class="flex border-b border-slate-200 px-5 text-sm">
                    <button type="button" @click="panneau.onglet = 'agent'" :class="panneau.onglet === 'agent' ? 'border-dnh-600 text-dnh-700' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 px-3 py-2 font-medium">Ses projets</button>
                    <button type="button" @click="panneau.onglet = 'structure'" :class="panneau.onglet === 'structure' ? 'border-dnh-600 text-dnh-700' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 px-3 py-2 font-medium">Projets de la structure</button>
                </div>

                <template x-for="vue in [panneau.onglet === 'structure' && panneau.donnees.structure
                        ? { titre: 'Projets de : ' + panneau.donnees.structure.nom, taux: panneau.donnees.structure.taux, projets: panneau.donnees.structure.projets, consolide: true }
                        : { titre: 'Projets affectés', taux: panneau.donnees.taux, projets: panneau.donnees.projets, consolide: false }]">
                    <section class="px-5 py-4">
                        <div class="mb-3 flex items-baseline justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-800" x-text="vue.titre"></h3>
                            <p x-show="vue.taux !== null" class="shrink-0 text-xs text-slate-500">
                                <span x-text="vue.consolide ? 'Taux consolidé' : 'Taux moyen'"></span> :
                                <span class="text-base font-bold text-slate-900" x-text="vue.taux + ' %'"></span>
                            </p>
                        </div>

                        <p x-show="!vue.projets.length" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Aucun projet en cours.</p>

                        <ul class="space-y-4">
                            <template x-for="projet in vue.projets" :key="projet.id">
                                <li class="rounded-xl border border-slate-200 p-4" :class="projet.enRetard ? 'border-red-300 bg-red-50/40' : ''">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-semibold text-slate-900" x-text="projet.titre"></p>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="classeStatut(projet.statut)" x-text="projet.statutLibelle"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        <span x-text="'Du ' + (projet.dateDebut ?? '—') + ' au ' + (projet.dateFin ?? '—')"></span>
                                        <span x-show="projet.role" x-text="' · ' + (projet.role === 'responsable' ? 'Responsable' : 'Membre')"></span>
                                        <span x-show="vue.consolide && projet.responsables" x-text="' · Resp. : ' + projet.responsables"></span>
                                        <span x-show="vue.consolide" x-text="' · ' + projet.structure"></span>
                                    </p>
                                    <p x-show="projet.enRetard" class="mt-1 text-xs font-semibold text-red-700">⚠ En retard : échéance dépassée</p>

                                    {{-- Barre de progression : rouge < 30 %, orange 30–70 %, vert > 70 % --}}
                                    <div class="mt-3 flex items-center gap-3">
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" :aria-valuenow="projet.taux" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full transition-all" :class="couleurBarre(projet.couleur)" :style="'width:' + projet.taux + '%'"></div>
                                        </div>
                                        <span class="w-10 text-right text-sm font-bold" :class="couleurTexte(projet.couleur)" x-text="projet.taux + '%'"></span>
                                    </div>

                                    {{-- Étapes --}}
                                    <ol class="mt-3 space-y-1.5">
                                        <template x-for="etape in projet.etapes">
                                            <li class="flex items-start gap-2 text-sm">
                                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                                                      :class="etape.terminee ? 'border-green-600 bg-green-600 text-white' : 'border-slate-300 bg-white'">
                                                    <svg x-show="etape.terminee" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                                </span>
                                                <span class="min-w-0">
                                                    <span :class="etape.terminee ? 'text-slate-800' : 'text-slate-600'" x-text="etape.libelle"></span>
                                                    <span x-show="etape.terminee" class="ml-1 text-xs font-medium text-green-700" x-text="'✔ ' + etape.date"></span>
                                                    <span x-show="etape.commentaire" class="block text-xs italic text-slate-500" x-text="etape.commentaire"></span>
                                                </span>
                                            </li>
                                        </template>
                                    </ol>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>
        </template>
    </div>
</aside>
