<?php

namespace App\Filament\Resources\StripeEvents\Tables;

use App\Models\StripeEvent;
use App\Services\Stripe\StripePayloadSanitizer;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StripeEventsTable
{
    public static function configure(Table $table): Table
    {
        $sanitizer = app(StripePayloadSanitizer::class);

        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Event')
                    ->badge()
                    ->color(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['color'])
                    ->icon(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['icon'])
                    ->searchable(),
                TextColumn::make('flow_stage')
                    ->label('Flow Stage')
                    ->state(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['label'])
                    ->description(fn (StripeEvent $record): ?string => $sanitizer->summarize($record->type, $record->payload)['payment_method']
                        ?? $sanitizer->summarize($record->type, $record->payload)['outcome_message']
                    ),
                TextColumn::make('order.number')
                    ->label('Order #')
                    ->badge()
                    ->placeholder('Unassigned')
                    ->url(fn (StripeEvent $record): ?string => $record->order ? route('filament.admin.resources.orders.edit', ['record' => $record->order]) : null),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->state(fn (StripeEvent $record): ?string => $sanitizer->summarize($record->type, $record->payload)['amount'])
                    ->placeholder('—')
                    ->weight('bold'),
                TextColumn::make('stripe_event_id')
                    ->label('Event ID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processed_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (StripeEvent $record): string => $record->processed_at ? 'Processed' : 'Pending')
                    ->color(fn (StripeEvent $record): string => $record->processed_at ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'checkout.session.completed' => 'Checkout Completed',
                        'checkout.session.expired' => 'Checkout Expired',
                        'charge.refunded' => 'Charge Refunded',
                        'payment_intent.succeeded' => 'Payment Intent Succeeded',
                        'payment_intent.payment_failed' => 'Payment Intent Failed',
                    ]),
                TernaryFilter::make('processed_at')
                    ->label('Processing Status')
                    ->nullable()
                    ->trueLabel('Processed')
                    ->falseLabel('Pending Worker'),
            ])
            ->actions([
                Action::make('inspect')
                    ->label('Inspect')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (StripeEvent $record): string => "Stripe Webhook: {$record->type}")
                    ->modalDescription(fn (StripeEvent $record): string => "Event ID: {$record->stripe_event_id} • Received {$record->created_at?->toDayDateTimeString()}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(function (StripeEvent $record) use ($sanitizer): array {
                        $summary = $sanitizer->summarize($record->type, $record->payload);
                        $cleanData = $sanitizer->sanitize($record->payload);

                        return [
                            Grid::make(3)->schema([
                                TextInput::make('event_type')
                                    ->label('Event Type')
                                    ->default($record->type)
                                    ->disabled(),
                                TextInput::make('flow_stage')
                                    ->label('Flow Stage')
                                    ->default($summary['label'])
                                    ->disabled(),
                                TextInput::make('linked_order')
                                    ->label('Linked Order')
                                    ->default($record->order?->number ?? 'Unassigned')
                                    ->disabled(),
                                TextInput::make('payment_method')
                                    ->label('Payment Method')
                                    ->default($summary['payment_method'] ?? '—')
                                    ->disabled(),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->default($summary['amount'] ?? '—')
                                    ->disabled(),
                                TextInput::make('processed_at')
                                    ->label('Processed Timestamp')
                                    ->default($record->processed_at ? $record->processed_at->toDayDateTimeString() : 'Pending')
                                    ->disabled(),
                            ]),
                            Textarea::make('sanitized_payload')
                                ->label('Sanitized Audit Payload (PII Redacted)')
                                ->rows(15)
                                ->default(json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                ->disabled()
                                ->columnSpanFull(),
                        ];
                    }),
            ]);
    }
}
