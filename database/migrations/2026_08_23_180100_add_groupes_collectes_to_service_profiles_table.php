<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_profiles', function (Blueprint $table) {
            $table->json('groupes_collectes')->nullable()->after('notes_generales');
        });
    }

    public function down(): void
    {
        Schema::table('service_profiles', function (Blueprint $table) {
            $table->dropColumn('groupes_collectes');
        });
    }
};
