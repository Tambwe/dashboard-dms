<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = [
        'menages',
        'individus',
        'f_0_5',
        'f_6_17',
        'f_18_59',
        'f_60_plus',
        'h_0_5',
        'h_6_17',
        'h_18_59',
        'h_60_plus',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sites') || ! Schema::hasTable('site_mouvements_population')) {
            return;
        }

        $availableFields = array_values(array_filter(
            self::FIELDS,
            fn (string $field) => Schema::hasColumn('sites', $field)
        ));

        if ($availableFields === []) {
            return;
        }

        if (! Schema::hasTable('site_population_legacy_archives')) {
            Schema::create('site_population_legacy_archives', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_id')->unique();
                foreach (self::FIELDS as $field) {
                    $table->integer($field)->nullable();
                }
                $table->date('date_mise_a_jour')->nullable();
                $table->string('source')->nullable();
                $table->string('round')->nullable();
                $table->timestamp('archived_at');
            });
        }

        $selectColumns = array_merge(['id'], $availableFields);
        foreach (['date_mise_a_jour', 'source', 'round'] as $column) {
            if (Schema::hasColumn('sites', $column)) {
                $selectColumns[] = $column;
            }
        }

        DB::table('sites')
            ->select($selectColumns)
            ->orderBy('id')
            ->chunkById(200, function ($sites) use ($availableFields): void {
                foreach ($sites as $site) {
                    $archive = [
                        'site_id' => $site->id,
                        'date_mise_a_jour' => $site->date_mise_a_jour ?? null,
                        'source' => $site->source ?? null,
                        'round' => $site->round ?? null,
                        'archived_at' => now(),
                    ];
                    foreach (self::FIELDS as $field) {
                        $archive[$field] = in_array($field, $availableFields, true)
                            ? $site->{$field}
                            : null;
                    }
                    DB::table('site_population_legacy_archives')->insertOrIgnore($archive);

                    $hasPopulation = collect($availableFields)
                        ->contains(fn (string $field) => (int) ($site->{$field} ?? 0) !== 0);
                    $hasValidatedLedger = DB::table('site_mouvements_population')
                        ->where('site_id', $site->id)
                        ->where('statut', 'valide')
                        ->exists();

                    if (! $hasPopulation || $hasValidatedLedger) {
                        continue;
                    }

                    $movement = [
                        'site_id' => $site->id,
                        'date_mouvement' => $site->date_mise_a_jour ?? now()->toDateString(),
                        'type_mouvement' => 'recensement',
                        'periode' => date('Y-m', strtotime($site->date_mise_a_jour ?? now()->toDateString())),
                        'raison' => 'Migration des anciennes colonnes de population du site',
                        'description' => 'Instantané préservé avant suppression des colonnes redondantes de la table sites.',
                        'source' => $site->source ?? 'migration_sites_population_ledger',
                        'round' => $site->round ?? null,
                        'statut' => 'valide',
                        'validated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    foreach (self::FIELDS as $field) {
                        $movement[$field] = abs((int) ($site->{$field} ?? 0));
                    }
                    DB::table('site_mouvements_population')->insert($movement);
                }
            }, 'id');

        Schema::table('sites', function (Blueprint $table) use ($availableFields) {
            $table->dropColumn($availableFields);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        $missingFields = array_values(array_filter(
            self::FIELDS,
            fn (string $field) => ! Schema::hasColumn('sites', $field)
        ));

        if ($missingFields !== []) {
            Schema::table('sites', function (Blueprint $table) use ($missingFields) {
                foreach ($missingFields as $field) {
                    $table->integer($field)->nullable();
                }
            });
        }

        if (! Schema::hasTable('site_population_legacy_archives')) {
            return;
        }

        DB::table('site_population_legacy_archives')->orderBy('id')->chunkById(200, function ($archives): void {
            foreach ($archives as $archive) {
                $values = [];
                foreach (self::FIELDS as $field) {
                    $values[$field] = $archive->{$field};
                }
                DB::table('sites')->where('id', $archive->site_id)->update($values);
            }
        });
    }
};
