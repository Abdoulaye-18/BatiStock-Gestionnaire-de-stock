<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('lignes_commande', function (Blueprint $t) {
        $t->unsignedInteger('quantite_livree')->default(0)->after('quantite');
    });
}

public function down(): void
{
    Schema::table('lignes_commande', function (Blueprint $t) {
        $t->dropColumn('quantite_livree');
    });
}
};
