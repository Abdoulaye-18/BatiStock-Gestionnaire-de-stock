<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chantier;
use App\Models\Client;
use App\Models\CommandeClient;
use App\Models\DemandeChantier;
use App\Models\Livraison;
use App\Models\Livreur;
use App\Models\MouvementStock;
use App\Models\Role;
use App\Models\StockArticle;
use App\Models\User;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivraisonTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;
    private Client $client;
    private Livreur $livreur;
    private User $gestionnaire;

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
        $this->livreur = Livreur::create(['nom' => 'Ibou', 'disponible' => true]);
        $this->gestionnaire = $this->user();

        StockArticle::create(['article_id' => $this->article->id, 'entrepot_id' => 1, 'quantite' => 200]);
    }

    private function user(string $role = 'gestionnaire'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    private function stock(): int
    {
        return (int) StockArticle::where('article_id', $this->article->id)->where('entrepot_id', 1)->value('quantite');
    }

    private function commande(int $qte = 50, bool $preparee = true): CommandeClient
    {
        $this->actingAs($this->gestionnaire)->post('/commandes', [
            'client_id' => $this->client->id,
            'adresse_destination' => 'Rufisque',
            'lignes' => [['article_id' => $this->article->id, 'quantite' => $qte]],
        ]);
        $c = CommandeClient::firstOrFail();

        if ($preparee) {
            $this->actingAs($this->gestionnaire)->patch("/commandes/{$c->id}/confirmer");
            $this->actingAs($this->gestionnaire)->patch("/commandes/{$c->id}/preparer");
        }

        return $c->fresh();
    }

    private function demandePreparee(int $qte = 30): DemandeChantier
    {
        $resp = $this->user('chantier');
        $chantier = Chantier::create([
            'nom' => 'Chantier Diop', 'etat' => 'actif', 'responsable_id' => $resp->id, 'localisation' => 'Keur Massar',
        ]);
        $this->actingAs($resp)->post('/demandes', [
            'chantier_id' => $chantier->id, 'motif' => 'Fondations',
            'lignes' => [['article_id' => $this->article->id, 'quantite' => $qte]],
        ]);
        $d = DemandeChantier::firstOrFail();
        $this->actingAs($this->user('controle'))->patch("/demandes/{$d->id}/approuver");
        $this->actingAs($this->gestionnaire)->patch("/demandes/{$d->id}/preparer");

        return $d->fresh();
    }

    private function planifier(string $type, int $sourceId, int $qte)
    {
        return $this->actingAs($this->gestionnaire)->post('/livraisons', [
            'source_type' => $type, 'source_id' => $sourceId, 'livreur_id' => $this->livreur->id,
            'adresse_destination' => 'Rufisque', 'date_prevue' => now()->toDateString(),
            'lignes' => [['article_id' => $this->article->id, 'entrepot_id' => 1, 'qte_prevue' => $qte]],
        ]);
    }

    private function derniere(): Livraison
    {
        return Livraison::latest('id')->firstOrFail();
    }

    private function confirmer(Livraison $l, int $livre, ?string $motif = null)
    {
        $ligne = $l->lignes()->firstOrFail();

        return $this->actingAs($this->gestionnaire)->patch("/livraisons/{$l->id}/confirmer", [
            'lignes' => [['id' => $ligne->id, 'qte_livree' => $livre, 'motif_ecart' => $motif]],
        ]);
    }

    public function test_la_planification_ne_touche_pas_au_stock(): void
    {
        $c = $this->commande();

        $this->planifier('commande', $c->id, 50)->assertRedirect();

        $l = $this->derniere();
        $this->assertSame('planifiee', $l->statut);
        $this->assertStringStartsWith('LIV-', $l->numero);
        $this->assertSame(200, $this->stock());
        $this->assertSame(0, MouvementStock::count());
    }

    public function test_scenario_a_la_remise_fait_passer_le_stock_de_200_a_150(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $l = $this->derniere();

        $this->confirmer($l, 50)->assertRedirect();

        $this->assertSame(150, $this->stock());
        $this->assertSame('livree', $l->fresh()->statut);
        $this->assertSame('livree', $c->fresh()->statut);
        $this->assertSame(50, $c->lignes()->first()->quantite_livree);
        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'sortie', 'quantite' => -50, 'origine_type' => 'livraison', 'origine_id' => $l->id,
        ]);
    }

    public function test_scenario_b_la_demande_est_satisfaite_et_le_stock_diminue_de_30(): void
    {
        $d = $this->demandePreparee(30);
        $this->planifier('demande', $d->id, 30)->assertRedirect();

        $this->confirmer($this->derniere(), 30)->assertRedirect();

        $this->assertSame(170, $this->stock());
        $this->assertSame('satisfaite', $d->fresh()->statut);
        $this->assertSame(30, $d->lignes()->first()->quantite_servie);
    }

    public function test_une_livraison_partielle_laisse_le_reste_identifiable(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $this->confirmer($this->derniere(), 30, 'Camion plein')->assertRedirect();

        $this->assertSame(170, $this->stock());
        $this->assertSame('partiellement_livree', $this->derniere()->statut);
        $this->assertSame('partiellement_livree', $c->fresh()->statut);

        $this->planifier('commande', $c->id, 21)->assertSessionHasErrors('statut'); // reste : 20
        $this->planifier('commande', $c->id, 20)->assertRedirect();
        $this->confirmer($this->derniere(), 20)->assertRedirect();

        $this->assertSame(150, $this->stock());
        $this->assertSame('livree', $c->fresh()->statut);
    }

    public function test_un_ecart_sans_motif_est_refuse(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $l = $this->derniere();

        $this->confirmer($l, 30)->assertSessionHasErrors('statut');

        $this->assertSame(200, $this->stock());
        $this->assertSame('planifiee', $l->fresh()->statut);
        $this->assertSame(0, MouvementStock::count());
    }

    public function test_une_livraison_echouee_ne_touche_pas_au_stock(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);

        $this->confirmer($this->derniere(), 0, 'Client absent')->assertRedirect();

        $this->assertSame('echouee', $this->derniere()->statut);
        $this->assertSame('en_preparation', $c->fresh()->statut);
        $this->assertSame(200, $this->stock());
        $this->assertSame(0, MouvementStock::count());

        $this->planifier('commande', $c->id, 50)->assertRedirect(); // tout le reste est replanifiable
    }

    public function test_le_stock_insuffisant_annule_toute_la_confirmation(): void
    {
        StockArticle::first()->update(['quantite' => 20]);
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $l = $this->derniere();

        $this->confirmer($l, 50)->assertSessionHasErrors('statut');

        $this->assertSame(20, $this->stock());
        $this->assertSame(0, MouvementStock::count());
        $this->assertSame('planifiee', $l->fresh()->statut);
        $this->assertSame(0, $c->lignes()->first()->quantite_livree);
        $this->assertSame('en_preparation', $c->fresh()->statut);
    }

    public function test_on_ne_planifie_pas_plus_que_le_reste_a_livrer(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 40)->assertRedirect();

        $this->planifier('commande', $c->id, 20)->assertSessionHasErrors('statut'); // 10 restants
        $this->assertSame(1, Livraison::count());
    }

    public function test_on_ne_planifie_pas_une_commande_non_preparee(): void
    {
        $c = $this->commande(50, false);

        $this->planifier('commande', $c->id, 10)->assertSessionHasErrors('statut');
        $this->assertSame(0, Livraison::count());
    }

    public function test_on_ne_livre_pas_plus_que_le_prevu(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);

        $this->confirmer($this->derniere(), 60)->assertSessionHasErrors('statut');
        $this->assertSame(200, $this->stock());
    }

    public function test_on_ne_confirme_pas_deux_fois_la_meme_remise(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $l = $this->derniere();

        $this->confirmer($l, 50)->assertRedirect();
        $this->confirmer($l, 50)->assertSessionHasErrors('statut');

        $this->assertSame(150, $this->stock());
        $this->assertSame(1, MouvementStock::count());
    }

    public function test_une_livraison_peut_etre_demarree_puis_confirmee(): void
    {
        $c = $this->commande(50);
        $this->planifier('commande', $c->id, 50);
        $l = $this->derniere();

        $this->actingAs($this->gestionnaire)->patch("/livraisons/{$l->id}/demarrer")->assertRedirect();
        $this->assertSame('en_cours', $l->fresh()->statut);

        $this->actingAs($this->gestionnaire)->patch("/livraisons/{$l->id}/demarrer")->assertSessionHasErrors('statut');

        $this->confirmer($l, 50)->assertRedirect();
        $this->assertSame('livree', $l->fresh()->statut);
    }

    public function test_les_droits_d_acces_aux_livraisons(): void
    {
        $c = $this->commande(50);

        $this->actingAs($this->user('chantier'))->get('/livraisons')->assertForbidden();
        $this->actingAs($this->user('controle'))->get('/livraisons')->assertOk();
        $this->planifier('commande', $c->id, 10); // crée une livraison avec le gestionnaire

        $this->actingAs($this->user('controle'))->post('/livraisons', [
            'source_type' => 'commande', 'source_id' => $c->id, 'livreur_id' => $this->livreur->id,
            'adresse_destination' => 'X', 'date_prevue' => now()->toDateString(),
            'lignes' => [['article_id' => $this->article->id, 'entrepot_id' => 1, 'qte_prevue' => 5]],
        ])->assertForbidden();

        $l = $this->derniere();
        $this->actingAs($this->user('controle'))->patch("/livraisons/{$l->id}/demarrer")->assertForbidden();
        $this->assertSame(1, Livraison::count());
    }
}