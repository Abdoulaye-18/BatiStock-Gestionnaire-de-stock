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
    Schema::create('journal_activites', function (Blueprint $t) {
        $t->id();
        $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        $t->string('action');
        $t->string('entite')->nullable();
        $t->unsignedBigInteger('entite_id')->nullable();
        $t->text('detail')->nullable();
        $t->string('ip', 45)->nullable();
        $t->timestamp('created_at')->useCurrent();
        $t->index(['entite', 'entite_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('journal_activites');
}
};
