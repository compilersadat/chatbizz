<?php
namespace App\Filament\Resources\MerchantResource\RelationManagers;

use App\Models\MerchantAccount;
use App\Services\RazorpayXOnboardingService;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Notifications\Notification;

class MerchantAccountRelationManager extends RelationManager
{
    protected static string $relationship = 'merchantAccount'; // from Merchant model

    protected static ?string $recordTitleAttribute = 'account_holder_name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('account_holder_name')->required(),
            Forms\Components\TextInput::make('bank_account_number')->required(),
            Forms\Components\TextInput::make('ifsc_code')->required(),

            Forms\Components\TextInput::make('bank_name'),
            Forms\Components\TextInput::make('bank_branch'),

            Forms\Components\TextInput::make('razorpay_contact_id')->disabled(),
            Forms\Components\TextInput::make('razorpay_fund_account_id')->disabled(),
            Forms\Components\TextInput::make('razorpay_virtual_account_id')->disabled(),

            Forms\Components\Select::make('verification_status')
                ->options([
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'failed' => 'Failed',
                ])
                ->disabled(),

            Forms\Components\Textarea::make('kyc_notes')->rows(2)->disabled(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_holder_name'),
                Tables\Columns\TextColumn::make('bank_account_number')->limit(10),
                Tables\Columns\TextColumn::make('ifsc_code'),
                Tables\Columns\TextColumn::make('verification_status')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Onboard to RazorpayX')
                    ->icon('heroicon-o-rocket-launch')
                    ->visible(fn ($record) => !$record->razorpay_fund_account_id)
                    ->action(function (MerchantAccount $record) {
                        $result = app(RazorpayXOnboardingService::class)->onboard($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('RazorpayX Onboarding Successful')
                                ->success()
                                ->body('Merchant has been onboarded.')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Onboarding Failed')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    }),
            ]);
    }
}

