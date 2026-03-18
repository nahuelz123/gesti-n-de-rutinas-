<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }




    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->when($user, function (Builder $query) use ($user) {
                if ($user->role === 'super_admin') {
                    return;
                }

                $query->where('gym_id', $user->gym_id);

                if ($user->role === 'coach') {
                    $query->where('role', 'client');
                }
            });
    }


    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'admin']);
    }


    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if (! $user) return false;

        if (in_array($user->role, ['super_admin', 'admin'])) {
            return $record->gym_id === $user->gym_id;
        }

        // coach: no edita users
        return false;
    }



    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
