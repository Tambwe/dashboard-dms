<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class OssatChoix extends Model
{
    protected $table = 'ossat_choices';

    protected $fillable = [
        'groupe',
        'valeur',
        'libelle',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeGroupe($query, string $groupe)
    {
        return $query->where('groupe', $groupe);
    }

    // ── Accesseur : libellé affiché ─────────────────────────────────────────

    public function getDisplayLabelAttribute(): string
    {
        return $this->libelle ?? $this->valeur;
    }

    // ── Méthodes statiques utilitaires ──────────────────────────────────────

    /**
     * Retourne tous les choix actifs groupés par groupe.
     * Résultat mis en cache 10 minutes (invalidé lors des modifications admin).
     *
     * @return array<string, array<string>>
     */
    public static function allGrouped(): array
    {
        return Cache::remember('ossat_choices_grouped', 600, function () {
            return self::actif()
                ->orderBy('groupe')
                ->orderBy('ordre')
                ->orderBy('valeur')
                ->get()
                ->groupBy('groupe')
                ->map(fn($items) => $items->pluck('valeur')->all())
                ->all();
        });
    }

    /**
     * Retourne les valeurs d'un seul groupe (actives, ordonnées).
     *
     * @return array<string>
     */
    public static function forGroupe(string $groupe): array
    {
        return Cache::remember("ossat_choices_{$groupe}", 600, function () use ($groupe) {
            return self::actif()
                ->groupe($groupe)
                ->orderBy('ordre')
                ->orderBy('valeur')
                ->pluck('valeur')
                ->all();
        });
    }

    /**
     * Invalide le cache des choix OSSAT.
     */
    public static function clearCache(): void
    {
        Cache::forget('ossat_choices_grouped');
        // les caches par groupe sont oubliés via le tag en production
        // en développement on vide tout simplement le groupe concerné
        foreach (self::distinct()->pluck('groupe') as $g) {
            Cache::forget("ossat_choices_{$g}");
        }
    }
}
