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
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('nom');
        $table->string('description')->nullable();
        $table->timestamps();
    });

    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('role_id')->nullable()->after('password')->constrained('roles');
        $table->boolean('actif')->default(true)->after('role_id');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropConstrainedForeignId('role_id');
        $table->dropColumn('actif');
    });
    Schema::dropIfExists('roles');
}
};
