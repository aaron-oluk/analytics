<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatBreakdown extends Model
{
    protected $fillable = [
        'site_id', 'date', 'dimension', 'value', 'visitors', 'pageviews',
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
