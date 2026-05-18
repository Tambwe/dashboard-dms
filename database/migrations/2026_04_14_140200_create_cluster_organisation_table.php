<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_organisation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_id');
            $table->unsignedBigInteger('organisation_id');
            $table->timestamps();

            $table->unique(['cluster_id', 'organisation_id'], 'cluster_organisation_unique');
            $table->foreign('cluster_id', 'co_cluster_fk')
                ->references('id')
                ->on('clusters')
                ->cascadeOnDelete();
            $table->foreign('organisation_id', 'co_org_fk')
                ->references('id')
                ->on('organisations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_organisation');
    }
};
