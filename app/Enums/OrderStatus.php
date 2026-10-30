<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case PendingPayment = 'pending_payment';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::PendingPayment => 'Pending Payment',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::PendingPayment => 'warning',
            self::Processing => 'info',
            self::Shipped => 'primary',
            self::Delivered, self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'gray',
        };
    }
}
