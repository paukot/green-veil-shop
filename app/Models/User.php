<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Storefront customers never have back-office administrative category access.
     */
    public function hasCategoryAccess(string $category): bool
    {
        return false;
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /** @return HasMany<CartItem, $this> */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->latest();
    }

    /** @return HasMany<Favourite, $this> */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class)->latest();
    }

    /** @return HasMany<CustomerAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->latest();
    }

    /**
     * Get the customer's default shipping address, if any.
     */
    public function defaultAddress(): ?CustomerAddress
    {
        if ($this->relationLoaded('addresses')) {
            return $this->addresses->firstWhere('is_default', true) ?? $this->addresses->first();
        }

        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    /**
     * Check if customer can add another shipping address (max 3).
     */
    public function canAddAddress(): bool
    {
        $count = $this->relationLoaded('addresses')
            ? $this->addresses->count()
            : $this->addresses()->count();

        return $count < CustomerAddress::MAX_PER_USER;
    }
}
