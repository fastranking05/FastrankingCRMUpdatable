<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoQuestionCategory extends Model
{
    use HasFactory;

    protected $table = 'seo_question_categories';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(SeoQuestion::class, 'seo_question_category_id');
    }
}
