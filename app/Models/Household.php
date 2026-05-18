<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'niveau_enregistrement',
        'numero_menage',
        
        // Chef de ménage
        'chef_nom',
        'chef_postnom',
        'chef_prenom',
        'chef_sexe',
        'chef_date_naissance',
        'chef_age',
        'chef_lieu_naissance',
        'chef_nationalite',
        'chef_etat_civil',
        'chef_telephone',
        'chef_email',
        'chef_type_document',
        'chef_numero_document',
        'chef_photo',
        'chef_empreinte',
        'chef_empreinte_2',
        'chef_empreinte_3',
        'chef_empreinte_hash_1',
        'chef_empreinte_hash_2',
        'chef_empreinte_hash_3',
        
        // Origine
        'province_origine_id',
        'territoire_origine_id',
        'commune_origine',
        'village_origine',
        'raison_deplacement',
        'date_arrivee_site',
        
        // Composition (Niveau 1)
        'nombre_hommes',
        'nombre_femmes',
        'nombre_garcons',
        'nombre_filles',
        'nombre_total_personnes',
        
        // Vulnérabilités
        'nombre_femmes_enceintes',
        'nombre_femmes_allaitantes',
        'nombre_personnes_handicapees',
        'nombre_personnes_agees',
        'nombre_enfants_orphelins',
        'nombre_enfants_separes',
        'nombre_malades_chroniques',
        
        // Conditions de vie
        'type_abri',
        'acces_eau_potable',
        'acces_latrines',
        'acces_electricite',
        
        // Assistance
        'recu_kits_nfi',
        'recu_assistance_alimentaire',
        'recu_soins_sante',
        
        // Suivi
        'statut',
        'observations',
        'enregistre_par',
        'date_enregistrement',
        'verifie_par',
        'date_verification',
    ];

    protected $casts = [
        'chef_date_naissance' => 'date',
        'date_arrivee_site' => 'date',
        'date_enregistrement' => 'datetime',
        'date_verification' => 'datetime',
        'acces_eau_potable' => 'boolean',
        'acces_latrines' => 'boolean',
        'acces_electricite' => 'boolean',
        'recu_kits_nfi' => 'boolean',
        'recu_assistance_alimentaire' => 'boolean',
        'recu_soins_sante' => 'boolean',
    ];

    /**
     * Relations
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function provinceOrigine(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_origine_id');
    }

    public function territoireOrigine(): BelongsTo
    {
        return $this->belongsTo(Territoire::class, 'territoire_origine_id');
    }

    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }

    public function verifiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifie_par');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    /**
     * Scopes
     */
    public function scopeNiveau1($query)
    {
        return $query->where('niveau_enregistrement', '1');
    }

    public function scopeNiveau2($query)
    {
        return $query->where('niveau_enregistrement', '2');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Méthodes utilitaires
     */
    
    /**
     * Génère un numéro unique de ménage
     */
    public static function generateNumeroMenage($siteId): string
    {
        $site = Site::find($siteId);
        $code = $site ? strtoupper($site->code ?? 'SITE') : 'SITE';

        // Numéro séquentiel = total des ménages du site + 1
        $seq = self::where('site_id', $siteId)->count() + 1;

        // Suffixe aléatoire 4 chiffres pour éliminer les collisions concurrentes
        $rand = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%d-%s', $code, $seq, $rand);
    }

    /**
     * Obtient le nom complet du chef de ménage
     */
    public function getChefNomCompletAttribute(): string
    {
        return trim(implode(' ', [
            $this->chef_nom,
            $this->chef_postnom,
            $this->chef_prenom
        ]));
    }

    /**
     * Calcule l'âge du chef à partir de sa date de naissance
     */
    public function calculateChefAge(): ?int
    {
        if (!$this->chef_date_naissance) {
            return null;
        }
        
        return $this->chef_date_naissance->age;
    }

    /**
     * Vérifie si le ménage a été enregistré au niveau 2
     */
    public function isNiveau2(): bool
    {
        return $this->niveau_enregistrement === '2';
    }

    /**
     * Obtient le nombre total de membres enregistrés (niveau 2)
     */
    public function getMembresEnregistresCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Vérifie si le ménage a des vulnérabilités
     */
    public function hasVulnerabilites(): bool
    {
        return $this->nombre_femmes_enceintes > 0
            || $this->nombre_femmes_allaitantes > 0
            || $this->nombre_personnes_handicapees > 0
            || $this->nombre_personnes_agees > 0
            || $this->nombre_enfants_orphelins > 0
            || $this->nombre_enfants_separes > 0
            || $this->nombre_malades_chroniques > 0;
    }

    /**
     * Obtient la badge CSS pour le statut
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->statut) {
            'actif' => 'bg-green-100 text-green-800',
            'déplacé' => 'bg-blue-100 text-blue-800',
            'retourné' => 'bg-purple-100 text-purple-800',
            'réinstallé' => 'bg-indigo-100 text-indigo-800',
            'décédé' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Obtient la badge CSS pour le niveau
     */
    public function getNiveauBadgeClass(): string
    {
        return $this->niveau_enregistrement === '1' 
            ? 'bg-yellow-100 text-yellow-800' 
            : 'bg-green-100 text-green-800';
    }

    /**
     * Met à jour le nombre total de personnes
     */
    public function updateNombreTotalPersonnes(): void
    {
        $this->nombre_total_personnes = 
            $this->nombre_hommes + 
            $this->nombre_femmes + 
            $this->nombre_garcons + 
            $this->nombre_filles;
        $this->save();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($household) {
            if (empty($household->date_enregistrement)) {
                $household->date_enregistrement = now();
            }
        });
    }
}
