<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientChantierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    public function test_le_gestionnaire_peut_creer_un_client(): void
    {
        $this->actingAs($this->user('gestionnaire'))
            ->post('/clients', ['nom' => 'ETS Bâtir Plus', 'telephone' => '771234567'])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['nom' => 'ETS Bâtir Plus']);
    }

    public function test_le_responsable_de_chantier_n_a_pas_acces_aux_clients(): void
    {
        $u = $this->user('chantier');

        $this->actingAs($u)->get('/clients')->assertForbidden();
        $this->actingAs($u)->post('/clients', ['nom' => 'Pirate'])->assertForbidden();
        $this->assertDatabaseMissing('clients', ['nom' => 'Pirate']);
    }

    public function test_un_responsable_cree_un_chantier_dont_il_est_le_responsable(): void
    {
        $moi = $this->user('chantier');
        $autre = $this->user('chantier');

        $this->actingAs($moi)->post('/chantiers', [
            'nom' => 'Villa Diop', 'etat' => 'actif', 'responsable_id' => $autre->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('chantiers', ['nom' => 'Villa Diop', 'responsable_id' => $moi->id]);
    }

    public function test_un_responsable_ne_modifie_pas_le_chantier_d_un_autre(): void
    {
        $moi = $this->user('chantier');
        $autre = $this->user('chantier');
        $chantier = Chantier::create(['nom' => 'X', 'etat' => 'actif', 'responsable_id' => $autre->id]);

        $this->actingAs($moi)->put("/chantiers/{$chantier->id}", ['nom' => 'Y', 'etat' => 'actif'])->assertForbidden();

        $this->assertDatabaseHas('chantiers', ['id' => $chantier->id, 'nom' => 'X']);
    }

    public function test_un_responsable_ne_voit_que_ses_chantiers(): void
    {
        $moi = $this->user('chantier');
        $autre = $this->user('chantier');
        Chantier::create(['nom' => 'Le mien', 'etat' => 'actif', 'responsable_id' => $moi->id]);
        Chantier::create(['nom' => 'Pas le mien', 'etat' => 'actif', 'responsable_id' => $autre->id]);

        $this->actingAs($moi)->get('/chantiers')->assertInertia(
            fn (Assert $page) => $page->component('Chantiers/Index')->has('chantiers', 1)
        );
    }

    public function test_le_controle_peut_consulter_mais_pas_creer_de_chantier(): void
    {
        $u = $this->user('controle');
        Chantier::create(['nom' => 'A', 'etat' => 'actif']);

        $this->actingAs($u)->get('/chantiers')->assertInertia(
            fn (Assert $page) => $page->has('chantiers', 1)
        );
        $this->actingAs($u)->post('/chantiers', ['nom' => 'B', 'etat' => 'actif'])->assertForbidden();
    }

    public function test_un_responsable_ne_peut_pas_supprimer_un_chantier(): void
    {
        $moi = $this->user('chantier');
        $chantier = Chantier::create(['nom' => 'Le mien', 'etat' => 'actif', 'responsable_id' => $moi->id]);

        $this->actingAs($moi)->delete("/chantiers/{$chantier->id}")->assertForbidden();
        $this->assertDatabaseHas('chantiers', ['id' => $chantier->id]);
    }
}