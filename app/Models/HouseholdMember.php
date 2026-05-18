<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'nom',
        'postnom',
        'prenom',
        'sexe',
        'date_naissance',
        'age',
        'lieu_naissance',
        'nationalite',
        'lien_avec_chef',
        'etat_civil',
        'type_document',
        'numero_document',
        'photo',
        'empreinte',
        'niveau_education',
        'scolarise_actuellement',
        'profession',
        'handicap',
        'type_handicap',
        'maladie_chronique',
        'type_maladie',
        'femme_enceinte',
        'femme_allaitante',
        'enfant_orphelin',
        'enfant_separe',
        'personne_agee',
        'telephone',
        'email',
        'observations',
        'statut',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'scolarise_actuellement' => 'boolean',
        'handicap' => 'boolean',
        'maladie_chronique' => 'boolean',
        'femme_enceinte' => 'boolean',
        'femme_allaitante' => 'boolean',
        'enfant_orphelin' => 'boolean',
        'enfant_separe' => 'boolean',
        'personne_agee' => 'boolean',
    ];

    /**
     * Relations
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeHommes($query)
    {
        return $query->where('sexe', 'M')->where('age', '>=', 18);
    }

    public function scopeFemmes($query)
    {
        return $query->where('sexe', 'F')->where('age', '>=', 18);
    }

    public function scopeEnfants($query)
    {
        return $query->where('age', '<', 18);
    }

    public function scopeGarcons($query)
    {
        return $query->where('sexe', 'M')->where('age', '<', 18);
    }

    public function scopeFilles($query)
    {
        return $query->where('sexe', 'F')->where('age', '<', 18);
    }

    /**
     * Méthodes utilitaires
     */
    
    /**
     * Obtient le nom complet
     */
    public function getNomCompletAttribute(): string
    {
        return trim(implode(' ', [
            $this->nom,
            $this->postnom,
            $this->prenom
        ]));
    }

    /**
     * Calcule l'âge à partir de la date de naissance
     */
    public function calculateAge(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }
        
        return $this->date_naissance->age;
    }

    /**
     * Vérifie si c'est un enfant (moins de 18 ans)
     */
    public function isEnfant(): bool
    {
        return $this->age < 18;
    }

    /**
     * Vérifie si c'est un adulte (18 ans et plus)
     */
    public function isAdulte(): bool
    {
        return $this->age >= 18;
    }

    /**
     * Vérifie si la personne a des vulnérabilités
     */
    public function hasVulnerabilites(): bool
    {
        return $this->handicap
            || $this->maladie_chronique
            || $this->femme_enceinte
            || $this->femme_allaitante
            || $this->enfant_orphelin
            || $this->enfant_separe
            || $this->personne_agee;
    }

    /**
     * Obtient la liste des vulnérabilités
     */
    public function getVulnerabilites(): array
    {
        $vulnerabilites = [];
        
        if ($this->handicap) {
            $vulnerabilites[] = 'Handicap' . ($this->type_handicap ? ': ' . $this->type_handicap : '');
        }
        if ($this->maladie_chronique) {
            $vulnerabilites[] = 'Maladie chronique' . ($this->type_maladie ? ': ' . $this->type_maladie : '');
        }
        if ($this->femme_enceinte) {
            $vulnerabilites[] = 'Femme enceinte';
        }
        if ($this->femme_allaitante) {
            $vulnerabilites[] = 'Femme allaitante';
        }
        if ($this->enfant_orphelin) {
            $vulnerabilites[] = 'Enfant orphelin';
        }
        if ($this->enfant_separe) {
            $vulnerabilites[] = 'Enfant séparé';
        }
        if ($this->personne_agee) {
            $vulnerabilites[] = 'Personne âgée';
        }
        
        return $vulnerabilites;
    }

    /**
     * Obtient la badge CSS pour le sexe
     */
    public function getSexeBadgeClass(): string
    {
        return $this->sexe === 'M' 
            ? 'bg-blue-100 text-blue-800' 
            : 'bg-pink-100 text-pink-800';
    }

    /**
     * Obtient la badge CSS pour le statut
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->statut) {
            'actif' => 'bg-green-100 text-green-800',
            'décédé' => 'bg-gray-100 text-gray-800',
            'parti' => 'bg-yellow-100 text-yellow-800',
            'transféré' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($member) {
            // Mettre à jour automatiquement le nombre total de personnes du ménage
            $member->household->updateNombreTotalPersonnes();
        });

        static::deleted(function ($member) {
            // Mettre à jour automatiquement le nombre total de personnes du ménage
            $member->household->updateNombreTotalPersonnes();
        });
    }
}
