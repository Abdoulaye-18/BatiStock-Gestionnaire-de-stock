<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\Role;
use App\Models\StockArticle;
use App\Models\User;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MouvementTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ReferentielSeeder::class);

        $this->article = Article::create([
            'reference' => 'CIM-50', 'nom' => 'Ciment 50kg', 'categorie_id' => 1,
            'unite_id' => 1, 'seuil_alerte' => 20, 'actif' => true,
        ]);
    }

    private function user(string $role = 'gestionnaire'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    private function qte(int $depot): int
    {
        return (int) StockArticle::where('article_id', $this->article->id)->where('entrepot_id', $depot)->value('quantite');
    }

    private function entree(User $u, int $quantite, int $depot = 1)
    {
        return $this->actingAs($u)->post('/mouvements/entree', [
            'article_id' => $this->article->id, 'entrepot_id' => $depot,
            'quantite' => $quantite, 'motif' => 'Réception',
        ]);
    }

    public function test_une_entree_augmente_le_stock(): void
    {
        $this->entree($this->user(), 200)->assertRedirect();

        $this->assertSame(200, $this->qte(1));
        $this->assertDatabaseHas('mouvements_stock', ['type' => 'entree', 'quantite' => 200]);
    }

    public function test_une_sortie_diminue_le_stock(): void
    {
        $u = $this->user();
        $this->entree($u, 200);

        $this->actingAs($u)->post('/mouvements/sortie', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 50, 'motif' => 'Vente',
        ])->assertRedirect();

        $this->assertSame(150, $this->qte(1));
        $this->assertDatabaseHas('mouvements_stock', ['type' => 'sortie', 'quantite' => -50]);
    }

    public function test_une_sortie_superieure_au_stock_est_bloquee(): void
    {
        $u = $this->user();
        $this->entree($u, 100);

        $this->actingAs($u)->post('/mouvements/sortie', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 500, 'motif' => 'Vente',
        ])->assertSessionHasErrors('quantite');

        $this->assertSame(100, $this->qte(1));
        $this->assertDatabaseMissing('mouvements_stock', ['type' => 'sortie']);
    }

    public function test_un_transfert_conserve_le_stock_total(): void
    {
        $u = $this->user();
        $this->entree($u, 200);

        $this->actingAs($u)->post('/mouvements/transfert', [
            'article_id' => $this->article->id, 'entrepot_id' => 1,
            'entrepot_destination_id' => 2, 'quantite' => 80, 'motif' => 'Réapprovisionnement',
        ])->assertRedirect();

        $this->assertSame(120, $this->qte(1));
        $this->assertSame(80, $this->qte(2));
        $this->assertSame(200, $this->qte(1) + $this->qte(2));

        $ids = MouvementStock::whereIn('type', ['transfert_sortie', 'transfert_entree'])->pluck('id_transfert');
        $this->assertCount(2, $ids);
        $this->assertSame(1, $ids->unique()->count());
    }

    public function test_un_transfert_impossible_ne_laisse_aucune_trace(): void
    {
        $u = $this->user();
        $this->entree($u, 50);

        $this->actingAs($u)->post('/mouvements/transfert', [
            'article_id' => $this->article->id, 'entrepot_id' => 1,
            'entrepot_destination_id' => 2, 'quantite' => 999, 'motif' => 'Test',
        ])->assertSessionHasErrors('quantite');

        $this->assertSame(50, $this->qte(1));
        $this->assertSame(0, $this->qte(2));
        $this->assertSame(1, MouvementStock::count()); // seule l'entrée initiale existe
    }

    public function test_le_transfert_vers_le_meme_depot_est_refuse(): void
    {
        $u = $this->user();
        $this->entree($u, 50);

        $this->actingAs($u)->post('/mouvements/transfert', [
            'article_id' => $this->article->id, 'entrepot_id' => 1,
            'entrepot_destination_id' => 1, 'quantite' => 10, 'motif' => 'Test',
        ])->assertSessionHasErrors('entrepot_destination_id');
    }

    public function test_un_ajustement_negatif_ne_peut_pas_rendre_le_stock_negatif(): void
    {
        $u = $this->user();
        $this->entree($u, 30);

        $this->actingAs($u)->post('/mouvements/ajustement', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => -100, 'motif' => 'Inventaire',
        ])->assertSessionHasErrors('quantite');

        $this->actingAs($u)->post('/mouvements/ajustement', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => -10, 'motif' => 'Inventaire',
        ])->assertRedirect();

        $this->assertSame(20, $this->qte(1));
    }

    public function test_le_stock_est_egal_a_la_somme_des_mouvements(): void
    {
        $u = $this->user();
        $this->entree($u, 200);
        $this->actingAs($u)->post('/mouvements/sortie', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 30, 'motif' => 'Vente',
        ]);
        $this->actingAs($u)->post('/mouvements/ajustement', [
            'article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 5, 'motif' => 'Inventaire',
        ]);

        $somme = (int) MouvementStock::sum('quantite');
        $this->assertSame($somme, $this->qte(1));
        $this->assertSame(175, $somme);
    }

    public function test_un_mouvement_ne_peut_pas_etre_modifie(): void
    {
        $this->entree($this->user(), 10);
        $mouvement = MouvementStock::first();

        $this->expectException(\LogicException::class);
        $mouvement->update(['quantite' => 999]);
    }

    public function test_le_responsable_de_chantier_ne_peut_pas_enregistrer_de_mouvement(): void
    {
        $this->entree($this->user('chantier'), 10)->assertForbidden();
        $this->assertSame(0, MouvementStock::count());
    }
}