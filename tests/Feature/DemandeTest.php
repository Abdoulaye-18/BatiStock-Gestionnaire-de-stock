<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chantier;
use App\Models\DemandeChantier;
use App\Models\MouvementStock;
use App\Models\Role;
use App\Models\User;
use App\Services\DemandeService;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemandeTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(ReferentielSeeder::class);

        $this->article = Article::create([
            'reference' => 'CIM-50', 'nom' => 'Ciment 50kg', 'categorie_id' => 1,
            'unite_id' => 1, 'seuil_alerte' => 20, 'actif' => true,
        ]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role_id' => Role::where('code', $role)->value('id'), 'actif' => true])->save();

        return $u;
    }

    private function chantierDe(User $u): Chantier
    {
        return Chantier::create(['nom' => 'Chantier ' . $u->id, 'etat' => 'actif', 'responsable_id' => $u->id]);
    }

    private function payload(Chantier $c, int $quantite = 30): array
    {
        return [
            'chantier_id' => $c->id,
            'motif' => 'Fondations',
            'lignes' => [['article_id' => $this->article->id, 'quantite' => $quantite]],
        ];
    }

    private function demandeEnAttente(User $demandeur): DemandeChantier
    {
        $this->actingAs($demandeur)->post('/demandes', $this->payload($this->chantierDe($demandeur)));

        return DemandeChantier::firstOrFail();
    }

    public function test_un_responsable_soumet_une_demande_pour_son_chantier(): void
    {
        $moi = $this->user('chantier');

        $this->actingAs($moi)->post('/demandes', $this->payload($this->chantierDe($moi)))->assertRedirect();

        $demande = DemandeChantier::first();
        $this->assertSame('en_attente', $demande->statut);
        $this->assertStringStartsWith('DEM-', $demande->numero);
        $this->assertSame(30, $demande->lignes()->first()->quantite);
    }

    public function test_une_demande_ne_peut_pas_cibler_le_chantier_d_un_autre(): void
    {
        $moi = $this->user('chantier');
        $autre = $this->chantierDe($this->user('chantier'));

        $this->actingAs($moi)->post('/demandes', $this->payload($autre))->assertSessionHasErrors('chantier_id');
        $this->assertSame(0, DemandeChantier::count());
    }

    public function test_un_article_en_double_est_refuse(): void
    {
        $moi = $this->user('chantier');
        $data = $this->payload($this->chantierDe($moi));
        $data['lignes'][] = ['article_id' => $this->article->id, 'quantite' => 5];

        $this->actingAs($moi)->post('/demandes', $data)->assertSessionHasErrors('lignes.1.article_id');
    }

    public function test_le_controle_approuve_sans_toucher_au_stock(): void
    {
        $demande = $this->demandeEnAttente($this->user('chantier'));
        $controle = $this->user('controle');

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver")->assertRedirect();

        $demande->refresh();
        $this->assertSame('approuvee', $demande->statut);
        $this->assertSame($controle->id, $demande->validateur_id);
        $this->assertSame(0, MouvementStock::count());
    }

    public function test_on_ne_peut_pas_approuver_deux_fois(): void
    {
        $demande = $this->demandeEnAttente($this->user('chantier'));
        $controle = $this->user('controle');

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver");
        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver")->assertSessionHasErrors('statut');
    }

    public function test_un_refus_exige_un_motif(): void
    {
        $demande = $this->demandeEnAttente($this->user('chantier'));
        $controle = $this->user('controle');

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/refuser", [])->assertSessionHasErrors('motif_refus');

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/refuser", ['motif_refus' => 'Budget dépassé'])
            ->assertRedirect();

        $this->assertSame('refusee', $demande->fresh()->statut);
    }

    public function test_un_responsable_de_chantier_ne_peut_pas_approuver(): void
    {
        $moi = $this->user('chantier');
        $demande = $this->demandeEnAttente($moi);

        $this->actingAs($moi)->patch("/demandes/{$demande->id}/approuver")->assertForbidden();
        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    public function test_on_ne_valide_pas_sa_propre_demande(): void
    {
        $admin = $this->user('admin');
        $demande = $this->demandeEnAttente($admin);

        $this->actingAs($admin)->patch("/demandes/{$demande->id}/approuver")->assertSessionHasErrors('statut');
        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    public function test_la_preparation_exige_une_demande_approuvee(): void
    {
        $demande = $this->demandeEnAttente($this->user('chantier'));
        $gestionnaire = $this->user('gestionnaire');

        $this->actingAs($gestionnaire)->patch("/demandes/{$demande->id}/preparer")->assertSessionHasErrors('statut');

        $this->actingAs($this->user('controle'))->patch("/demandes/{$demande->id}/approuver");
        $this->actingAs($gestionnaire)->patch("/demandes/{$demande->id}/preparer")->assertRedirect();

        $this->assertSame('en_preparation', $demande->fresh()->statut);
    }

    public function test_un_responsable_ne_voit_que_ses_demandes_et_pas_le_stock(): void
    {
        $moi = $this->user('chantier');
        $this->demandeEnAttente($moi);
        $this->actingAs($this->user('chantier'))->post('/demandes', $this->payload($this->chantierDe($this->user('chantier'))));

        $this->actingAs($moi)->get('/demandes')->assertInertia(
            fn (Assert $page) => $page->has('demandes.data', 1)->where('demandes.data.0.lignes.0.disponible', null)
        );
    }

    public function test_le_statut_est_recalcule_selon_les_quantites_servies(): void
    {
        $demande = $this->demandeEnAttente($this->user('chantier'));
        $service = app(DemandeService::class);

        $demande->lignes()->update(['quantite_servie' => 10]);
        $service->recalculerStatut($demande->fresh());
        $this->assertSame('partiellement_satisfaite', $demande->fresh()->statut);

        $demande->lignes()->update(['quantite_servie' => 30]);
        $service->recalculerStatut($demande->fresh());
        $this->assertSame('satisfaite', $demande->fresh()->statut);
    }
}