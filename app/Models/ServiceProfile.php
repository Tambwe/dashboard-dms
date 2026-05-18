<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'date_collecte',
        'collecteur_id',
        
        // Santé
        'sante_disponible',
        'sante_structures_fonctionnelles',
        'sante_personnel_medical',
        'sante_services_offerts',
        'sante_consultations_mois',
        'sante_observations',
        
        // Éducation
        'education_disponible',
        'education_ecoles_fonctionnelles',
        'education_enseignants',
        'education_eleves_inscrits',
        'education_salles_classe',
        'education_niveaux_offerts',
        'education_observations',
        
        // WASH
        'wash_disponible',
        'wash_points_eau',
        'wash_litres_par_personne',
        'wash_latrines',
        'wash_douches',
        'wash_gestion_dechets',
        'wash_observations',
        
        // Environnement
        'environnement_disponible',
        'environnement_gestion_dechets',
        'environnement_drainage',
        'environnement_espaces_verts',
        'environnement_risques',
        'environnement_observations',
        
        // Abri et AME
        'abri_ame_disponible',
        'abri_logements_fonctionnels',
        'abri_types',
        'abri_menages_ame',
        'abri_ame_distribues',
        'abri_observations',
        
        // Gestion et coordination
        'gestion_disponible',
        'gestion_comite_site',
        'gestion_membres_comite',
        'gestion_mecanisme_plainte',
        'gestion_reunions_mois',
        'gestion_partenaires',
        'gestion_observations',
        
        // Métadonnées
        'statut',
        'notes_generales',
    ];

    protected $casts = [
        'date_collecte' => 'date',
        'sante_disponible' => 'boolean',
        'education_disponible' => 'boolean',
        'wash_disponible' => 'boolean',
        'wash_gestion_dechets' => 'boolean',
        'environnement_disponible' => 'boolean',
        'environnement_gestion_dechets' => 'boolean',
        'environnement_drainage' => 'boolean',
        'environnement_espaces_verts' => 'boolean',
        'abri_ame_disponible' => 'boolean',
        'gestion_disponible' => 'boolean',
        'gestion_comite_site' => 'boolean',
        'gestion_mecanisme_plainte' => 'boolean',
        'sante_services_offerts' => 'array',
        'education_niveaux_offerts' => 'array',
        'environnement_risques' => 'array',
        'abri_types' => 'array',
        'abri_ame_distribues' => 'array',
        'gestion_partenaires' => 'array',
    ];

    /**
     * Relation avec le site
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Relation avec le collecteur (utilisateur)
     */
    public function collecteur()
    {
        return $this->belongsTo(User::class, 'collecteur_id');
    }

    /**
     * Scope pour filtrer par site
     */
    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('statut', $status);
    }

    /**
     * Scope pour les collectes récentes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date_collecte', '>=', now()->subDays($days));
    }

    /**
     * Vérifie si au moins un service est disponible
     */
    public function hasAnyService()
    {
        return $this->sante_disponible 
            || $this->education_disponible 
            || $this->wash_disponible 
            || $this->environnement_disponible 
            || $this->abri_ame_disponible 
            || $this->gestion_disponible;
    }

    /**
     * Retourne le nombre de secteurs avec services disponibles
     */
    public function getAvailableServicesCount()
    {
        $count = 0;
        if ($this->sante_disponible) $count++;
        if ($this->education_disponible) $count++;
        if ($this->wash_disponible) $count++;
        if ($this->environnement_disponible) $count++;
        if ($this->abri_ame_disponible) $count++;
        if ($this->gestion_disponible) $count++;
        return $count;
    }

    /**
     * Retourne les secteurs avec services disponibles
     */
    public function getAvailableServices()
    {
        $services = [];
        if ($this->sante_disponible) $services[] = 'Santé';
        if ($this->education_disponible) $services[] = 'Éducation';
        if ($this->wash_disponible) $services[] = 'WASH';
        if ($this->environnement_disponible) $services[] = 'Environnement';
        if ($this->abri_ame_disponible) $services[] = 'Abri et AME';
        if ($this->gestion_disponible) $services[] = 'Gestion et Coordination';
        return $services;
    }

    /**
     * Badge de statut avec couleur
     */
    public function getStatusBadgeClass()
    {
        return match($this->statut) {
            'brouillon' => 'bg-gray-200 text-gray-800',
            'soumis' => 'bg-blue-200 text-blue-800',
            'valide' => 'bg-green-200 text-green-800',
            'rejete' => 'bg-red-200 text-red-800',
            default => 'bg-gray-200 text-gray-800',
        };
    }

    /**
     * Label de statut formaté
     */
    public function getStatusLabel()
    {
        return match($this->statut) {
            'brouillon' => 'Brouillon',
            'soumis' => 'Soumis',
            'valide' => 'Validé',
            'rejete' => 'Rejeté',
            default => ucfirst($this->statut),
        };
    }
}
