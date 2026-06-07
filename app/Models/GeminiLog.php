<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeminiLog extends Model
{
    protected $fillable = [
        'user_id', 'trip_id', 'kind', 'model',
        'prompt_tokens', 'output_tokens', 'latency_ms',
        'grounded', 'status', 'error',
    ];

    protected $casts = [
        'grounded' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
