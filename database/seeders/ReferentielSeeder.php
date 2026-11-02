<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $add = fn ($rows) => collect($rows)->map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now])->all();

        DB::table('categories')->insert($add([
            ['code' => 'CIM', 'nom' => 'Ciment et liants'],
            ['code' => 'FER', 'nom' => 'Fer et acier'],
            ['code' => 'SAB', 'nom' => 'Sable et gravier'],
            ['code' => 'PEI', 'nom' => 'Peinture'],
            ['code' => 'CAR', 'nom' => 'Carrelage'],
            ['code' => 'PLO', 'nom' => 'Plomberie'],
            ['code' => 'ELE', 'nom' => 'Électricité'],
        ]));

        DB::table('unites')->insert($add([
            ['code' => 'sac', 'nom' => 'Sac'],
            ['code' => 'barre', 'nom' => 'Barre'],
            ['code' => 'm3', 'nom' => 'Mètre cube'],
            ['code' => 'pot', 'nom' => 'Pot'],
            ['code' => 'carton', 'nom' => 'Carton'],
            ['code' => 'piece', 'nom' => 'Pièce'],
        ]));

        DB::table('entrepots')->insert($add([
            ['code' => 'DEP-PRINC', 'nom' => 'Dépôt Principal', 'localisation' => 'Pikine'],
            ['code' => 'DEP-SEC', 'nom' => 'Dépôt Secondaire', 'localisation' => 'Rufisque'],
        ]));
    }
}