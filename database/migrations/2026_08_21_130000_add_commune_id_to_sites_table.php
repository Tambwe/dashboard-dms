<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sites', 'commune_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->foreignId('commune_id')->nullable()->constrained('communes')->nullOnDelete();
            });
        }

        $this->backfillSiteCommuneLinks();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sites', 'commune_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropConstrainedForeignId('commune_id');
            });
        }
    }

    private function backfillSiteCommuneLinks(): void
    {
        $sites = DB::table('sites')
            ->select('id', 'nom', 'province', 'territoire', 'zone_sante')
            ->get();

        foreach ($sites as $site) {
            $commune = $this->resolveCommune($site);

            if ($commune) {
                DB::table('sites')
                    ->where('id', $site->id)
                    ->update(['commune_id' => $commune->id]);
            }
        }
    }

    private function resolveCommune(object $site): ?object
    {
        $targets = array_values(array_filter([
            $site->zone_sante ?? null,
            $site->nom ?? null,
        ], function ($value) {
            return is_string($value) && trim($value) !== '';
        }));

        if (empty($targets)) {
            return null;
        }

        $siteProvince = $this->normalize($site->province ?? null);
        $siteTerritoire = $this->normalize($site->territoire ?? null);

        $communes = DB::table('communes')
            ->join('territoires', 'territoires.id', '=', 'communes.territoire_id')
            ->join('provinces', 'provinces.id', '=', 'communes.province_id')
            ->select(
                'communes.id',
                'communes.name',
                'communes.pcode',
                'provinces.name as province_name',
                'territoires.name as territoire_name'
            )
            ->get();

        foreach ($targets as $target) {
            $targetNormalized = $this->normalize($target);

            if ($targetNormalized === '') {
                continue;
            }

            foreach ($communes as $commune) {
                if ($this->normalize($commune->name) === $targetNormalized || $this->normalize($commune->pcode) === $targetNormalized) {
                    return $commune;
                }

                if ($siteProvince !== '' && $siteTerritoire !== '') {
                    if ($this->normalize($commune->province_name) === $siteProvince
                        && $this->normalize($commune->territoire_name) === $siteTerritoire
                        && $this->normalize($commune->name) === $targetNormalized) {
                        return $commune;
                    }
                }
            }
        }

        if ($siteProvince !== '' && $siteTerritoire !== '') {
            foreach ($communes as $commune) {
                if ($this->normalize($commune->province_name) === $siteProvince
                    && $this->normalize($commune->territoire_name) === $siteTerritoire) {
                    return $commune;
                }
            }
        }

        return null;
    }

    private function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->value());
    }
};
