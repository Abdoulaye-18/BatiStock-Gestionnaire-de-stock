<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    private array $article = [
        'reference' => 'CIM-001', 'nom' => 'Ciment 50kg',
        'categorie_id' => 1, 'unite_id' => 1, 'seuil_alerte' => 20,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ReferentielSeeder::class);
    }

    private function userAvecRole(string $code): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $code)->value('id'), 'actif' => true])->save();

        return $u;
    }

    public function test_gestionnaire_peut_creer_un_article(): void
    {
        $this->actingAs($this->userAvecRole('gestionnaire'))->post('/articles', $this->article)->assertRedirect();
        $this->assertDatabaseHas('articles', ['reference' => 'CIM-001']);
    }

    public function test_responsable_chantier_ne_peut_pas_creer_un_article(): void
    {
        $this->actingAs($this->userAvecRole('chantier'))->post('/articles', $this->article)->assertForbidden();
        $this->assertDatabaseMissing('articles', ['reference' => 'CIM-001']);
    }

    public function test_la_reference_doit_etre_unique(): void
    {
        $user = $this->userAvecRole('gestionnaire');
        $this->actingAs($user)->post('/articles', $this->article);
        $this->actingAs($user)->post('/articles', $this->article)->assertSessionHasErrors('reference');
    }
}