<?php

namespace Database\Seeders;

use App\Models\MobileQuestionnaire;
use App\Support\XlsFormParser;
use Illuminate\Database\Seeder;

class MobileQuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paths = [
            'H:\\aa7wFcDVAPxjU5JvAJ6mAr (7).xlsx',
            base_path('storage/app/service-cartography.xlsx'),
        ];

        $sourcePath = null;
        foreach ($paths as $path) {
            if (is_file($path)) {
                $sourcePath = $path;
                break;
            }
        }

        if (! $sourcePath) {
            $this->command?->warn('MobileQuestionnaireSeeder: XLSForm introuvable, import ignoré.');
            return;
        }

        $parser = new XlsFormParser();
        $parsed = $parser->parse($sourcePath);

        $currentVersion = (int) MobileQuestionnaire::query()
            ->where('code', 'service-cartography')
            ->max('version');

        $existing = MobileQuestionnaire::query()
            ->where('code', 'service-cartography')
            ->where('source_file', $sourcePath)
            ->where('version', $currentVersion > 0 ? $currentVersion : 1)
            ->first();

        MobileQuestionnaire::query()
            ->where('code', 'service-cartography')
            ->update(['is_active' => false]);

        if ($existing) {
            $existing->update([
                'title' => 'Cartographie des services (XLSForm)',
                'description' => 'Questionnaire mobile configurable importé depuis XLSForm.',
                'is_active' => true,
                'survey' => $parsed['survey'],
                'choices' => $parsed['choices'],
                'settings' => $parsed['settings'],
                'published_at' => now(),
            ]);

            return;
        }

        MobileQuestionnaire::query()->create([
            'code' => 'service-cartography',
            'title' => 'Cartographie des services (XLSForm)',
            'description' => 'Questionnaire mobile configurable importé depuis XLSForm.',
            'version' => max(1, $currentVersion + 1),
            'is_active' => true,
            'survey' => $parsed['survey'],
            'choices' => $parsed['choices'],
            'settings' => $parsed['settings'],
            'source_file' => $sourcePath,
            'published_at' => now(),
        ]);
    }
}
