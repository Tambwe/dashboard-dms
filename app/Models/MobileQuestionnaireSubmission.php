<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileQuestionnaireSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'user_id',
        'province_id',
        'territoire_id',
        'commune_id',
        'site_id',
        'date_collecte',
        'answers',
        'status',
        'synced_at',
        'validation_status',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'date_collecte' => 'date',
        'answers' => 'array',
        'synced_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(MobileQuestionnaire::class, 'questionnaire_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
