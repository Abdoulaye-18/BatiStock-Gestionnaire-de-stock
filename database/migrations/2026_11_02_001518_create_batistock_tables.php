<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- Catalogue ----------
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('nom');
            $t->timestamps();
        });

        Schema::create('unites', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('nom');
            $t->timestamps();
        });

        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('nom');
            $t->foreignId('categorie_id')->constrained('categories');
            $t->foreignId('unite_id')->constrained('unites');
            $t->decimal('prix_vente', 12, 2)->nullable();
            $t->unsignedInteger('seuil_alerte')->default(0);
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        // ---------- Dépôts et stock ----------
        Schema::create('entrepots', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('nom');
            $t->string('localisation')->nullable();
            $t->timestamps();
        });

        Schema::create('stock_articles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained('articles');
            $t->foreignId('entrepot_id')->constrained('entrepots');
            $t->unsignedInteger('quantite')->default(0); // jamais négatif
            $t->timestamps();
            $t->unique(['article_id', 'entrepot_id']);
        });

        Schema::create('fournisseurs', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('telephone')->nullable();
            $t->string('email')->nullable();
            $t->string('adresse')->nullable();
            $t->timestamps();
        });

        // ---------- Clients, chantiers, livreurs ----------
        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('telephone')->nullable();
            $t->string('email')->nullable();
            $t->string('adresse_livraison')->nullable();
            $t->timestamps();
        });

        Schema::create('chantiers', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('localisation')->nullable();
            $t->string('etat')->default('actif'); // actif | termine
            $t->foreignId('responsable_id')->nullable()->constrained('users');
            $t->timestamps();
        });

        Schema::create('livreurs', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('telephone')->nullable();
            $t->boolean('disponible')->default(true);
            $t->timestamps();
        });

        // ---------- Commandes clients ----------
        Schema::create('commandes_client', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->foreignId('client_id')->constrained('clients');
            $t->foreignId('user_id')->constrained('users'); // créateur
            $t->date('date_commande');
            $t->string('statut')->default('brouillon');
            // brouillon | confirmee | en_preparation | partiellement_livree | livree | annulee
            $t->string('adresse_destination')->nullable();
            $t->decimal('montant_total', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('lignes_commande', function (Blueprint $t) {
            $t->id();
            $t->foreignId('commande_id')->constrained('commandes_client')->cascadeOnDelete();
            $t->foreignId('article_id')->constrained('articles');
            $t->unsignedInteger('quantite');
            $t->decimal('prix_unitaire', 12, 2)->default(0);
            $t->timestamps();
        });

        // ---------- Demandes de chantier ----------
        Schema::create('demandes_chantier', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->foreignId('chantier_id')->constrained('chantiers');
            $t->foreignId('demandeur_id')->constrained('users');
            $t->foreignId('validateur_id')->nullable()->constrained('users');
            $t->string('statut')->default('en_attente');
            // en_attente | approuvee | refusee | en_preparation | partiellement_satisfaite | satisfaite
            $t->string('motif')->nullable();
            $t->string('motif_refus')->nullable();
            $t->date('date_souhaitee')->nullable();
            $t->timestamps();
        });

        Schema::create('lignes_demande', function (Blueprint $t) {
            $t->id();
            $t->foreignId('demande_id')->constrained('demandes_chantier')->cascadeOnDelete();
            $t->foreignId('article_id')->constrained('articles');
            $t->unsignedInteger('quantite');
            $t->timestamps();
        });

        // ---------- Livraisons ----------
        Schema::create('livraisons', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->foreignId('commande_id')->nullable()->constrained('commandes_client');
            $t->foreignId('demande_id')->nullable()->constrained('demandes_chantier');
            $t->foreignId('livreur_id')->nullable()->constrained('livreurs');
            $t->string('adresse_destination');
            $t->date('date_prevue');
            $t->date('date_effective')->nullable();
            $t->string('statut')->default('planifiee');
            // planifiee | en_cours | livree | partiellement_livree | echouee
            $t->text('observation')->nullable();
            $t->timestamps();
        });

        Schema::create('lignes_livraison', function (Blueprint $t) {
            $t->id();
            $t->foreignId('livraison_id')->constrained('livraisons')->cascadeOnDelete();
            $t->foreignId('article_id')->constrained('articles');
            $t->foreignId('entrepot_id')->constrained('entrepots');
            $t->unsignedInteger('qte_prevue');
            $t->unsignedInteger('qte_livree')->default(0);
            $t->string('motif_ecart')->nullable();
            $t->timestamps();
        });

        // ---------- Journal des mouvements ----------
        Schema::create('mouvements_stock', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_article_id')->constrained('stock_articles');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs');
            $t->string('type');
            // entree | sortie | ajustement | transfert_sortie | transfert_entree
            $t->integer('quantite'); // signée pour les ajustements
            $t->string('motif');
            $t->dateTime('date_mouvement');
            $t->uuid('id_transfert')->nullable()->index();
            $t->string('origine_type')->nullable(); // commande | demande | livraison
            $t->unsignedBigInteger('origine_id')->nullable();
            $t->timestamps();
            $t->index(['type', 'date_mouvement']);
        });
    }

    public function down(): void
    {
        foreach ([
            'mouvements_stock', 'lignes_livraison', 'livraisons', 'lignes_demande',
            'demandes_chantier', 'lignes_commande', 'commandes_client', 'livreurs',
            'chantiers', 'clients', 'fournisseurs', 'stock_articles', 'entrepots',
            'articles', 'unites', 'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};