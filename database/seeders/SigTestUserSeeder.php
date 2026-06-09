<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SigTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organisation = Organisation::query()->where('is_active', true)->first();

        User::updateOrCreate(
            ['email' => 'sig.test@dms-cccm.org'],
            [
                'name' => 'Utilisateur SIG Test',
                'password' => Hash::make('dmscccm@2026'),
                'organisation_id' => $organisation?->id,
                'role' => 'sig_user',
                'is_active' => true,
                'phone' => '+243 000 000 000',
                'must_change_password' => false,
            ]
        );
    }
}
