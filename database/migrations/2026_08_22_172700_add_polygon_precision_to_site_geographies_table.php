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
        if (!Schema::hasTable('site_geographies')) {
            return;
        }

        Schema::table('site_geographies', function (Blueprint $table) {
            if (!Schema::hasColumn('site_geographies', 'polygon_segment_distances_m')) {
                $table->json('polygon_segment_distances_m')->nullable()->after('geojson_data');
            }
            if (!Schema::hasColumn('site_geographies', 'polygon_segment_min_m')) {
                $table->decimal('polygon_segment_min_m', 10, 2)->nullable()->after('polygon_segment_distances_m');
            }
            if (!Schema::hasColumn('site_geographies', 'polygon_segment_max_m')) {
                $table->decimal('polygon_segment_max_m', 10, 2)->nullable()->after('polygon_segment_min_m');
            }
            if (!Schema::hasColumn('site_geographies', 'polygon_segment_avg_m')) {
                $table->decimal('polygon_segment_avg_m', 10, 2)->nullable()->after('polygon_segment_max_m');
            }
            if (!Schema::hasColumn('site_geographies', 'polygon_perimeter_m')) {
                $table->decimal('polygon_perimeter_m', 10, 2)->nullable()->after('polygon_segment_avg_m');
            }
            if (!Schema::hasColumn('site_geographies', 'polygon_point_count')) {
                $table->unsignedInteger('polygon_point_count')->nullable()->after('polygon_perimeter_m');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('site_geographies')) {
            return;
        }

        Schema::table('site_geographies', function (Blueprint $table) {
            $columns = [
                'polygon_segment_distances_m',
                'polygon_segment_min_m',
                'polygon_segment_max_m',
                'polygon_segment_avg_m',
                'polygon_perimeter_m',
                'polygon_point_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('site_geographies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
