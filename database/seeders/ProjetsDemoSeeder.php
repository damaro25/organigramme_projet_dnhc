<?php

namespace Database\Seeders;

use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Projets de démonstration propres au secteur des hydrocarbures.
 * Les dates sont relatives à aujourd'hui pour que les retards restent visibles.
 */
class ProjetsDemoSeeder extends Seeder
{
    /**
     * sigle de section, titre, description, statut, début (mois), échéance (mois),
     * étapes [libellé, poids], nombre d'étapes terminées, responsable = chef de section ?, archivé ?
     */
    public const PROJETS = [
        ['SSTD', 'Suivi des stations-service', "Recensement et contrôle de conformité des stations-service sur l'ensemble du territoire.", 'en_cours', -8, 4, [
            ['Élaboration de la grille de contrôle', 1], ['Recensement des stations de Conakry', 2], ['Recensement des stations de l\'intérieur', 3],
            ['Contrôles de conformité sur site', 3], ['Saisie dans la base de données', 1], ['Rapport de synthèse', 2],
        ], 3, false, false],
        ['SSTD', 'Contrôle des dépôts pétroliers', 'Inspection des capacités, de la sécurité et de la traçabilité des stocks dans les dépôts.', 'en_cours', -6, -1, [
            ['Planification des inspections', 1], ['Inspection du dépôt principal', 3], ['Inspection des dépôts secondaires', 3],
            ['Vérification des jauges et des stocks', 2], ['Rapport et recommandations', 2],
        ], 2, true, false],
        ['SSTD', 'Plan de distribution des produits pétroliers', 'Optimisation des circuits de transport et de distribution vers les régions.', 'non_demarre', 1, 10, [
            ['Diagnostic des circuits actuels', 2], ['Modélisation des flux', 3], ['Consultation des transporteurs', 1], ['Validation du plan', 1],
        ], 0, false, false],
        ['SAI', 'Approvisionnement en carburant – programme annuel', 'Programmation des importations et suivi des cargaisons de produits pétroliers.', 'en_cours', -10, 2, [
            ['Évaluation des besoins nationaux', 2], ['Appel d\'offres des importateurs', 2], ['Attribution des quotas', 1],
            ['Suivi des cargaisons', 3], ['Contrôle qualité au déchargement', 2], ['Bilan trimestriel', 1], ['Bilan annuel', 1],
        ], 5, true, false],
        ['SAI', 'Constitution du stock de sécurité', 'Mise en place d\'un stock stratégique couvrant 30 jours de consommation.', 'suspendu', -5, 6, [
            ['Étude de dimensionnement', 2], ['Identification des capacités disponibles', 2], ['Montage financier', 3], ['Constitution progressive du stock', 3],
        ], 1, false, false],
        ['SISR', 'Étude de faisabilité d\'une raffinerie', 'Évaluation technique et économique d\'une unité de raffinage nationale.', 'en_cours', -12, -2, [
            ['Termes de référence', 1], ['Recrutement du cabinet', 1], ['Étude de marché', 2], ['Étude technique', 3],
            ['Étude financière', 3], ['Atelier de restitution', 1],
        ], 4, true, false],
        ['SISR', 'Réhabilitation des infrastructures portuaires pétrolières', 'Diagnostic et programme de réhabilitation des appontements pétroliers.', 'termine', -18, -3, [
            ['Diagnostic des installations', 2], ['Programme de travaux', 2], ['Suivi des travaux', 3], ['Réception des ouvrages', 1],
        ], 4, false, true],
        ['SPCP', 'Mise à jour du cadastre pétrolier', 'Numérisation et mise à jour des blocs pétroliers et de leurs titulaires.', 'en_cours', -9, 3, [
            ['Collecte des données existantes', 1], ['Numérisation des cartes', 3], ['Mise à jour des titres', 2],
            ['Contrôle qualité SIG', 2], ['Publication du cadastre', 1],
        ], 3, true, false],
        ['SPCP', 'Promotion du bassin sédimentaire offshore', 'Préparation d\'un cycle de promotion des blocs libres auprès des investisseurs.', 'en_cours', -4, 8, [
            ['Préparation des data packages', 3], ['Organisation des roadshows', 2], ['Salle de données', 2], ['Analyse des manifestations d\'intérêt', 1],
        ], 1, false, false],
        ['SEEP', 'Suivi des travaux d\'exploration', 'Suivi des engagements de travaux des titulaires de permis d\'exploration.', 'en_cours', -7, 5, [
            ['Revue des programmes de travaux', 1], ['Suivi des campagnes sismiques', 3], ['Suivi des forages', 3],
            ['Analyse des rapports trimestriels', 2], ['Rapport annuel d\'activité', 1],
        ], 2, false, false],
        ['SEEP', 'Évaluation des ressources en hydrocarbures', 'Mise à jour de l\'estimation des ressources prospectives nationales.', 'non_demarre', 2, 14, [
            ['Compilation des données géologiques', 2], ['Interprétation', 3], ['Estimation des volumes', 3], ['Rapport d\'évaluation', 1],
        ], 0, true, false],
        ['SCSC', 'Suivi des contrats d\'exploration', 'Contrôle du respect des clauses contractuelles par les sociétés pétrolières.', 'en_cours', -11, -1, [
            ['Inventaire des contrats en vigueur', 1], ['Tableau de bord des obligations', 2], ['Audits contractuels', 3],
            ['Notification des manquements', 1], ['Rapport au Ministre', 1],
        ], 3, true, false],
        ['SCSC', 'Politique de contenu local', 'Élaboration du cadre de suivi du contenu local dans le secteur pétrolier.', 'en_cours', -3, 9, [
            ['Diagnostic des capacités nationales', 2], ['Benchmark régional', 1], ['Rédaction du projet de texte', 3],
            ['Concertation avec les opérateurs', 2], ['Mise en place des indicateurs', 2], ['Plateforme de suivi', 2],
        ], 1, false, false],
        ['SESD', 'Schéma directeur des hydrocarbures 2026–2040', 'Élaboration du schéma directeur national du secteur des hydrocarbures.', 'en_cours', -14, 1, [
            ['Diagnostic du secteur', 2], ['Projections de la demande', 3], ['Scénarios d\'offre', 3], ['Plan d\'investissement', 2],
            ['Évaluation environnementale stratégique', 2], ['Validation nationale', 1], ['Adoption en Conseil des Ministres', 1], ['Diffusion', 1],
        ], 5, true, false],
        ['SESD', 'Base de données sectorielle', 'Centralisation des statistiques de production, d\'importation et de consommation.', 'en_cours', -5, 7, [
            ['Spécifications fonctionnelles', 1], ['Développement', 3], ['Reprise des historiques', 2], ['Formation des utilisateurs', 1],
        ], 1, false, false],
        ['SRNS', 'Normes de sécurité des installations pétrolières', 'Révision des normes de sécurité applicables aux dépôts et stations-service.', 'en_cours', -6, -2, [
            ['Revue des normes existantes', 1], ['Benchmark international', 1], ['Rédaction des projets de normes', 3],
            ['Consultation des acteurs', 2], ['Adoption des normes', 2],
        ], 2, true, false],
        ['SRNS', 'Spécifications des carburants', 'Mise à jour des spécifications techniques de l\'essence et du gasoil.', 'termine', -10, -1, [
            ['Analyse des spécifications actuelles', 1], ['Proposition de nouvelles spécifications', 2], ['Validation technique', 2], ['Publication de l\'arrêté', 1],
        ], 4, false, false],
        ['SPSR', 'Révision du Code pétrolier', 'Accompagnement de la révision du cadre législatif et réglementaire du secteur.', 'en_cours', -9, 6, [
            ['Diagnostic juridique', 2], ['Ateliers de consultation', 2], ['Rédaction de l\'avant-projet', 3],
            ['Revue par le Secrétariat général', 1], ['Transmission à l\'Assemblée', 1],
        ], 2, true, false],
        ['SPSR', 'Stratégie de prix des produits pétroliers', 'Analyse de la structure des prix et proposition d\'un mécanisme d\'ajustement.', 'en_cours', -4, 2, [
            ['Analyse de la structure des prix', 2], ['Simulation des mécanismes', 3], ['Note de stratégie', 2], ['Présentation à la Direction', 1],
        ], 3, false, false],
    ];

