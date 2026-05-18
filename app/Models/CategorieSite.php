<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Relation : Une catégorie peut avoir plusieurs sites
     */
    public function sites()
    {
        return $this->hasMany(Site::class, 'categorie_site_id');
    }
}
