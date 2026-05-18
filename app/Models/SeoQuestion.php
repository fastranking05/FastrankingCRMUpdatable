<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoQuestion extends Model
{
    use HasFactory;

    protected $table = 'seo_questions';

    protected $fillable = [
        'name',
        'answer_type',
        'dropdown_options',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dropdown_options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(SeoQuestionAnswer::class, 'seo_question_id');
    }
}
