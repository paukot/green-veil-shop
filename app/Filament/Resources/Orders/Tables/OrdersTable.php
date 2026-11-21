<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        $isTest = str_starts_with((string) config('services.stripe.secret'), 'sk_test_') || app()->environment('local', 'testing');
        $stripeBase = $isTest ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Order #')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (OrderStatus $state) => $state->color()),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->color()),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('deliveryOption.code')
                    ->label('Delivery')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Manage'),
                ActionGroup::make([
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
                    Action::make('viewInStripe')
                        ->label('View in Stripe')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(
                            fn (Order $record): ?string => $record->stripe_payment_intent_id
                                ? "{$stripeBase}payments/{$record->stripe_payment_intent_id}"
                                : ($record->stripe_session_id ? "{$stripeBase}checkout/sessions/{$record->stripe_session_id}" : null),
                            shouldOpenInNewTab: true
                        )
                        ->visible(fn (Order $record): bool => filled($record->stripe_payment_intent_id) || filled($record->stripe_session_id)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
