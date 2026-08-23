<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'site_id', 'visitor_hash', 'session_id', 'pathname', 'referrer_domain',
        'utm_source', 'utm_medium', 'utm_campaign', 'country_code',
        'device_type', 'browser', 'os', 'is_new_visitor', 'is_new_session',
        'duration_seconds', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_new_visitor' => 'boolean',
            'is_new_session' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
