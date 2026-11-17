<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chantier;
use App\Models\DemandeChantier;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferentielSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function soumettre(User $demandeur): DemandeChantier
    {
        $chantier = Chantier::create(['nom' => 'Villa Diop', 'etat' => 'actif', 'responsable_id' => $demandeur->id]);

        $this->actingAs($demandeur)->post('/demandes', [
            'chantier_id' => $chantier->id,
            'motif' => 'Fondations',
            'lignes' => [['article_id' => $this->article->id, 'quantite' => 30]],
        ]);

        return DemandeChantier::firstOrFail();
    }

    public function test_l_approbation_notifie_les_gestionnaires_actifs_et_le_demandeur(): void
    {
        $demandeur = $this->user('chantier');
        $g1 = $this->user('gestionnaire');
        $g2 = $this->user('gestionnaire');
        $g2->forceFill(['actif' => false])->save();
        $controle = $this->user('controle');
        $demande = $this->soumettre($demandeur);

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver")->assertRedirect();

        $this->assertCount(1, $g1->notifications);
        $this->assertSame('a_preparer', $g1->notifications->first()->data['evenement']);
        $this->assertSame('/demandes?statut=approuvee', $g1->notifications->first()->data['lien']);
        $this->assertCount(0, $g2->fresh()->notifications);
        $this->assertCount(0, $controle->notifications);
        $this->assertCount(1, $demandeur->notifications);
        $this->assertSame('approuvee', $demandeur->notifications->first()->data['evenement']);
    }

    public function test_le_refus_notifie_uniquement_le_demandeur_avec_le_motif(): void
    {
        $demandeur = $this->user('chantier');
        $gestionnaire = $this->user('gestionnaire');
        $demande = $this->soumettre($demandeur);

        $this->actingAs($this->user('controle'))
            ->patch("/demandes/{$demande->id}/refuser", ['motif_refus' => 'Budget dépassé'])
            ->assertRedirect();

        $this->assertCount(1, $demandeur->notifications);
        $this->assertStringContainsString('Budget dépassé', $demandeur->notifications->first()->data['message']);
        $this->assertCount(0, $gestionnaire->notifications);
    }

    public function test_une_approbation_refusee_n_envoie_aucune_notification(): void
    {
        $admin = $this->user('admin');
        $this->user('gestionnaire');
        $demande = $this->soumettre($admin);

        $this->actingAs($admin)->patch("/demandes/{$demande->id}/approuver")->assertSessionHasErrors('statut');

        $this->assertSame(0, DatabaseNotification::count());
    }

    public function test_une_double_approbation_ne_notifie_pas_deux_fois(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $controle = $this->user('controle');
        $demande = $this->soumettre($this->user('chantier'));

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver");
        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver")->assertSessionHasErrors('statut');

        $this->assertCount(1, $gestionnaire->notifications);
    }

    public function test_ouvrir_une_notification_la_marque_lue_et_redirige_vers_les_demandes_approuvees(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $demande = $this->soumettre($this->user('chantier'));
        $this->actingAs($this->user('controle'))->patch("/demandes/{$demande->id}/approuver");

        $notification = $gestionnaire->notifications->first();

        $this->actingAs($gestionnaire)
            ->patch("/notifications/{$notification->id}/lue")
            ->assertRedirect('/demandes?statut=approuvee');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_on_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $intrus = $this->user('controle');
        $demande = $this->soumettre($this->user('chantier'));
        $this->actingAs($this->user('controle'))->patch("/demandes/{$demande->id}/approuver");
        $notification = $gestionnaire->notifications->first();

        $this->actingAs($intrus)->patch("/notifications/{$notification->id}/lue")->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_tout_marquer_comme_lu(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $demande = $this->soumettre($this->user('chantier'));
        $this->actingAs($this->user('controle'))->patch("/demandes/{$demande->id}/approuver");

        $this->actingAs($gestionnaire)->patch('/notifications/lues')->assertRedirect();

        $this->assertSame(0, $gestionnaire->unreadNotifications()->count());
    }

    public function test_les_notifications_sont_partagees_avec_l_interface(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $demandeur = $this->user('chantier');
        $demande = $this->soumettre($demandeur);
        $this->actingAs($this->user('controle'))->patch("/demandes/{$demande->id}/approuver");

        $this->actingAs($gestionnaire)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('notifications.non_lues', 1)
            ->has('notifications.recentes', 1)
            ->where('notifications.recentes.0.titre', 'Demande à préparer'));

        $this->actingAs($demandeur)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('notifications.recentes.0.titre', 'Demande approuvée'));
    }

    public function test_le_bandeau_a_traiter_suit_l_etat_des_demandes(): void
    {
        $gestionnaire = $this->user('gestionnaire');
        $controle = $this->user('controle');
        $demande = $this->soumettre($this->user('chantier'));

        $this->actingAs($controle)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('aTraiter', 1)->where('aTraiter.0.couleur', 'orange'));
        $this->actingAs($gestionnaire)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('aTraiter', 0));

        $this->actingAs($controle)->patch("/demandes/{$demande->id}/approuver");

        $this->actingAs($gestionnaire)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('aTraiter', 1)
            ->where('aTraiter.0.href', route('demandes.index', ['statut' => 'approuvee'])));
        $this->actingAs($controle)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('aTraiter', 0));
    }
}