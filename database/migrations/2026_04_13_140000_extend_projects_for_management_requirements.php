<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('funding_amount', 15, 2)->nullable()->after('end_date');
            $table->unsignedInteger('beneficiaries_female_0_17')->default(0)->after('funding_amount');
            $table->unsignedInteger('beneficiaries_female_18_59')->default(0)->after('beneficiaries_female_0_17');
            $table->unsignedInteger('beneficiaries_female_60_plus')->default(0)->after('beneficiaries_female_18_59');
            $table->unsignedInteger('beneficiaries_male_0_17')->default(0)->after('beneficiaries_female_60_plus');
            $table->unsignedInteger('beneficiaries_male_18_59')->default(0)->after('beneficiaries_male_0_17');
            $table->unsignedInteger('beneficiaries_male_60_plus')->default(0)->after('beneficiaries_male_18_59');
        });

        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('project_donors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('project_status_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_status_id')->constrained('project_statuses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'project_status_id']);
        });

        Schema::create('project_donor_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_donor_id')->constrained('project_donors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'project_donor_id']);
        });

        Schema::create('project_execution_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->foreignId('territoire_id')->constrained('territoires')->cascadeOnDelete();
            $table->foreignId('commune_id')->constrained('communes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'province_id', 'territoire_id', 'commune_id'], 'project_zone_unique');
        });

        DB::table('project_statuses')->insert([
            ['name' => 'Planifie', 'code' => 'planifie', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'En cours', 'code' => 'en_cours', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Suspendu', 'code' => 'suspendu', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Termine', 'code' => 'termine', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Annule', 'code' => 'annule', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('project_donors')->insert([
            ['name' => 'UNICEF', 'code' => 'UNICEF', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'UNHCR', 'code' => 'UNHCR', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ECHO', 'code' => 'ECHO', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'USAID', 'code' => 'USAID', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PAM', 'code' => 'PAM', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $projectStatusMap = DB::table('project_statuses')->pluck('id', 'code');
        $projects = DB::table('projects')->select('id', 'status')->get();

        foreach ($projects as $project) {
            $statusCode = $project->status ?: 'planifie';
            $statusId = $projectStatusMap[$statusCode] ?? $projectStatusMap['planifie'];

            DB::table('project_status_project')->insert([
                'project_id' => $project->id,
                'project_status_id' => $statusId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_execution_zones');
        Schema::dropIfExists('project_donor_project');
        Schema::dropIfExists('project_status_project');
        Schema::dropIfExists('project_donors');
        Schema::dropIfExists('project_statuses');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'funding_amount',
                'beneficiaries_female_0_17',
                'beneficiaries_female_18_59',
                'beneficiaries_female_60_plus',
                'beneficiaries_male_0_17',
                'beneficiaries_male_18_59',
                'beneficiaries_male_60_plus',
            ]);
        });
    }
};
