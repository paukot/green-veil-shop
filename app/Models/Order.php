<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property int|null $user_id
 * @property int|null $delivery_option_id
 * @property int|null $payment_option_id
 * @property int|null $promotion_id
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $stripe_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property float $subtotal
 * @property float $delivery_amount
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total
 * @property float $refunded_amount
 * @property string $currency
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property array<string, string>|null $shipping_address
 * @property array<string, string>|null $billing_address
 * @property string|null $notes
 * @property CarbonImmutable|null $placed_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $reservations_released_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'public_id', 'number', 'user_id', 'delivery_option_id', 'payment_option_id', 'promotion_id',
    'status', 'payment_status', 'stripe_session_id', 'stripe_payment_intent_id', 'stripe_charge_id',
    'subtotal', 'delivery_amount', 'discount_amount', 'tax_amount', 'total', 'refunded_amount', 'currency',
    'customer_name', 'customer_email', 'customer_phone',
    'shipping_address', 'billing_address', 'notes', 'placed_at', 'paid_at', 'reservations_released_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->public_id)) {
                $order->public_id = (string) Str::ulid();
            }

            if (empty($order->number)) {
                $order->number = static::generateNumber();
            }

            if (empty($order->placed_at)) {
                $order->placed_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'delivery_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'reservations_released_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<DeliveryOption, $this> */
    public function deliveryOption(): BelongsTo
    {
        return $this->belongsTo(DeliveryOption::class);
    }

    /** @return BelongsTo<PaymentOption, $this> */
    public function paymentOption(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class);
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<StripeEvent, $this> */
    public function stripeEvents(): HasMany
    {
        return $this->hasMany(StripeEvent::class)->orderBy('created_at', 'asc');
    }

    /** The amount that can still be refunded, in euros. */
    public function refundableAmount(): float
    {
        return max(0.0, round((float) $this->total - (float) $this->refunded_amount, 2));
    }

    /**
     * Generate a unique, human-readable order number.
     *
     * Eight random base-36 characters, re-drawn on the rare collision. The unique index on
     * `number` remains the final guard against a concurrent duplicate.
     */
    public static function generateNumber(): string
    {
        do {
            $number = 'GV-'.strtoupper(Str::random(8));
        } while (static::withTrashed()->where('number', $number)->exists());

        return $number;
    }

    /**
     * Determine if this order belongs to an authenticated user other than the given user.
     */
    public function isOwnedByAnotherUser(User|AdminUser|null $user): bool
    {
        if ($this->user_id === null || ! ($user instanceof User)) {
            return false;
        }

        return (int) $this->user_id !== $user->id;
    }
}
