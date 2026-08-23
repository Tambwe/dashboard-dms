<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileQuestionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'version',
        'is_active',
        'survey',
        'choices',
        'settings',
        'source_file',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'survey' => 'array',
        'choices' => 'array',
        'settings' => 'array',
        'published_at' => 'datetime',
    ];

    public function submissions()
    {
        return $this->hasMany(MobileQuestionnaireSubmission::class, 'questionnaire_id');
    }
}
