<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OrganisationSeeder::class,
            AdminBoundariesSeeder::class,
            SuperAdminSeeder::class,
            DefaultUsersSeeder::class,
            SigTestUserSeeder::class,
            OssatChoixSeeder::class,
            MobileQuestionnaireSeeder::class,
        ]);
    }
}
