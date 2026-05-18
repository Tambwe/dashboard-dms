<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $statuses = [
            ['name' => 'Planifie', 'code' => 'planifie', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'En cours', 'code' => 'en_cours', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Suspendu', 'code' => 'suspendu', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Termine', 'code' => 'termine', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Annule', 'code' => 'annule', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($statuses as $status) {
            DB::table('project_statuses')->updateOrInsert(
                ['code' => $status['code']],
                ['name' => $status['name'], 'is_active' => $status['is_active'], 'updated_at' => $now]
            );
        }

        $donors = [
            ['name' => 'UNICEF', 'code' => 'UNICEF'],
            ['name' => 'UNHCR', 'code' => 'UNHCR'],
            ['name' => 'ECHO', 'code' => 'ECHO'],
            ['name' => 'USAID', 'code' => 'USAID'],
            ['name' => 'PAM', 'code' => 'PAM'],
        ];

        foreach ($donors as $donor) {
            DB::table('project_donors')->updateOrInsert(
                ['code' => $donor['code']],
                ['name' => $donor['name'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('project_donors')->whereIn('code', ['UNICEF', 'UNHCR', 'ECHO', 'USAID', 'PAM'])->delete();
    }
};
