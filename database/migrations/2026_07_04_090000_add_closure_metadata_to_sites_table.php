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
            $table->string('raison_fermeture')->nullable()->after('date_fermeture');
            $table->text('commentaire_fermeture')->nullable()->after('raison_fermeture');
            $table->string('document_fermeture')->nullable()->after('commentaire_fermeture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'raison_fermeture',
                'commentaire_fermeture',
                'document_fermeture',
            ]);
        });
    }
};