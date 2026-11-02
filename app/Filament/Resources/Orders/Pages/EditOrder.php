<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Checkout\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentManager;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $isTest = str_starts_with((string) config('services.stripe.secret'), 'sk_test_') || app()->environment('local', 'testing');
        $stripeBase = $isTest ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

        return [
            Action::make('viewInStripe')
                ->label('View in Stripe')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(
                    fn (Order $record): ?string => $record->stripe_payment_intent_id
                        ? "{$stripeBase}payments/{$record->stripe_payment_intent_id}"
                        : ($record->stripe_session_id ? "{$stripeBase}checkout/sessions/{$record->stripe_session_id}" : null),
                    shouldOpenInNewTab: true
                )
                ->visible(fn (Order $record): bool => filled($record->stripe_payment_intent_id) || filled($record->stripe_session_id)),

            Action::make('markProcessing')
                ->label('Mark Processing')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Pending, OrderStatus::PendingPayment]))
                ->action(function (Order $record): void {
                    $record->update(['status' => OrderStatus::Processing]);
                    Notification::make()->title('Order marked as processing')->success()->send();
                }),

            Action::make('markShipped')
                ->label('Mark Shipped')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Processing]))
                ->action(function (Order $record): void {
                    $record->update(['status' => OrderStatus::Shipped]);
                    Notification::make()->title('Order marked as shipped')->success()->send();
                }),

            Action::make('markDelivered')
                ->label('Mark Delivered')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped)
                ->action(function (Order $record): void {
                    $record->update(['status' => OrderStatus::Delivered]);
                    Notification::make()->title('Order marked as delivered')->success()->send();
                }),

            Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->modalHeading(fn (Order $record): string => "Refund Order #{$record->number}")
                ->modalDescription('Process a full or partial refund. If this order was paid via Stripe, the refund is executed directly with the Stripe gateway.')
                ->visible(fn (Order $record): bool => in_array($record->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded]))
                ->form([
                    TextInput::make('amount')
                        ->label('Refund Amount (€)')
                        ->numeric()
                        ->required()
                        ->prefix('€')
                        ->default(fn (Order $record): float => (float) $record->total)
                        ->minValue(0.01)
                        ->maxValue(fn (Order $record): float => (float) $record->total)
                        ->helperText(fn (Order $record): string => 'Order total is €'.number_format($record->total, 2)),
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
                        ->helperText('Increment product inventory stock by the quantities in this order.'),
                ])
                ->action(function (array $data, Order $record, PaymentManager $paymentManager, ReleaseOrderStock $releaseOrderStock): void {
                    $amountInEuros = (float) $data['amount'];
                    $amountInCents = (int) round($amountInEuros * 100);
                    $reason = $data['reason'] ?? 'requested_by_customer';

                    $driver = $record->paymentOption->driver ?? ($record->stripe_payment_intent_id ? 'stripe' : 'offline');
                    $gateway = $paymentManager->gateway($driver);

                    $result = $gateway->refund($record, $amountInCents, $reason);

                    if (! $result->successful) {
                        Notification::make()
                            ->title('Refund Failed')
                            ->body($result->errorMessage ?? 'Unable to process refund with gateway.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $isFullRefund = ($amountInEuros >= (float) $record->total);
                    $newPaymentStatus = $isFullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;

                    $record->update([
                        'payment_status' => $newPaymentStatus,
                        'status' => $isFullRefund ? OrderStatus::Refunded : $record->status,
                    ]);

                    Payment::where('order_id', $record->id)->update([
                        'status' => $newPaymentStatus,
                    ]);

                    if (! empty($data['restore_inventory'])) {
                        $releaseOrderStock->execute($record);
                    }

                    Notification::make()
                        ->title('Refund Processed')
                        ->body('Refund of €'.number_format($amountInEuros, 2).' was successfully recorded.')
                        ->success()
                        ->send();
                }),

            Action::make('cancelOrder')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Are you sure you want to cancel this order? Any reserved inventory will be automatically restored to stock.')
                ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Delivered, OrderStatus::Completed]))
                ->action(function (Order $record, ReleaseOrderStock $releaseOrderStock): void {
                    $releaseOrderStock->execute($record);
                    $record->update(['status' => OrderStatus::Cancelled]);
                    Notification::make()->title('Order cancelled and inventory released')->warning()->send();
                }),

            //            DeleteAction::make(),
            //            ForceDeleteAction::make(),
            //            RestoreAction::make(),
        ];
    }
}
