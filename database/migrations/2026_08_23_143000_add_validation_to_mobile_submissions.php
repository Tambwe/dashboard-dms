<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mobile_collection_submissions', 'mobile_questionnaire_submissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'validation_status')) {
                    $table->string('validation_status', 20)->default('pending')->index();
                }
                if (! Schema::hasColumn($tableName, 'validated_by')) {
                    $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn($tableName, 'validated_at')) {
                    $table->timestamp('validated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['mobile_collection_submissions', 'mobile_questionnaire_submissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'validated_by')) {
                    $table->dropConstrainedForeignId('validated_by');
                }
                foreach (['validation_status', 'validated_at'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
