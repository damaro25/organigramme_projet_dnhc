{{-- Styleguide interne : référence visuelle de l'identité DNH pour les évolutions de l'application. --}}
@php
    $echelle = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
    $primaire = ['50' => '#eef2fb', '100' => '#d9e1f5', '200' => '#b5c4eb', '300' => '#869ddb', '400' => '#5672c6', '500' => '#3552ae',
        '600' => '#263f95', '700' => '#1c3079', '800' => '#13235c', '900' => '#0a1747', '950' => '#000b3d'];
    $autres = [
        ['Accent', 'accent-500', '#f2a900', 'Indicateurs, CTA secondaires, graphiques. Texte marine dessus.'],
        ['Accent texte', 'accent-700', '#b45309', 'Ambre lisible en texte sur fond blanc.'],
        ['Succès', 'success-600', '#1e7b4f', 'Terminé, validé.'],
        ['Avertissement', 'warning-600', '#b45309', 'Suspendu, attention.'],
        ['Danger', 'danger-600', '#b42335', 'Retard, suppression, erreur.'],
        ['Info', 'info-600', '#2f5bb7', 'Information neutre.'],
        ['Fond de page', 'fond', '#f5f7fb', 'Fond général.'],
        ['Toile', 'fond-toile', '#ecf0f7', "Fond de l'arbre."],
        ['Bordure', 'fond-bord', '#dde3ee', 'Bordures des cartes.'],
    ];
