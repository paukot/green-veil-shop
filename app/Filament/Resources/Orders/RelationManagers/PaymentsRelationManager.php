<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\PaymentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payment Attempts & Records';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $isTest = str_starts_with((string) config('services.stripe.secret'), 'sk_test_') || app()->environment('local', 'testing');
        $stripeBase = $isTest ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('driver')
                    ->label('Gateway')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => $state->color()),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EUR')
                    ->weight('bold'),
                TextColumn::make('stripe_payment_intent_id')
                    ->label('Payment Intent')
                    ->placeholder('—')
                    ->copyable()
                    ->url(fn (?string $state): ?string => $state ? "{$stripeBase}payments/{$state}" : null, shouldOpenInNewTab: true),
                TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->placeholder('—')
                    ->color('danger')
                    ->wrap(),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y H:i:s')
                    ->placeholder('Unpaid'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
