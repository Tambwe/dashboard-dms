<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaisonMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie_mouvement_id',
        'name',
        'code',
        'description',
    ];

    /**
     * Relation : Une raison appartient à une catégorie
     */
    public function categorieMouvement()
    {
        return $this->belongsTo(CategorieMouvement::class, 'categorie_mouvement_id');
    }

    /**
     * Relation : Une raison est utilisée dans plusieurs mouvements de population
     */
    public function mouvementsPopulation()
    {
        return $this->hasMany(SiteMouvementPopulation::class, 'raison_mouvement_id');
    }

    /**
     * Scope pour filtrer par catégorie
     */
    public function scopePourCategorie($query, $categorieId)
    {
        return $query->where('categorie_mouvement_id', $categorieId);
    }

    /**
     * Scope pour les raisons de nouvelle entrée
     */
    public function scopeNouvelleEntree($query)
    {
        return $query->whereHas('categorieMouvement', function($q) {
            $q->where('name', 'nouvelle entree');
        });
    }

    /**
     * Scope pour les raisons de sortie
     */
    public function scopeSortie($query)
    {
        return $query->whereHas('categorieMouvement', function($q) {
            $q->where('name', 'sortie');
        });
    }
}
