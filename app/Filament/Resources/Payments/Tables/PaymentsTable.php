<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        $isTest = str_starts_with((string) config('services.stripe.secret'), 'sk_test_') || app()->environment('local', 'testing');
        $stripeBase = $isTest ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

        return $table
            ->columns([
                TextColumn::make('order.number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable()
                    ->url(fn (Payment $record): ?string => $record->order ? route('filament.admin.resources.orders.edit', ['record' => $record->order]) : null),
                TextColumn::make('order.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('driver')
                    ->label('Gateway')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => match ($state) {
                        'stripe' => 'success',
                        'bank_transfer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => $state->color()),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EUR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('stripe_payment_intent_id')
                    ->label('Payment Intent')
                    ->copyable()
                    ->placeholder('—')
                    ->url(fn (?string $state): ?string => $state ? "{$stripeBase}payments/{$state}" : null, shouldOpenInNewTab: true),
                TextColumn::make('failure_reason')
                    ->label('Failure Note')
                    ->placeholder('—')
                    ->color('danger')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('m.j.Y, H:i')
                    ->placeholder('Unpaid')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Attempted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('driver')
                    ->options([
                        'stripe' => 'Stripe',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
            ])
            ->recordActions([
                Action::make('inspect')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Payment $record): string => "Transaction #{$record->id} - Order {$record->order?->number}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(fn (Payment $record): array => [
                        Grid::make(2)->schema([
                            TextInput::make('order_number')
                                ->label('Order Number')
                                ->default($record->order?->number ?? '—')
                                ->disabled(),
                            TextInput::make('customer')
                                ->label('Customer Name')
                                ->default($record->order?->customer_name ?? '—')
                                ->disabled(),
                            TextInput::make('gateway')
                                ->label('Gateway Driver')
                                ->default(strtoupper($record->driver))
                                ->disabled(),
                            TextInput::make('payment_status')
                                ->label('Payment Status')
                                ->default($record->status->label())
                                ->disabled(),
                            TextInput::make('amount_formatted')
                                ->label('Amount Charged')
                                ->default('€'.number_format($record->amount, 2).' '.$record->currency)
                                ->disabled(),
                            TextInput::make('paid_timestamp')
                                ->label('Paid At')
                                ->default($record->paid_at ? $record->paid_at->toDayDateTimeString() : 'Unpaid')
                                ->disabled(),
                            TextInput::make('stripe_session_id')
                                ->label('Stripe Session ID')
                                ->default($record->stripe_session_id ?? '—')
                                ->disabled()
                                ->columnSpanFull(),
                            TextInput::make('stripe_payment_intent_id')
                                ->label('Stripe Payment Intent ID')
                                ->default($record->stripe_payment_intent_id ?? '—')
                                ->disabled()
                                ->columnSpanFull(),
                            Textarea::make('failure_reason')
                                ->label('Failure Reason')
                                ->default($record->failure_reason ?? 'None recorded')
                                ->disabled()
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
