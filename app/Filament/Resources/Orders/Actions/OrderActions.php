<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\RefundOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * Order actions shared by the orders table and the edit page.
 */
class OrderActions
{
    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->modalHeading(fn (Order $record): string => "Refund Order #{$record->number}")
            ->modalDescription('Process a full or partial refund. If this order was paid via Stripe, the refund is executed directly with the Stripe gateway.')
            ->visible(fn (Order $record): bool => in_array($record->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)
                && $record->refundableAmount() > 0)
            ->schema([
                TextInput::make('amount')
                    ->label('Refund Amount (€)')
                    ->numeric()
                    ->required()
                    ->prefix('€')
                    ->default(fn (Order $record): float => $record->refundableAmount())
                    ->minValue(0.01)
                    ->maxValue(fn (Order $record): float => $record->refundableAmount())
                    ->helperText(fn (Order $record): string => 'Remaining refundable balance is €'.number_format($record->refundableAmount(), 2)
                        .' of €'.number_format((float) $record->total, 2).'.'),
                Select::make('reason')
                    ->label('Reason')
                    ->options([
                        'requested_by_customer' => 'Customer requested',
                        'duplicate' => 'Duplicate payment',
                        'fraudulent' => 'Fraudulent',
                    ])
                    ->default('requested_by_customer'),
                Toggle::make('restore_inventory')
                    ->label('Restock products into inventory')
                    ->default(true)
                    ->helperText('Return the order\'s quantities to stock. This happens at most once per order.'),
            ])
            ->action(function (array $data, Order $record, RefundOrder $refundOrder): void {
                $amount = (float) $data['amount'];

                $result = $refundOrder->execute(
                    $record,
                    $amount,
                    $data['reason'] ?? 'requested_by_customer',
                    restock: ! empty($data['restore_inventory']),
                );

                if (! $result->successful) {
                    Notification::make()
                        ->title('Refund Failed')
                        ->body($result->errorMessage ?? 'Unable to process refund with gateway.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Refund Processed')
                    ->body('Refund of €'.number_format($amount, 2).' was successfully recorded.')
                    ->success()
                    ->send();
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancelOrder')
            ->label('Cancel Order')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel Order')
            ->modalDescription('Are you sure you want to cancel this order? Any reserved inventory will be automatically restored to stock.')
            ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Delivered, OrderStatus::Completed], true))
            ->action(function (Order $record, CancelOrder $cancelOrder): void {
                $cancelOrder->execute($record);

                Notification::make()->title('Order cancelled and inventory released')->warning()->send();
            });
    }
}
