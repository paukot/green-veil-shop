<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $stripe_event_id
 * @property string $type
 * @property Carbon|null $processed_at
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'stripe_event_id', 'type', 'processed_at', 'payload',
])]
class StripeEvent extends Model
{
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