    public function run(): void
    {
        $validateur = User::where('email', 'dnh@dnh.local')->first();
        $aujourdhui = Carbon::today();

        foreach (self::PROJETS as $n => [$sigle, $titre, $description, $statut, $debut, $echeance, $etapes, $terminees, $chefResponsable, $archive]) {
            $section = Structure::where('sigle', $sigle)->firstOrFail();
            $chef = $section->agents()->where('est_responsable', true)->firstOrFail();
            $charges = $section->agents()->where('est_responsable', false)->orderBy('ordre')->get();

            $dateDebut = $aujourdhui->copy()->addMonths($debut)->startOfMonth();
            $projet = Projet::create([
                'titre' => $titre,
                'description' => $description,
                'structure_id' => $section->id,
                'date_debut' => $dateDebut,
                'date_fin_prevue' => $aujourdhui->copy()->addMonths($echeance)->endOfMonth(),
                'statut' => $statut,
                'archive' => $archive,
                'valide_par' => $statut === 'termine' ? $validateur?->id : null,
                'valide_le' => $statut === 'termine' ? now()->subWeeks(2) : null,
            ]);

            // Affectation : un responsable et deux ou trois membres, en faisant tourner les chargés d'études.
            $responsable = $chefResponsable ? $chef : $charges[$n % 5];
            $affectation = [$responsable->id => ['role' => 'responsable']];
            foreach ([1, 2, 3] as $d) {
                $membre = $charges[($n + $d) % 5];
                if ($membre->id !== $responsable->id && count($affectation) < 2 + $n % 2 + 1) {
                    $affectation[$membre->id] = ['role' => 'membre'];
                }
            }
            if (! $chefResponsable) {
                $affectation[$chef->id] = ['role' => 'membre'];
            }
            $projet->agents()->attach($affectation);

            // Étapes, dont les premières sont terminées avec des dates étalées depuis le début du projet.
            foreach ($etapes as $i => [$libelle, $poids]) {
                $terminee = $i < $terminees;
                $projet->etapes()->create([
                    'libelle' => $libelle,
                    'ordre' => $i + 1,
                    'poids' => $poids,
                    'est_terminee' => $terminee,
                    'date_realisation' => $terminee
                        ? min($dateDebut->copy()->addWeeks(3 * ($i + 1)), $aujourdhui->copy()->subDays(2))
                        : null,
                    'commentaire' => $terminee && $i === 0 ? 'Étape validée en réunion de division.' : null,
                    'valide_par' => null,
                ]);
            }
        }
    }
}
