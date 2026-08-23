<?php

namespace Tests\Unit;

use App\Models\SiteMouvementPopulation;
use App\Services\SitePopulationService;
use Tests\TestCase;

class SitePopulationServiceTest extends TestCase
{
    public function test_recensement_replaces_previous_state_and_later_movements_are_applied(): void
    {
        $service = new SitePopulationService();
        $population = $service->reduceMovements([
            $this->movement('recensement', 10, 40, 5),
            $this->movement('arrivee', 3, 12, 2),
            $this->movement('depart', -2, -8, -1),
            $this->movement('recensement', 20, 80, 10),
            $this->movement('ajustement', -1, -4, -1),
        ]);

        $this->assertSame(19, $population['menages']);
        $this->assertSame(76, $population['individus']);
        $this->assertSame(9, $population['f_0_5']);
    }

    public function test_only_validated_movements_contribute_to_population(): void
    {
        $service = new SitePopulationService();
        $valid = $this->movement('recensement', 10, 40, 5);
        $rejected = $this->movement('arrivee', 100, 400, 50);
        $rejected->statut = 'rejete';

        $population = $service->reduceMovements([$valid, $rejected]);

        $this->assertSame(10, $population['menages']);
        $this->assertSame(40, $population['individus']);
        $this->assertSame(5, $population['f_0_5']);
    }

    public function test_departure_is_rejected_when_it_would_make_a_population_field_negative(): void
    {
        $service = new class extends SitePopulationService {
            public function forSite(int $siteId, ?\Carbon\CarbonInterface $periodEnd = null): array
            {
                return [
                    ...array_fill_keys(self::FIELDS, 10),
                    'date_mouvement' => null,
                ];
            }
        };

        $violations = $service->negativeProjections(1, [
            ...array_fill_keys(SitePopulationService::FIELDS, -5),
            'h_60_plus' => -11,
        ], 'depart');

        $this->assertCount(1, $violations);
        $this->assertSame('h_60_plus', $violations[0]['field']);
        $this->assertSame(-1, $violations[0]['projected']);
    }

    private function movement(string $type, int $menages, int $individus, int $demographicValue): SiteMouvementPopulation
    {
        return new SiteMouvementPopulation([
            'site_id' => 1,
            'date_mouvement' => '2026-08-01',
            'type_mouvement' => $type,
            'statut' => 'valide',
            'menages' => $menages,
            'individus' => $individus,
            'f_0_5' => $demographicValue,
            'f_6_17' => $demographicValue,
            'f_18_59' => $demographicValue,
            'f_60_plus' => $demographicValue,
            'h_0_5' => $demographicValue,
            'h_6_17' => $demographicValue,
            'h_18_59' => $demographicValue,
            'h_60_plus' => $demographicValue,
        ]);
    }
}
