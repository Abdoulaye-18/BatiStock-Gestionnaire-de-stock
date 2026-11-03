<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['name' => 'Admin', 'email' => 'admin@batistock.test', 'role' => 'admin'],
            ['name' => 'Gestionnaire', 'email' => 'gestionnaire@batistock.test', 'role' => 'gestionnaire'],
            ['name' => 'Moussa Diop', 'email' => 'chantier@batistock.test', 'role' => 'chantier'],
            ['name' => 'Fatou Ndiaye', 'email' => 'controle@batistock.test', 'role' => 'controle'],
        ];

        foreach ($comptes as $c) {
    $user = User::firstOrNew(['email' => $c['email']]);
    $user->forceFill([
        'name' => $c['name'],
        'password' => Hash::make(env('SEED_PASSWORD', 'BatiStock#2026')),
        'role_id' => Role::where('code', $c['role'])->value('id'),
        'actif' => true,
        'email_verified_at' => now(),
    ])->save();
}
    }
}