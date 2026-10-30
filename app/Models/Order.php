<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int|null $user_id
 * @property int|null $delivery_option_id
 * @property int|null $promotion_id
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property float $subtotal
 * @property float $delivery_amount
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total
 * @property string $currency
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property array<string, string>|null $shipping_address
 * @property array<string, string>|null $billing_address
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'number', 'user_id', 'delivery_option_id', 'promotion_id',
    'status', 'payment_status', 'stripe_payment_intent_id', 'stripe_charge_id',
    'subtotal', 'delivery_amount', 'discount_amount', 'tax_amount', 'total', 'currency',
    'customer_name', 'customer_email', 'customer_phone',
    'shipping_address', 'billing_address', 'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

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
            'shipping_address' => 'array',
            'billing_address' => 'array',
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

    /** Generate a unique, human-readable order number. */
    public static function generateNumber(): string
    {
        return 'GV-'.strtoupper(substr(uniqid(), -6));
    }
}
