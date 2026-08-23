<?php

namespace App\Models;

use App\Services\SitePopulationService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $hidden = [
        '__population_snapshot',
        'mouvementsPopulationValides',
    ];

    protected $fillable = [
        'nom',
        'code_site',
        'organisation_id',
        'type_site_id',
        'commune_id',
        'gestionnaire_id',
        'coordinateur_id',
        'categorie_site_id',
        'province',
        'code_province',
        'territoire',
        'code_territoire',
        'zone_sante',
        'code_zone_sante',
        'aire_sante',
        'code_aire_sante',
        'longitude',
        'latitude',
        'photos',
        'geojson_data',
        'geometry_type',
        'collection_accuracy_m',
        'geometry_collected_at',
        'source',
        'round',
        'type_gestion',
        'date_fermeture',
        'raison_fermeture',
        'commentaire_fermeture',
        'document_fermeture',
        'date_mise_a_jour',
        'type_fichier',
    ];

    protected $casts = [
        'longitude' => 'decimal:8',
        'latitude' => 'decimal:8',
        'photos' => 'array',
        'geojson_data' => 'array',
        'collection_accuracy_m' => 'decimal:2',
        'geometry_collected_at' => 'datetime',
        'date_fermeture' => 'date',
        'date_mise_a_jour' => 'date',
    ];

    /**
     * Scope : sites actuellement ouverts.
     */
    public function scopeOuverts($query)
    {
        return $query->whereNull('date_fermeture');
    }

    /**
     * Scope : sites actifs à une date donnée.
     */
    public function scopeActifsALaDate($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('date_fermeture')
                ->orWhere('date_fermeture', '>', $date);
        });
    }

    /**
     * Relation : Un site peut être géré par une organisation
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    /**
     * Get the users that have access to this site.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'site_user_access')
            ->withPivot(['can_edit', 'can_collect', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    /**
     * Relation : Un site appartient à un type de site
     */
    public function typeSite()
    {
        return $this->belongsTo(TypeSite::class, 'type_site_id');
    }

    /**
     * Relation : Un site appartient à une commune (zone de santé)
     */
    public function commune()
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }

    /**
     * Relation : Un site appartient à un gestionnaire
     */
    public function gestionnaire()
    {
        return $this->belongsTo(Gestionnaire::class, 'gestionnaire_id');
    }

    /**
     * Relation : Un site appartient à un coordinateur
     */
    public function coordinateur()
    {
        return $this->belongsTo(Coordinateur::class, 'coordinateur_id');
    }

    /**
     * Relation : Un site appartient à une catégorie
     */
    public function categorieSite()
    {
        return $this->belongsTo(CategorieSite::class, 'categorie_site_id');
    }

    /**
     * Relation : Un site a une province via sa commune
     */
    public function province()
    {
        return $this->hasOneThrough(
            Province::class,
            Commune::class,
            'id',           // Clé étrangère sur communes
            'id',           // Clé étrangère sur provinces
            'commune_id',   // Clé locale sur sites
            'province_id'   // Clé locale sur communes
        );
    }

    /**
     * Relation : Un site a un territoire via sa commune
     */
    public function territoire()
    {
        return $this->hasOneThrough(
            Territoire::class,
            Commune::class,
            'id',            // Clé étrangère sur communes
            'id',            // Clé étrangère sur territoires
            'commune_id',    // Clé locale sur sites
            'territoire_id'  // Clé locale sur communes
        );
    }

    /**
     * Relation : Un site a plusieurs mouvements de population
     */
    public function mouvementsPopulation()
    {
        return $this->hasMany(SiteMouvementPopulation::class, 'site_id');
    }

    public function mouvementsPopulationValides()
    {
        return $this->mouvementsPopulation()
            ->where('statut', 'valide')
            ->orderBy('date_mouvement')
            ->orderBy('id');
    }

    /**
     * Relation : Un site a plusieurs profils de services
     */
    public function serviceProfiles()
    {
        return $this->hasMany(ServiceProfile::class, 'site_id');
    }

    /**
     * Relation : Un site a plusieurs ménages
     */
    public function households()
    {
        return $this->hasMany(Household::class, 'site_id');
    }

    /**
     * Relation : Historique des géographies collectées pour ce site.
     */
    public function geographies()
    {
        return $this->hasMany(SiteGeography::class, 'site_id')->orderByDesc('collected_at')->orderByDesc('id');
    }

    /**
     * Calcule le total des femmes
     */
    public function getTotalFemmesAttribute()
    {
        return $this->populationValue('total_femmes');
    }

    /**
     * Calcule le total des hommes
     */
    public function getTotalHommesAttribute()
    {
        return $this->populationValue('total_hommes');
    }

    /**
     * Calcule le total des enfants (0-17 ans)
     */
    public function getTotalEnfantsAttribute()
    {
        return $this->populationValue('total_enfants');
    }

    /**
     * Calcule le total des adultes (18-59 ans)
     */
    public function getTotalAdultesAttribute()
    {
        return $this->populationValue('total_adultes');
    }

    /**
     * Calcule le total des personnes âgées (60+ ans)
     */
    public function getTotalPersonnesAgeesAttribute()
    {
        return $this->populationValue('total_personnes_agees');
    }

    public function getMenagesAttribute(): int
    {
        return $this->populationValue('menages');
    }

    public function getIndividusAttribute(): int
    {
        return $this->populationValue('individus');
    }

    public function getF05Attribute(): int
    {
        return $this->populationValue('f_0_5');
    }

    public function getF617Attribute(): int
    {
        return $this->populationValue('f_6_17');
    }

    public function getF1859Attribute(): int
    {
        return $this->populationValue('f_18_59');
    }

    public function getF60PlusAttribute(): int
    {
        return $this->populationValue('f_60_plus');
    }

    public function getH05Attribute(): int
    {
        return $this->populationValue('h_0_5');
    }

    public function getH617Attribute(): int
    {
        return $this->populationValue('h_6_17');
    }

    public function getH1859Attribute(): int
    {
        return $this->populationValue('h_18_59');
    }

    public function getH60PlusAttribute(): int
    {
        return $this->populationValue('h_60_plus');
    }

    private function populationValue(string $field): int
    {
        if (! array_key_exists('__population_snapshot', $this->relations)) {
            $movements = $this->relationLoaded('mouvementsPopulationValides')
                ? $this->getRelation('mouvementsPopulationValides')
                : $this->mouvementsPopulationValides()->get();
            $this->setRelation(
                '__population_snapshot',
                collect(app(SitePopulationService::class)->reduceMovements($movements))
            );
        }

        return (int) $this->getRelation('__population_snapshot')->get($field, 0);
    }
}
