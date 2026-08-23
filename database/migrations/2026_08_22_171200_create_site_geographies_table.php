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
        if (Schema::hasTable('site_geographies')) {
            return;
        }

        Schema::create('site_geographies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('mobile_collection_submission_id')->nullable()->constrained('mobile_collection_submissions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('geometry_type')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->string('point_category')->nullable();
            $table->string('point_category_other')->nullable();
            $table->string('polygon_category')->nullable();
            $table->string('polygon_block_name')->nullable();
            $table->json('geojson_data')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->string('source')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'collected_at'], 'site_geographies_site_collected_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_geographies');
    }
};
