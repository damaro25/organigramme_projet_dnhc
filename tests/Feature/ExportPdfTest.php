<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Structure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_l_organigramme_complet_est_exporte_en_pdf(): void
    {
        $reponse = $this->get('/organigramme.pdf');

        $reponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
        $this->assertStringContainsString('organigramme-dnh-', $reponse->headers->get('content-disposition'));
    }

    public function test_la_fiche_projets_d_un_chef_de_section_est_exportee_en_pdf(): void
    {
        $chef = Agent::where('structure_id', Structure::where('sigle', 'SSTD')->value('id'))
            ->where('est_responsable', true)->firstOrFail();

        $reponse = $this->get("/agents/{$chef->id}/fiche.pdf");

        $reponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_la_fiche_d_un_agent_desactive_n_est_pas_exportee(): void
    {
        $agent = Agent::where('est_responsable', false)->firstOrFail();
        $agent->update(['actif' => false]);

        $this->get("/agents/{$agent->id}/fiche.pdf")->assertNotFound();
    }
}
