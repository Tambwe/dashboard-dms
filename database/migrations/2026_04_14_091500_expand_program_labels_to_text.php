<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE program_sector_objectives MODIFY label TEXT NOT NULL');
        DB::statement('ALTER TABLE program_strategic_objectives MODIFY label TEXT NOT NULL');
        DB::statement('ALTER TABLE program_indicators MODIFY label TEXT NOT NULL');
        DB::statement('ALTER TABLE program_activities MODIFY label TEXT NOT NULL');
        DB::statement('ALTER TABLE program_sub_activities MODIFY label TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE program_sector_objectives MODIFY label VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE program_strategic_objectives MODIFY label VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE program_indicators MODIFY label VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE program_activities MODIFY label VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE program_sub_activities MODIFY label VARCHAR(255) NOT NULL');
    }
};