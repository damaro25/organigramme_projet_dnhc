<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    private function compte(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_l_upload_genere_une_photo_carree_et_une_miniature(): void
    {
        $agent = Agent::where('est_responsable', false)->first();

        $this->actingAs($this->compte('admin@dnh.local'))
            ->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->image('photo.jpg', 900, 600)])
            ->assertOk()->assertJsonPath('aPhoto', true);

        $agent->refresh();
        Storage::disk('public')->assertExists([$agent->photo, $agent->photo_miniature]);
        [$l, $h] = getimagesizefromstring(Storage::disk('public')->get($agent->photo));
        $this->assertSame([512, 512], [$l, $h]);
        [$l, $h] = getimagesizefromstring(Storage::disk('public')->get($agent->photo_miniature));
        $this->assertSame([128, 128], [$l, $h]);
    }

    public function test_le_remplacement_supprime_l_ancienne_photo(): void
    {
        $agent = Agent::first();
        $admin = $this->compte('admin@dnh.local');

        $this->actingAs($admin)->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->image('a.png', 300, 300)]);
        $anciens = [$agent->fresh()->photo, $agent->fresh()->photo_miniature];

        $this->actingAs($admin)->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->image('b.webp', 400, 400)]);

        Storage::disk('public')->assertMissing($anciens);
        Storage::disk('public')->assertExists($agent->fresh()->photo);
    }

    public function test_la_suppression_revient_a_l_avatar_par_defaut(): void
    {
        $agent = Agent::first();
        $admin = $this->compte('admin@dnh.local');
        $this->actingAs($admin)->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)]);
        $chemin = $agent->fresh()->photo;

        $this->actingAs($admin)->deleteJson(route('admin.agents.photo.destroy', $agent))->assertOk();

        Storage::disk('public')->assertMissing($chemin);
        $this->assertNull($agent->fresh()->photo);
        $this->assertStringContainsString('avatar-defaut', $agent->fresh()->photo_url);
    }

    public function test_les_fichiers_invalides_sont_refuses(): void
    {
        $agent = Agent::first();
        $admin = $this->compte('admin@dnh.local');

        $this->actingAs($admin)->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->actingAs($admin)->postJson(route('admin.agents.photo.store', $agent), ['photo' => UploadedFile::fake()->image('lourde.jpg')->size(3000)])
            ->assertUnprocessable()->assertJsonValidationErrors(['photo' => 'ne doit pas dépasser 2 Mo']);
    }

    public function test_un_charge_d_etudes_ne_modifie_que_sa_propre_photo(): void
    {
        $charge = $this->compte('charge.etudes@dnh.local');
        $collegue = Agent::where('structure_id', $charge->agent->structure_id)->whereKeyNot($charge->agent_id)->first();

        $this->actingAs($charge)->postJson(route('admin.agents.photo.store', $charge->agent), ['photo' => UploadedFile::fake()->image('moi.jpg', 300, 300)])->assertOk();
        $this->actingAs($charge)->postJson(route('admin.agents.photo.store', $collegue), ['photo' => UploadedFile::fake()->image('lui.jpg', 300, 300)])->assertForbidden();
    }

    public function test_un_chef_de_section_modifie_la_photo_de_ses_agents_seulement(): void
    {
        $chef = $this->compte('chef.section@dnh.local');
        $sien = Agent::where('structure_id', $chef->agent->structure_id)->where('est_responsable', false)->first();
        $autre = Agent::whereNotIn('structure_id', $chef->structuresGerees())->first();

        $this->actingAs($chef)->postJson(route('admin.agents.photo.store', $sien), ['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)])->assertOk();
        $this->actingAs($chef)->postJson(route('admin.agents.photo.store', $autre), ['photo' => UploadedFile::fake()->image('b.jpg', 300, 300)])->assertForbidden();
    }
}
