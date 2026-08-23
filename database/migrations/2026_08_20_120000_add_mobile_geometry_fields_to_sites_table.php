<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('geometry_type')->nullable()->after('geojson_data');
            $table->decimal('collection_accuracy_m', 8, 2)->nullable()->after('geometry_type');
            $table->timestamp('geometry_collected_at')->nullable()->after('collection_accuracy_m');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['geometry_type', 'collection_accuracy_m', 'geometry_collected_at']);
        });
    }
};
