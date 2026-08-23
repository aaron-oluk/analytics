<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;

    protected $fillable = ['name', 'domain', 'timezone'];

    protected static function booted(): void
    {
        static::creating(function (Site $site) {
            $site->public_id ??= (string) Str::ulid()->toBase32();
            $site->public_id = substr($site->public_id, -10);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(DailyStat::class);
    }

    public function statBreakdowns(): HasMany
    {
        return $this->hasMany(StatBreakdown::class);
    }

    /**
     * The salt used to hash visitor IP+UA into a daily-rotating anonymous
     * fingerprint. Rotating it daily means the same visitor cannot be
     * correlated across days, which is the core of the no-cookie,
     * privacy-friendly tracking approach.
     */
    public function currentSalt(): string
    {
        return hash('sha256', $this->id.'|'.$this->created_at.'|'.now()->toDateString());
    }
}
