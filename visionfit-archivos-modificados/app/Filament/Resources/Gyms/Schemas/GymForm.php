<?php

namespace App\Filament\Resources\Gyms\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class GymForm
{
  public static function configure(Schema $schema): Schema
{
    return $schema
        ->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('plan')
                ->label('Plan')
                ->required()
                ->default('basic')
                ->maxLength(50),

            Toggle::make('active')
                ->label('Activo')
                ->default(true),

            TextInput::make('logo')
                ->label('Logo')
                ->nullable()
                ->maxLength(255),

            Placeholder::make('invite_qr')
                ->label('QR de alta para este gimnasio')
                ->columnSpanFull()
                ->visible(fn ($record) => $record !== null)
                ->content(function ($record) {
                    if (! $record) {
                        return null;
                    }

                    $url = $record->joinUrl();
                    $qr = $record->qrImageUrl();

                    return new HtmlString(
                        '<div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">'
                        .'<img src="'.$qr.'" alt="QR de alta" style="width:150px; height:150px; border-radius:8px; background:#fff; padding:6px;">'
                        .'<div style="font-size:13px;">'
                        .'<div style="margin-bottom:6px;">Imprimí este QR o compartí el link. Quien lo escanee/abra puede iniciar sesión si ya tiene cuenta, o registrarse como cliente de <b>'.e($record->name).'</b> directamente.</div>'
                        .'<div style="font-family:monospace; word-break:break-all; opacity:0.8;">'.e($url).'</div>'
                        .'</div>'
                        .'</div>'
                    );
                }),
        ]);
}

}
