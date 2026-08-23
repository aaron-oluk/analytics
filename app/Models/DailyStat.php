<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStat extends Model
{
    protected $fillable = [
        'site_id', 'date', 'visitors', 'pageviews', 'sessions', 'bounces', 'total_duration_seconds',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
