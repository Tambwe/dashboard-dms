<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des organisations de test
        $organisations = [
            [
                'name' => 'HCR - Haut Commissariat pour les Réfugiés',
                'code' => 'HCR',
                'description' => 'Organisation humanitaire internationale',
                'email' => 'contact@hcr-rdc.org',
                'phone' => '+243 123 456 789',
                'is_active' => true,
            ],
            [
                'name' => 'WNH - World Nourishment Help',
                'code' => 'WNH',
                'description' => 'Organisation d\'aide alimentaire',
                'email' => 'info@wnh-rdc.org',
                'phone' => '+243 987 654 321',
                'is_active' => true,
            ],
            [
                'name' => 'CCCM - Camp Coordination and Camp Management',
                'code' => 'CCCM',
                'description' => 'Coordination des camps',
                'email' => 'cccm@dms-rdc.org',
                'phone' => '+243 456 789 123',
                'is_active' => true,
            ],
        ];

        foreach ($organisations as $orgData) {
            $org = Organisation::create($orgData);

            // Créer un admin pour chaque organisation
            User::create([
                'name' => 'Admin ' . $orgData['code'],
                'email' => 'admin@' . strtolower($orgData['code']) . '.com',
                'password' => Hash::make('dmscccm@2026'),
                'organisation_id' => $org->id,
                'role' => 'admin_organisation',
                'is_active' => true,
                'must_change_password' => false,
            ]);

            // Créer quelques utilisateurs pour chaque organisation
            User::create([
                'name' => 'Utilisateur 1 ' . $orgData['code'],
                'email' => 'user1@' . strtolower($orgData['code']) . '.com',
                'password' => Hash::make('dmscccm@2026'),
                'organisation_id' => $org->id,
                'role' => 'user',
                'is_active' => true,
                'must_change_password' => false,
            ]);
        }
    }
}
