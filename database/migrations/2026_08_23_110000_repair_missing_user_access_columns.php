<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'organisation_id')) {
                $table->foreignId('organisation_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organisations')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'admin_organisation', 'user', 'sig_user'])
                    ->default('user')
                    ->after('organisation_id');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('phone');
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('email', 'superadmin@dms-cccm.org')
                ->update([
                    'role' => 'super_admin',
                    'organisation_id' => null,
                    'is_active' => true,
                ]);
        }
    }

    public function down(): void
    {
        // Migration de réparation : pas de rollback destructif automatique.
    }
};
