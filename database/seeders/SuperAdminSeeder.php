<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columns = Schema::getColumnListing('users');

        $attributes = [
            'name' => 'Super Administrateur',
            'password' => Hash::make('dmscccm@2026'),
        ];

        if (in_array('organisation_id', $columns, true)) {
            $attributes['organisation_id'] = null;
        }
        if (in_array('role', $columns, true)) {
            $attributes['role'] = 'super_admin';
        }
        if (in_array('is_active', $columns, true)) {
            $attributes['is_active'] = true;
        }
        if (in_array('phone', $columns, true)) {
            $attributes['phone'] = '+243 972 902 713';
        }
        if (in_array('must_change_password', $columns, true)) {
            $attributes['must_change_password'] = false;
        }

        User::updateOrCreate(
            ['email' => 'superadmin@dms-cccm.org'],
            $attributes
        );
    }
}
