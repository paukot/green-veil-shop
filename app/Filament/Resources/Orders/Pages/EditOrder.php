<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
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

            OrderActions::refund(),

            OrderActions::cancel(),

            //            DeleteAction::make(),
            //            ForceDeleteAction::make(),
            //            RestoreAction::make(),
        ];
    }
}
