<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BodyMeasurement;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $measurements = $user->bodyMeasurements()->get(); // desc por measured_at

        $latest = $measurements->first();
        $previous = $measurements->get(1);

        return view('client.progress.index', [
            'measurements' => $measurements,
            'chartData' => $measurements->sortBy('measured_at')->values(), // asc para el gráfico
            'hasProfile' => (bool) ($user->height_cm && $user->sex),
            'latest' => $latest,
            'previous' => $previous,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'height_cm' => ['required', 'numeric', 'min:100', 'max:250'],
            'sex' => ['required', 'in:m,f'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Datos guardados.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'waist' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'chest' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'hip' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'arm' => ['nullable', 'numeric', 'min:10', 'max:100'],
            'thigh' => ['nullable', 'numeric', 'min:10', 'max:150'],
            'neck' => ['nullable', 'numeric', 'min:10', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $bodyFat = BodyMeasurement::calculateBodyFat(
            $user,
            $data['waist'] ?? null,
            $data['neck'] ?? null,
            $data['hip'] ?? null,
        );

        BodyMeasurement::updateOrCreate(
            [
                'client_id' => $user->id,
                'measured_at' => $data['measured_at'],
            ],
            [
                ...$data,
                'body_fat_percentage' => $bodyFat,
            ]
        );

        return back()->with('success', '¡Medición registrada!');
    }

    public function destroy(Request $request, BodyMeasurement $measurement)
    {
        abort_unless($measurement->client_id === $request->user()->id, 403);

        $measurement->delete();

        return back()->with('success', 'Medición eliminada.');
    }
}
