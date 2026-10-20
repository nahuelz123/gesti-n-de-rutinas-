<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Services\AssignmentTransition;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AssignmentTransition::class)->save(
            $data,
            fn (array $normalized): Model => parent::handleRecordUpdate($record, $normalized),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
