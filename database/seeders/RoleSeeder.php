<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'admin', 'nom' => 'Administrateur', 'description' => 'Gère les comptes et les paramètres'],
            ['code' => 'gestionnaire', 'nom' => 'Gestionnaire de stock', 'description' => 'Gère le stock, prépare les sorties et les livraisons'],
            ['code' => 'chantier', 'nom' => 'Responsable de chantier', 'description' => 'Soumet les demandes de matériaux'],
            ['code' => 'controle', 'nom' => 'Responsable de contrôle', 'description' => 'Approuve ou refuse les demandes'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['code' => $role['code']], $role);
        }
    }
}