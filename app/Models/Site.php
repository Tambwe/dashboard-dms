<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

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
        'source',
        'round',
        'type_gestion',
        'date_mise_a_jour',
        'type_fichier',
    ];

    protected $casts = [
        'longitude' => 'decimal:8',
        'latitude' => 'decimal:8',
        'photos' => 'array',
        'geojson_data' => 'array',
        'menages' => 'integer',
        'individus' => 'integer',
        'f_0_5' => 'integer',
        'f_6_17' => 'integer',
        'f_18_59' => 'integer',
        'f_60_plus' => 'integer',
        'h_0_5' => 'integer',
        'h_6_17' => 'integer',
        'h_18_59' => 'integer',
        'h_60_plus' => 'integer',
        'date_mise_a_jour' => 'date',
    ];

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
     * Calcule le total des femmes
     */
    public function getTotalFemmesAttribute()
    {
        return ($this->f_0_5 ?? 0) + ($this->f_6_17 ?? 0) + ($this->f_18_59 ?? 0) + ($this->f_60_plus ?? 0);
    }

    /**
     * Calcule le total des hommes
     */
    public function getTotalHommesAttribute()
    {
        return ($this->h_0_5 ?? 0) + ($this->h_6_17 ?? 0) + ($this->h_18_59 ?? 0) + ($this->h_60_plus ?? 0);
    }

    /**
     * Calcule le total des enfants (0-17 ans)
     */
    public function getTotalEnfantsAttribute()
    {
        return ($this->f_0_5 ?? 0) + ($this->f_6_17 ?? 0) + ($this->h_0_5 ?? 0) + ($this->h_6_17 ?? 0);
    }

    /**
     * Calcule le total des adultes (18-59 ans)
     */
    public function getTotalAdultesAttribute()
    {
        return ($this->f_18_59 ?? 0) + ($this->h_18_59 ?? 0);
    }

    /**
     * Calcule le total des personnes âgées (60+ ans)
     */
    public function getTotalPersonnesAgeesAttribute()
    {
        return ($this->f_60_plus ?? 0) + ($this->h_60_plus ?? 0);
    }
}
