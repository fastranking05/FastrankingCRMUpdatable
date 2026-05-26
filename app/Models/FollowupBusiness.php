<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class FollowupBusiness extends Model
{
    use HasFactory;

    protected $table = 'followup_businesses';

    protected $fillable = [
        'name',
        'category',
        'type',
        'website',
        'phone',
        'email',
        'created_by',
    ];

    protected $casts = [
        'latest_followup_date' => 'date',
        'latest_followup_time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Recompute denormalized sort columns from followup_details (latest by date, then time).
     * Used for cursor pagination on list endpoints and kept in sync via FollowupDetailObserver.
     */
    public static function refreshLatestFollowupSortFromDetails(int $businessId): void
    {
        $detail = FollowupDetail::query()
            ->where('followup_business_id', $businessId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->first();

        static::withoutEvents(function () use ($businessId, $detail): void {
            DB::table('followup_businesses')
                ->where('id', $businessId)
                ->update([
                    'latest_followup_date' => $detail?->date?->format('Y-m-d'),
                    'latest_followup_time' => ($detail !== null && $detail->time !== null)
                        ? $detail->time->format('H:i:s')
                        : null,
                    'updated_at' => now(),
                ]);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authPersons(): BelongsToMany
    {
        return $this->belongsToMany(FollowupAuthPerson::class, 'followup_business_auth_person', 'followup_business_id', 'followup_auth_person_id')
            ->withTimestamps();
    }

    public function followupDetails(): HasMany
    {
        return $this->hasMany(FollowupDetail::class, 'followup_business_id');
    }

    public function latestFollowupDetail(): HasMany
    {
        return $this->hasMany(FollowupDetail::class, 'followup_business_id')
            ->latest('date')
            ->latest('time')
            ->limit(1);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'followup_business_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'followup_business_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'followup_business_id');
    }

    public function seoDetails(): HasMany
    {
        return $this->hasMany(SeoDetail::class, 'followup_business_id');
    }
}
