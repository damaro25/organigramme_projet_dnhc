<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Structure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reproduit l'organigramme papier de la DNH : DNH, DNAH, 6 services rattachés,
 * 3 divisions, 9 sections (5 chargés d'études chacune) et les services déconcentrés.
 * Tous les noms sont fictifs.
 */
class OrganigrammeSeeder extends Seeder
{
    /**
     * Charte graphique du Gouvernement : rouge, jaune, vert, blanc et gris.
     * Les divisions reprennent les trois couleurs nationales (le jaune est foncé
     * pour rester lisible en texte sur fond blanc).
     */
    public const COULEURS = [
        'direction' => '#006a45',
        'planification' => '#009460',
        'aval' => '#ce1126',
        'amont' => '#a37f00',
        'deconcentre' => '#6b7280',
    ];

    public const DIVISIONS = [
        'planification' => [
            'nom' => 'Division Planification / Économie',
            'sigle' => 'DPE',
            'sections' => [
                ['Section Politique / Stratégie / Réglementation', 'SPSR'],
                ['Section Étude Schéma Directeur', 'SESD'],
                ['Section Réglementation / Normes / Sécurité', 'SRNS'],
            ],
        ],
        'aval' => [
            'nom' => 'Division Aval Pétrolier',
            'sigle' => 'DAVP',
            'sections' => [
                ['Section Approvisionnement / Importation', 'SAI'],
                ['Section Stockage / Transport / Distribution', 'SSTD'],
                ['Section Infrastructures / Stratégie / Raffinage', 'SISR'],
            ],
        ],
        'amont' => [
            'nom' => 'Division Amont Pétrolier',
            'sigle' => 'DAMP',
            'sections' => [
                ['Section Promotion / Cadastre Pétrolier', 'SPCP'],
                ['Section Exploration / Évaluation & Production', 'SEEP'],
                ['Section Contrats / Suivi Technique / Contenu Local', 'SCSC'],
            ],
        ],
    ];

    /** [nom, sigle, groupe, poste du titulaire, est_responsable] */
    public const SERVICES_RATTACHES = [
        ['Service Administratif et Financier', 'SAF', 'appui_technique', 'Chef du Service Administratif et Financier', true],
        ['Service Statistique', 'Statistique', 'appui_technique', 'Chef du Service Statistique', true],
        ['Service Informatique', 'Informatique', 'appui_technique', 'Chef du Service Informatique', true],
        ['Service Courrier', 'Courrier', 'personnel_appui', 'Agent du Service Courrier', false],
        ['Chauffeur', 'Chauffeur', 'personnel_appui', 'Chauffeur', false],
        ['Planton', 'Planton', 'personnel_appui', 'Planton', false],
    ];

    /** Prénoms fictifs : [prénom, féminin ?]. 31 prénoms et 23 noms donnent 65 combinaisons uniques. */
    private const PRENOMS = [
        ['Mamadou', false], ['Aïssatou', true], ['Ibrahima', false], ['Fatoumata', true], ['Alpha', false],
        ['Mariama', true], ['Ousmane', false], ['Kadiatou', true], ['Sékou', false], ['Hawa', true],
        ['Abdoulaye', false], ['Djénabou', true], ['Thierno', false], ['Aminata', true], ['Lansana', false],
        ['Fanta', true], ['Moussa', false], ['Nènè', true], ['Mohamed', false], ['Oumou', true],
        ['Amadou', false], ['Bintou', true], ['Boubacar', false], ['Saran', true], ['Souleymane', false],
        ['Mabinty', true], ['Kémoko', false], ['Ramatoulaye', true], ['Fodé', false], ['Aïcha', true],
        ['Daouda', false],
    ];

    private const NOMS = [
        'DIALLO', 'BAH', 'CAMARA', 'SYLLA', 'BARRY', 'KEITA', 'SOUMAH', 'TOURÉ', 'CONDÉ', 'KOUROUMA',
        'BANGOURA', 'CISSÉ', 'SOW', 'TRAORÉ', 'KABA', 'FOFANA', 'DOUMBOUYA', 'CONTÉ', 'SAMOURA',
        'KOUYATÉ', 'YATTARA', 'MAGASSOUBA', 'BALDÉ',
    ];

    private int $compteur = 0;

    public function run(): void
    {
        $c = self::COULEURS;

        $dnh = Structure::create([
            'nom' => 'Direction Nationale des Hydrocarbures', 'sigle' => 'DNH',
            'type' => 'direction', 'couleur' => $c['direction'], 'ordre' => 1,
        ]);
        $dnah = Structure::create([
            'nom' => 'Direction Nationale Adjointe des Hydrocarbures', 'sigle' => 'DNAH',
            'type' => 'direction', 'parent_id' => $dnh->id, 'couleur' => $c['direction'], 'ordre' => 1,
        ]);

        $agentDnh = $this->agent($dnh, 'Directeur National des Hydrocarbures', null, true, feminin: false);
        $agentDnah = $this->agent($dnah, 'Directrice Nationale Adjointe des Hydrocarbures', $agentDnh, true, feminin: true);

        // Services rattachés au DNH (affichés sur le côté).
        foreach (self::SERVICES_RATTACHES as $i => [$nom, $sigle, $groupe, $poste, $responsable]) {
            $service = Structure::create([
                'nom' => $nom, 'sigle' => $sigle, 'type' => 'service_rattache', 'groupe' => $groupe,
                'parent_id' => $dnh->id, 'couleur' => $groupe === 'appui_technique' ? '#f3f4f6' : '#006a45',
                'ordre' => $i + 1,
            ]);
            $this->agent($service, $poste, $agentDnh, $responsable);
        }

        // Divisions, sections et chargés d'études.
        $ordreDivision = 1;
        foreach (self::DIVISIONS as $cle => $def) {
            $division = Structure::create([
                'nom' => $def['nom'], 'sigle' => $def['sigle'], 'type' => 'division',
                'parent_id' => $dnah->id, 'couleur' => $c[$cle], 'ordre' => $ordreDivision++,
            ]);
            $chefDivision = $this->agent($division, 'Chef de division', $agentDnah, true);

            foreach ($def['sections'] as $j => [$nomSection, $sigleSection]) {
                $section = Structure::create([
                    'nom' => $nomSection, 'sigle' => $sigleSection, 'type' => 'section',
                    'parent_id' => $division->id, 'ordre' => $j + 1,
                ]);
                $chefSection = $this->agent($section, 'Chef de section', $chefDivision, true);

                for ($k = 1; $k <= 5; $k++) {
                    $this->agent($section, "Chargé d'études", $chefSection, false, ordre: $k);
                }
            }
        }

        // Services déconcentrés : lien fonctionnel (trait en pointillés) avec le DNAH.
        $sd = Structure::create([
            'nom' => 'Services Déconcentrés', 'sigle' => 'SD', 'type' => 'service_deconcentre',
            'parent_id' => $dnah->id, 'type_lien' => 'fonctionnel', 'couleur' => $c['deconcentre'], 'ordre' => 4,
        ]);
        $ir = Structure::create([
            'nom' => 'Inspections Régionales', 'sigle' => 'I.R', 'type' => 'service_deconcentre',
            'parent_id' => $sd->id, 'couleur' => $c['deconcentre'], 'ordre' => 1,
        ]);
        Structure::create([
            'nom' => 'Directions Préfectorales', 'sigle' => 'D.P', 'type' => 'service_deconcentre',
            'parent_id' => $ir->id, 'couleur' => $c['deconcentre'], 'ordre' => 1,
        ]);
    }

    private function agent(Structure $s, string $poste, ?Agent $superieur, bool $responsable, ?bool $feminin = null, int $ordre = 0): Agent
    {
        $i = $this->compteur++;
        [$prenom, $estFeminin] = self::PRENOMS[$i % count(self::PRENOMS)];
        if ($feminin !== null && $feminin !== $estFeminin) {
            // Garde la cohérence entre le poste imposé et le prénom.
            [$prenom, $estFeminin] = self::PRENOMS[($i + 1) % count(self::PRENOMS)];
        }
        $nom = self::NOMS[$i % count(self::NOMS)];

        if ($estFeminin) {
            $poste = str_replace(
                ["Chargé d'études", 'Chef de division', 'Chef de section', 'Chef du '],
                ["Chargée d'études", 'Cheffe de division', 'Cheffe de section', 'Cheffe du '],
                $poste
            );
        }

        return Agent::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'poste' => $poste,
            'structure_id' => $s->id,
            'superieur_id' => $superieur?->id,
            'email' => Str::slug($prenom).'.'.Str::slug($nom).$i.'@dnh.local',
            'telephone' => sprintf('6%02d %02d %02d %02d', 20 + $i % 70, ($i * 7) % 100, ($i * 13) % 100, ($i * 29) % 100),
            'est_responsable' => $responsable,
            'actif' => true,
            'ordre' => $ordre,
        ]);
    }
}
