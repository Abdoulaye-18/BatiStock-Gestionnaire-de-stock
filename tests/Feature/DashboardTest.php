<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chantier;
use App\Models\Client;
use App\Models\CommandeClient;
use App\Models\DemandeChantier;
use App\Models\Livraison;
use App\Models\Role;
use App\Models\StockArticle;
use App\Models\User;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(ReferentielSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    private function article(string $ref, int $seuil, int $stock): Article
    {
        $a = Article::create([
            'reference' => $ref, 'nom' => "Article {$ref}", 'categorie_id' => 1,
            'unite_id' => 1, 'seuil_alerte' => $seuil, 'actif' => true,
        ]);

        if ($stock > 0) {
            StockArticle::create(['article_id' => $a->id, 'entrepot_id' => 1, 'quantite' => $stock]);
        }

        return $a;
    }

    private function demande(User $demandeur, Chantier $chantier, string $numero, string $statut): void
    {
        DemandeChantier::create([
            'numero' => $numero, 'chantier_id' => $chantier->id,
            'demandeur_id' => $demandeur->id, 'statut' => $statut, 'motif' => 'Test',
        ]);
    }

    public function test_les_indicateurs_de_la_vue_complete_sont_corrects(): void
    {
        $g = $this->user('gestionnaire');
        $this->article('A', 20, 12);   // sous le seuil
        $this->article('B', 10, 100);  // normal

        $client = Client::create(['nom' => 'ETS']);
        foreach (['en_preparation', 'brouillon'] as $i => $statut) {
            CommandeClient::create([
                'numero' => "CMD-T{$i}", 'client_id' => $client->id, 'user_id' => $g->id,
                'date_commande' => today(), 'statut' => $statut, 'adresse_destination' => 'X',
            ]);
        }

        $chantier = Chantier::create(['nom' => 'C', 'etat' => 'actif']);
        $this->demande($g, $chantier, 'DEM-T1', 'en_attente');

        Livraison::create(['numero' => 'LIV-T1', 'adresse_destination' => 'X', 'date_prevue' => now()->subDay()->toDateString(), 'statut' => 'planifiee']);
        Livraison::create(['numero' => 'LIV-T2', 'adresse_destination' => 'X', 'date_prevue' => now()->toDateString(), 'statut' => 'livree']);

        $this->actingAs($g)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('vue', 'global')
            ->where('donnees.kpis.0.valeur', 2)
            ->where('donnees.kpis.1.valeur', 112)
            ->where('donnees.kpis.2.valeur', 1)
            ->where('donnees.kpis.3.valeur', 1)
            ->where('donnees.kpis.4.valeur', 1)
            ->where('donnees.kpis.4.sous_titre', '1 en retard')
            ->where('donnees.kpis.5.valeur', 1)
            ->has('donnees.evolution', 7)
            ->where('donnees.evolution.6.total', 112)
            ->where('donnees.categories.0.pct', 100)
            ->has('donnees.alertes', 1)
            ->where('donnees.alertes.0.statut', 'faible')
            ->where('alertes', 1));
    }

    public function test_un_article_sans_stock_est_en_rupture(): void
    {
        $this->article('Z', 5, 0);

        $this->actingAs($this->user('gestionnaire'))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('donnees.alertes.0.statut', 'rupture')
            ->where('donnees.alertes.0.stock', 0));
    }

    public function test_l_evolution_du_stock_est_reconstituee_a_partir_des_mouvements(): void
    {
        $g = $this->user('gestionnaire');
        $a = $this->article('A', 0, 0);

        $this->actingAs($g)->post('/mouvements/entree', [
            'article_id' => $a->id, 'entrepot_id' => 1, 'quantite' => 100, 'motif' => 'Réception',
        ]);

        $this->actingAs($g)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('donnees.evolution.5.total', 0)    // hier soir : rien en stock
            ->where('donnees.evolution.6.total', 100)  // aujourd'hui
            ->has('donnees.mouvements', 1));
    }

    public function test_le_responsable_de_chantier_ne_recoit_aucune_donnee_de_stock(): void
    {
        $moi = $this->user('chantier');
        $autre = $this->user('chantier');
        $this->article('A', 20, 12);

        $mien = Chantier::create(['nom' => 'Mien', 'etat' => 'actif', 'responsable_id' => $moi->id]);
        $leur = Chantier::create(['nom' => 'Autre', 'etat' => 'actif', 'responsable_id' => $autre->id]);
        $this->demande($moi, $mien, 'DEM-T1', 'en_attente');
        $this->demande($moi, $mien, 'DEM-T2', 'satisfaite');
        $this->demande($autre, $leur, 'DEM-T3', 'en_attente');

        $this->actingAs($moi)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('vue', 'chantier')
            ->where('donnees.kpis.0.valeur', 1)
            ->where('donnees.kpis.1.valeur', 1)
            ->where('donnees.kpis.3.valeur', 1)
            ->has('donnees.demandes', 2)
            ->missing('donnees.evolution')
            ->missing('donnees.categories')
            ->missing('donnees.alertes')
            ->missing('donnees.mouvements')
            ->where('alertes', 0));
    }

    public function test_les_actions_rapides_dependent_du_role(): void
    {
        $this->actingAs($this->user('gestionnaire'))->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->has('actions', 3));

        $this->actingAs($this->user('chantier'))->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->has('actions', 1));

        $this->actingAs($this->user('controle'))->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->has('actions', 0));

        $this->actingAs($this->user('admin'))->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->has('actions', 4));
    }
}