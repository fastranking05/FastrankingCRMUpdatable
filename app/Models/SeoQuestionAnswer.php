<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoQuestionAnswer extends Model
{
    use HasFactory;

    protected $table = 'seo_question_answers';

    protected $fillable = [
        'seo_details_id',
        'seo_question_id',
        'answer',
        'comments',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function seoDetail(): BelongsTo
    {
        return $this->belongsTo(SeoDetail::class, 'seo_details_id');
    }

    public function seoQuestion(): BelongsTo
    {
        return $this->belongsTo(SeoQuestion::class, 'seo_question_id');
    }

    /** Alias for eager loads that use `question` (same as seoQuestion()). */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SeoQuestion::class, 'seo_question_id');
    }
}
