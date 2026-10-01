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
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        try {
            $photo = $data['photo'];
            $mime = $photo->getMimeType();
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new RuntimeException('La foto debe ser JPG, PNG o WebP.');
            }

            $draft = $reader->read($photo->get(), $mime, $request->user()->gym_id);
        } catch (RuntimeException $error) {
            report($error);

            return back()->withErrors(['photo' => $error->getMessage()]);
        }

        // La foto no se guarda. Solo se conserva el borrador durante la redirección.
        $request->session()->flash('routine-photo-draft', $draft);

        return redirect(RoutineResource::getUrl('create'));
    }
}
