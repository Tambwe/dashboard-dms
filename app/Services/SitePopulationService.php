<?php

namespace App\Services;

use App\Models\SiteMouvementPopulation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class SitePopulationService
{
    public const FIELDS = [
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

    public const FIELD_LABELS = [
        'menages' => 'ménages',
        'individus' => 'individus',
        'f_0_5' => 'femmes de 0 à 5 ans',
        'f_6_17' => 'femmes de 6 à 17 ans',
        'f_18_59' => 'femmes de 18 à 59 ans',
        'f_60_plus' => 'femmes de 60 ans et plus',
        'h_0_5' => 'hommes de 0 à 5 ans',
        'h_6_17' => 'hommes de 6 à 17 ans',
        'h_18_59' => 'hommes de 18 à 59 ans',
        'h_60_plus' => 'hommes de 60 ans et plus',
    ];

    public function forSite(int $siteId, ?CarbonInterface $periodEnd = null): array
    {
        $query = SiteMouvementPopulation::query()
            ->where('site_id', $siteId)
            ->where('statut', 'valide');

        if ($periodEnd) {
            $query->whereDate('date_mouvement', '<=', $periodEnd->toDateString());
        }

        return $this->reduceMovements($query
            ->orderBy('date_mouvement')
            ->orderBy('id')
            ->get());
    }

    public function snapshotForSite(int $siteId, ?CarbonInterface $periodEnd = null): ?SiteMouvementPopulation
    {
        $population = $this->forSite($siteId, $periodEnd);
        if (! $population['date_mouvement']) {
            return null;
        }

        return new SiteMouvementPopulation([
            ...array_intersect_key($population, array_flip(self::FIELDS)),
            'site_id' => $siteId,
            'date_mouvement' => $population['date_mouvement'],
            'type_mouvement' => 'recensement',
            'statut' => 'valide',
        ]);
    }

    public function forSites(iterable $siteIds, ?CarbonInterface $periodEnd = null): Collection
    {
        $ids = collect($siteIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $query = SiteMouvementPopulation::query()
            ->whereIn('site_id', $ids)
            ->where('statut', 'valide');

        if ($periodEnd) {
            $query->whereDate('date_mouvement', '<=', $periodEnd->toDateString());
        }

        return $query
            ->orderBy('date_mouvement')
            ->orderBy('id')
            ->get()
            ->groupBy('site_id')
            ->map(fn (Collection $movements) => $this->reduceMovements($movements));
    }

    public function reduceMovements(iterable $movements): array
    {
        $totals = array_fill_keys(self::FIELDS, 0);
        $lastMovementDate = null;

        foreach ($movements as $movement) {
            if ($movement->statut !== 'valide') {
                continue;
            }

            if ($movement->type_mouvement === 'recensement') {
                foreach (self::FIELDS as $field) {
                    $totals[$field] = abs((int) $movement->{$field});
                }
            } else {
                foreach (self::FIELDS as $field) {
                    $totals[$field] += (int) $movement->{$field};
                }
            }

            $lastMovementDate = $movement->date_mouvement;
        }

        foreach (self::FIELDS as $field) {
            $totals[$field] = max(0, $totals[$field]);
        }

        $totals['total_femmes'] = $totals['f_0_5'] + $totals['f_6_17'] + $totals['f_18_59'] + $totals['f_60_plus'];
        $totals['total_hommes'] = $totals['h_0_5'] + $totals['h_6_17'] + $totals['h_18_59'] + $totals['h_60_plus'];
        $totals['total_enfants'] = $totals['f_0_5'] + $totals['f_6_17'] + $totals['h_0_5'] + $totals['h_6_17'];
        $totals['total_adultes'] = $totals['f_18_59'] + $totals['h_18_59'];
        $totals['total_personnes_agees'] = $totals['f_60_plus'] + $totals['h_60_plus'];
        $totals['date_mouvement'] = $lastMovementDate;

        return $totals;
    }

    public function snapshots(iterable $movements): Collection
    {
        return collect($movements)
            ->groupBy('site_id')
            ->map(function (Collection $siteMovements) {
                $ordered = $siteMovements
                    ->sortBy(fn ($movement) => sprintf(
                        '%s-%020d',
                        $movement->date_mouvement?->format('Y-m-d') ?? (string) $movement->date_mouvement,
                        $movement->id
                    ))
                    ->values();
                $totals = $this->reduceMovements($ordered);
                $lastMovement = $ordered->last();
                $snapshot = new SiteMouvementPopulation([
                    ...array_intersect_key($totals, array_flip(self::FIELDS)),
                    'site_id' => $lastMovement->site_id,
                    'date_mouvement' => $totals['date_mouvement'],
                    'type_mouvement' => 'recensement',
                    'statut' => 'valide',
                ]);
                if ($lastMovement->relationLoaded('site')) {
                    $snapshot->setRelation('site', $lastMovement->site);
                }

                return $snapshot;
            })
            ->values();
    }

    public function negativeProjections(int $siteId, array $movementValues, string $movementType): array
    {
        if (! in_array($movementType, ['depart', 'ajustement'], true)) {
            return [];
        }

        $current = $this->forSite($siteId);
        $violations = [];

        foreach (self::FIELDS as $field) {
            $movementValue = (int) ($movementValues[$field] ?? 0);
            $normalizedValue = $movementType === 'depart' ? -abs($movementValue) : $movementValue;
            $projected = (int) $current[$field] + $normalizedValue;

            if ($projected < 0) {
                $violations[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field],
                    'current' => (int) $current[$field],
                    'movement' => $normalizedValue,
                    'projected' => $projected,
                ];
            }
        }

        return $violations;
    }

    public function applyToSites(EloquentCollection|Collection $sites, ?CarbonInterface $periodEnd = null): void
    {
        $populations = $this->forSites($sites->pluck('id'), $periodEnd);

        foreach ($sites as $site) {
            $population = $populations->get($site->id, array_fill_keys(self::FIELDS, 0));
            $site->setRelation('__population_snapshot', collect($population));
            foreach (self::FIELDS as $field) {
                $site->setAttribute($field, (int) ($population[$field] ?? 0));
            }
            $site->setAttribute('population_date', $population['date_mouvement'] ?? null);
        }
    }
}
