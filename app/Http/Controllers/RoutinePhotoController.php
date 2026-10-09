<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Routines\RoutineResource;
use App\Services\RoutinePhotoReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RoutinePhotoController extends Controller
{
    public function show(): View
    {
        abort_unless(RoutineResource::canCreate(), 403);

        return view('routines.upload-photo');
    }

    public function store(Request $request, RoutinePhotoReader $reader): RedirectResponse
    {
        abort_unless(RoutineResource::canCreate(), 403);

        $data = $request->validate([
            'photo' => ['required', 'file', 'extensions:jpg,jpeg,png,webp,pdf,docx,xlsx', 'max:8192'],
            'photo_processing_consent' => ['accepted'],
        ]);

        $request->user()->consents()->create([
            'scope' => 'routine_photo_upload',
            'version' => config('legal.versions.routine_photo_upload'),
            'granted_at' => now(),
        ]);

        try {
            $photo = $data['photo'];
            $mime = $photo->getMimeType();
            $extension = strtolower($photo->getClientOriginalExtension());
            if (in_array($extension, ['docx', 'xlsx'], true)) {
                if (! in_array($mime, ['application/zip', 'application/octet-stream',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true)) {
                    throw new RuntimeException('El documento no es un Word o Excel válido.');
                }
                $mime = $extension;
            } elseif ($extension === 'pdf') {
                if ($mime !== 'application/pdf') {
                    throw new RuntimeException('El archivo PDF no es válido.');
                }
            } elseif (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new RuntimeException('El archivo debe ser una imagen, PDF, DOCX o XLSX.');
            }

            $draft = $reader->read($photo->get(), $mime, $request->user()->gym_id);
        } catch (RuntimeException $error) {
            report($error);

            return back()->withErrors(['photo' => $error->getMessage()]);
        }

        // El archivo no se guarda. Solo se conserva el borrador durante la redirección.
        $request->session()->flash('routine-photo-draft', $draft);

        return redirect(RoutineResource::getUrl('create'));
    }
}
