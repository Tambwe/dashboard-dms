<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteMouvementPopulation extends Model
{
    use HasFactory;

    protected $table = 'site_mouvements_population';

    protected $fillable = [
        'site_id',
        'date_mouvement',
        'type_mouvement',
        'raison_mouvement_id',
        'periode',
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
        'raison',
        'description',
        'source',
        'round',
        'created_by',
        'statut',
        'validated_at',
        'validated_by',
        'rejection_reason',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
        'validated_at' => 'datetime',
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
    ];

    /**
     * Relation avec le site
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Relation avec l'utilisateur ayant créé le mouvement
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec la raison du mouvement
     */
    public function raisonMouvement()
    {
        return $this->belongsTo(RaisonMouvement::class, 'raison_mouvement_id');
    }

    /**
     * Relation avec l'utilisateur validant le mouvement
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Vérifie si le mouvement est en attente
     */
    public function isPending()
    {
        return $this->statut === 'en_attente';
    }

    /**
     * Vérifie si le mouvement est validé
     */
    public function isValidated()
    {
        return $this->statut === 'valide';
    }

    /**
     * Vérifie si le mouvement est rejeté
     */
    public function isRejected()
    {
        return $this->statut === 'rejete';
    }

    /**
     * Calcule le total des femmes
     */
    public function getTotalFemmesAttribute()
    {
        return $this->f_0_5 + $this->f_6_17 + $this->f_18_59 + $this->f_60_plus;
    }

    /**
     * Calcule le total des hommes
     */
    public function getTotalHommesAttribute()
    {
        return $this->h_0_5 + $this->h_6_17 + $this->h_18_59 + $this->h_60_plus;
    }

    /**
     * Vérifie si les totaux sont cohérents
     */
    public function getTotauxCoherentsAttribute()
    {
        return $this->individus === ($this->total_femmes + $this->total_hommes);
    }

    /**
     * Scope pour filtrer par type de mouvement
     */
    public function scopeDeType($query, $type)
    {
        return $query->where('type_mouvement', $type);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopePourPeriode($query, $dateDebut, $dateFin = null)
    {
        if ($dateFin) {
            return $query->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
        }
        return $query->where('date_mouvement', '>=', $dateDebut);
    }

    /**
     * Scope pour les mouvements en attente de validation
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les mouvements validés
     */
    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    /**
     * Scope pour les mouvements rejetés
     */
    public function scopeRejetes($query)
    {
        return $query->where('statut', 'rejete');
    }

    /**
     * Scope pour les arrivées uniquement
     */
    public function scopeArrivees($query)
    {
        return $query->where('type_mouvement', 'arrivee');
    }

    /**
     * Scope pour les départs uniquement
     */
    public function scopeDeparts($query)
    {
        return $query->where('type_mouvement', 'depart');
    }

    /**
     * Scope pour les recensements uniquement
     */
    public function scopeRecensements($query)
    {
        return $query->where('type_mouvement', 'recensement');
    }
}
