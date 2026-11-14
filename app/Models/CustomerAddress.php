<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string $recipient_name
 * @property string|null $phone
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property string $postal_code
 * @property string $country
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'label',
    'recipient_name',
    'phone',
    'line1',
    'line2',
    'city',
    'postal_code',
    'country',
    'is_default',
])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    /**
     * Maximum addresses allowed per customer.
     */
    public const int MAX_PER_USER = 3;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $address): void {
            // First address for this user automatically becomes default
            if (! static::query()->where('user_id', $address->user_id)->exists()) {
                $address->is_default = true;
            }
        });

        static::saving(function (self $address): void {
            // Clean up 2-letter uppercase country code
            $address->country = strtoupper(trim($address->country));

            // If set as default, unset any prior default for this user
            if ($address->is_default) {
                static::query()
                    ->where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });

        static::deleted(function (self $address): void {
            // If the deleted address was the default, promote the newest remaining address
            if ($address->is_default) {
                static::query()
                    ->where('user_id', $address->user_id)
                    ->latest()
                    ->first()
                    ?->update(['is_default' => true]);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable formatted address string.
     */
    public function formattedAddress(): string
    {
        $parts = array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->postal_code,
            $this->countryName(),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Resolve full country name.
     */
    public function countryName(): string
    {
        return Country::getNameByCode($this->country) ?? $this->country;
    }
}