@endphp
<x-app-layout titre="Styleguide">
    <x-slot name="header">
        <h1 class="titre-page">Styleguide</h1>
        <p class="text-sm text-slate-600">Couleurs, typographie et composants de l'identité de la Direction Nationale des Hydrocarbures.</p>
    </x-slot>

    <div class="space-y-8">
        {{-- Logo --}}
        <x-card titre="Logo">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="flex items-center justify-center rounded-md bg-primary-950 p-6"><x-logo-dnh class="w-64" /></div>
                <div class="flex items-center justify-center rounded-md border border-fond-bord bg-white p-6"><x-logo-dnh variante="marine" class="w-64" /></div>
                <div class="flex items-center justify-center gap-6 rounded-md border border-fond-bord bg-fond p-6">
                    <x-logo-dnh symbole variante="marine" class="w-20" />
                    <img src="{{ asset('favicon.svg') }}" alt="Icône d'onglet" class="h-12 w-12">
                    <x-tricolore class="h-1.5 w-20" />
                </div>
            </div>
            <p class="aide mt-3">Blanc sur fonds sombres, marine sur fonds clairs ; ne pas déformer, recolorer ni ombrer. Le symbole seul sert aux petits formats.</p>
        </x-card>

        {{-- Couleurs --}}
        <x-card titre="Couleurs">
            <p class="etiquette mb-2">Primaire (marine du logo)</p>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                @foreach ($primaire as $nuance => $hex)
                    <div>
                        <div class="h-14 rounded-md border border-black/5" style="background: {{ $hex }}"></div>
                        <p class="mt-1 text-xs font-semibold">primary-{{ $nuance }}</p>
                        <p class="text-[11px] uppercase text-slate-600">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>
            <p class="etiquette mb-2 mt-6">Accent, états et surfaces</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($autres as [$nom, $classe, $hex, $usage])
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 shrink-0 rounded-md border border-black/5" style="background: {{ $hex }}"></div>
                        <div class="text-sm"><p class="font-semibold">{{ $nom }} <span class="font-normal text-slate-600">· {{ $classe }} · {{ strtoupper($hex) }}</span></p><p class="text-xs text-slate-600">{{ $usage }}</p></div>
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Typographie --}}
        <x-card titre="Typographie">
            <div class="space-y-4">
                <div><p class="aide">Titre de page · Barlow Condensed 600, capitales, 24 px</p><p class="titre-page">Organigramme de la DNH</p></div>
                <div><p class="aide">Titre de section · Barlow Condensed 600, capitales, 16 px</p><p class="titre-section">Comparaison des divisions</p></div>
                <div><p class="aide">Libellé · Barlow Condensed 600, capitales, 13 px</p><p class="etiquette">Date de réalisation</p></div>
                <div><p class="aide">Texte courant · Barlow 400, 16 px, interligne 1,5</p><p class="max-w-2xl">La Direction Nationale des Hydrocarbures suit l'approvisionnement en carburant, le contrôle des dépôts pétroliers et les contrats d'exploration.</p></div>
                <div><p class="aide">Échelle</p>
                    <p class="flex flex-wrap items-baseline gap-4">@foreach (['text-xs' => 12, 'text-sm' => 14, 'text-base' => 16, 'text-lg' => 18, 'text-xl' => 20, 'text-2xl' => 24, 'text-3xl' => 30, 'text-4xl' => 36] as $c => $px)<span class="{{ $c }}">{{ $px }}</span>@endforeach</p>
                </div>
            </div>
        </x-card>

        {{-- Boutons --}}
        <x-card titre="Boutons">
            <div class="flex flex-wrap items-center gap-3">
                <x-button type="button">Primaire</x-button>
                <x-button type="button" variante="secondaire">Secondaire</x-button>
                <x-button type="button" variante="tertiaire">Tertiaire</x-button>
                <x-button type="button" variante="danger">Supprimer</x-button>
                <x-button type="button" variante="accent">Accent</x-button>
                <x-button type="button" disabled>Désactivé</x-button>
                <button type="button" class="btn-primaire" disabled>
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3"/></svg>
                    Enregistrement…
                </button>
                <button type="button" class="btn-outil px-3">Outil</button>
            </div>
            <p class="aide mt-3">Focus clavier : anneau marine visible (touche Tab). Un seul bouton primaire par zone d'action.</p>
        </x-card>

        {{-- Formulaires --}}
        <x-card titre="Formulaires">
            <div class="grid gap-5 md:grid-cols-2">
                <x-input name="exemple_titre" label="Titre du projet" required aide="Visible dans la fiche de l'agent." value="Contrôle des dépôts de Kaloum" />
                <div>
                    <label for="exemple_erreur" class="etiquette">Échéance</label>
                    <input id="exemple_erreur" type="date" class="champ border-danger-600 focus:border-danger-600 focus:ring-danger-600" aria-invalid="true" aria-describedby="exemple_erreur_msg">
                    <p id="exemple_erreur_msg" class="mt-1 text-xs font-medium text-danger-700">L'échéance doit être postérieure ou égale à la date de début.</p>
                </div>
                <div>
                    <label for="exemple_select" class="etiquette">Statut</label>
                    <select id="exemple_select" class="champ"><option>En cours</option><option>Terminé</option></select>
                </div>
                <div class="flex items-end gap-6 pb-2">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" checked class="rounded border-slate-300 text-primary-950 focus:ring-primary-600"> Responsable</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="r" checked class="border-slate-300 text-primary-950 focus:ring-primary-600"> Membre</label>
                </div>
                <div class="md:col-span-2">
                    <label for="exemple_desactive" class="etiquette">Champ en lecture seule</label>
                    <input id="exemple_desactive" class="champ" value="Division Aval Pétrolier" disabled>
                </div>
            </div>
        </x-card>

        {{-- Badges et alertes --}}
        <div class="grid gap-8 lg:grid-cols-2">
            <x-card titre="Badges de statut">
                <div class="flex flex-wrap gap-2">
                    <x-badge statut="non_demarre">Non démarré</x-badge>
                    <x-badge statut="en_cours">En cours</x-badge>
                    <x-badge statut="termine">Terminé</x-badge>
                    <x-badge statut="suspendu">Suspendu</x-badge>
                    <x-badge couleur="danger">⚠ En retard</x-badge>
                    <x-badge couleur="accent">Nouveau</x-badge>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach ([18, 52, 86] as $t)<x-admin-barre-taux :taux="$t" />@endforeach
                </div>
                <p class="aide mt-2">Taux d'exécution : rouge sous 30 %, orange de 30 à 70 %, vert au-delà.</p>
            </x-card>
            <x-card titre="Alertes">
                <div class="space-y-3">
                    <x-alert titre="Information">Les fichiers sont réservés aux comptes connectés.</x-alert>
                    <x-alert type="succes">Le projet a été enregistré.</x-alert>
                    <x-alert type="avertissement">L'échéance approche : 3 étapes restent à faire.</x-alert>
                    <x-alert type="danger" titre="Le formulaire contient des erreurs">Le titre est obligatoire.</x-alert>
                </div>
            </x-card>
        </div>

        {{-- Tableau --}}
        <x-card titre="Tableau de données">
            <div class="-mx-5 -mb-5 overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Projet</th><th>Structure</th><th>Statut</th><th>Échéance</th><th>Taux</th></tr></thead>
                    <tbody>
                        @foreach ([['Suivi des stations-service', 'SSTD', 'en_cours', '31/12/2026', 64], ['Cadastre pétrolier', 'SPCP', 'termine', '30/09/2026', 100], ['Normes de sécurité des dépôts', 'SRNS', 'suspendu', '15/03/2027', 22]] as [$t, $s, $st, $e, $tx])
                            <tr><td class="font-medium text-primary-950">{{ $t }}</td><td>{{ $s }}</td><td><x-badge :statut="$st">{{ \App\Enums\StatutProjet::from($st)->libelle() }}</x-badge></td><td>{{ $e }}</td><td class="w-48"><x-admin-barre-taux :taux="$tx" /></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Cartes d'indicateurs --}}
        <x-card titre="Cartes d'indicateurs">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-primary-950 p-5 text-white shadow-md">
                    <p class="font-display text-xs font-semibold uppercase tracking-titre text-primary-200">Taux global</p>
                    <p class="mt-1 font-display text-5xl font-bold text-accent-500">68<span class="text-3xl"> %</span></p>
                </div>
                <div class="carte border-t-4 border-t-primary-600 p-5"><p class="font-display text-xs font-semibold uppercase tracking-titre text-slate-600">En cours</p><p class="mt-1 font-display text-4xl font-bold text-primary-950">24</p></div>
                <div class="carte border-t-4 border-t-danger-600 p-5"><p class="font-display text-xs font-semibold uppercase tracking-titre text-slate-600">⚠ En retard</p><p class="mt-1 font-display text-4xl font-bold text-danger-700">3</p></div>
            </div>
        </x-card>

        {{-- Organigramme --}}
        <x-card titre="Nœuds de l'organigramme">
            <div class="toile flex flex-wrap items-start gap-5 rounded-md p-6">
                @foreach ([['#263f95', 'DPE', 46, '#ea580c'], ['#b45309', 'DAVP', 27, '#dc2626'], ['#0f766e', 'DAMP', 81, '#16a34a']] as [$c, $sigle, $taux, $ct])
                    <div class="oc-noeud" style="width: 190px; height: 180px; border-top-color: {{ $c }}">
                        <span class="oc-badge" style="background: {{ $ct }}">{{ $taux }}%</span>
                        <span class="oc-photo"><img src="{{ asset('images/avatar-defaut.svg') }}" alt=""></span>
                        <div class="oc-nom">Prénom NOM</div>
                        <div class="oc-poste">Chef de division</div>
                        <div class="oc-structure" style="color: {{ $c }}">{{ $sigle }}</div>
                    </div>
                @endforeach
                <div class="flex w-56 flex-col gap-1.5">
                    <div class="oc-service oc-service--clair"><div class="oc-service-titre">SAF</div><span class="text-[11px]">Service d'appui technique</span></div>
                    <div class="oc-service oc-service--bleu"><div class="oc-service-titre">Courrier</div><span class="text-[11px]">Personnel d'appui</span></div>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
