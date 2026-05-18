<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le super administrateur global
        User::create([
            'name' => 'Super Administrateur',
            'email' => 'superadmin@dms-cccm.org',
            'password' => Hash::make('dmscccm@2026'),
            'organisation_id' => null,
            'role' => 'super_admin',
            'is_active' => true,
            'phone' => '+243 972 902 713',
            'must_change_password' => false,
        ]);
    }
}
