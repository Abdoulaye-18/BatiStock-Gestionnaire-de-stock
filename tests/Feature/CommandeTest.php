<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Client;
use App\Models\CommandeClient;
use App\Models\MouvementStock;
use App\Models\Role;
use App\Models\StockArticle;
use App\Models\User;
use App\Services\CommandeService;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandeTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(ReferentielSeeder::class);

        $this->article = Article::create([
            'reference' => 'CIM-50', 'nom' => 'Ciment 50kg', 'categorie_id' => 1,
            'unite_id' => 1, 'seuil_alerte' => 20, 'prix_vente' => 6000, 'actif' => true,
        ]);
        $this->client = Client::create(['nom' => 'ETS Bâtir Plus', 'adresse_livraison' => 'Rufisque']);
    }

    private function user(string $role = 'gestionnaire'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    private function payload(int $quantite = 50, ?float $prix = null): array
    {
        $ligne = ['article_id' => $this->article->id, 'quantite' => $quantite];
        if ($prix !== null) {
            $ligne['prix_unitaire'] = $prix;
        }

        return [
            'client_id' => $this->client->id,
            'adresse_destination' => 'Rufisque, route de Bargny',
            'lignes' => [$ligne],
        ];
    }

    private function commande(User $u): CommandeClient
    {
        $this->actingAs($u)->post('/commandes', $this->payload());

        return CommandeClient::firstOrFail();
    }

    public function test_le_gestionnaire_cree_un_brouillon_avec_le_prix_de_l_article(): void
    {
        $this->actingAs($this->user())->post('/commandes', $this->payload(10))->assertRedirect();

        $c = CommandeClient::first();
        $this->assertSame('brouillon', $c->statut);
        $this->assertStringStartsWith('CMD-', $c->numero);
        $this->assertEquals(60000, $c->montant_total);
    }

    public function test_le_montant_est_calcule_par_le_serveur(): void
    {
        $data = $this->payload(10, 5500);
        $data['montant_total'] = 1; // ignoré

        $this->actingAs($this->user())->post('/commandes', $data)->assertRedirect();

        $this->assertEquals(55000, CommandeClient::first()->montant_total);
    }

    public function test_un_article_en_double_est_refuse(): void
    {
        $data = $this->payload();
        $data['lignes'][] = ['article_id' => $this->article->id, 'quantite' => 5];

        $this->actingAs($this->user())->post('/commandes', $data)->assertSessionHasErrors('lignes.1.article_id');
    }

    public function test_le_responsable_de_chantier_n_a_pas_acces_aux_commandes(): void
    {
        $u = $this->user('chantier');

        $this->actingAs($u)->get('/commandes')->assertForbidden();
        $this->actingAs($u)->post('/commandes', $this->payload())->assertForbidden();
        $this->assertSame(0, CommandeClient::count());
    }

    public function test_le_controle_consulte_sans_pouvoir_creer(): void
    {
        $u = $this->user('controle');

        $this->actingAs($u)->get('/commandes')->assertOk();
        $this->actingAs($u)->post('/commandes', $this->payload())->assertForbidden();
    }

    public function test_la_commande_ne_diminue_pas_le_stock(): void
    {
        StockArticle::create(['article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 200]);
        $g = $this->user();
        $c = $this->commande($g);

        $this->actingAs($g)->patch("/commandes/{$c->id}/confirmer");
        $this->actingAs($g)->patch("/commandes/{$c->id}/preparer");

        $this->assertSame('en_preparation', $c->fresh()->statut);
        $this->assertSame(200, (int) StockArticle::first()->quantite);
        $this->assertSame(0, MouvementStock::count());
    }

    public function test_on_ne_confirme_pas_deux_fois(): void
    {
        $g = $this->user();
        $c = $this->commande($g);

        $this->actingAs($g)->patch("/commandes/{$c->id}/confirmer");
        $this->actingAs($g)->patch("/commandes/{$c->id}/confirmer")->assertSessionHasErrors('statut');
    }

    public function test_la_preparation_exige_une_commande_confirmee(): void
    {
        $g = $this->user();
        $c = $this->commande($g);

        $this->actingAs($g)->patch("/commandes/{$c->id}/preparer")->assertSessionHasErrors('statut');
        $this->assertSame('brouillon', $c->fresh()->statut);
    }

    public function test_un_brouillon_est_modifiable_et_le_montant_recalcule(): void
    {
        $g = $this->user();
        $c = $this->commande($g);

        $this->actingAs($g)->put("/commandes/{$c->id}", $this->payload(20))->assertRedirect();

        $this->assertEquals(120000, $c->fresh()->montant_total);
        $this->assertSame(1, $c->lignes()->count());
    }

    public function test_une_commande_confirmee_n_est_plus_modifiable(): void
    {
        $g = $this->user();
        $c = $this->commande($g);
        $this->actingAs($g)->patch("/commandes/{$c->id}/confirmer");

        $this->actingAs($g)->put("/commandes/{$c->id}", $this->payload(99))->assertSessionHasErrors('statut');
        $this->assertSame(50, $c->lignes()->first()->quantite);
    }

    public function test_l_annulation_exige_un_motif_et_n_est_possible_qu_une_fois(): void
    {
        $g = $this->user();
        $c = $this->commande($g);

        $this->actingAs($g)->patch("/commandes/{$c->id}/annuler", [])->assertSessionHasErrors('motif_annulation');

        $this->actingAs($g)->patch("/commandes/{$c->id}/annuler", ['motif_annulation' => 'Client indisponible'])
            ->assertRedirect();
        $this->assertSame('annulee', $c->fresh()->statut);

        $this->actingAs($g)->patch("/commandes/{$c->id}/annuler", ['motif_annulation' => 'Encore'])
            ->assertSessionHasErrors('statut');
    }

    public function test_le_statut_est_recalcule_selon_les_quantites_livrees(): void
    {
        $c = $this->commande($this->user());
        $service = app(CommandeService::class);

        $c->lignes()->update(['quantite_livree' => 20]);
        $service->recalculerStatut($c->fresh());
        $this->assertSame('partiellement_livree', $c->fresh()->statut);

        $c->lignes()->update(['quantite_livree' => 50]);
        $service->recalculerStatut($c->fresh());
        $this->assertSame('livree', $c->fresh()->statut);
    }
}