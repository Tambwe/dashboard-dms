<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Relation : Une catégorie a plusieurs raisons
     */
    public function raisonMouvements()
    {
        return $this->hasMany(RaisonMouvement::class, 'categorie_mouvement_id');
    }

    /**
     * Scope pour filtrer par nom
     */
    public function scopeNouvelleEntree($query)
    {
        return $query->where('name', 'nouvelle entree');
    }

    /**
     * Scope pour filtrer par sortie
     */
    public function scopeSortie($query)
    {
        return $query->where('name', 'sortie');
    }
}
