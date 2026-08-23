<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organisation = Organisation::query()->where('is_active', true)->first();
        $userColumns = Schema::getColumnListing('users');

        $mobileUserAttributes = [
            'name' => 'Agent Collecte Mobile',
            'password' => Hash::make('password'),
        ];

        if (in_array('organisation_id', $userColumns, true)) {
            $mobileUserAttributes['organisation_id'] = $organisation?->id;
        }
        if (in_array('role', $userColumns, true)) {
            $mobileUserAttributes['role'] = 'user';
        }
        if (in_array('is_active', $userColumns, true)) {
            $mobileUserAttributes['is_active'] = true;
        }
        if (in_array('phone', $userColumns, true)) {
            $mobileUserAttributes['phone'] = '+243 000 111 222';
        }
        if (in_array('must_change_password', $userColumns, true)) {
            $mobileUserAttributes['must_change_password'] = false;
        }

        User::updateOrCreate(
            ['email' => 'heaney.titus@example.org'],
            $mobileUserAttributes
        );

        $adminUserAttributes = [
            'name' => 'Admin Organisation',
            'password' => Hash::make('dmscccm@2026'),
        ];

        if (in_array('organisation_id', $userColumns, true)) {
            $adminUserAttributes['organisation_id'] = $organisation?->id;
        }
        if (in_array('role', $userColumns, true)) {
            $adminUserAttributes['role'] = 'admin_organisation';
        }
        if (in_array('is_active', $userColumns, true)) {
            $adminUserAttributes['is_active'] = true;
        }
        if (in_array('phone', $userColumns, true)) {
            $adminUserAttributes['phone'] = '+243 000 333 444';
        }
        if (in_array('must_change_password', $userColumns, true)) {
            $adminUserAttributes['must_change_password'] = false;
        }

        User::updateOrCreate(
            ['email' => 'admin.org@dms-cccm.org'],
            $adminUserAttributes
        );
    }
}
