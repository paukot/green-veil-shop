<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\StripeEvent;
use App\Services\Stripe\StripePayloadSanitizer;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StripeEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'stripeEvents';

    protected static ?string $title = 'Stripe Event Logs & Flow';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $sanitizer = app(StripePayloadSanitizer::class);

        return $table
            ->recordTitleAttribute('stripe_event_id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Event')
                    ->badge()
                    ->color(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['color'])
                    ->icon(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['icon'])
                    ->searchable(),
                TextColumn::make('flow_stage')
                    ->label('Customer Flow Stage')
                    ->state(fn (StripeEvent $record): string => $sanitizer->summarize($record->type, $record->payload)['label'])
                    ->description(fn (StripeEvent $record): ?string => $sanitizer->summarize($record->type, $record->payload)['outcome_message']
                        ?? $sanitizer->summarize($record->type, $record->payload)['failure_message']
                    ),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->state(fn (StripeEvent $record): ?string => $sanitizer->summarize($record->type, $record->payload)['payment_method'])
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->state(fn (StripeEvent $record): ?string => $sanitizer->summarize($record->type, $record->payload)['amount'])
                    ->placeholder('—')
                    ->weight('bold'),
                TextColumn::make('processed_status')
                    ->label('Worker Status')
                    ->badge()
                    ->state(fn (StripeEvent $record): string => $record->processed_at ? 'Processed' : 'Pending')
                    ->color(fn (StripeEvent $record): string => $record->processed_at ? 'success' : 'warning')
                    ->description(fn (StripeEvent $record): ?string => $record->processed_at?->diffForHumans()),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('inspect')
                    ->label('Inspect Event')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (StripeEvent $record): string => "Stripe Event: {$record->type}")
                    ->modalDescription(fn (StripeEvent $record): string => "Event ID: {$record->stripe_event_id} • Received {$record->created_at?->toDayDateTimeString()}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(function (StripeEvent $record) use ($sanitizer): array {
                        $summary = $sanitizer->summarize($record->type, $record->payload);
                        $cleanData = $sanitizer->sanitize($record->payload);

                        return [
                            Grid::make(3)->schema([
                                TextInput::make('event_type')
                                    ->label('Type')
                                    ->default($record->type)
                                    ->disabled(),
                                TextInput::make('flow_label')
                                    ->label('Flow Stage')
                                    ->default($summary['label'])
                                    ->disabled(),
                                TextInput::make('payment_method')
                                    ->label('Payment Method')
                                    ->default($summary['payment_method'] ?? '—')
                                    ->disabled(),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->default($summary['amount'] ?? '—')
                                    ->disabled(),
                                TextInput::make('status')
                                    ->label('Payment Status')
                                    ->default($summary['status'] ?? '—')
                                    ->disabled(),
                                TextInput::make('processed_at')
                                    ->label('Processed At')
                                    ->default($record->processed_at ? $record->processed_at->toDayDateTimeString() : 'Pending')
                                    ->disabled(),
                            ]),
                            Textarea::make('sanitized_payload')
                                ->label('Sanitized Audit Payload (PII Redacted)')
                                ->rows(14)
                                ->default(json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                ->disabled()
                                ->columnSpanFull(),
                        ];
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
